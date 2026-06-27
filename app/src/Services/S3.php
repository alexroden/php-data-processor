<?php

namespace App\Services;

use App\Repositories\S3ClientInterface;
use Psr\Http\Message\StreamInterface;

final class S3
{
    public function __construct(private S3ClientInterface $client) {}

    public function listCsvFiles(string $bucket): array
    {
        $result = $this->client->listObjectsV2([
            'Bucket' => $bucket,
        ]);

        $files = [];

        foreach ($result['Contents'] ?? [] as $object) {
            if (str_ends_with($object['Key'], '.csv')) {
                $files[] = $object['Key'];
            }
        }

        return $files;
    }

    public function getObject(string $bucket, string $key): StreamInterface
    {
        $result = $this->client->getObject([
            'Bucket' => $bucket,
            'Key'    => $key,
        ]);

        return $result['Body'];
    }

    public function putObject(string $bucket, string $key, string $body): void
    {
        $this->client->putObject([
            'Bucket' => $bucket,
            'Key'    => $key,
            'Body'   => $body,
        ]);
    }

    public function deleteObject(string $bucket, string $key): void
    {
        $this->client->deleteObject([
            'Bucket' => $bucket,
            'Key'    => $key,
        ]);
    }
}