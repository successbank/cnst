#!/bin/bash

echo "=== PHP Installation Check Script ==="
echo ""

# Check if PHP is installed
echo "1. Checking PHP installation..."
if command -v php &> /dev/null; then
    echo "✓ PHP is installed"
    php -v
else
    echo "✗ PHP is not installed"
    echo "  Install PHP with: sudo apt-get install php php-fpm php-mysql"
fi
echo ""

# Check PHP-FPM service
echo "2. Checking PHP-FPM service..."
if systemctl is-active --quiet php*-fpm; then
    echo "✓ PHP-FPM is running"
    systemctl status php*-fpm --no-pager | grep "Active:"
else
    echo "✗ PHP-FPM is not running"
    echo "  Start with: sudo systemctl start php8.0-fpm"
fi
echo ""

# Check nginx service
echo "3. Checking nginx service..."
if systemctl is-active --quiet nginx; then
    echo "✓ nginx is running"
else
    echo "✗ nginx is not running"
    echo "  Start with: sudo systemctl start nginx"
fi
echo ""

# Check PHP-FPM socket
echo "4. Checking PHP-FPM socket..."
if [ -S /var/run/php/php*-fpm.sock ]; then
    echo "✓ PHP-FPM socket found:"
    ls -la /var/run/php/php*-fpm.sock
else
    echo "✗ PHP-FPM socket not found"
    echo "  Check PHP-FPM configuration"
fi
echo ""

# Check nginx configuration
echo "5. Checking nginx configuration..."
if nginx -t 2>/dev/null; then
    echo "✓ nginx configuration is valid"
else
    echo "✗ nginx configuration has errors"
    echo "  Test with: sudo nginx -t"
fi
echo ""

# Provide configuration instructions
echo "=== Configuration Instructions ==="
echo ""
echo "To enable PHP in nginx:"
echo "1. Copy nginx.conf to nginx sites-available:"
echo "   sudo cp nginx.conf /etc/nginx/sites-available/project1"
echo ""
echo "2. Enable the site:"
echo "   sudo ln -s /etc/nginx/sites-available/project1 /etc/nginx/sites-enabled/"
echo ""
echo "3. Test nginx configuration:"
echo "   sudo nginx -t"
echo ""
echo "4. Reload nginx:"
echo "   sudo systemctl reload nginx"
echo ""
echo "5. Set proper permissions:"
echo "   sudo chown -R www-data:www-data /home/successbank/projects/docker/project1/html"
echo "   sudo chmod -R 755 /home/successbank/projects/docker/project1/html"
echo ""
echo "6. Test PHP:"
echo "   curl http://211.248.112.67:1112/phpinfo.php"