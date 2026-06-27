<?php

namespace App\Interfaces;

use Psr\Http\Message\StreamInterface;

interface CsvChunkerInterface
{
    public function split(StreamInterface $stream, int $rowsPerChunk = 250): array;
}