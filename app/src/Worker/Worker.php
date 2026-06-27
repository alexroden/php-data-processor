<?php

namespace App\Worker;

use App\Services\S3;
use App\Services\Sqs;
use App\Services\StudentImporter;
use App\Services\StudentSubjectImporter;
use PDO;

final class Worker
{
    public function __construct(
        private readonly Sqs                    $sqs,
        private readonly S3                     $s3,
        private readonly PDO                    $pdo,
        private readonly string                 $bucket,
        private readonly StudentImporter        $students,
        private readonly StudentSubjectImporter $subjects,
    )
    {
    }

    public function run(): void
    {
        echo "Worker started...\n";

        while (true) {
            try {
                $messages = $this->sqs->receiveMessages();

                if (empty($messages)) {
                    sleep(2);
                    continue;
                }

                foreach ($messages as $message) {
                    $this->processMessage($message);
                }

            } catch (\Throwable $e) {
                echo "Worker error: {$e->getMessage()}\n";
                sleep(5);
            }
        }
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    private function processMessage(array $message): void
    {
        $body = json_decode($message['Body'], true, flags: JSON_THROW_ON_ERROR);

        echo "Worker " . getmypid() . " processing batch {$body['batch']}\n";

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
            if ($row === [null]) continue;
            $rows[] = $row;
        }

        fclose($stream);

        return $rows;
    }
}