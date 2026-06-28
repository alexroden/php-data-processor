<?php

namespace App\Importers;

use App\Importers\Traits\FailedJobs;
use App\Interfaces\ImporterInterface;
use DateTime;
use PDO;

final class StudentImporter implements ImporterInterface
{
    use FailedJobs;

    const STUDENT_EXTERNAL_ID = 0;
    const STUDENT_FIRSTNAME = 1;
    const STUDENT_LASTNAME = 2;
    const STUDENT_GENDER = 3;
    const STUDENT_YEAR_GROUP = 4;
    const STUDENT_TUTOR_GROUP = 5;
    const STUDENT_ADMISSION_DATE = 6;
    const STUDENT_ADMISSION_PERCENTAGE = 7;
    const STUDENT_PARENT_NAME = 8;
    const STUDENT_PARENT_PHONE = 9;
    const STUDENT_PARENT_EMAIL = 10;

    /**
     * @param PDO $pdo
     */
    public function __construct(private PDO $pdo) {}

    /**
     * @param array $rows
     *
     * @return void
     */
    public function import(array $rows): void
    {
        $insert = $this->pdo->prepare("
            INSERT INTO students (
                external_id,
                first_name,
                last_name,
                gender,
                admission_date
            ) VALUES (
                :external_id,
                :first_name,
                :last_name,
                :gender,
                :admission_date
            )
            ON DUPLICATE KEY UPDATE
                first_name = VALUES(first_name),
                last_name = VALUES(last_name),
                gender = VALUES(gender),
                admission_date = VALUES(admission_date)
        ");

        $getId = $this->pdo->prepare("
            SELECT id
            FROM students
            WHERE external_id = :external_id
            LIMIT 1
        ");

        $guardian = $this->pdo->prepare("
        INSERT INTO student_guardians (
            student_id,
            name,
            phone,
            email
        ) VALUES (
            :student_id,
            :name,
            :phone,
            :email
        )
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            phone = VALUES(phone),
            email = VALUES(email)
    ");

        foreach ($rows as $row) {
            $genderRaw = strtoupper(trim($row[self::STUDENT_GENDER]));
            $gender = match ($genderRaw) {
                'M', 'MALE' => 'M',
                'F', 'FEMALE' => 'F',
                default => null,
            };

            if ($gender === null) {
                $e = "SKIP gender: " . json_encode($row) . PHP_EOL;
                $this->submitFailedJob($row[self::STUDENT_EXTERNAL_ID], 'student', $e);

                echo $e;
                continue;
            }

            $dateRaw = trim($row[self::STUDENT_ADMISSION_DATE] ?? '');

            $date = DateTime::createFromFormat('d/m/Y', $dateRaw)
                ?: DateTime::createFromFormat('Y-m-d', $dateRaw);

            if (!$date) {
                $e = "BAD DATE: " . $dateRaw . PHP_EOL;
                $this->submitFailedJob($row[self::STUDENT_EXTERNAL_ID], 'student', $e);

                echo $e;
                continue;
            }

            $insert->execute([
                'external_id'    => $row[self::STUDENT_EXTERNAL_ID],
                'first_name'     => $row[self::STUDENT_FIRSTNAME],
                'last_name'      => $row[self::STUDENT_LASTNAME],
                'gender'         => $gender,
                'admission_date' => $date->format('Y-m-d H:i:s'),
            ]);
            $getId->execute([
                'external_id' => $row[self::STUDENT_EXTERNAL_ID],
            ]);

            $studentId = $getId->fetchColumn();

            if (!$studentId) {
                $e = "Unable to get student". PHP_EOL;
                $this->submitFailedJob($row[self::STUDENT_EXTERNAL_ID], 'student', $e);

                echo $e;
                continue;
            }

            $guardian->execute([
                'student_id' => $studentId,
                'name'       => $row[self::STUDENT_PARENT_NAME],
                'phone'      => $row[self::STUDENT_PARENT_PHONE],
                'email'      => $row[self::STUDENT_PARENT_EMAIL],
            ]);
        }
    }

    /**
     * @return PDO
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }
}