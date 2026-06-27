<?php

namespace Tests\Worker;

use App\Worker\Worker;
use PHPUnit\Framework\TestCase;
use Tests\Fakers\PDOFake;
use Tests\Fakers\S3Fake;
use Tests\Fakers\SqsFake;
use Tests\Fakers\StudentImporterFake;
use Tests\Fakers\StudentSubjectImporterFake;

final class WorkerTest extends TestCase
{
    public function test_students_message_routes_to_students_importer(): void
    {
        $sqs = new SqsFake();
        $s3 = new S3Fake();

        $sqs->addMessage('/assets/chunk.csv', 'students', 0);

        $students = new StudentImporterFake();
        $subjects = new StudentSubjectImporterFake();

        $worker = new Worker(
            $sqs,
            $s3,
            new PDOFake(),
            'bucket',
            $students,
            $subjects
        );

        $worker->processOnce();

        $this->assertCount(1, $students->calls);

        $this->assertCount(1, $sqs->deleted);
        $this->assertSame([
            'bucket' => 'bucket',
            'key' => '/assets/chunk.csv',
        ], $s3->deleted[0]);
    }
}