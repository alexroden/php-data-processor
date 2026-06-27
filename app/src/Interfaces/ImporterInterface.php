<?php

namespace App\Interfaces;

interface ImporterInterface
{
    public function import(array $rows): void;
}