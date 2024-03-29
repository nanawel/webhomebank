
.PHONY: shell
shell:
	docker-compose exec -u $$(id -u):$$(id -g) app bash

.PHONY: build
build:
	docker-compose build

.PHONY: upd
upd:
	docker-compose up -d

.PHONY: stop
stop:
	docker-compose stop

.PHONY: logs
logs:
	docker-compose logs

.PHONY: clear-tmp-database
clear-tmp-database:
	docker-compose exec app sh -c 'rm /var/www/html/var/tmp/*.sqlite'
