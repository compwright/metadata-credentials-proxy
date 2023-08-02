.DEFAULT_GOAL := build

lint:
	vendor/bin/phpstan analyse -c phpstan.neon
	vendor/bin/php-cs-fixer fix

build: lint
	docker build --no-cache .

publish: lint
	docker build --no-cache --push --tag compwright/docker-ec2-metadata:latest .
