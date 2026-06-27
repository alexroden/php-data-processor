<?php

require __DIR__ . '/../../vendor/autoload.php';

use App\Repositories\AwsS3ClientAdapter;
use App\Repositories\AwsSqsClientAdapter;
use App\Services\Sqs;
use Aws\S3\S3Client;
use App\Services\S3;
use Aws\Sqs\SqsClient;
use Psr\Http\Message\StreamInterface;

function main(): void
{
    $bucket = getenv('S3_BUCKET');

    echo "Runner started...\n";

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

    $sqsClient = new SqsClient([
        'version' => 'latest',
        'region'  => getenv('AWS_DEFAULT_REGION') ?: 'eu-west-1',
        'endpoint' => getenv('SQS_URL'),
        'credentials' => [
            'key'    => getenv('AWS_ACCESS_KEY_ID'),
            'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
        ],
    ]);

    $sqs = new Sqs(new AwsSqsClientAdapter($sqsClient),  getenv('SQS_QUEUE_URL'));

    $files = $s3->listCsvFiles($bucket);

    if (empty($files)) {
        echo "No CSV files found.\n";
        return;
    }
    $priority = [
        'students.csv' => 0,
    ];

    usort($files, function ($a, $b) use ($priority) {

        $aName = basename($a);
        $bName = basename($b);

        $pa = $priority[$aName] ?? 999;
        $pb = $priority[$bName] ?? 999;

        if ($pa === $pb) {
            return $a <=> $b;
        }

        return $pa <=> $pb;
    });

    foreach ($files as $file) {
        echo "Processing: {$file}\n";

        $csv = $s3->getObject($bucket, $file);

        $rowCount = countCsvRows($csv);

        echo "Row count: {$rowCount}\n";

        $type = preg_replace('/\.csv$/', '', basename($file));

        $sqs->addBatches($file, $type, $rowCount, 250);
    }

    echo "Done.\n";
}

main();


function countCsvRows(StreamInterface $body): int
{
    $count = 0;

    $stream = fopen('php://temp', 'r+');
    stream_copy_to_stream($body->detach(), $stream);
    rewind($stream);

    while (($row = fgetcsv($stream, 0, ",", "\"", "\\")) !== false) {
        if ($row && count($row) > 1) {
            $count++;
        }
    }

    fclose($stream);

    return $count;
}