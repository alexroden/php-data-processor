<?php

namespace App\Worker;

use App\Interfaces\ImporterInterface;
use App\Interfaces\S3Interface;
use App\Interfaces\SqsInterface;
use PDO;

final readonly class Worker
{
    /**
     * @param SqsInterface $sqs
     * @param S3Interface $s3
     * @param PDO $pdo
     * @param string $bucket
     * @param ImporterInterface $students
     * @param ImporterInterface $subjects
     */
    public function __construct(
        private SqsInterface      $sqs,
        private S3Interface       $s3,
        private PDO               $pdo,
        private string            $bucket,
        private ImporterInterface $students,
        private ImporterInterface $subjects,
    )
    {
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function processOnce(): void
    {
        $messages = $this->sqs->receiveMessages();

        if (empty($messages)) {
            return;
        }

        foreach ($messages as $message) {
            $this->processMessage($message);
        }

        echo "Done.\n";
    }

    /**
     * @param array $message
     *
     * @return void
     */
    private function processMessage(array $message): void
    {
        try {
            $body = json_decode($message['Body'], true, flags: JSON_THROW_ON_ERROR);

            echo sprintf(
                "Processing batch %d: %s\n",
                $body['batch'],
                $body['file'],
            );

            $csv = $this->s3->getObject($this->bucket, $body['file']);
            $rows = $this->processCsv($csv);

            $this->route($body['type'], $rows);
        }  catch (\JsonException $e) {
            echo "INVALID JSON MESSAGE: {$e->getMessage()}\n";
            $this->sqs->deleteMessage($message['ReceiptHandle']);
            return;
        } catch (\Throwable $e) {
            echo "FAILED MESSAGE: {$e->getMessage()}\n";
            // @TODO: Failed jobs
            return;
        }

        $this->safeCleanup($message, $body['file']);
    }

    /**
     * @param string $type
     * @param array $rows
     *
     * @return void
     *
     * @throws \Exception
     */
    private function route(string $type, array $rows): void
    {
        match ($type) {
            'students' => $this->students->import($rows),
            'student_subjects_grades' => $this->subjects->import($rows),
            default => throw new \Exception("Unknown type: {$type}"),
        };
    }

    /**
     * @param string $csv
     *
     * @return array
     */
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

    /**
     * @param array $message
     * @param string $file
     *
     * @return void
     */
    private function safeCleanup(array $message, string $file): void
    {
        try {
            $this->sqs->deleteMessage($message['ReceiptHandle']);
        } catch (\Throwable $e) {
            echo "Failed to delete message: {$e->getMessage()}\n";
        }

        try {
            $this->s3->deleteObject($this->bucket, $file);
        } catch (\Throwable $e) {
            echo "Failed to delete file: {$e->getMessage()}\n";
        }
    }
}