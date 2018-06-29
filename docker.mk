include .env

.PHONY: up down stop prune ps shell drush logs

default: up

DRUPAL_ROOT ?= /var/www/html/web

up:
	@echo "Starting up containers for $(PROJECT_NAME)..."
	docker-compose pull
	docker-compose up -d --remove-orphans

down: stop

status: ps
start: up

clean: rm-clean

nuke:
	rm-containers
	rm-vendor-dir

stop:
	@echo "Stopping containers for $(PROJECT_NAME)..."
	@docker-compose stop

prune:
	@echo "Removing containers for $(PROJECT_NAME)..."
	@docker-compose down -v

ps:
	@docker ps --filter name='$(PROJECT_NAME)*'

shell:
	docker exec -ti -e COLUMNS=$(shell tput cols) -e LINES=$(shell tput lines) $(shell docker ps --filter name='$(PROJECT_NAME)_php' --format "{{ .ID }}") sh

drush:
	docker exec $(shell docker ps --filter name='$(PROJECT_NAME)_php' --format "{{ .ID }}") drush -r $(DRUPAL_ROOT) $(filter-out $@,$(MAKECMDGOALS))

logs:
	@docker-compose logs -f $(filter-out $@,$(MAKECMDGOALS))


rm-vendor-dir:
	@echo "Removing vendor directory for $(PROJECT_NAME)..."
	rm -rf vendor

rm-clean:
	@echo "Removing vendor directory, docroot/core and stuff for $(PROJECT_NAME)..."
	rm -rf vendor composer.lock docroot/core docroot/modules/contrib docroot/profiles/contrib docroot/themes/contrib

# https://stackoverflow.com/a/6273809/1826109
%:
	@: