<?php

namespace App\Adapters;

use App\Interfaces\SqsClientInterface;
use Aws\Result;
use Aws\Sqs\SqsClient;

final class AwsSqsClientAdapter implements SqsClientInterface
{
    /**
     * @param SqsClient $client
     */
    public function __construct(private SqsClient $client) {}

    /**
     * @param array $payload
     *
     * @return Result
     */
    public function sendMessage(array $payload): Result
    {
        return $this->client->sendMessage($payload);
    }

    /**
     * @param array $args
     *
     * @return Result
     */
    public function receiveMessage(array $args = []): Result
    {
        return $this->client->receiveMessage($args);
    }

    /**
     * @param array $args
     *
     * @return Result
     */
    public function deleteMessage(array $args = []): Result
    {
        return $this->client->deleteMessage($args);
    }

}