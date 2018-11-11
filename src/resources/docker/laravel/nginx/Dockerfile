FROM nginx:latest

MAINTAINER Mohamed Alsharaf <mohamed.alsharaf@gmail.com>

COPY ./default.conf /etc/nginx/conf.d/default.conf

ENV LANG en_US.UTF-8

WORKDIR /var/www/html

# Shrink image
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
