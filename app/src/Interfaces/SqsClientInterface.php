<?php

namespace App\Interfaces;

use Aws\Result;

interface SqsClientInterface
{
    /**
     * @param array $payload
     *
     * @return Result
     */
    public function sendMessage(array $payload): Result;

    /**
     * @param array $args
     *
     * @return Result
     */
    public function receiveMessage(array $args = []): Result;

    /**
     * @param array $args
     *
     * @return Result
     */
    public function deleteMessage(array $args = []): Result;
}
