FROM php:5.6-fpm

MAINTAINER Mohamed Alsharaf <mohamed.alsharaf@gmail.com>

# Install needed libraries
RUN apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*
RUN apt-get update
RUN apt-get install -y libmcrypt-dev
RUN apt-get install -y libtidy-dev
RUN apt-get install -y libpng12-dev
RUN apt-get install -y libjpeg62-turbo-dev
RUN apt-get install -y libfreetype6-dev
RUN apt-get install -y git-core

# PHP extensions
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install tidy
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install zip
RUN docker-php-ext-install -j$(nproc) iconv
RUN docker-php-ext-install -j$(nproc) mcrypt
RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/
RUN docker-php-ext-install -j$(nproc) gd

# PHP configs
RUN echo "date.timezone = Pacific/Auckland" > /usr/local/etc/php/conf.d/timezone.ini
RUN echo "display_startup_errors=on\nerror_reporting=ALL\ndisplay_errors =on" > /usr/local/etc/php/conf.d/error_reporting.ini
RUN echo "upload_max_filesize = 5M\n" > /usr/local/etc/php/conf.d/max-upload.ini
RUN echo "memory_limit = 1G\n" > /usr/local/etc/php/conf.d/memory-limit.ini

# Tell PHP to log emails
COPY templates/sendmail /var/www/sendmail
RUN echo 'sendmail_path = /usr/local/bin/php /var/www/sendmail' > /usr/local/etc/php/conf.d/mailcatcher.ini
RUN chmod +x /var/www/sendmail
RUN mkdir /var/www/mail

# To grant permissions to www-data
RUN usermod -u 1000 www-data

WORKDIR /var/www/html
