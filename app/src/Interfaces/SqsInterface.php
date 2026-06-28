<?php

namespace App\Interfaces;

interface SqsInterface
{
    /**
     * @param string $file
     * @param string $type
     * @param int $chunkIndex
     *
     * @return void
     */
    public function addMessage(string $file, string $type, int $chunkIndex): void;

    /**
     * @return array
     */
    public function receiveMessages(): array;

    /**
     * @param string $handle
     *
     * @return void
     */
    public function deleteMessage(string $handle): void;
}