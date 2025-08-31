# Database Backup Information

## 백업 정보
- **백업 일시**: 2025-08-26 05:09:48
- **데이터베이스명**: project5_db
- **백업 파일**: project5_db_backup_20250826_050948.sql
- **파일 크기**: 28KB

## 백업 포함 테이블
- board_comments
- board_consignment
- board_news
- board_notice
- board_quote
- members
- product_categories
- product_images
- products
- rebar_length_data
- rebar_length_info
- rebar_materials
- rebar_prices
- rebar_products
- rebar_specifications
- site_settings
- unit_weights

## 복원 방법
```bash
# Docker 컨테이너를 사용한 복원
docker exec -i [mysql_container_name] mysql -u root -prootpassword project5_db < project5_db_backup_20250826_050948.sql

# 또는 직접 MySQL 명령어 사용
mysql -u root -prootpassword project5_db < project5_db_backup_20250826_050948.sql
```

## 백업 생성 명령어
```bash
docker exec [mysql_container_name] mysqldump -u root -prootpassword project5_db > database_backup/project5_db_backup_$(date +%Y%m%d_%H%M%S).sql
```

## 주의사항
- 이 백업은 전체 데이터베이스 구조와 데이터를 포함합니다.
- 복원 시 기존 데이터가 덮어씌워질 수 있으므로 주의하세요.
- 정기적인 백업을 권장합니다.