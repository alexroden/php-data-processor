# PHP Data Processor 

---

## Overview

This project is a standalone data processing pipeline built in PHP. It demonstrates how large datasets can be ingested, processed in chunks, and persisted through a distributed-style workflow using object storage, a message queue, and worker consumers.

The system follows a simple architecture:

```
Data Source → Runner → S3 (chunk storage) → SQS (queue) → Worker(s) → Database
```

The goal is to simulate a scalable ETL-style pipeline with emphasis on:

- Batch processing
- Fault tolerance
- Retry safety
- Separation of concerns
- Worker-based concurrency
- Data reconciliation potential

---

## Data Flow
### 1. Runner (Ingestion + Chunking)

The Runner is responsible for:

- Listing CSV files from S3
- Sorting files (priority-based ingestion)
- Downloading each file
- Splitting CSVs into chunks
- Uploading chunks back to S3
- Sending SQS messages per chunk

Each message contains:
```json
{
"file": "/assets/students/chunk-0001-abc.csv",
"type": "students",
"batch": 1
}
```

### 2. S3 (Object Storage Layer)

Used for:

- Storing raw CSV files
- Storing chunked CSV files
- Retrieving files for processing
- Deleting processed files

This abstracts away local filesystem dependencies and simulates scalable storage.

### 3. SQS (Queue Layer)

Used for:

- Decoupling ingestion from processing
- Enabling parallel worker execution
- Ensuring retry capability via message visibility

Each worker consumes messages independently.

### 4. Worker (Processing Layer)

The Worker:

- Continuously polls SQS
- Parses message payloads
- Loads CSV chunk data from S3
- Converts CSV into structured rows
- Routes data to the correct importer:
- StudentImporter
- StudentSubjectImporter
- Deletes processed messages
- Deletes processed S3 objects

### 5. Importers (Business Logic Layer)

Importers encapsulate domain-specific processing:

- Transform raw CSV rows into structured entities
- Persist data into MySQL via PDO
- Separate concerns between students and subjects

---

## Architecture Highlights
### Decoupled pipeline

Each stage is independent:

- Runner does not know about database
- Worker does not know about ingestion
- S3/SQS act as infrastructure boundaries

### Horizontal scalability

Workers can be scaled independently:

```shell
docker compose up --scale worker=4
```

### Chunk-based processing

Large datasets are split into smaller chunks:

- Reduces memory pressure
- Enables parallel processing
- Improves retry granularity

### Failure isolation
- File-level failure isolation in Runner
- Message-level isolation in Worker
- Partial retry handling via SQS

### Error Handling Strategy
#### Runner
Individual file failures do not stop processing

- Chunk upload failures are retried
- Logging is used for observability

#### Worker

- Each message is processed independently
- Failures do not block queue consumption
- Messages are deleted only after successful processing

### Retry Strategy

A basic exponential backoff retry system is used for:

- S3 uploads
- SQS message publishing
- File deletion

Example:
```php
private function retry(callable $fn)
```

This prevents transient failures from breaking the pipeline.

### Assumptions / Design Decisions
- CSV format is consistent and well-formed
- File types are inferred from filename (students.csv, student_subjects.csv)
- SQS messages are eventually consistent
- At-least-once delivery is acceptable (idempotency assumed downstream)
- PDO is used directly for simplicity rather than ORM

---

## Running the Project
### 1. Start infrastructure
```shell
make setup
```

This will start:

- MySQL
- S3 (MinIO)
- SQS
- Seed data
- Database migrations

### 2. Start workers
```shell
make start-workers
```

Runs multiple workers:

- `worker=4` parallel consumers
- Continuously polling SQS

### 3. Trigger processing
```shell
make trigger-runner
```

Starts the ingestion pipeline:

- Reads CSV files
- Splits into chunks
- Pushes work into SQS

### 4. Cleanup environment
```shell
make cleanup
```

Removes:

- Containers
- Volumes
- Images (local)

Resets system to a clean state.

---

## Testing

The system is designed with testability in mind:

- S3, SQS, and chunking are interface-driven
- Fakes are used for deterministic testing
- Worker and Runner can be executed in isolation

Run tests:
```shell
vendor/bin/phpunit
```

---

## Challenges & Trade-offs
### 1. File-based chunking

Using temporary files simplifies chunk handling but introduces:

- File system dependency
- Cleanup responsibility
- Potential race conditions in distributed setups

### 2. At-least-once delivery

SQS guarantees at-least-once delivery, meaning:

- Duplicate processing is possible
- Idempotency is required at database level

### 3. Infinite worker loop

Workers run continuously:

```php
while (true)
```

This is simple but:

- Harder to test directly
- Requires controlled shutdown strategy in production

### 4. Error handling scope

Current retry logic is local:

- No global dead-letter queue
- No centralized failure tracking

---
 
## Future Improvements
### 1. Dead-letter queue (DLQ)

Add support for:

- Failed message tracking
- Retry limits

### 2. Observability

Add:

- Structured logging
- Metrics (queue depth, processing time)
- Tracing per batch

### 3. Replace raw PDO usage

Introduce:

- Repository pattern
- Transaction management
- Better separation of persistence logic

---

## Summary

This project demonstrates a simplified but realistic distributed ETL pipeline using PHP. It focuses on:

- Scalability through workers
- Decoupling via queueing
- Fault tolerance via retries
- Clean separation of ingestion, processing, and persistence