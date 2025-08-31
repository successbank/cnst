#!/bin/bash
# Force fix for project1 containers

echo "=== Force fixing project1 containers ==="
echo ""

cd /home/successbank/projects/docker/project1

echo "1. Force killing stuck containers..."
sudo docker kill project1_web project1_php project1_mysql project1_pgadmin 2>/dev/null

echo ""
echo "2. Force removing containers..."
sudo docker rm -f project1_web project1_php project1_mysql project1_pgadmin 2>/dev/null

echo ""
echo "3. Cleaning up any remaining containers..."
sudo docker ps -a | grep project1 | awk '{print $1}' | xargs -r sudo docker rm -f

echo ""
echo "4. Removing old network..."
sudo docker network rm project1_project1_default 2>/dev/null

echo ""
echo "5. Starting fresh containers..."
sudo docker-compose up -d

echo ""
echo "6. Waiting for containers to initialize..."
sleep 15

echo ""
echo "7. Fixing PHP-FPM listen configuration..."
sudo docker exec project1_php sed -i 's/listen = 127.0.0.1:9000/listen = 9000/' /usr/local/etc/php-fpm.d/www.conf
sudo docker exec project1_php kill -USR2 1

echo ""
echo "8. Waiting for PHP-FPM to reload..."
sleep 5

echo ""
echo "9. Checking container status..."
sudo docker ps | grep project1

echo ""
echo "10. Testing connections..."
echo "Testing PHP:"
sudo docker exec project1_php php -v | head -1

echo ""
echo "Testing MySQL:"
sudo docker exec project1_mysql mysql -uroot -prootpassword -e "SELECT 'MySQL is working' as status;" 2>/dev/null || echo "MySQL connection failed"

echo ""
echo "Testing web server:"
curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" http://localhost:1112/test.php

echo ""
echo "=== Force fix complete ==="
echo ""
echo "Please test: http://211.248.112.67:1112/"