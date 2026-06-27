<?php

namespace Tests\Services;

use App\Services\Sqs;
use Aws\Result;
use PHPUnit\Framework\TestCase;
use Tests\Fakers\SqsClientFake;

class SqsTest extends TestCase
{
    public function test_add_messge(): void
    {
        $spy = new SqsClientFake();

        $service = new Sqs($spy, "queue-url");

        $service->addMessage('file.cvs', 'students', 0);

        $this->assertCount(1, $spy->sentMessages);

        $this->assertSame([
            'QueueUrl' => 'queue-url',
            'MessageBody' => '{"file":"file.cvs","type":"students","batch":0}'
        ], $spy->sentMessages[0]);
    }

    public function test_receive_messages(): void
    {
        $spy = new SqsClientFake();

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

    public function test_delete_message(): void
    {
        $spy = new SqsClientFake();

        $service = new Sqs($spy, 'queue-url');

        $service->deleteMessage('receipt-handle-123');

        $this->assertCount(1, $spy->deletedMessages);

        $this->assertSame([
            'QueueUrl' => 'queue-url',
            'ReceiptHandle' => 'receipt-handle-123',
        ], $spy->deletedMessages[0]);
    }
}