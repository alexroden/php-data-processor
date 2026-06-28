<?php

namespace App\Importers\Traits;

trait FailedJobs
{
    /**
     * @param string $externalId
     * @param string $file
     * @param string $exception
     *
     * @return void
     */
    private function submitFailedJob(string $externalId, string $file, string $exception): void
    {
        $pdo = $this->getPdo();

        $stmt = $pdo->prepare("
            INSERT INTO failed_jobs (
                external_id,
                file,
                exception
            ) VALUES (
                :external_id,
                :file,
                :exception
            )
        ");

        $stmt->execute([
            'external_id' => $externalId,
            'file'        => $file,
            'exception'   => $exception,
        ]);
    }
}