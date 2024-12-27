FROM python:3.11-slim-bookworm AS common-base

ENV ENV=${ENV} \
  DB_HOST=${DB_HOST} \
  DB_PORT=${DB_PORT} \
  DB_USER=${DB_USER} \
  DB_PASSWORD=${DB_PASSWORD} \
  DB_DATA=${DB_DATA} \
  PYTHONFAULTHANDLER=1 \
  PYTHONUNBUFFERED=1 \
  PYTHONHASHSEED=random \
  PIP_NO_CACHE_DIR=off \
  PIP_DISABLE_PIP_VERSION_CHECK=on \
  PIP_DEFAULT_TIMEOUT=100 \
  POETRY_VERSION=1.8.4

#COPY install-packages.sh .
#RUN ./install-packages.sh

RUN pip install "poetry==$POETRY_VERSION"

WORKDIR /app
COPY ./py_agent /app
COPY ./py_agent/poetry.lock ./py_agent/pyproject.toml /app/
RUN poetry config virtualenvs.create false && \
    poetry install $(test $ENV == "prod" && echo "--no-dev") --no-interaction --no-ansi

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
ENTRYPOINT ["tail"]
CMD ["-f","/dev/null"]
