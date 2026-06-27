<?php

namespace App\Interfaces;

interface ImporterInterface
{
    public function getPdo(): \PDO;

    public function import(array $rows): void;
}