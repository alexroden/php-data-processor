.PHONY:  cleanup

setup:
	@docker compose -p processor up -d minio
	@docker compose -p processor up -d sqs
	@docker compose -p processor up -d mysql
	@sleep 5
	@docker compose -p processor up -d seed-assets
	@docker compose -p processor up -d seed-sqs
	@docker compose -p processor up -d migrate

cleanup:
	@docker compose -p processor down -v --rmi all