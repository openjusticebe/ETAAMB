# ----------------------------------------------------
# Base-image
# ----------------------------------------------------
FROM php:apache-bullseye as common-base

# Fix root
ENV APACHE_DOCUMENT_ROOT /app/etaamb
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Activate .htaccess support
RUN sed -i '/LoadModule rewrite_module/s/^#//g' /etc/apache2/apache2.conf && \
    sed -i 's#AllowOverride [Nn]one#AllowOverride All#' /etc/apache2/apache2.conf

# Etaamb uses Rewrite, Expires and Headers mods
RUN cp /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/ \
    cp /etc/apache2/mods-available/expires.load /etc/apache2/mods-enabled/ \
    cp /etc/apache2/mods-available/headers.load /etc/apache2/mods-enabled/

# Install needed PHP extensions
RUN docker-php-ext-install mysqli

WORKDIR /app

# COPY install-packages.sh .
# RUN ./install-packages.sh

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
#CMD ["bash"]

# ----------------------------------------------------
# Run Prod
# ----------------------------------------------------
FROM app-run AS prod
RUN rm etaamb/phpinfo.php
#CMD ["bash"]
