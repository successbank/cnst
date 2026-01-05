#!/bin/bash

echo "=== Nginx PHP Configuration Setup ==="
echo ""
echo "Run these commands to enable PHP support:"
echo ""

# Copy configuration
echo "sudo cp /home/successbank/projects/docker/project1/html/nginx-php-config.conf /etc/nginx/sites-available/project1-php"

# Enable site
echo "sudo ln -s /etc/nginx/sites-available/project1-php /etc/nginx/sites-enabled/"

# Check for default site
echo "sudo ls -la /etc/nginx/sites-enabled/"

# Test configuration
echo "sudo nginx -t"

# Reload nginx
echo "sudo systemctl reload nginx"

# Set permissions
echo "sudo chown -R www-data:www-data /home/successbank/projects/docker/project1/html"
echo "sudo chmod -R 755 /home/successbank/projects/docker/project1/html"

echo ""
echo "After running these commands, test PHP at:"
echo "http://211.248.112.67:1112/test.php"
echo "http://211.248.112.67:1112/phpinfo.php"