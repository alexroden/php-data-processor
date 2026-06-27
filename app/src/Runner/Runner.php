<?php

namespace App\Runner;

namespace App\Runner;

use App\Services\S3;
use App\Services\Sqs;
use Psr\Http\Message\StreamInterface;

final readonly class Runner
{
    public function __construct(
        private S3     $s3,
        private Sqs    $sqs,
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

        $chunks = $this->splitCsvIntoChunks($csv);

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

    private function splitCsvIntoChunks(
        StreamInterface $stream
    ): array {
        $files = [];

        $temp = fopen('php://temp', 'r+');
        stream_copy_to_stream($stream->detach(), $temp);
        rewind($temp);

        $header = fgetcsv($temp, 0, ',', '"', '\\');

        $rowCount = 0;
        $current = null;

        while (($row = fgetcsv($temp, 0, ',', '"', '\\')) !== false) {

            if ($rowCount % 250 === 0) {

                if ($current) {
                    fclose($current);
                }

                $path = sys_get_temp_dir() . '/chunk-' . uniqid() . '.csv';

                $current = fopen($path, 'w');
                $files[] = $path;

                fputcsv($current, $header, ',', '"', '\\');
            }

            fputcsv($current, $row, ',', '"', '\\');
            $rowCount++;
        }

        if ($current) {
            fclose($current);
        }

        fclose($temp);

        return $files;
    }
}