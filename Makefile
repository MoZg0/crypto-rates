# ---- Settings ---------------------------------------------------------------
DC            ?= docker-compose
PHP_SVC       ?= php
ENV           ?= dev
LOGS_FOLLOW   ?= true
# ---------------------------------------------------------------------------

.DEFAULT_GOAL := help

# Internal helper: exec inside container (no TTY)
exec = $(DC) exec -T $(PHP_SVC)
# Internal helper: interactive shell
iexec = $(DC) exec $(PHP_SVC)

## help: Show help for available targets
help:
	@printf "\n\033[1mCrypto Rates — Make targets\033[0m\n\n"
	@awk 'BEGIN {FS=":.*##"; printf "\033[1m%-24s\033[0m %s\n\n","Target","Description"} \
		/^[a-zA-Z0-9_.-]+:.*##/ { printf "\033[36m%-24s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)

# ---- Docker ----------------------------------------------------------------

up: ## Start local containers (detached)
	@if [ ! -f vendor/autoload.php ]; then \
    	echo "[make up] vendor/ is missing, running composer install on host..."; \
        composer install --no-interaction --prefer-dist --no-scripts --no-ansi --no-progress --ignore-platform-reqs; \
	fi
	$(DC) up -d

build: ## Build images
	$(DC) build

stop: ## Stop containers (without removing)
	$(DC) stop

down: ## Stop and remove containers/networks (add V=1 to also remove volumes)
	@if [ "$(V)" = "1" ]; then $(DC) down -v; else $(DC) down; fi

ps: ## List containers
	$(DC) ps

logs: ## Tail PHP service logs (LOGS_FOLLOW=true for -f)
	@if [ "$(LOGS_FOLLOW)" = "true" ]; then $(DC) logs -f $(PHP_SVC); else $(DC) logs $(PHP_SVC); fi

sh bash shell enter: ## Enter container bash
	$(iexec) bash

# ---- Doctrine / Migrations --------------------------------------------------

migrate: ## Run Doctrine migrations for ENV (ENV=dev|test|prod)
	$(exec) bash -lc "APP_ENV=$(ENV) bin/console doctrine:migrations:migrate --no-interaction"

schema-validate: ## Validate Doctrine schema (skip sync)
	$(exec) bash -lc "APP_ENV=$(ENV) bin/console doctrine:schema:validate --skip-sync"

# ---- Tests & QA -------------------------------------------------------------

test-setup: ## Prepare test env (cache + migrations)
	$(exec) bash -lc "composer tests:setup"

test: ## Run tests
	$(test-setup)
	$(exec) bash -lc "composer tests:run"

analyze: ## Run static analysis (composer analyze:run)
	$(exec) bash -lc "composer analyze:run"

deptrac: ## Run Deptrac (both configs via composer script)
	$(exec) bash -lc "composer deptrac:run"

# ---- Shortcuts --------------------------------------------------------------

up-all: build up ## Build images then start containers

qa: analyze test ## Run analyze + tests

.PHONY: help up build stop down ps logs sh bash shell migrate schema-validate \
        test-setup test analyze deptrac up-all qa
