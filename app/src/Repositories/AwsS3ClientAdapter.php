<?php

namespace App\Repositories;

use Aws\Result;
use Aws\S3\S3Client;

final class AwsS3ClientAdapter implements S3ClientInterface
{
    public function __construct(private S3Client $client) {}

    public function listObjectsV2(array $args = []): array
    {
        return $this->client->listObjectsV2($args)->toArray();
    }

    public function getObject(array $args = []): array
    {
        return $this->client->getObject($args)->toArray();
    }

    public function putObject(array $args = []): Result
    {
        return $this->client->putObject($args);
    }

    public function deleteObject(array $args = []): Result
    {
        return $this->client->deleteObject($args);
    }
}