<?php

use App\Repositories\AwsS3ClientAdapter;
use App\Repositories\AwsSqsClientAdapter;
use App\Services\S3;
use App\Services\Sqs;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;

require __DIR__ . '/../../vendor/autoload.php';

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

const STUDENT_SUBJECT_EXTERNAL_ID = 0;
const STUDENT_SUBJECT_QUALIFICATION = 1;
const STUDENT_SUBJECT_SUBJECT = 2;
const STUDENT_SUBJECT_PREDICTED_GRADE = 3;
const STUDENT_SUBJECT_ACTUAL_GRADE = 4;

function main(): void
{
    $bucket = getenv('S3_BUCKET');

    echo "Worker started...\n";

    $sqsClient = new SqsClient([
        'version' => 'latest',
        'region'  => getenv('AWS_DEFAULT_REGION') ?: 'eu-west-1',
        'endpoint' => getenv('SQS_URL'),
        'credentials' => [
            'key'    => getenv('AWS_ACCESS_KEY_ID'),
            'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
        ],
    ]);

    $sqs = new SQS(new AwsSqsClientAdapter($sqsClient), getenv('SQS_QUEUE_URL'));

    $s3Client = new S3Client([
        'version' => 'latest',
        'region'  => getenv('AWS_DEFAULT_REGION') ?: 'eu-west-1',
        'endpoint' => getenv('S3_URL'),
        'use_path_style_endpoint' => true,
        'credentials' => [
            'key'    => getenv('AWS_ACCESS_KEY_ID'),
            'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
        ],
    ]);

    $s3 = new S3(new AwsS3ClientAdapter($s3Client));

    $pdo = db();

    while (true) {
        try {
            $messages = $sqs->receiveMessages();

            if (empty($messages)) {
                sleep(2);
                continue;
            }

            foreach ($messages as $message) {
                $body = json_decode($message['Body'], true, flags: JSON_THROW_ON_ERROR);

                echo "Worker " . getmypid() . " processing batch {$body['batch']}\n";

                echo sprintf(
                    "Processing batch %d: %s\n",
                    $body['batch'],
                    $body['file']
                );

                $csv = $s3->getObject($bucket, $body['file']);
                $rows = processCsv($csv);

                switch ($body['type']) {
                    case 'students':
                        processStudents($rows, $pdo);
                        break;
                    case 'student_subjects_grades':
                        processSubjects($rows, $pdo);
                        break;
                    default:
                        $e = new Exception("Unknown file type: {$body['type']}");
                        echo $e->getMessage();
                }

                try {
                    $sqs->deleteMessage($message['ReceiptHandle']);
                } catch (Throwable $e) {
                    echo $e->getMessage();
                }
            }
        } catch (Throwable $e) {
            echo "Worker error: " . $e->getMessage() . PHP_EOL;
            sleep(5);
        }
    }
}

main();

function processStudents(array $rows, PDO $pdo): void
{
    $studentsStmt = $pdo->prepare("
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

    $getIdStmt = $pdo->prepare("
        SELECT id
        FROM students
        WHERE external_id = :external_id
        LIMIT 1
    ");

    $guardianStmt = $pdo->prepare("
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
        $genderRaw = strtoupper(trim($row[STUDENT_GENDER]));
        $gender = match ($genderRaw) {
            'M', 'MALE' => 'M',
            'F', 'FEMALE' => 'F',
            default => null,
        };

        if ($gender === null) {
            echo "SKIP gender: " . json_encode($row) . PHP_EOL;
            continue;
        }

        $dateRaw = trim($row[STUDENT_ADMISSION_DATE] ?? '');

        $date = DateTime::createFromFormat('d/m/Y', $dateRaw)
            ?: DateTime::createFromFormat('Y-m-d', $dateRaw);

        if (!$date) {
            echo "BAD DATE: " . $dateRaw . PHP_EOL;
            continue;
        }

        try {
            $studentsStmt->execute([
                'external_id'    => $row[STUDENT_EXTERNAL_ID],
                'first_name'     => $row[STUDENT_FIRSTNAME],
                'last_name'      => $row[STUDENT_LASTNAME],
                'gender'         => $gender,
                'admission_date' => $date->format('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            echo "DB ERROR ROW: " . json_encode($row) . PHP_EOL;
            echo $e->getMessage() . PHP_EOL;
        }

        try {
            $getIdStmt->execute([
                'external_id' => $row[STUDENT_EXTERNAL_ID],
            ]);
        } catch (Throwable $e) {
            echo "DB ERROR ROW: " . json_encode($row) . PHP_EOL;
            echo $e->getMessage() . PHP_EOL;
        }

        $studentId = $getIdStmt->fetchColumn();
        if (!$studentId) {
            echo "MISSING STUDENT for external_id: " . $row[STUDENT_EXTERNAL_ID] . PHP_EOL;
            continue;
        }

        try {
            $guardianStmt->execute([
                'student_id' => $studentId,
                'name'       => $row[STUDENT_PARENT_NAME],
                'phone'      => $row[STUDENT_PARENT_PHONE],
                'email'      => $row[STUDENT_PARENT_EMAIL],
            ]);
        } catch (Throwable $e) {
            echo "DB ERROR ROW: " . json_encode($row) . PHP_EOL;
            echo $e->getMessage() . PHP_EOL;
        }
    }
}

function processSubjects(array $rows, PDO $pdo): void
{
    $stmt = $pdo->prepare("
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

    try {
        $getIdStmt = $pdo->prepare("
            SELECT id
            FROM students
            WHERE external_id = :external_id
            LIMIT 1
        ");
    } catch (Throwable $e) {
        echo "DB ERROR: " . $e->getMessage() . PHP_EOL;
    }

    foreach ($rows as $row) {
        $getIdStmt->execute([
            'external_id' => $row[STUDENT_SUBJECT_EXTERNAL_ID],
        ]);

        $studentId = $getIdStmt->fetchColumn();

        try {
            $stmt->execute([
                'student_id'        => $studentId,
                'qualification'     => $row[STUDENT_SUBJECT_QUALIFICATION],
                'subject'           => $row[STUDENT_SUBJECT_SUBJECT],
                'predicted_grade'   => $row[STUDENT_SUBJECT_PREDICTED_GRADE] ?? null,
                'actual_grade'     => $row[STUDENT_SUBJECT_ACTUAL_GRADE] ?? null,
            ]);
        } catch (Throwable $e) {
            echo "DB ERROR: " . $e->getMessage() . PHP_EOL;
        }
    }
}

function db(): PDO {
    return new PDO(
        sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            getenv('MYSQL_HOST') ?: 'mysql',
            getenv('MYSQL_DATABASE'),
        ),
        getenv('MYSQL_USER'),
        getenv('MYSQL_PASSWORD'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

function processCsv(string $csv): array
{
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, $csv);
    rewind($stream);

    $rows = [];

    fgetcsv($stream, 0, ',', '"', '\\');
    while (($row = fgetcsv($stream, 0, ',', '"', '\\')) !== false) {
        if ($row === [null] || $row === false) {
            continue;
        }

        $rows[] = $row;
    }

    fclose($stream);

    return $rows;
}
