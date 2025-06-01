

.PHONY: shell
shell:
	docker-compose exec -u $$(id -u):$$(id -g) app bash

.PHONY: build
build:
	docker-compose build --build-arg installXdebug=0

.PHONY: build-with-xdebug
build-with-xdebug:
	docker-compose build --build-arg installXdebug=1

.PHONY: build-verbose
build-verbose:
	docker-compose build --progress=plain --build-arg installXdebug=0

.PHONY: upd
upd:
	docker-compose up -d

.PHONY: stop
stop:
	docker-compose stop

.PHONY: restartd
restartd:
	$(MAKE) stop
	$(MAKE) upd

.PHONY: logs
logs:
	docker-compose logs

.PHONY: logs-follow
logs-follow:
	docker-compose logs -f --tail=50

.PHONY: clear-tmp-database
clear-tmp-database:
	docker-compose exec app sh -c 'rm /var/www/html/var/tmp/*.sqlite'
