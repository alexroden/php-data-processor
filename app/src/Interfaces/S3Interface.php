<?php

namespace App\Interfaces;

use Psr\Http\Message\StreamInterface;

interface S3Interface
{
    public function listCsvFiles(string $bucket): array;

    public function getObject(string $bucket, string $key): StreamInterface;

    public function putObject(string $bucket, string $key, string $body): void;

    public function deleteObject(string $bucket, string $key): void;
}