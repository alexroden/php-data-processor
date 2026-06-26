<?php

namespace src\Services;

use src\Repositories\S3ClientInterface;

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

    public function getObject(string $bucket, string $key): string
    {
        $result = $this->client->getObject([
            'Bucket' => $bucket,
            'Key'    => $key,
        ]);

        return (string) $result['Body'];
    }
}