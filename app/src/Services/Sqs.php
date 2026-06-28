<?php

namespace App\Services;

use App\Interfaces\SqsClientInterface;
use App\Interfaces\SqsInterface;

final class Sqs implements SqsInterface
{
    /**
     * @param SqsClientInterface $client
     * @param string $queueUrl
     */
    public function __construct(private SqsClientInterface $client, private string $queueUrl) {}

    /**
     * @param string $file
     * @param string $type
     * @param int $chunkIndex
     *
     * @return void
     *
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

    /**
     * @return array
     */
    public function receiveMessages(): array
    {
        $result = $this->client->receiveMessage([
            'QueueUrl' => $this->queueUrl,
            'MaxNumberOfMessages' => 10,
            'WaitTimeSeconds' => 20,
        ]);

        return $result->get('Messages') ?? [];
    }

    /**
     * @param string $handle
     *
     * @return void
     */
    public function deleteMessage(string $handle): void
    {
        $this->client->deleteMessage([
            'QueueUrl' => $this->queueUrl,
            'ReceiptHandle' => $handle,
        ]);
    }
}