<?php

namespace Tests\Runner;

use App\Runner\Runner;
use PHPUnit\Framework\TestCase;
use Tests\Fakers\S3Fake;
use Tests\Fakers\SqsFake;
use Tests\Fakers\CsvChunkerFake;

final class RunnerTest extends TestCase
{
    public function test_run_does_nothing_when_no_files_exist(): void
    {
        $s3 = new S3Fake();
        $sqs = new SqsFake();
        $chunker = new CsvChunkerFake();

        $s3->setFiles([]);

        $runner = new Runner($s3, $sqs, $chunker, 'bucket');
        $runner->run();

        $this->assertEmpty($s3->uploaded);
        $this->assertEmpty($sqs->messages);
    }

    public function test_students_file_processed_first(): void
    {
        $s3 = new S3Fake();
        $sqs = new SqsFake();
        $chunker = new CsvChunkerFake();

        $s3->setFiles([
            '/assets/student_subjects.csv',
            '/assets/students.csv',
        ]);

        $s3->setFiles(['/assets/students.csv', 'csv-data']);

        $chunker->setChunks([
            '/fake/chunk-1.csv',
        ]);

        $runner = new Runner($s3, $sqs, $chunker, 'bucket');
        $runner->run();

        $this->assertNotEmpty($s3->uploaded);

        $this->assertStringContainsString(
            '/assets/students/chunk-0000-',
            $s3->uploaded[0]['key']
        );
    }

    public function test_chunk_uploaded_and_queued(): void
    {
        $s3 = new S3Fake();
        $sqs = new SqsFake();
        $chunker = new CsvChunkerFake();

        $s3->setFiles(['/assets/students.csv']);

        $chunkFile = tempnam(sys_get_temp_dir(), 'chunk');
        file_put_contents($chunkFile, 'csv-data');

        $chunker->setChunks([$chunkFile]);

        $runner = new Runner($s3, $sqs, $chunker, 'bucket');
        $runner->run();

        $this->assertCount(1, $s3->uploaded);
        $this->assertCount(1, $sqs->messages);

        $this->assertSame('csv-data', $s3->uploaded[0]['body']);

        unlink($chunkFile);
    }

    public function test_multiple_chunks(): void
    {
        $s3 = new S3Fake();
        $sqs = new SqsFake();
        $chunker = new CsvChunkerFake();

        $s3->setFiles(['/assets/students.csv']);

        $c1 = tempnam(sys_get_temp_dir(), 'c1');
        $c2 = tempnam(sys_get_temp_dir(), 'c2');

        file_put_contents($c1, 'one');
        file_put_contents($c2, 'two');

        $chunker->setChunks([$c1, $c2]);

        $runner = new Runner($s3, $sqs, $chunker, 'bucket');
        $runner->run();

        $this->assertCount(2, $s3->uploaded);
        $this->assertCount(2, $sqs->messages);

        $this->assertSame('one', $s3->uploaded[0]['body']);
        $this->assertSame('two', $s3->uploaded[1]['body']);

        unlink($c1);
        unlink($c2);
    }
}