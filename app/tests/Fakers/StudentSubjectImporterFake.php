<?php

namespace Tests\Fakers;

use App\Interfaces\ImporterInterface;

final class StudentSubjectImporterFake implements ImporterInterface
{
    public array $calls = [];

    public function import(array $rows): void
    {
        $this->calls[] = $rows;
    }
}