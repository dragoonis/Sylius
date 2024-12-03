FROM ghcr.io/sylius/sylius-php:8.2-fixuid-xdebug-alpine

USER root
RUN apk update && apk add nano make
RUN apk add nodejs npm yarn

COPY --chown=1000:1000 .docker/test/php.ini /usr/local/etc/php/php-cli.ini

USER sylius

