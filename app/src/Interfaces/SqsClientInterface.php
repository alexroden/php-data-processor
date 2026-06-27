<?php

namespace App\Interfaces;

use Aws\Result;

interface SqsClientInterface
{
    public function sendMessage(array $payload): Result;

    public function receiveMessage(array $args = []): Result;

    public function deleteMessage(array $args = []): Result;
}
