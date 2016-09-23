FROM httpd:2.4

MAINTAINER Mohamed Alsharaf <mohamed.alsharaf@gmail.com>

COPY ./httpd.conf /usr/local/apache2/conf/httpd.conf

RUN mkdir -p /var/www/html/public

ENV LANG en_US.UTF-8

WORKDIR /var/www/html/public
