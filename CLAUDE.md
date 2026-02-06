# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 프로젝트 개요

충남스틸(Chungnam Steel) 웹사이트 - PHP 기반 철강 제품 관리 시스템. 전자상거래, 견적, 비즈니스 관리 기능을 포함. Docker 환경(Nginx + PHP-FPM + MariaDB)에서 운영.

## 개발팀 페르소나
- ./persona1.md


## 작업 시 필수 주의사항

### 핵심 규칙
1. **개발 요청한 제품군만 개발 진행** - 진행중인 개발 제품군 외에는 절대 수정/변경 금지
2. **소통 언어**: 한국어
3. **데이터베이스 작업 시**:
   - WHERE 조건 필수 확인
   - UPDATE/DELETE 전 SELECT로 대상 확인
   - 트랜잭션 사용 권장
4. **테스트**: 수정 후 반드시 실제 페이지 테스트, 에러 로그 확인
5. **별도 요청 없이 수정 금지** - 명시적으로 요청하지 않은 파일, 코드, 설정은 절대 수정/변경하지 말 것

## 서버 환경

### Docker 컨테이너
| 컨테이너 | 이미지 | 포트 |
|---------|--------|------|
| project1_web | nginx:alpine | 80, 8080, 1112 |
| project1_php | project5-php | 9000 (내부) |
| project1_mysql | mariadb:10.11 | 3306 |
| project1_pgadmin | dpage/pgadmin4 | 8081 |

### 경로 매핑
```
호스트: /home/cnst/www/html/webservice/html/html/
   ↓
Nginx:  /usr/share/nginx/html/
PHP:    /var/www/html/
```

## 필수 명령어

### Docker 관리
```bash
# 컨테이너 상태 확인
sudo docker ps

# 로그 확인
sudo docker logs project1_php
sudo docker logs project1_web

# 컨테이너 재시작
cd /home/cnst/www/html/webservice && sudo docker compose restart
```

### 데이터베이스 접근
```bash
# MySQL CLI 접속
sudo docker exec -it project1_mysql mysql -u root -prootpassword project1_db

# 또는 일반 사용자로
sudo docker exec -it project1_mysql mysql -u user -puserpassword project1_db
```

### 데이터베이스 백업/복원
```bash
# 백업
sudo docker exec project1_mysql mysqldump -u root -prootpassword project1_db > backup.sql

# 복원
sudo docker exec -i project1_mysql mysql -u root -prootpassword project1_db < backup.sql
```

### 제품 데이터 확인 (Docker 내부)
```bash
sudo docker exec -it project1_php php -r "
require_once '/var/www/html/db.php';
\$pdo = getDB();
\$stmt = \$pdo->prepare('SELECT * FROM products WHERE id = ?');
\$stmt->execute([제품ID]);
print_r(\$stmt->fetch());
"
```

## 아키텍처

### 기술 스택
- **Frontend**: PHP 템플릿, Vanilla JS, CSS (samsung-style.css)
- **Backend**: PHP 8.3 + PDO
- **Database**: MariaDB 10.11 (DB: project1_db)
- **Server**: Nginx + PHP-FPM

### 디렉토리 구조
```
/html/
├── index.php           # 홈페이지
├── db.php              # DB 연결 (getDB() 함수)
├── head.php            # 공통 헤더
├── tail.php            # 공통 푸터
├── admin/              # 관리자 패널
│   ├── admin_check.php # 인증 미들웨어
│   ├── admin_head.php  # 관리자 헤더/메뉴
│   └── ajax/           # AJAX 엔드포인트
├── ajax/               # 프론트엔드 AJAX
├── includes/           # 공통 컴포넌트
│   └── sub_layout.php  # 서브페이지 레이아웃
├── css/                # 스타일시트
├── js/                 # 자바스크립트
└── uploads/            # 업로드 파일
```

### 주요 테이블
- `members` - 회원 (bcrypt 비밀번호)
- `products`, `product_categories` - 제품 카탈로그
- `product_quotes`, `product_quote_items` - 견적 시스템
- `board_notice`, `board_news`, `board_consignment` - 게시판
- `banners` - 홈페이지 배너
- `unit_weights` - 단중 계산

### 제품 데이터 구조
`products` 테이블 주요 필드:
- `available_materials`: JSON 형식 재질 목록 (예: `["SS275","SM490B"]`)
- `material_price_data`: JSON 형식 재질별 가격
- `has_calculator`: 계산기 보유 여부 (0/1)
- `parent_product_id`: 부모 제품 ID (계산기 상속용)

### 인증 시스템
- 회원: `member_check.php` - 세션 기반 인증
- 관리자: `admin/admin_check.php` - 관리자 권한 확인
- 비밀번호: bcrypt 해싱

### 페이지 구조 패턴
```php
<?php
$currentPage = 'about';
$pageTitle = '회사소개';
require_once 'includes/sub_layout.php';
include 'head.php';

startSubPage('회사소개', 'about');
companySidebar('about');
?>

<main class="sub-content">
    <!-- 콘텐츠 -->
</main>

<?php
endSubPage();
include 'tail.php';
?>
```

## 주요 URL

- 메인 사이트: http://103.124.103.229/
- 관리자 패널: http://103.124.103.229/admin/

## 파일 수정 시 주의사항

1. **권한 문제**: 파일 수정 전 `sudo chmod 666 파일경로` 필요할 수 있음
2. **product_detail.php**: 루트와 html 디렉토리에 각각 존재 - 수정 시 둘 다 확인
3. **경량H형강 처리**: 카테고리 코드 `light-h-beam`, 자식 제품은 부모 재질 대신 자체 재질 사용
4. **JSON 필드**: `available_materials`, `material_price_data` 등은 JSON 형식으로 저장
