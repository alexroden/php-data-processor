<?php

namespace App\Repositories;

use Aws\Sqs\SqsClient;

final class AwsSqsClientAdapter implements SqsClientInterface
{
    public function __construct(private SqsClient $client) {}

    public function sendMessage(array $payload): void
    {
        $this->client->sendMessage($payload);
    }

}