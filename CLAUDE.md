# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# 개발팀 페르소나
- .claude/CLAUDE.md

## 프로젝트 개요

충남스틸(Chungnam Steel) 웹사이트 - PHP 기반 철강 제품 관리 시스템. 전자상거래, 견적, 위탁판매, 비즈니스 관리 기능을 포함. Docker 환경(Nginx + PHP-FPM + MariaDB)에서 운영.

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
| project1_web | nginx:alpine | 80, 443 |
| project1_php | project5-php | 9000 (내부) |
| project1_mysql | mariadb:10.11 | 127.0.0.1:3306 |
| project1_pgadmin | dpage/pgadmin4 | 127.0.0.1:8081 |

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
# 컨테이너 재시작
cd /home/cnst/www/html/webservice && docker compose restart

# 로그 확인
docker logs project1_php
docker logs project1_web
```

### 데이터베이스 접근
```bash
# 비밀번호는 /home/cnst/www/html/webservice/.env 참조
docker exec -it project1_mysql mysql -u root -p'<.env의 MYSQL_ROOT_PASSWORD>' project1_db
```

### PHP 실행 (Docker 내부)
```bash
docker exec -it project1_php php -r "
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
- **환경변수**: `/home/cnst/www/html/webservice/.env` (git에 포함되지 않음)

### 주요 테이블
- `members` - 회원 (bcrypt 비밀번호)
- `admin_users` - 관리자 (totp_secret, totp_enabled 포함)
- `products`, `product_categories` - 제품 카탈로그
- `product_quotes`, `product_quote_items` - 견적 시스템
- `board_notice`, `board_news`, `board_consignment` - 게시판
- `banners` - 홈페이지 배너
- `unit_weights` - 단중 계산
- `site_settings` - 사이트 설정 (카카오 등, setting_group별 그룹화)

### 제품 데이터 구조
`products` 테이블 주요 필드:
- `available_materials`: JSON 형식 재질 목록 (예: `["SS275","SM490B"]`)
- `material_price_data`: JSON 형식 재질별 가격
- `has_calculator`: 계산기 보유 여부 (0/1)
- `parent_product_id`: 부모 제품 ID (계산기 상속용)

## 요청 처리 흐름 (Request Pipeline)

모든 요청은 다음 순서로 처리됨:

```
1. db.php 로드 → includes/waf.php 실행 (모든 요청에 WAF 적용)
2. 세션 초기화 (cookie: httponly, secure, SameSite=Lax)
3. getDB() → .env에서 MYSQL_PASSWORD 로드 (하드코딩 없음)
4. 관리자 페이지: admin_check.php → POST/PUT/DELETE 시 CSRF 자동 검증
5. 회원 페이지: member_check.php → 세션 기반 인증
```

## 보안 계층 구조

### Layer 1: WAF (`includes/waf.php`)
- `db.php` 최상단에서 로드, 모든 요청에 적용
- HTTP 메서드 제한(GET/POST/HEAD/OPTIONS만), URI 경로 탐색 차단, 스캐너 UA 차단
- SQL/XSS 패턴 탐지, 요청 헤더 크기 제한
- IP 자동차단: 5분 내 10회 이상 위반 → 30분 차단 (파일 기반 `/tmp/waf_blocks/`)
- 내부 IP(127.0.0.1, 172.x, 10.x) 자동차단 면제
- 킬 스위치: `WAF_DISABLED=1` 환경변수
- 로그: `logs/waf.log`

### Layer 2: CSRF 보호 (`includes/csrf.php`)
- `generateCsrfToken()` / `verifyCsrfToken()` / `csrfField()`
- `admin/admin_check.php`에서 POST/PUT/DELETE 자동 검증
- `admin/admin_head.php`에서 메타 태그 + JS 인터셉터로 X-CSRF-TOKEN 자동 주입
- 프론트엔드 폼(login, register, find_password, edit_profile)에 csrf_token 필드 포함

### Layer 3: 관리자 2FA (`includes/totp.php`)
- 순수 PHP TOTP (RFC 6238) + Base32 + 백업 코드
- 로그인 → 2FA 활성화 시 `admin_totp_verify.php`로 리다이렉트
- `admin_check.php`가 `totp_pending` 상태에서 다른 관리자 페이지 접근 차단
- QR: cdnjs qrcodejs (클라이언트 사이드)

### Layer 4: 입력 검증 (`includes/input_validator.php`)
- SQL 인젝션/XSS 패턴 탐지
- Rate Limiting: `checkRateLimit($key, $maxAttempts, $windowSeconds)`

### Layer 5: Nginx
- HSTS (1년), CSP, Rate limiting (로그인 5r/m, API 30r/m)
- 민감한 확장자 차단 (.csv, .sql, .py, .bak, .env, .log, .sh)
- `/logs/` 접근 차단, server_tokens off

## 인증 시스템

### 회원 인증 (`member_check.php`, `login.php`)
- `checkLogin()`: 미인증 시 로그인 페이지 리다이렉트
- `isLoggedIn()` / `getMemberInfo()` / `isAdmin()`
- Remember Me: `includes/auth_tokens.php` (영구 로그인 토큰)
- 세션: member_id, user_id, member_name, member_email, member_grade, is_admin

### 관리자 인증 (`admin/admin_login.php`, `admin/admin_check.php`)
- Rate limit: 5회/5분
- 로그인 → session_regenerate_id(true) → 2FA 확인 → admin_logged_in=true
- 세션 타임아웃: 1시간 (비활동 시)
- 로그인 이벤트 기록: `admin_login_logs` 테이블

## 주요 includes 라이브러리

| 파일 | 용도 |
|------|------|
| `waf.php` | Web Application Firewall |
| `csrf.php` | CSRF 토큰 생성/검증 |
| `totp.php` | 관리자 2FA (TOTP + 백업 코드) |
| `input_validator.php` | 입력 검증 + Rate Limiting |
| `auth_tokens.php` | Remember Me 토큰 |
| `kakao_config.php` | 카카오 API 설정 (DB에서 로드, PHP 파일 쓰기 아님) |
| `sub_layout.php` | 서브페이지 레이아웃 함수 |
| `CalculationEngine.php` | 제품 계산 로직 |
| `SteelCalculator.php` | 철강 단중/체적 계산 |
| `KakaoNotificationService.php` | 카카오 알림톡 발송 |
| `EmailService.php` | 이메일 발송 (Mailcow SMTP) |
| `BoardPermissionHelper.php` | 게시판 권한 매트릭스 |

## 페이지 구조 패턴

### 프론트엔드 서브페이지
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

### 관리자 페이지
```php
<?php
$pageTitle = '제목';
require_once 'admin_head.php';  // admin_check.php 포함 + CSRF 자동 보호
?>
<!-- POST 폼에 csrfField() 포함 필수 -->
<form method="POST">
    <?php echo csrfField(); ?>
    ...
</form>
```

### AJAX 엔드포인트 패턴
```php
<?php
session_start();
require_once '../db.php';
require_once '../includes/csrf.php';
if (!verifyCsrfToken(false)) {
    die(json_encode(['error' => 'CSRF token invalid']));
}
// 비즈니스 로직
```

## 카카오 통합

- 설정: `site_settings` 테이블 (setting_group='kakao')에서 로드
- `includes/kakao_config.php`가 DB에서 설정 로드 (PHP 파일 쓰기 방식 아님 - RCE 취약점 제거됨)
- 용도: 견적 확인, 위탁판매 알림, 재고 업데이트 알림

## 크론 작업 (`cron/`)

| 스크립트 | 스케줄 | 용도 |
|---------|--------|------|
| `backup_cron.php` | 매일 1시, 13시 | DB 백업 + 이메일 알림 |
| `log_cleanup_cron.php` | 매일 2시 | 30일 초과 로그 삭제 |
| `security_monitor.php` | 15분마다 | 실패 로그인/WAF 차단 감시 |
| `ssl_check.sh` | 매일 6시 | SSL 인증서 만료 체크 |
| `offsite_backup.sh` | 매일 1시30분, 13시30분 | rclone 원격 백업 |

## 파일 수정 시 주의사항

1. **product_detail.php**: 루트와 html 디렉토리에 각각 존재 - 수정 시 둘 다 확인
2. **경량H형강 처리**: 카테고리 코드 `light-h-beam`, 자식 제품은 부모 재질 대신 자체 재질 사용
3. **JSON 필드**: `available_materials`, `material_price_data` 등은 JSON 형식으로 저장
4. **db.php 수정 주의**: WAF 로딩, 세션 설정, 파일 업로드 보안이 모두 포함 - 실수 시 전체 사이트 영향
5. **CSRF 토큰**: 새 POST 폼 추가 시 `csrfField()`, AJAX 시 X-CSRF-TOKEN 헤더 필수
6. **비밀번호**: `.env` 파일에만 존재, 코드/커밋에 절대 하드코딩 금지

## 주요 URL

- 메인 사이트: https://cnst.co.kr/
- 관리자 패널: https://cnst.co.kr/admin/
