FROM php:8-cli-buster

WORKDIR /app

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
  && rm composer.lock
