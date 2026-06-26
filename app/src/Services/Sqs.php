<?php

namespace App\Services;

use App\Repositories\SqsClientInterface;

final class Sqs
{
    public function __construct(private SqsClientInterface $client, private string $queueUrl) {}

    public function addBatches(string $file, int $totalRows, int $batchSize): void
    {
        foreach ($this->createBatches($totalRows, $batchSize) as $batch) {
            $message = [
                'file'  => $file,
                'start' => $batch['start'],
                'end'   => $batch['end'],
            ];

            echo "Sending batch: " . json_encode($message) . "\n";

            $this->client->sendMessage([
                'QueueUrl' => $this->queueUrl,
                'MessageBody' => json_encode($message),
            ]);
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
}