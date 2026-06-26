<?php

namespace App\Repositories;

interface SqsClientInterface
{
    public function sendMessage(array $payload): void;
}