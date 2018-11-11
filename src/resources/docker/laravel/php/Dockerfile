FROM php:{{php}}-fpm

MAINTAINER Mohamed Alsharaf <mohamed.alsharaf@gmail.com>

# Install needed libraries
RUN apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*
RUN apt-get update
RUN apt-get install -y libmcrypt-dev
RUN apt-get install -y libtidy-dev
RUN apt-get install -y libpng-dev
RUN apt-get install -y libjpeg62-turbo-dev
RUN apt-get install -y libfreetype6-dev
RUN apt-get install -y git-core
RUN apt-get install -y zlib1g-dev libicu-dev g++

# PHP extensions
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install mysqli
RUN docker-php-ext-configure intl
RUN docker-php-ext-install intl
RUN docker-php-ext-install tidy
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install zip
RUN docker-php-ext-install -j$(nproc) iconv
#RUN docker-php-ext-install -j$(nproc) mcrypt
RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/
RUN docker-php-ext-install -j$(nproc) gd

# PHP configs
COPY templates/php.ini /usr/local/etc/php/php.ini
COPY templates/php-fpm.conf /usr/local/etc/

# Tell PHP to log emails
COPY templates/sendmail /var/www/sendmail
RUN echo 'sendmail_path = /usr/local/bin/php /var/www/sendmail' > /usr/local/etc/php/conf.d/mailcatcher.ini
RUN chmod +x /var/www/sendmail
RUN if [ ! -d /var/www/mail ]; then mkdir /var/www/mail; fi

# To grant permissions to www-data
RUN usermod -u 1000 www-data

# Copy composer from composer container
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Shrink image
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

WORKDIR /var/www/html
