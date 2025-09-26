# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the Chungnam Steel (충남스틸) website - a PHP-based steel products management system with comprehensive e-commerce, quotation, and business management features. The system runs on Docker with Nginx, PHP-FPM, and MariaDB.

## 🚨 작업 시 필수 주의사항

### 항상 지켜야 할 규칙
1. ** I형강 제품군 작업 시**
   - 카테고리 코드: `light-h-beam`만 대상
   - 다른 제품군은 수정하지 않음
   - 재질 데이터는 현재 제품 우선, 부모 제품은 참조용

2. **파일 수정 시**
   - 백업 먼저 생성: `cp original.php original.php.bak`
   - 루트와 html 디렉토리에 중복 파일 확인
   - 두 파일 모두 수정 필요한지 확인

3. **데이터베이스 작업 시**
   - WHERE 조건 필수 확인
   - UPDATE/DELETE 전 SELECT로 대상 확인
   - 트랜잭션 사용 권장
   - DB : project1_db

4. **테스트**
   - 수정 후 반드시 실제 페이지 테스트
   - 관련 없는 제품군 영향 확인
   - 에러 로그 확인: `docker logs project1_php`

### 반복되는 작업 패턴
```bash
# 1. 제품 데이터 확인
php -r "require_once 'db.php'; \$pdo = getDB(); \$stmt = \$pdo->prepare('SELECT * FROM products WHERE id = ?'); \$stmt->execute([ID]); print_r(\$stmt->fetch());"

# 2. 경량H형강 제품만 조회
mysql -u root -pWNtl@akdnj12 bridge -e "SELECT * FROM products WHERE category_code = 'light-h-beam';"

# 3. 파일 백업
cp /path/to/file.php /path/to/file.php.bak.$(date +%Y%m%d_%H%M%S)
```

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
docker exec -it project1_mysql mysql -u user -puserpassword project1_db

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

## Recent Work History (2025-09-26)

### 경량H형강 재질 표시 개선 작업

#### 작업 내용
경량H형강(light-h-beam) 제품의 재질 정보가 제품 상세 페이지에서 올바르게 표시되지 않는 문제를 해결했습니다.

#### 문제점
1. **하드코딩 문제**: `html/product_detail.php`에 SS275가 기본값으로 하드코딩되어 있었음
2. **데이터 상속 문제**: 자식 제품이 부모 제품의 재질 정보를 상속받아 실제 재질이 표시되지 않음
3. **중복 파일 존재**: `product_detail.php`가 루트와 html 디렉토리에 각각 존재

#### 수정 사항

1. **product_detail.php (루트 디렉토리)**:
```php
// 경량H형강의 경우 현재 제품의 재질 정보를 우선 사용
if ($category_code === 'light-h-beam' && !empty($current_product) && !empty($current_product['available_materials'])) {
    $available_materials = json_decode($current_product['available_materials'], true) ?? [];
} else {
    $available_materials = json_decode($product['available_materials'], true) ?? [];
}
```

2. **html/product_detail.php**:
- SS275 하드코딩 제거
- 경량H형강 제품의 경우 현재 제품의 재질 정보 우선 사용

#### 데이터베이스 구조
- `products` 테이블의 주요 필드:
  - `available_materials`: JSON 형식의 재질 목록 (예: `["SS275","SUS304","SM570","SM520"]`)
  - `material_price_data`: JSON 형식의 재질별 가격 정보
  - `has_calculator`: 계산기 보유 여부 (0 또는 1)
  - `parent_product_id`: 부모 제품 ID (계산기 상속용)

#### 테스트 케이스
- ID 390 (경량H형강 LHB 300*150*4.5*6.0): SS275, SUS304, SM570, SM520
- ID 397 (경량H형강 LHB 250*250*6.0*8.0): SS275, SM490B, SM570

### 파일 위치 정리
- **제품 상세 페이지 (두 개 존재)**:
  - `/home/successbank/projects/docker/project1/product_detail.php`
  - `/home/successbank/projects/docker/project1/html/product_detail.php`
- **관리자 제품 편집**:
  - `/home/successbank/projects/docker/project1/html/admin/admin_products_edit.php`

### 주의사항
- 경량H형강 카테고리 코드: `light-h-beam`
- 일반 H형강 카테고리 코드: `light-h-beam`
- 제품이 계산기를 가지지 않으면 부모 제품의 데이터를 참조하는 구조
- 재질 정보는 JSON 배열로 저장되며, 첫 번째 재질이 기본값으로 표시됨