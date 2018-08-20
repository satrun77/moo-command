FROM mariadb:10.1

MAINTAINER Mohamed Alsharaf <mohamed.alsharaf@gmail.com>

# see https://github.com/dockerfile/mariadb/issues/3
RUN echo -e "\nexport TERM=xterm" >> ~/.bashrc

# use custom my.cnf to set UTF-8 as a default
COPY my.cnf /etc/mysql/conf.d/my.cnf
