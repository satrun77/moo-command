# Solr 4.x required for silverstripe
FROM makuk66/docker-solr:4.10.4

MAINTAINER Mohamed Alsharaf <mohamed.alsharaf@gmail.com>

# Change as needed to set max and min memory
ENV SOLR_HEAP=512m

# To be able to access index folder
USER root

RUN if [ ! -d /var/www/solr ]; then mkdir -p /var/www/solr; fi
RUN chown -R solr /var/www/solr/

# copy in custom config with different cores folder, delete example folder
COPY solr.xml /opt/solr/example/solr/solr.xml
RUN rm -rf  /opt/solr/example/solr/collection1

# This would be needed, if using later version of solr
#USER $SOLR_USER
