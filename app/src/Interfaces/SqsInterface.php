<?php

namespace App\Interfaces;

interface SqsInterface
{
    public function addMessage(string $file, string $type, int $chunkIndex): void;

    public function receiveMessages(): array;

    public function deleteMessage(string $handle): void;
}