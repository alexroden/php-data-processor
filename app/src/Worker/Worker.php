<?php

namespace App\Worker;

use App\Interfaces\ImporterInterface;
use App\Interfaces\S3Interface;
use App\Interfaces\SqsInterface;
use PDO;

final class Worker
{
    public function __construct(
        private readonly SqsInterface      $sqs,
        private readonly S3Interface       $s3,
        private readonly PDO               $pdo,
        private readonly string            $bucket,
        private readonly ImporterInterface $students,
        private readonly ImporterInterface $subjects,
    )
    {
    }

    public function run(): void
    {
        echo "Worker started...\n";

        while (true) {
            try {
                $this->processOnce();
                sleep(2);
            } catch (\Throwable $e) {
                echo "Worker error: {$e->getMessage()}\n";
                sleep(5);
            }
        }
    }

    public function processOnce(): void
    {
        $messages = $this->sqs->receiveMessages();

        if (empty($messages)) {
            return;
        }

        foreach ($messages as $message) {
            $this->processMessage($message);
        }
    }

    private function processMessage(array $message): void
    {
        $body = json_decode($message['Body'], true, flags: JSON_THROW_ON_ERROR);

        $csv = $this->s3->getObject($this->bucket, $body['file']);
        $rows = $this->processCsv($csv);

        $this->route($body['type'], $rows);

        $this->sqs->deleteMessage($message['ReceiptHandle']);
        $this->s3->deleteObject($this->bucket, $body['file']);
    }

    private function route(string $type, array $rows): void
    {
        match ($type) {
            'students' => $this->students->import($rows),
            'student_subjects_grades' => $this->subjects->import($rows),
            default => throw new \Exception("Unknown type: {$type}"),
        };
    }

    private function processCsv(string $csv): array
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csv);
        rewind($stream);

        $rows = [];

        fgetcsv($stream, 0, ',', '"', '\\');

        while (($row = fgetcsv($stream, 0, ',', '"', '\\')) !== false) {
            if ($row === [null]) {
                continue;
            }

            $rows[] = $row;
        }

        fclose($stream);

        return $rows;
    }
}