<?php

namespace App\Services;

use App\Interfaces\ImporterInterface;
use PDO;

final class StudentSubjectImporter implements ImporterInterface
{
    const STUDENT_SUBJECT_EXTERNAL_ID = 0;
    const STUDENT_SUBJECT_QUALIFICATION = 1;
    const STUDENT_SUBJECT_SUBJECT = 2;
    const STUDENT_SUBJECT_PREDICTED_GRADE = 3;
    const STUDENT_SUBJECT_ACTUAL_GRADE = 4;

    public function __construct(private PDO $pdo) {}

    public function import(array $rows): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO student_subjects (
                student_id,
                qualification,
                subject,
                predicted_grade,
                actual_grade
            ) VALUES (
                :student_id,
                :qualification,
                :subject,
                :predicted_grade,
                :actual_grade
            )
            ON DUPLICATE KEY UPDATE
                predicted_grade = VALUES(predicted_grade),
                actual_grade = VALUES(actual_grade)
        ");

        $getId = $this->pdo->prepare("
            SELECT id
            FROM students
            WHERE external_id = :external_id
            LIMIT 1
        ");

        foreach ($rows as $row) {
            $getId->execute([
                'external_id' => $row[self::STUDENT_SUBJECT_EXTERNAL_ID],
            ]);

            $studentId = $getId->fetchColumn();

            if (!$studentId) continue;

            $stmt->execute([
                'student_id'        => $studentId,
                'qualification'     => $row[self::STUDENT_SUBJECT_QUALIFICATION],
                'subject'           => $row[self::STUDENT_SUBJECT_SUBJECT],
                'predicted_grade'   => $row[self::STUDENT_SUBJECT_PREDICTED_GRADE] ?? null,
                'actual_grade'     => $row[self::STUDENT_SUBJECT_ACTUAL_GRADE] ?? null,
            ]);
        }
    }
}