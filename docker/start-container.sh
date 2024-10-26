#!/bin/bash

echo ""
echo "STEP 1: Setting up .env"\
echo ""
echo "===================="
printenv > /var/www/html/.env

echo API_KEY=12345 >> /var/www/html/.env
echo TINA4_VERSION=1.0.0 >> /var/www/html/.env
echo TINA4_DEBUG_LEVEL=[TINA4_LOG_ALL] >> /var/www/html/.env
echo TINA4_DEBUG=true >> /var/www/html/.env
echo TINA4_CACHE_ON=false >> /var/www/html/.env
echo SWAGGER_TITLE='Tina4 Project' >> /var/www/html/.env
echo SWAGGER_DESCRIPTION='Edit your .env file to change this description' >> /var/www/html/.env
echo SWAGGER_VERSION=1.0.0 >> /var/www/html/.env


echo "Starting the container"
echo "===================="
echo ""
echo "STEP 2: Doing composer install"
echo ""
composer install
echo "===================="

#echo ""
#echo "STEP 3: Starting the services"
#echo ""
#composer start-service &
#
#sleep 3

echo "===================="
echo ""
echo "STEP 3: Starting the server"
echo ""
composer start