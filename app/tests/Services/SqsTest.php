<?php

namespace Tests\Services;

use App\Repositories\SqsClientInterface;
use App\Services\Sqs;
use Aws\Result;
use PHPUnit\Framework\TestCase;

class SqsTest extends TestCase
{
    public function testAddBatches(): void
    {
        $spy = new SqsClientSpy();

        $service = new Sqs($spy, "queue-url");

        $service->addBatches('bucket', 1000, 250);

        $this->assertCount(4, $spy->sentMessages);

        $this->assertSame([
            'QueueUrl' => 'queue-url',
            'MessageBody' => '{"file":"bucket","start":0,"end":250}'
        ], $spy->sentMessages[0]);
    }

    public function testReceiveMessages(): void
    {
        $spy = new SqsClientSpy();

        $spy->receiveResult = new Result([
            'Messages' => [
                ['Body' => 'one'],
                ['Body' => 'two'],
            ],
        ]);

        $service = new Sqs($spy, 'queue-url');

        $messages = $service->receiveMessages();

        $this->assertSame([
            [
                'QueueUrl' => 'queue-url',
                'MaxNumberOfMessages' => 10,
                'WaitTimeSeconds' => 20,
            ],
        ], $spy->receivedCalls);

        $this->assertCount(2, $messages);
        $this->assertSame('one', $messages[0]['Body']);
        $this->assertSame('two', $messages[1]['Body']);
    }

    public function testDeleteMessage(): void
    {
        $spy = new SqsClientSpy();

        $service = new Sqs($spy, 'queue-url');

        $service->deleteMessage('receipt-handle');

        $this->assertSame([
            [
                'QueueUrl' => 'queue-url',
                'ReceiptHandle' => 'receipt-handle',
            ],
        ], $spy->deletedMessages);
    }
}

final class SqsClientSpy implements SqsClientInterface
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

    public function sendMessage(array $args = []): Result
    {
        $this->sentMessages[] = $args;

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