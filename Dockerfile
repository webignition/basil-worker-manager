FROM php:8-fpm-buster

WORKDIR /app

ARG APP_ENV=prod
ARG DATABASE_URL=postgresql://database_user:database_password@0.0.0.0:5432/database_name?serverVersion=12&charset=utf8
ARG MESSENGER_TRANSPORT_DSN=amqp://rabbitmq_user:rabbitmq_password@rabbitmq_host:5672/%2f/messages
ARG APP_MACHINE_NAME_PREFIX=dev
ARG APP_DIGITALOCEAN_ACCESS_TOKEN=digitalocean_access_token
ARG APP_DIGITALOCEAN_REGION=lon1
ARG APP_DIGITALOCEAN_SIZE=s-1vcpu-1gb
ARG APP_DIGITALOCEAN_IMAGE=ubuntu-16-04-x64
ARG APP_DIGITALOCEAN_TAG=worker
ARG APP_CREATE_RETRY_LIMIT=3
ARG APP_GET_RETRY_LIMIT=10
ARG APP_DELETE_RETRY_LIMIT=10
ARG APP_FIND_RETRY_LIMIT=3
ARG APP_MACHINE_IS_ACTIVE_DISPATCH_DELAY=10000

ENV APP_ENV=$APP_ENV
ENV DATABASE_URL=$DATABASE_URL
ENV MESSENGER_TRANSPORT_DSN=$MESSENGER_TRANSPORT_DSN
ENV MACHINE_NAME_PREFIX=$APP_MACHINE_NAME_PREFIX
ENV DIGITALOCEAN_ACCESS_TOKEN=$APP_DIGITALOCEAN_ACCESS_TOKEN
ENV DIGITALOCEAN_REGION=$APP_DIGITALOCEAN_REGION
ENV DIGITALOCEAN_SIZE=$APP_DIGITALOCEAN_SIZE
ENV DIGITALOCEAN_IMAGE=$APP_DIGITALOCEAN_IMAGE
ENV DIGITALOCEAN_TAG=$APP_DIGITALOCEAN_TAG
ENV CREATE_RETRY_LIMIT=$APP_CREATE_RETRY_LIMIT
ENV GET_RETRY_LIMIT=$APP_GET_RETRY_LIMIT
ENV DELETE_RETRY_LIMIT=$APP_DELETE_RETRY_LIMIT
ENV FIND_RETRY_LIMIT=$APP_FIND_RETRY_LIMIT
ENV MACHINE_IS_ACTIVE_DISPATCH_DELAY=$APP_MACHINE_IS_ACTIVE_DISPATCH_DELAY

ENV DOCKERIZE_VERSION="v2.1.0"

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/install-php-extensions
COPY composer.json composer.lock /app/
COPY bin/console /app/bin/console
COPY public/index.php public/
COPY src /app/src
COPY config/bundles.php config/services.yaml /app/config/
COPY config/packages/*.yaml /app/config/packages/
COPY config/packages/prod /app/config/packages/prod
COPY config/routes/annotations.yaml /app/config/routes/

RUN apt-get -qq update && apt-get -qq -y install  \
  librabbitmq-dev \
  libpq-dev \
  libzip-dev \
  supervisor \
  zip \
  && docker-php-ext-install \
  pdo_pgsql \
  zip \
  && install-php-extensions amqp \
  && rm /usr/bin/install-php-extensions \
  && docker-php-ext-enable amqp \
  && apt-get autoremove -y \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
  && composer check-platform-reqs --ansi \
  && composer install --no-dev --no-scripts \
  && rm composer.lock \
  && touch /app/.env \
  && curl -L --output dockerize.tar.gz \
     https://github.com/presslabs/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
  && tar -C /usr/local/bin -xzvf dockerize.tar.gz \
  && rm dockerize.tar.gz \
  && mkdir -p var/log/supervisor \
  && php bin/console cache:clear --env=prod

COPY build/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY build/supervisor/conf.d/app.conf /etc/supervisor/conf.d/supervisord.conf

CMD dockerize -wait tcp://rabbitmq:5672 -timeout 30s -wait tcp://postgres:5432 -timeout 30s supervisord -c /etc/supervisor/supervisord.conf