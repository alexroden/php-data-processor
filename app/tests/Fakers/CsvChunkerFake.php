<?php

namespace Tests\Fakers;

use App\Interfaces\CsvChunkerInterface;
use Psr\Http\Message\StreamInterface;

final class CsvChunkerFake implements CsvChunkerInterface
{
    private array $chunks = [];

    public function setChunks(array $chunks): void
    {
        $this->chunks = $chunks;
    }

    public function split(StreamInterface $stream, int $rowsPerChunk = 250): array
    {
        return $this->chunks;
    }
}