# PHP Setup Manual for Port 1112

## Current Issue
PHP files are being downloaded instead of executed when accessing:
- http://211.248.112.67:1112/about.php
- http://211.248.112.67:1112/services.php
- etc.

## Solution Steps

### 1. Install PHP-FPM
```bash
sudo apt update
sudo apt install -y php-fpm php-mysql php-common php-cli php-curl php-mbstring php-xml
```

### 2. Check PHP Installation
```bash
php -v
systemctl status php*-fpm
```

### 3. Find PHP-FPM Socket
```bash
ls -la /var/run/php/
# Look for something like: php8.0-fpm.sock or php7.4-fpm.sock
```

### 4. Configure Nginx

Find your nginx configuration file that handles port 1112 and add:

```nginx
# Inside the server block for port 1112
location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    # Update the socket path based on step 3
}

# Ensure index.php is in the index directive:
index index.php index.html index.htm;
```

### 5. Test and Reload
```bash
sudo nginx -t
sudo systemctl reload nginx
```

### 6. Set Permissions
```bash
sudo chown -R www-data:www-data /home/successbank/projects/docker/project1/html
sudo chmod -R 755 /home/successbank/projects/docker/project1/html
```

### 7. Test PHP
```bash
curl http://211.248.112.67:1112/test.php
# Or visit in browser: http://211.248.112.67:1112/phpinfo.php
```

## Files Ready for Use
- `/index.php` - Main page
- `/about.php` - About page
- `/services.php` - Services page
- `/contact.php` - Contact page with form
- `/phpinfo.php` - PHP information
- `/test.php` - Simple PHP test

## Troubleshooting
If PHP files still download:
1. Check if PHP-FPM is running: `systemctl status php*-fpm`
2. Check nginx error log: `tail -f /var/log/nginx/error.log`
3. Verify socket path matches your PHP version
4. Ensure nginx user can access PHP-FPM socket