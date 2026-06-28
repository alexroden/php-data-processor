<?php

namespace App\Interfaces;

use Psr\Http\Message\StreamInterface;

interface S3Interface
{
    /**
     * @param string $bucket
     *
     * @return array
     */
    public function listCsvFiles(string $bucket): array;

    /**
     * @param string $bucket
     * @param string $key
     *
     * @return StreamInterface
     */
    public function getObject(string $bucket, string $key): StreamInterface;

    /**
     * @param string $bucket
     * @param string $key
     * @param string $body
     *
     * @return void
     */
    public function putObject(string $bucket, string $key, string $body): void;

    /**
     * @param string $bucket
     * @param string $key
     *
     * @return void
     */
    public function deleteObject(string $bucket, string $key): void;
}