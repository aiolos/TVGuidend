# Dockerfile for tvguidend
FROM phpearth/php:7.2-nginx

RUN apk add --no-cache composer
RUN apk add --no-cache nodejs
