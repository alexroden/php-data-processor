<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use src\Services\S3;
use src\Repositories\S3ClientInterface;

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
}