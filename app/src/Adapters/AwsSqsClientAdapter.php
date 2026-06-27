<?php

namespace App\Adapters;

use App\Interfaces\SqsClientInterface;
use Aws\Result;
use Aws\Sqs\SqsClient;

final class AwsSqsClientAdapter implements SqsClientInterface
{
    public function __construct(private SqsClient $client) {}

    public function sendMessage(array $payload): Result
    {
        return $this->client->sendMessage($payload);
    }

    public function receiveMessage(array $args = []): Result
    {
        return $this->client->receiveMessage($args);
    }

    public function deleteMessage(array $args = []): Result
    {
        return $this->client->deleteMessage($args);
    }

}