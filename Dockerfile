FROM composer:2.7.2 AS composer
FROM php:8.3.6-cli-alpine3.19

# Copy `composer` binary to main image.
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Install OpenTelemetry and Protobuf extensions.
RUN apk update \
    && apk add --virtual .build_deps $PHPIZE_DEPS \
    && pecl install opentelemetry-1.0.3 \
    && pecl install protobuf-4.26.1 \
    && docker-php-ext-enable opentelemetry protobuf \
    && apk del --no-network .build_deps \
    && rm -rf /tmp/pear ~/.pearrc

# Copy application source code to main image.
COPY --chown=www-data:www-data . /app

# Define OpenTelemetry environment variables.
ENV OTEL_PHP_AUTOLOAD_ENABLED=true
ENV OTEL_EXPORTER_OTLP_ENDPOINT=http://collector:4318
ENV OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf
ENV OTEL_PROPAGATORS=baggage,tracecontext
ENV OTEL_TRACES_EXPORTER=otlp
ENV OTEL_METRICS_EXPORTER=none
ENV OTEL_LOGS_EXPORTER=none

# Install Composer packages and run the application.
USER www-data
WORKDIR /app
RUN composer install --no-dev
CMD ["-f", "index.php"]
