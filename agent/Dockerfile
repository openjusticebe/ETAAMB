FROM debian:bullseye-slim AS common-base
# Etaamb Steward : tools and utils to manage and update the application

WORKDIR /agent
ENV PATH="/agent:/agent/misc:/agent/moniteur_import:$PATH"

COPY ./agent/install-packages.sh .
RUN  ./install-packages.sh

RUN sed -e 's/max_execution_time = 30/max_execution_time = 3600/' -i /etc/php/7.4/cli/php.ini

COPY ./resources /resources
COPY ./etaamb /etaamb
COPY ./agent ./



# ----------------------------------------------------
# Run Dev
# ----------------------------------------------------
FROM common-base AS dev
ENTRYPOINT ["tail"]
CMD ["-f","/dev/null"]

# ----------------------------------------------------
# Run Prod
# ----------------------------------------------------
FROM common-base AS prod
COPY ./etaamb/config.docker.php /etaamb/config.php
ENTRYPOINT ["tail"]
CMD ["-f","/dev/null"]
