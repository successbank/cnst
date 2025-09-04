# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the Chungnam Steel (충남스틸) website - a PHP-based steel products management system with comprehensive e-commerce, quotation, and business management features. The system runs on Docker with Nginx, PHP-FPM, and MariaDB.

## Essential Commands

### Development & Testing
```bash
# Start all Docker containers
cd /home/successbank/projects/docker/project1
docker compose up -d

# Check container status
docker compose ps

# View logs
docker compose logs -f [service_name]  # php, mysql, web

# Restart services
docker compose restart

# Run database migrations
docker exec -it project1_php php /var/www/html/[migration_file.php]
```

### Database Access
```bash
# Access MySQL CLI
docker exec -it project1_mysql mysql -u user -puserpassword project5_db

# Common database operations
mysql> SHOW TABLES;
mysql> DESCRIBE [table_name];
```

### Testing & Debugging
```bash
# Check PHP configuration
docker exec -it project1_php php -i

# Test specific functionality
curl http://localhost:1112/test_db.php

# Clear PHP session data (if needed)
docker exec -it project1_php rm -rf /var/lib/php/sessions/*
```

### TypeScript/Node.js commands (if using the Node.js parts)
```bash
npm run dev    # Development server with hot reload
npm run build  # Build TypeScript to JavaScript
npm run start  # Run production server
```

## Architecture Overview

### Technology Stack
- **Frontend**: PHP templates, vanilla JavaScript, custom CSS (samsung-style.css)
- **Backend**: PHP 8.3 with PDO
- **Database**: MariaDB 10.11
- **Server**: Nginx with PHP-FPM
- **Container**: Docker Compose

### Directory Structure
```
/html/
├── index.php              # Homepage
├── admin/                 # Admin panel (protected)
│   ├── admin_index.php   # Dashboard
│   ├── admin_check.php   # Auth middleware
│   └── ajax/             # AJAX endpoints
├── includes/             # Shared components
│   ├── sub_layout.php   # Page layouts
│   └── settings.php     # Configuration
├── db.php               # Database connection & utilities
├── head.php            # Common header
├── tail.php            # Common footer
└── uploads/            # User uploaded files
```

### Key Design Patterns
1. **Template-based views**: PHP files serve as both controller and view
2. **Include-based composition**: Common elements via head.php/tail.php
3. **Session-based authentication**: Via member_check.php
4. **Direct database access**: PDO prepared statements in individual files
5. **AJAX for dynamic content**: Endpoints in admin/ajax/

### Database Schema (Key Tables)
- `members` - User accounts with bcrypt passwords
- `products`, `product_categories` - Product catalog
- `product_quotes`, `product_quote_items` - Quotation system
- `board_notice`, `board_news` - Content management
- `unit_weights` - Product weight calculations
- `rebar_materials` - Steel rebar specifications
- `banners` - Homepage carousel management

### Authentication & Security
- Two user types: members and admins
- Password hashing: bcrypt (see README_password_hashing.md)
- Session-based auth with `member_check.php` functions
- Admin area protected by `admin_check.php`
- XSS prevention via `htmlspecialchars()`
- SQL injection prevention via PDO prepared statements

### Business Logic Components

#### Product Management
- Category-based organization (H형강, I형강, etc.)
- Product specifications with variants
- Unit weight calculations
- Price range management
- Stock status tracking

#### Quotation System  
- Shopping cart functionality (sessionStorage)
- Quote request forms
- Admin quote management
- PDF generation capability

#### Special Features
- Rebar calculator (`rebar_quote.php`)
- Consignment board with privacy protection
- Kakao notification integration
- Multi-origin product support
- Member address management

### Common Development Tasks

#### Adding a New Product Category
1. Insert into `product_categories` table
2. Add category slug mapping
3. Update navigation if needed

#### Modifying Admin Menu
1. Edit `admin/admin_head.php`
2. Add corresponding admin page
3. Update `admin_check.php` if needed

#### Creating New Database Tables
1. Add SQL to `sql/` directory
2. Create PHP migration script
3. Run via Docker: `docker exec -it project1_php php migration.php`

#### Adding AJAX Functionality
1. Create endpoint in `admin/ajax/` or `ajax/`
2. Return JSON with proper headers
3. Handle errors appropriately

### Important URLs
- Main site: http://211.248.112.67:1112/
- Admin panel: http://211.248.112.67:1112/admin/
- Webmail: http://211.248.112.67:1112/webmail/

### Notes for Future Development
- The codebase mixes modern (PDO, AJAX) and traditional PHP patterns
- No formal MVC framework - logic embedded in view files
- Admin panel has comprehensive business management features
- Mobile-responsive design implemented
- Consider implementing CSRF tokens for form submissions
- Some TypeScript/Node.js scaffolding exists but main app is PHP