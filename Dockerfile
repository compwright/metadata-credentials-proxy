FROM ubuntu AS test
RUN apt-get update && apt-get install -y curl

FROM php:8.2-alpine
RUN apk add --no-cache docker-cli
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER 1
WORKDIR /opt/docker-ec2-metadata
COPY . .
RUN composer install --no-scripts --no-autoloader --prefer-dist --quiet \
    && rm -rf .composer/cache
ENTRYPOINT ["/opt/docker-ec2-metadata/bin/docker-ec2-metadata", "-p", "80", "-vvv"]
