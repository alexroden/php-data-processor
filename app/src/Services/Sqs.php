<?php

namespace App\Services;

use App\Repositories\SqsClientInterface;

final class Sqs
{
    public function __construct(private SqsClientInterface $client, private string $queueUrl) {}

    public function addBatches(string $file, int $totalRows, int $batchSize): void
    {
        $batchIndex = 0;
        foreach ($this->createBatches($totalRows, $batchSize) as $batch) {
            $message = [
                'file'  => $file,
                'start' => $batch['start'],
                'end'   => $batch['end'],
                'batch' => $batchIndex,
            ];

            echo "Sending batch: " . json_encode($message) . "\n";

            $this->client->sendMessage([
                'QueueUrl' => $this->queueUrl,
                'MessageBody' => json_encode($message),
            ]);

            $batchIndex++;
        }
    }

    private function createBatches(int $totalRows, int $batchSize): array
    {
        $batches = [];

        for ($start = 0; $start < $totalRows; $start += $batchSize) {
            $batches[] = [
                'start' => $start,
                'end'   => min($start + $batchSize, $totalRows),
            ];
        }

        return $batches;
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