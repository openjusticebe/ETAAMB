#!/bin/bash
# Why ?
# https://pythonspeed.com/articles/system-packages-docker/

# Bash "strict mode", to help catch problems and bugs in the shell
# script. Every bash script you write should include this. See
# http://redsymbol.net/articles/unofficial-bash-strict-mode/ for
# details.
set -euo pipefail

# Tell apt-get we're never going to be able to give manual
# feedback:
export DEBIAN_FRONTEND=noninteractive


# Update the package listing, so we know what package exist:
apt-get update -qq

# Install security updates:
apt-get -y upgrade

# Install a new package, without unnecessary recommended packages:
apt-get -y install --no-install-recommends \
    build-essential \
    curl \
    default-mysql-client\
    libdatetime-perl \
    libclass-dbi-perl \
    libdbd-mariadb-perl \
    libtext-unaccent-perl \
    libparallel-forkmanager-perl \
    libhtml-strip-perl  \
    libhtml-format-perl \
    libwww-perl \
    php7.4 \
    php7.4-mysql \
    php7.4-curl \
    vim-tiny

# Delete cached files we don't need anymore:
apt-get clean
rm -rf /var/lib/apt/lists/*
