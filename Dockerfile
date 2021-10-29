# ----------------------------------------------------
# Base-image
# ----------------------------------------------------
FROM php:apache-bullseye as common-base
ARG ADMIN_MAIL root@localhost.be
ARG SMTP_HOST localhost
ARG SMTP_PORT 25
ARG SMTP_USER none
ARG SMTP_PASSWORD none

# Fix root

ENV APACHE_DOCUMENT_ROOT /app/etaamb
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Activate .htaccess support
RUN sed -i '/LoadModule rewrite_module/s/^#//g' /etc/apache2/apache2.conf && \
    sed -i 's#AllowOverride [Nn]one#AllowOverride All#' /etc/apache2/apache2.conf

# Etaamb uses Rewrite, Expires and Headers mods
RUN cp /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/ && \
    cp /etc/apache2/mods-available/expires.load /etc/apache2/mods-enabled/ && \
    cp /etc/apache2/mods-available/headers.load /etc/apache2/mods-enabled/

# Install needed PHP extensions
RUN docker-php-ext-install mysqli

WORKDIR /app

COPY install-packages.sh .
RUN ./install-packages.sh

# Activate and configure Mail

# Mail config
RUN echo "mailhub=${SMTP_HOST}:${SMTP_PORT}" >> /etc/ssmtp/ssmtp.conf && \
    echo "AuthUser=${SMTP_USER}" >> /etc/ssmtp/ssmtp.conf && \
    echo "AuthPass=${SMTP_PASSWORD}" >> /etc/ssmtp/ssmtp.conf && \
    echo "UseTLS=YES" >> /etc/ssmtp/ssmtp.conf && \
    echo "UseSTARTTLS=YES" >> /etc/ssmtp/ssmtp.conf && \
    echo "root=${ADMIN_MAIL}" >> /etc/ssmtp/ssmtp.conf && \
    echo "FromLineOverride=YES" >> /etc/ssmtp/ssmtp.conf

RUN echo "sendmail_path=/usr/sbin/sendmail -t -i" >> /usr/local/etc/php/conf.d/sendmail.ini
RUN sed -i '/#!\/bin\/sh/aservice sendmail restart' /usr/local/bin/docker-php-entrypoint
RUN sed -i '/#!\/bin\/sh/aecho "$(hostname -i)\t$(hostname) $(hostname).localhost" >> /etc/hosts' /usr/local/bin/docker-php-entrypoint

# ----------------------------------------------------
# Build project
# ----------------------------------------------------
FROM common-base AS app-run
COPY ./etaamb ./etaamb
COPY ./resources ./resources

# ----------------------------------------------------
# Run Dev
# ----------------------------------------------------
FROM app-run AS dev
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN sed -i 's/^SMTP = .*/SMTP=/' $PHP_INI_DIR/php.ini && \
    sed -i 's/^smtp_port = .*/smtp_port=/' $PHP_INI_DIR/php.ini && \
    sed -i 's/^SMTP = .*/SMTP=/' $PHP_INI_DIR/php.ini
#CMD ["bash"]

# ----------------------------------------------------
# Run Prod
# ----------------------------------------------------
FROM app-run AS prod
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN docker-php-ext-install opcache && \
    docker-php-ext-enable opcache
COPY ./etaamb/config.docker.php ./etaamb/config.php
