.PHONY: cleanup start-workers trigger-runner cleanup

setup:
	@docker compose -p processor up -d minio
	@docker compose -p processor up -d sqs
	@docker compose -p processor up -d mysql
	@sleep 5
	@docker compose -p processor up -d seed-assets
	@docker compose -p processor up -d seed-sqs
	@docker compose -p processor up -d migrate

start-workers:
	@docker compose -p processor up -d --build --scale worker=4 worker

trigger-runner:
	@docker compose -p processor up -d --build runner

cleanup:
	@docker compose -p processor down \
		--volumes \
		--remove-orphans \
		--rmi local