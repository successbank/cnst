# 프로젝트 데이터베이스 정보 요약

## 1. 데이터베이스 연결 정보

### 주요 설정 (db.php)
- **Host**: 127.0.0.1 (로컬) 또는 project1_mysql (Docker 컨테이너)
- **Port**: 3306
- **Database**: project1_db
- **User**: root
- **Password**: rootpassword

### Docker 설정 (docker-compose.yml)
- **Container**: project1_mysql
- **Image**: mariadb:10.11
- **Port Mapping**: 3306:3306
- **Volume**: mysql_data:/var/lib/mysql
- **초기 DB 설정**: project5_db (실제로는 project1_db 사용)

## 2. 데이터베이스 테이블 목록 (28개)

| 테이블명 | 레코드 수 | 설명 |
|---------|----------|------|
| banners | 0 | 배너 관리 |
| board_comments | 0 | 게시판 댓글 |
| board_consignment | 0 | 위탁판매 게시판 |
| board_news | 2 | 뉴스 게시판 |
| board_notice | 1 | 공지사항 게시판 |
| board_quote | 0 | 견적 게시판 |
| calculation_constants | 5 | 계산 상수 |
| calculation_formulas | 16 | 계산 공식 |
| calculation_history | 0 | 계산 이력 |
| calculation_logs | 8 | 계산 로그 |
| calculation_parameters | 18 | 계산 파라미터 |
| members | 0 | 회원 정보 |
| product_categories | 20 | 제품 카테고리 |
| product_icons | 17 | 제품 아이콘 |
| product_images | 0 | 제품 이미지 |
| product_quote_items | 4 | 제품 견적 항목 |
| product_quotes | 3 | 제품 견적 |
| product_specifications | 0 | 제품 사양 |
| products | 230 | 제품 정보 |
| rebar_length_data | 531 | 철근 길이별 데이터 |
| rebar_length_data_backup | 732 | 철근 데이터 백업 |
| rebar_length_info | 0 | 철근 길이 정보 |
| rebar_materials | 9 | 철근 재질 |
| rebar_prices | 3 | 철근 가격 |
| rebar_products | 0 | 철근 제품 |
| rebar_specifications | 12 | 철근 규격 |
| site_settings | 0 | 사이트 설정 |
| unit_weights | 8 | 단위 중량 |

**총 데이터**: 28개 테이블, 1,619개 레코드

## 3. 주요 테이블 상세 구조

### products (제품 정보)
- 230개 제품 데이터
- 주요 필드: id, category_code, specification, origin, material, unit_weight, price

### rebar_length_data (철근 길이 데이터)
- 531개 레코드 (D10~D51 규격, 6m~12m 범위)
- 주요 필드: spec_name, length, pieces_per_length, piece_weight

### product_categories (제품 카테고리)
- 20개 카테고리
- H빔, I빔, 철근, 파이프 등

## 4. Python 스크립트 데이터베이스 연결

### 주요 연결 설정
```python
DB_CONFIG = {
    'host': '127.0.0.1',
    'port': 3306,
    'database': 'project1_db',
    'user': 'root',
    'password': 'rootpassword'
}
```

### 데이터베이스 사용 스크립트
- import_rebar_length_data.py - 철근 길이 데이터 임포트
- import_h_beam_complete.py - H빔 데이터 임포트
- import_i_beam_fixed.py - I빔 데이터 임포트
- update_h_beam_prices.py - H빔 가격 업데이트
- update_i_beam_origins.py - I빔 원산지 업데이트
- check_h_beam_data.py - H빔 데이터 확인
- compare_h_beam_weights.py - H빔 중량 비교

## 5. PHP 데이터베이스 연결

### 메인 연결 파일
- `/db.php` - 프로젝트 루트
- `/html/db.php` - 웹 디렉토리

### PDO 연결 설정
```php
$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
$pdo = new PDO($dsn, DB_USER, DB_PASS);
```

## 6. 데이터베이스 액세스 포인트

### 외부 접속
- **Host**: 211.248.112.67
- **Port**: 3306
- **Web Interface**: http://211.248.112.67:1112/

### 내부 접속 (Docker)
- **Host**: project1_mysql (컨테이너 간)
- **Host**: 127.0.0.1 (로컬)

## 7. 백업 및 보안

### 백업 파일
- rebar_length_data_backup 테이블 (732 레코드)
- h_beam_backup_20250926_015956.sql

### 보안 설정
- Root 계정 사용 중 (프로덕션에서는 변경 필요)
- 모든 포트 외부 개방 (보안 강화 필요)

## 8. 주의사항

1. **DB 이름 불일치**: Docker 설정은 project5_db로 되어있지만 실제로는 project1_db 사용
2. **권한**: 현재 root 권한으로 모든 작업 수행 (보안상 위험)
3. **포트 노출**: 3306 포트가 외부로 직접 노출됨
4. **문자셋**: UTF8MB4 사용 (이모지 등 4바이트 문자 지원)