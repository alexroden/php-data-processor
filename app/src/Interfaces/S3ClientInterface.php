<?php

namespace App\Interfaces;

use Aws\Result;

interface S3ClientInterface
{
    /**
     * @param array $args
     *
     * @return array
     */
    public function listObjectsV2(array $args = []): array;

    /**
     * @param array $args
     *
     * @return array
     */
    public function getObject(array $args = []): array;

    /**
     * @param array $args
     *
     * @return Result
     */
    public function putObject(array $args = []): Result;

    /**
     * @param array $args
     *
     * @return Result
     */
    public function deleteObject(array $args = []): Result;
}