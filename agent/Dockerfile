FROM debian:bullseye-slim AS common-base
# Etaamb Steward : tools and utils to manage and update the application

WORKDIR /app

COPY install-packages.sh .
RUN ./install-packages.sh

ENTRYPOINT ["tail"]
CMD ["-f","/dev/null"]
