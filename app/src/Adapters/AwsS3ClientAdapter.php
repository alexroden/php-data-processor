<?php

namespace App\Adapters;

use App\Interfaces\S3ClientInterface;
use Aws\Result;
use Aws\S3\S3Client;

final class AwsS3ClientAdapter implements S3ClientInterface
{
    /**
     * @param S3Client $client
     */
    public function __construct(private S3Client $client) {}

    /**
     * @param array $args
     *
     * @return array
     */
    public function listObjectsV2(array $args = []): array
    {
        return $this->client->listObjectsV2($args)->toArray();
    }

    /**
     * @param array $args
     *
     * @return array
     */
    public function getObject(array $args = []): array
    {
        return $this->client->getObject($args)->toArray();
    }

    /**
     * @param array $args
     *
     * @return Result
     */
    public function putObject(array $args = []): Result
    {
        return $this->client->putObject($args);
    }

    /**
     * @param array $args
     *
     * @return Result
     */
    public function deleteObject(array $args = []): Result
    {
        return $this->client->deleteObject($args);
    }
}