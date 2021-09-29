# ----------------------------------------------------
# Base-image
# ----------------------------------------------------
FROM php:apache-bullseye as common-base
ENV APACHE_DOCUMENT_ROOT /app/etaamb

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

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
