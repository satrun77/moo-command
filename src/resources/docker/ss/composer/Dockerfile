FROM composer:latest

MAINTAINER Mohamed Alsharaf <mohamed.alsharaf@gmail.com>

RUN  echo "    IdentityFile /var/www/.ssh/id_rsa" >> /etc/ssh/ssh_config

USER root

WORKDIR /var/www/html/public

# Shrink image
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
