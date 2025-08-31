#!/bin/bash
# Full restart script for project1 Docker containers

echo "=== Fixing PHP-FPM and restarting all containers ==="

cd /home/successbank/projects/docker/project1

echo "1. Stopping all containers..."
docker-compose down

echo "2. Starting containers..."
docker-compose up -d

echo "3. Waiting for containers to start..."
sleep 10

echo "4. Fixing PHP-FPM configuration..."
docker exec project1_php sed -i 's/listen = 127.0.0.1:9000/listen = 9000/' /usr/local/etc/php-fpm.d/www.conf
docker exec project1_php sed -i 's/listen = 0.0.0.0:9000/listen = 9000/' /usr/local/etc/php-fpm.d/www.conf

echo "5. Restarting PHP-FPM..."
docker exec project1_php kill -USR2 1

echo "6. Waiting for PHP-FPM to reload..."
sleep 5

echo "7. Checking container status..."
docker ps | grep project1

echo "8. Testing PHP-FPM..."
docker exec project1_php netstat -tlnp | grep 9000 || echo "netstat not available"
docker exec project1_php ps aux | grep php-fpm

echo "9. Testing website..."
curl -I http://localhost:1112 2>&1 | head -5

echo ""
echo "Script completed. Please check if the website is accessible."