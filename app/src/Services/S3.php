<?php

namespace App\Services;

use App\Interfaces\S3ClientInterface;
use App\Interfaces\S3Interface;
use Psr\Http\Message\StreamInterface;

final class S3 implements S3Interface
{
    /**
     * @param S3ClientInterface $client
     */
    public function __construct(private S3ClientInterface $client) {}

    /**
     * @param string $bucket
     *
     * @return array
     */
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

    /**
     * @param string $bucket
     * @param string $key
     *
     * @return StreamInterface
     */
    public function getObject(string $bucket, string $key): StreamInterface
    {
        $result = $this->client->getObject([
            'Bucket' => $bucket,
            'Key'    => $key,
        ]);

        return $result['Body'];
    }

    /**
     * @param string $bucket
     * @param string $key
     * @param string $body
     *
     * @return void
     */
    public function putObject(string $bucket, string $key, string $body): void
    {
        $this->client->putObject([
            'Bucket' => $bucket,
            'Key'    => $key,
            'Body'   => $body,
        ]);
    }

    /**
     * @param string $bucket
     * @param string $key
     *
     * @return void
     */
    public function deleteObject(string $bucket, string $key): void
    {
        $this->client->deleteObject([
            'Bucket' => $bucket,
            'Key'    => $key,
        ]);
    }
}