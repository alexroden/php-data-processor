<?php

namespace Tests\Fakers;

use PDO;
use ReturnTypeWillChange;

final class PDOFake extends PDO
{
    public array $executedStatements = [];
    public array $preparedStatements = [];

    public function __construct() {}

    #[ReturnTypeWillChange]
    public function prepare(string $query, array $options = []): PDOStatementFake
    {
        $this->preparedStatements[] = $query;

        return new PDOStatementFake($this, $query);
    }
}

final class PDOStatementFake
{
    public function __construct(
        private PDOFake $pdo,
        private string $query
    ) {}

    public function execute(array $params = []): bool
    {
        $this->pdo->executed[] = [
            'query'  => $this->query,
            'params' => $params,
        ];

        return true;
    }

    public function fetchColumn(): mixed
    {
        return 1;
    }
}