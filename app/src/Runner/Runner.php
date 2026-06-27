<?php

namespace App\Runner;

namespace App\Runner;

use App\Interfaces\CsvChunkerInterface;
use App\Interfaces\S3Interface;
use App\Interfaces\SqsInterface;

final readonly class Runner
{
    public function __construct(
        private S3Interface     $s3,
        private SqsInterface    $sqs,
        private CsvChunkerInterface $chunker,
        private string $bucket,
    ) {}

    public function run(): void
    {
        echo "Runner started...\n";

        $files = $this->s3->listCsvFiles($this->bucket);

        if (empty($files)) {
            echo "No CSV files found.\n";
            return;
        }

        $this->sortFiles($files);

        foreach ($files as $file) {
            try {
                $this->processFile($file);
            } catch (\Throwable $e) {
                echo "FAILED file {$file}: {$e->getMessage()}\n";
                continue;
            }
        }

        echo "Done.\n";
    }

    /**
     * @throws \Throwable
     */
    private function processFile(string $file): void
    {
        echo "Processing: {$file}\n";

        $csv = $this->s3->getObject($this->bucket, $file);

        $chunks = $this->chunker->split($csv);

        $type = preg_replace('/\.csv$/', '', basename($file));

        $chunkIndex = 0;

        foreach ($chunks as $chunkPath) {
            $key = sprintf(
                "/assets/%s/chunk-%04d-%s.csv",
                $type,
                $chunkIndex,
                uniqid()
            );

            $content = file_get_contents($chunkPath);

            $this->retry(function () use ($content, $key) {
                $this->s3->putObject($this->bucket, $key, $content);
            });

            if (file_exists($chunkPath)) {
                unlink($chunkPath);
            }

            echo "Uploaded chunk: {$key}\n";

            $this->retry(function () use ($key, $type, $chunkIndex) {
                $this->sqs->addMessage($key, $type, $chunkIndex);
            });

            $chunkIndex++;
        }
    }

    private function sortFiles(array &$files): void
    {
        $priority = [
            'students.csv' => 0,
        ];

        usort($files, function ($a, $b) use ($priority) {
            $aName = basename($a);
            $bName = basename($b);

            $pa = $priority[$aName] ?? 999;
            $pb = $priority[$bName] ?? 999;

            return $pa <=> $pb ?: $a <=> $b;
        });
    }

    /**
     * @throws \Throwable
     */
    private function retry(callable $fn): void
    {
        $delayMs = 100;
        $attempt = 0;

        start:
        try {
            $fn();
            return;
        } catch (\Throwable $e) {
            $attempt++;

            if ($attempt >= 3) {
                throw $e;
            }

            usleep($delayMs * 1000);

            $delayMs *= 2;

            goto start;
        }
    }
}