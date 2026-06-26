<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\S3;
use App\Repositories\S3ClientInterface;
use Psr\Http\Message\StreamInterface;

class S3Test extends TestCase
{
    public function testListCsvFilesFiltersOnlyCsv(): void
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

    public function testGetObjectReturnsStream(): void
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
}