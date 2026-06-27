<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Runner\Runner;
use App\Repositories\AwsS3ClientAdapter;
use App\Repositories\AwsSqsClientAdapter;
use App\Services\S3;
use App\Services\Sqs;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;

$bucket = getenv('S3_BUCKET');

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

$sqsClient = new SqsClient([
    'version' => 'latest',
    'region'  => getenv('AWS_DEFAULT_REGION') ?: 'eu-west-1',
    'endpoint' => getenv('SQS_URL'),
    'credentials' => [
        'key'    => getenv('AWS_ACCESS_KEY_ID'),
        'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
    ],
]);

$runner = new Runner(
    new S3(new AwsS3ClientAdapter($s3Client)),
    new Sqs(new AwsSqsClientAdapter($sqsClient), getenv('SQS_QUEUE_URL')),
    $bucket
);

$runner->run();