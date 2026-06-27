<?php

namespace Tests\Fakers;

use App\Interfaces\SqsClientInterface;
use Aws\Result;

final class SqsClientFake implements SqsClientInterface
{
    public array $sentMessages = [];
    public array $receivedCalls = [];
    public array $deletedMessages = [];

    public Result $receiveResult;

    public function __construct()
    {
        $this->receiveResult = new Result([
            'Messages' => [],
        ]);
    }

    public function sendMessage(array $payload): Result
    {
        $this->sentMessages[] = $payload;

        return new Result([]);
    }

    public function receiveMessage(array $args = []): Result
    {
        $this->receivedCalls[] = $args;

        return $this->receiveResult;
    }

    public function deleteMessage(array $args = []): Result
    {
        $this->deletedMessages[] = $args;

        return new Result([]);
    }
}