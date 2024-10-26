FROM tina4stack/php:8.1

#COPY ./docker/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/

# Install Redis with PECL and enable the extension
RUN pecl install redis
RUN docker-php-ext-enable redis

WORKDIR /var/www/html
EXPOSE 7777
CMD ["bash", "./docker/start-container.sh"]
#CMD ["sh", "-c", "while :; do echo 'Running'; sleep 3600; done"]
