FROM node:6.3

RUN npm install -gD nodemon
RUN npm install -gD node-sass
RUN npm install -gD uglify-js
RUN npm install -gD parallelshell

ENV LANG en_US.UTF-8

RUN npm install

RUN npm rebuild node-sass
