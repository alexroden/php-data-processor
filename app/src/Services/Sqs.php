<?php

namespace App\Services;

use App\Repositories\SqsClientInterface;

final class Sqs
{
    public function __construct(private SqsClientInterface $client, private string $queueUrl) {}

    /**
     * @throws \JsonException
     */
    public function addMessage(string $file, string $type, int $chunkIndex): void
    {
        $message = [
            'file' => $file,
            'type' => $type,
            'batch' => $chunkIndex,
        ];

        $this->client->sendMessage([
            'QueueUrl'    => $this->queueUrl,
            'MessageBody' => json_encode($message, JSON_THROW_ON_ERROR),
        ]);
    }

    public function receiveMessages(): array
    {
        $result = $this->client->receiveMessage([
            'QueueUrl' => $this->queueUrl,
            'MaxNumberOfMessages' => 10,
            'WaitTimeSeconds' => 20,
        ]);

        return $result->get('Messages') ?? [];
    }

    public function deleteMessage(string $handle): void
    {
        $this->client->deleteMessage([
            'QueueUrl' => $this->queueUrl,
            'ReceiptHandle' => $handle,
        ]);
    }
}