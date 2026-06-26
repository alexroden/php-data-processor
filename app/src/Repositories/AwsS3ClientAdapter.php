<?php

namespace src\Repositories;

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
}