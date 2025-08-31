#!/bin/bash
# Script to restart nginx container after configuration changes

echo "Restarting project1_web nginx container..."
docker restart project1_web

if [ $? -eq 0 ]; then
    echo "✓ Nginx container restarted successfully"
    echo "Checking container status..."
    docker ps | grep project1_web
else
    echo "✗ Failed to restart nginx container"
    echo "Trying with docker-compose..."
    cd /home/successbank/projects/docker/project1
    docker-compose restart web
fi

echo ""
echo "Testing website availability..."
curl -I http://localhost:1112 2>/dev/null | head -n 1