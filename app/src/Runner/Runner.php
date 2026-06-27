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
            $this->processFile($file);
        }

        echo "Done.\n";
    }

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

            $this->s3->putObject(
                $this->bucket,
                $key,
                file_get_contents($chunkPath)
            );

            unlink($chunkPath);

            echo "Uploaded chunk: {$key}\n";

            $this->sqs->addMessage($key, $type, $chunkIndex);

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
}