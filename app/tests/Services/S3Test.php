<?php

namespace Tests\Services;

use App\Interfaces\S3ClientInterface;
use App\Services\S3;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

final class S3Test extends TestCase
{
    public function test_list_csv_files_filters_only_csv(): void
    {
        $mockClient = $this->createStub(S3ClientInterface::class);

        $mockClient->method('listObjectsV2')
            ->willReturn([
                'Contents' => [
                    ['Key' => 'file1.csv'],
                    ['Key' => 'file2.txt'],
                    ['Key' => 'folder/file3.csv'],
                ],
            ]);

        $service = new S3($mockClient);

        $result = $service->listCsvFiles('bucket');

        $this->assertSame([
            'file1.csv',
            'folder/file3.csv',
        ], $result);
    }

    public function test_get_object_returns_stream(): void
    {
        $mockStream = $this->createStub(StreamInterface::class);

        $mockStream->method('__toString')
            ->willReturn('csv-content-here');

        $mockClient = $this->createStub(S3ClientInterface::class);

        $mockClient->method('getObject')
            ->willReturn([
                'Body' => $mockStream,
            ]);

        $service = new S3($mockClient);

        $result = $service->getObject('my-bucket', 'file.csv');

        $this->assertSame($mockStream, $result);
    }

    public function test_put_object_sends_correct_payload(): void
    {
        $mockClient = $this->createMock(S3ClientInterface::class);

        $mockClient->expects($this->once())
            ->method('putObject')
            ->with([
                'Bucket' => 'bucket',
                'Key'    => 'path/file.csv',
                'Body'   => 'csv-data',
            ]);

        $service = new S3($mockClient);

        $service->putObject('bucket', 'path/file.csv', 'csv-data');
    }

    public function test_delete_object_sends_correct_payload(): void
    {
        $mockClient = $this->createMock(S3ClientInterface::class);

        $mockClient->expects($this->once())
            ->method('deleteObject')
            ->with([
                'Bucket' => 'bucket',
                'Key'    => 'path/file.csv',
            ]);

        $service = new S3($mockClient);

        $service->deleteObject('bucket', 'path/file.csv');
    }
}