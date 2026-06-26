<?php

namespace Tests\Services;

use App\Repositories\SqsClientInterface;
use App\Services\Sqs;
use PHPUnit\Framework\TestCase;

class SqsTest extends TestCase
{
    public function testAddBatches(): void
    {
        $spy = new SqsClientSpy();

        $service = new Sqs($spy, "queue-url");

        $service->addBatches('bucket', 1000, 250);

        $this->assertCount(4, $spy->messages);

        $this->assertSame([
            'QueueUrl' => 'queue-url',
            'MessageBody' => '{"file":"bucket","start":0,"end":250}'
        ], $spy->messages[0]);
    }
}

final class SqsClientSpy implements SqsClientInterface
{
    public array $messages = [];

    public function sendMessage(array $payload): void
    {
        $this->messages[] = $payload;
    }
}