<?php

namespace App\Interfaces;

use Psr\Http\Message\StreamInterface;

interface CsvChunkerInterface
{
    /**
     * @param StreamInterface $stream
     * @param int $rowsPerChunk
     *
     * @return array
     */
    public function split(StreamInterface $stream, int $rowsPerChunk = 250): array;
}