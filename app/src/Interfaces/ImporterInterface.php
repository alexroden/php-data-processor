<?php

namespace App\Interfaces;

interface ImporterInterface
{
    /**
     * @return \PDO
     */
    public function getPdo(): \PDO;

    /**
     * @param array $rows
     *
     * @return void
     */
    public function import(array $rows): void;
}