#!/bin/bash
# Restore project1 to working state

echo "=== Restoring project1 to original working state ==="
echo ""

cd /home/successbank/projects/docker/project1

echo "1. Stopping all project1 containers..."
docker stop project1_web project1_php project1_mysql project1_pgadmin 2>/dev/null

echo ""
echo "2. Starting containers with docker-compose..."
docker-compose up -d

echo ""
echo "3. Waiting for containers to be ready..."
sleep 10

echo ""
echo "4. Checking container status..."
docker ps | grep project1

echo ""
echo "5. Testing database connection from host..."
mysql -h127.0.0.1 -P3306 -uroot -prootpassword -e "SELECT 'Database accessible from host' as status;" 2>/dev/null && echo "✓ Host DB connection OK" || echo "✗ Host DB connection failed"

echo ""
echo "6. Testing web server..."
curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" http://localhost:1112/test.php

echo ""
echo "7. Checking logs for errors..."
echo "Recent nginx errors:"
docker logs project1_web 2>&1 | tail -5 | grep -i error || echo "No recent errors"

echo ""
echo "=== Restoration complete ==="
echo ""
echo "If still having issues, run with sudo:"
echo "sudo docker-compose down && sudo docker-compose up -d"