.DEFAULT_GOAL := help
.PHONY: help install build serve bench test stan cs cs-fix check \
        docker-build up dev logs smoke down clean

COMPOSE ?= docker compose
CS_ENV  := PHP_CS_FIXER_IGNORE_ENV=1

help: ## List available targets
	@grep -hE '^[a-zA-Z0-9_-]+:.*?## ' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

## --- Local (PHP) ---------------------------------------------------------

install: ## Install Composer dependencies
	composer install --no-interaction

build: ## Compile the app into var/compiled/
	php bin/infra build

serve: ## Run the PHP built-in dev server (per-request boot)
	php bin/infra serve

bench: build ## Benchmark the compiled kernel in-process
	php bench/bench.php

## --- Quality -------------------------------------------------------------

test: ## Run the test suite
	vendor/bin/phpunit

stan: ## Run static analysis
	vendor/bin/phpstan analyse --no-progress

cs: ## Check coding standards
	$(CS_ENV) vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Fix coding standards
	$(CS_ENV) vendor/bin/php-cs-fixer fix

check: cs stan test ## Run the full quality pipeline

## --- Docker / FrankenPHP worker -----------------------------------------

docker-build: ## Build the production Docker image
	$(COMPOSE) build

up: ## Start the FrankenPHP worker on :8080 (detached)
	$(COMPOSE) up --build -d app
	@echo "Worker running at http://127.0.0.1:8080"

dev: ## Start the dev worker (source mounted) on :8081
	$(COMPOSE) --profile dev up --build dev

logs: ## Tail worker logs
	$(COMPOSE) logs -f app

smoke: ## Probe the running worker endpoints
	@curl -fsS http://127.0.0.1:8080/ && echo
	@curl -fsS http://127.0.0.1:8080/hello/World && echo
	@curl -fsS http://127.0.0.1:8080/add/20/22 && echo
	@printf '404 check: '; curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8080/nope

down: ## Stop and remove containers
	$(COMPOSE) down

clean: ## Remove compiled artifacts and caches
	rm -rf var/compiled var/phpstan .php-cs-fixer.cache .phpunit.cache
