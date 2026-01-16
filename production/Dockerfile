# Versions
# https://hub.docker.com/r/serversideup/php/tags?name=8.4-fpm-nginx-alpine
# **CORRECTION/UPDATE:** The ARG should be set before the first FROM command
ARG SERVERSIDEUP_PHP_VERSION=8.3-fpm-nginx-alpine

# Add user/group
ARG USER_ID=9999
ARG GROUP_ID=9999
# **NOTE:** POSTGRES_VERSION is not defined, assuming it's an environment variable
# provided during the build or relies on a default in the base image.
# If not defined, the `apk add` will likely fail. You should define it here.
# ARG POSTGRES_VERSION=16 # Example

# =================================================================
# Stage 1: Composer dependencies (builder)
# =================================================================
FROM serversideup/php:${SERVERSIDEUP_PHP_VERSION} AS base

USER root

ARG USER_ID
ARG GROUP_ID

# **NOTE:** Assuming docker-php-serversideup-set-id is available in the base image.
RUN docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID && \
    docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID --service nginx

WORKDIR /var/www/html
COPY --chown=www-data:www-data composer.json composer.lock ./
# Ensure we use the correct user for running composer
USER www-data
RUN --mount=type=cache,target=/tmp/cache \
    COMPOSER_CACHE_DIR=/tmp/cache composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --optimize-autoloader \
    --no-progress \
    --ignore-platform-reqs \
    # Nettoyer le cache composer
    && rm -rf /var/www/.composer/cache
# **IMPROVEMENT:** Switching to www-data for composer install is safer.
# We will switch back to root in the next stage for system package installation.

# =================================================================
# Final Stage: Production image
# =================================================================
FROM serversideup/php:${SERVERSIDEUP_PHP_VERSION}

ARG USER_ID
ARG GROUP_ID
ARG TARGETPLATFORM

WORKDIR /var/www/html

USER root

# Installer extensions PHP requises
RUN install-php-extensions \
    pdo_mysql \
    mysqli \
    mbstring \
    xml \
    zip \
    bcmath \
    gd \
    redis \
    opcache \
    pcntl \
    sockets

RUN docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID && \
    docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID --service nginx

# Install system dependencies
RUN --mount=type=cache,target=/var/cache/apk \
    apk upgrade && \
    apk add --no-cache \
    postgresql${POSTGRES_VERSION}-client \
    openssh-client \
    git \
    git-lfs \
    jq \
    lsof \
    lsof \
    vim \
    nodejs \
    npm

# Configure shell aliases
# **IMPROVEMENT:** Using >> /etc/profile.d/aliases.sh is cleaner for Alpine/Linux setups
RUN echo "alias ll='ls -al'" > /etc/profile.d/aliases.sh && \
    echo "alias a='php artisan'" >> /etc/profile.d/aliases.sh && \
    echo "alias logs='tail -f storage/logs/laravel.log'" >> /etc/profile.d/aliases.sh && \
    chmod +x /etc/profile.d/aliases.sh

    # Configure PHP
COPY production/etc/php/conf.d/zzz-custom-php.ini /usr/local/etc/php/conf.d/zzz-custom-php.ini
ENV PHP_OPCACHE_ENABLE=1

# Configure PHP-FPM to retain environment variables (must be done as root)
RUN echo "[www]" > /usr/local/etc/php-fpm.d/zz-force-env.conf && \
    echo "clear_env = no" >> /usr/local/etc/php-fpm.d/zz-force-env.conf

# Configure entrypoint
COPY --chmod=755 production/entrypoint.d/ /etc/entrypoint.d

# Copy Composer dependencies from Stage 1
COPY --from=base --chown=www-data:www-data /var/www/html/vendor ./vendor

# Copy application source code
COPY --chown=www-data:www-data composer.json composer.lock ./
COPY --chown=www-data:www-data app ./app
COPY --chown=www-data:www-data bootstrap ./bootstrap
COPY --chown=www-data:www-data config ./config
COPY --chown=www-data:www-data database ./database
COPY --chown=www-data:www-data public ./public
COPY --chown=www-data:www-data routes ./routes
COPY --chown=www-data:www-data storage ./storage
COPY --chown=www-data:www-data resources/ ./resources/
COPY --chown=www-data:www-data artisan artisan


# ... (Après la ligne 'COPY --chown=www-data:www-data artisan artisan')

# =================================================================
# Correction: Nettoyage du cache de build avant le démarrage (Nouveau)
# =================================================================
# Si le build a laissé des fichiers de cache de l'étape Composer (rare, mais pour sécurité)
# ou si l'image de base a des optimisations. 
# On supprime TOUS les caches de configuration/routes/vues/composer.
USER root
RUN rm -f bootstrap/cache/*.php

# Pour les vues, on s'assure qu'elles ne sont pas compilées à l'avance.
# Laravel utilise storage/framework/views pour les vues compilées.
RUN rm -rf storage/framework/views/*

# Réinitialiser les permissions après le nettoyage
RUN docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID --service nginx

# =================================================================
# Suite de l'entrée : Configuration Nginx et S6
# =================================================================

# Configure Nginx and S6 overlay
COPY production/etc/nginx/conf.d/custom.conf /etc/nginx/conf.d/custom.conf
COPY production/etc/nginx/site-opts.d/http.conf /etc/nginx/site-opts.d/http.conf
COPY --chmod=755 production/etc/s6-overlay/ /etc/s6-overlay/

RUN mkdir -p /etc/nginx/conf.d && \
    chown -R www-data:www-data /etc/nginx && \
    chmod -R 755 /etc/nginx

# Switch to non-root user
USER www-data
