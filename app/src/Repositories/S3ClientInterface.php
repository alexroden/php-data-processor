<?php

namespace App\Repositories;

use Aws\Result;

interface S3ClientInterface
{
    public function listObjectsV2(array $args = []): array;

    public function getObject(array $args = []): array;

    public function putObject(array $args = []): Result;

    public function deleteObject(array $args = []): Result;
}