<?php

namespace Tests\Fakers;

use App\Interfaces\SqsInterface;

final class SqsFake implements SqsInterface
{
    public array $messages = [];
    public array $deleted = [];

    public function addMessage(string $file, string $type, int $chunkIndex): void
    {
        $this->messages[] = [
            'Body' => json_encode([
                'file' => $file,
                'type' => $type,
                'batch' => $chunkIndex,
            ]),
            'ReceiptHandle' => $file,
        ];
    }

    public function receiveMessages(): array
    {
        return $this->messages;
    }

    public function deleteMessage(string $handle): void
    {
        $this->deleted[] = $handle;
    }
}