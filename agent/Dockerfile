FROM debian:bullseye-slim AS common-base
# Etaamb Steward : tools and utils to manage and update the application

WORKDIR /agent
ENV PATH="/agent:$PATH"

COPY ./agent/install-packages.sh .
RUN  ./install-packages.sh

COPY ./resources /resources
COPY ./etaamb /etaamb
COPY ./agent ./


ENTRYPOINT ["tail"]
CMD ["-f","/dev/null"]
