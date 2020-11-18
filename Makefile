#!make
include .env
include .env.local
export $(shell sed 's/=.*//' .env)
export $(shell sed 's/=.*//' .env.local)

# Provides a bash in PHP container (user www-data)
bash-php: up
	docker-compose exec -u www-data php bash

# Provides a bash in PHP container (user root)
bash-php-root: up
	docker-compose exec php bash

composer-install: up
	# Install PHP dependencies
	docker-compose exec -u www-data php composer install

cache-clear: up
	docker-compose exec -u www-data php php bin/console cac:c

# Up containers
up:
	docker-compose up -d
	docker-compose exec php usermod -u ${HOST_UID} www-data
#	docker-compose exec apache usermod -u ${HOST_UID} www-data

# Up containers, with build forced
build:
	docker-compose up -d --build
	docker-compose exec php usermod -u ${HOST_UID} www-data
#	docker-compose exec apache usermod -u ${HOST_UID} www-data
# Down containers
down:
	docker-compose down
