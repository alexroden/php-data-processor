.PHONY: cleanup start-worker trigger-runner cleanup

setup:
	@docker compose -p processor up -d minio
	@docker compose -p processor up -d sqs
	@docker compose -p processor up -d mysql
	@sleep 5
	@docker compose -p processor up -d seed-assets
	@docker compose -p processor up -d seed-sqs
	@docker compose -p processor up -d migrate

start-worker:
	@docker compose -p processor up -d worker --build

trigger-runner:
	@docker compose -p processor up -d runner --build

cleanup:
	@docker compose -p processor down -v --rmi all