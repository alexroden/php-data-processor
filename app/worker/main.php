<?php

use App\Adapters\AwsS3ClientAdapter;
use App\Adapters\AwsSqsClientAdapter;
use App\Importers\StudentImporter;
use App\Importers\StudentSubjectImporter;
use App\Services\S3;
use App\Services\Sqs;
use App\Worker\Worker;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;

require __DIR__ . '/../vendor/autoload.php';


$bucket = getenv('S3_BUCKET');

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

$worker = new Worker(
    $sqs,
    $s3,
    db(),
    getenv('S3_BUCKET'),
    new StudentImporter(db()),
    new StudentSubjectImporter(db()),
);

$worker->run();
