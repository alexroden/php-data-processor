<?php

namespace App\Services;
use App\Interfaces\CsvChunkerInterface;
use Psr\Http\Message\StreamInterface;

final class CsvChunker implements CsvChunkerInterface
{
    /**
     * @return string[] Temporary file paths
     */
    public function split(StreamInterface $stream, int $rowsPerChunk = 250): array
    {
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