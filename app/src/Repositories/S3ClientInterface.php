<?php

namespace src\Repositories;

interface S3ClientInterface
{
    public function listObjectsV2(array $args = []): array;

    public function getObject(array $args = []): array;
}