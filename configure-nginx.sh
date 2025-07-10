#!/bin/bash

echo "=== Nginx Configuration for PHP ==="
echo ""
echo "Current nginx configuration needs to be updated to process PHP files."
echo ""
echo "1. First, check if nginx configuration exists for port 1112:"
echo "   grep -r '1112' /etc/nginx/sites-available/ /etc/nginx/conf.d/"
echo ""
echo "2. Find the configuration file and add PHP processing:"
echo ""
echo "Example nginx server block for PHP:"
echo "----------------------------------------"
cat << 'EOF'
server {
    listen 1112;
    server_name 211.248.112.67;
    root /home/successbank/projects/docker/project1/html;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        # Note: Update the socket path based on your PHP version
        # Common paths:
        # - /var/run/php/php7.4-fpm.sock
        # - /var/run/php/php8.0-fpm.sock
        # - /var/run/php/php8.1-fpm.sock
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF
echo "----------------------------------------"
echo ""
echo "3. After updating, test nginx configuration:"
echo "   sudo nginx -t"
echo ""
echo "4. Reload nginx:"
echo "   sudo systemctl reload nginx"
echo ""
echo "5. Set proper permissions:"
echo "   sudo chown -R www-data:www-data /home/successbank/projects/docker/project1/html"
echo "   sudo chmod -R 755 /home/successbank/projects/docker/project1/html"