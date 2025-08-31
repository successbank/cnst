#!/bin/bash
# Fix PHP-FPM configuration and restart container

echo "Fixing PHP-FPM configuration..."

# Update PHP-FPM to listen on all interfaces
docker exec project1_php sed -i 's/listen = 127.0.0.1:9000/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf
docker exec project1_php sed -i 's/listen = 127.0.0.1:9000/listen = 9000/' /usr/local/etc/php-fpm.d/www.conf

# Increase PHP-FPM process limits
docker exec project1_php sed -i 's/pm.max_children = 5/pm.max_children = 50/' /usr/local/etc/php-fpm.d/www.conf
docker exec project1_php sed -i 's/pm.start_servers = 2/pm.start_servers = 5/' /usr/local/etc/php-fpm.d/www.conf
docker exec project1_php sed -i 's/pm.min_spare_servers = 1/pm.min_spare_servers = 5/' /usr/local/etc/php-fpm.d/www.conf
docker exec project1_php sed -i 's/pm.max_spare_servers = 3/pm.max_spare_servers = 35/' /usr/local/etc/php-fpm.d/www.conf

echo "Restarting PHP container..."
docker restart project1_php

echo "Waiting for PHP-FPM to start..."
sleep 5

echo "Checking PHP-FPM status..."
docker exec project1_php ps aux | grep php-fpm

echo "Testing PHP-FPM connectivity..."
docker exec project1_web nc -zv php 9000

echo "Done! Please test the website now."