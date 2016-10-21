FROM composer/composer:php5

MAINTAINER Mohamed Alsharaf <mohamed.alsharaf@gmail.com>

RUN  echo "    IdentityFile /var/www/.ssh/id_rsa" >> /etc/ssh/ssh_config

USER root

ENV COMPOSER_HOME=/var/www/composer

WORKDIR /var/www/html
