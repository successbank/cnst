# 충남스틸 웹메일 데이터베이스 설계서

## 문서 정보
- **작성일**: 2026년 1월 7일
- **작성자**: 강민정 (시니어 DBA)
- **검토자**: 홍길동 (DBA팀장), 임현석 (시니어 DB개발자)
- **버전**: 1.0

---

## 1. DB 분리 전략

### 1.1 분리 원칙

```
┌─────────────────────────────────────────────────────────────────────┐
│                        데이터베이스 분리 아키텍처                      │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│   [기존 시스템]                    [신규 메일 시스템]                  │
│   ┌─────────────────────┐        ┌─────────────────────┐           │
│   │   project1_mysql    │        │   mailcow-mysql     │           │
│   │   (MariaDB 10.11)   │        │   (MySQL 8.0)       │           │
│   │                     │        │                     │           │
│   │   Container:        │        │   Container:        │           │
│   │   project1_mysql    │        │   mailcowdockerized │           │
│   │                     │        │   _mysql-mailcow_1  │           │
│   │   Port: 3306        │        │   Port: 13306       │           │
│   │                     │        │   (내부 전용)        │           │
│   │   Network:          │        │   Network:          │           │
│   │   project1_default  │        │   mailcow_default   │           │
│   │                     │        │                     │           │
│   │   Volume:           │        │   Volume:           │           │
│   │   project1_mysql_   │        │   mysql-vol-1       │           │
│   │   data              │        │                     │           │
│   └─────────────────────┘        └─────────────────────┘           │
│                                                                     │
│   ✓ 독립적 운영 - 서로 영향 없음                                      │
│   ✓ 독립적 백업 - 별도 스케줄                                         │
│   ✓ 독립적 복구 - 개별 복원 가능                                      │
│   ✓ 성능 격리 - 리소스 분리                                           │
└─────────────────────────────────────────────────────────────────────┘
```

### 1.2 분리 사유

| 항목 | 분리 방식 | 통합 방식 | 결정 |
|------|----------|----------|------|
| 운영 독립성 | ⭐⭐⭐⭐⭐ | ⭐⭐ | 분리 |
| 백업/복구 | ⭐⭐⭐⭐⭐ | ⭐⭐ | 분리 |
| 성능 영향 | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | 분리 |
| 보안 격리 | ⭐⭐⭐⭐⭐ | ⭐⭐ | 분리 |
| 관리 복잡도 | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | - |
| 리소스 사용 | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | - |

**결론**: 운영 안정성과 보안을 위해 **DB 분리** 방식 채택

---

## 2. Mailcow 데이터베이스 스키마

### 2.1 주요 테이블 ERD

```
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│     domain      │       │     mailbox     │       │      alias      │
├─────────────────┤       ├─────────────────┤       ├─────────────────┤
│ domain (PK)     │──┐    │ username (PK)   │       │ id (PK)         │
│ description     │  │    │ password        │       │ address         │
│ aliases         │  │    │ name            │       │ goto            │
│ mailboxes       │  │    │ maildir         │       │ domain (FK)     │
│ defquota        │  └───▶│ domain (FK)     │       │ created         │
│ maxquota        │       │ quota           │       │ modified        │
│ quota           │       │ active          │       │ active          │
│ active          │       │ created         │       └─────────────────┘
│ created         │       │ modified        │
│ modified        │       └─────────────────┘
└─────────────────┘
         │
         │         ┌─────────────────┐       ┌─────────────────┐
         │         │   quota2        │       │    sender_acl   │
         │         ├─────────────────┤       ├─────────────────┤
         │         │ username (PK)   │       │ id (PK)         │
         │         │ bytes           │       │ logged_in_as    │
         └────────▶│ messages        │       │ send_as         │
                   │ domain (FK)     │       └─────────────────┘
                   └─────────────────┘
```

### 2.2 테이블 상세 정의

#### 2.2.1 domain (도메인 관리)

```sql
CREATE TABLE `domain` (
  `domain` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT '',
  `aliases` int(11) NOT NULL DEFAULT 0,
  `mailboxes` int(11) NOT NULL DEFAULT 0,
  `defquota` bigint(20) NOT NULL DEFAULT 3072,
  `maxquota` bigint(20) NOT NULL DEFAULT 10240,
  `quota` bigint(20) NOT NULL DEFAULT 10240,
  `relayhost` varchar(255) NOT NULL DEFAULT '',
  `backupmx` tinyint(1) NOT NULL DEFAULT 0,
  `relay_all_recipients` tinyint(1) NOT NULL DEFAULT 0,
  `gal` tinyint(1) NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 초기 데이터
INSERT INTO `domain` VALUES
('cnst.co.kr', '충남스틸', 100, 50, 5120, 10240, 51200, '', 0, 0, 1, 1, NOW(), NOW());
```

#### 2.2.2 mailbox (메일함 계정)

```sql
CREATE TABLE `mailbox` (
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT '',
  `maildir` varchar(255) DEFAULT '',
  `quota` bigint(20) NOT NULL DEFAULT 0,
  `domain` varchar(255) NOT NULL,
  `local_part` varchar(255) NOT NULL,
  `kind` varchar(100) DEFAULT '',
  `multiple_bookings` int(11) NOT NULL DEFAULT -1,
  `sogo_access` tinyint(1) NOT NULL DEFAULT 1,
  `imap_access` tinyint(1) NOT NULL DEFAULT 1,
  `pop3_access` tinyint(1) NOT NULL DEFAULT 1,
  `smtp_access` tinyint(1) NOT NULL DEFAULT 1,
  `sieve_access` tinyint(1) NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `attributes` json DEFAULT NULL,
  PRIMARY KEY (`username`),
  KEY `domain` (`domain`),
  CONSTRAINT `mailbox_ibfk_1` FOREIGN KEY (`domain`) REFERENCES `domain` (`domain`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 비밀번호는 Mailcow 관리자 UI에서 설정
```

#### 2.2.3 alias (메일 별칭)

```sql
CREATE TABLE `alias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `address` varchar(255) NOT NULL,
  `goto` text NOT NULL,
  `domain` varchar(255) NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `private_comment` text DEFAULT NULL,
  `public_comment` text DEFAULT NULL,
  `sogo_visible` tinyint(1) NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `address` (`address`),
  KEY `domain` (`domain`),
  CONSTRAINT `alias_ibfk_1` FOREIGN KEY (`domain`) REFERENCES `domain` (`domain`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 2.2.4 quota2 (용량 관리)

```sql
CREATE TABLE `quota2` (
  `username` varchar(255) NOT NULL,
  `bytes` bigint(20) NOT NULL DEFAULT 0,
  `messages` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`username`),
  CONSTRAINT `quota2_ibfk_1` FOREIGN KEY (`username`) REFERENCES `mailbox` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. 백업 및 복구 전략

### 3.1 백업 스케줄

| 백업 유형 | 주기 | 보관 기간 | 방법 |
|----------|------|-----------|------|
| 전체 백업 | 매일 03:00 | 30일 | mysqldump --single-transaction |
| 증분 백업 | 매시간 | 7일 | binlog 기반 |
| 메일 데이터 | 매일 04:00 | 30일 | rsync |
| 설정 파일 | 매주 일요일 | 90일 | tar.gz |

### 3.2 백업 스크립트

```bash
#!/bin/bash
# /home/cnst/www/html/mailcow/backup/db_backup.sh

# 설정
BACKUP_DIR="/home/cnst/www/html/mailcow/backup/daily"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Mailcow 내장 백업 도구 사용
cd /home/cnst/www/html/mailcow

# 전체 백업 (DB + 메일 + 설정)
./helper-scripts/backup_and_restore.sh backup all --delete-days ${RETENTION_DAYS}

# 백업 결과 로깅
if [ $? -eq 0 ]; then
    echo "[${DATE}] Mailcow 백업 성공" >> /var/log/mailcow_backup.log
else
    echo "[${DATE}] Mailcow 백업 실패" >> /var/log/mailcow_backup.log
    # 슬랙 알림 (선택사항)
    # curl -X POST -H 'Content-type: application/json' --data '{"text":"Mailcow 백업 실패!"}' SLACK_WEBHOOK_URL
fi
```

### 3.3 복구 절차

```bash
# 1. 백업 목록 확인
cd /home/cnst/www/html/mailcow
./helper-scripts/backup_and_restore.sh restore --list

# 2. 특정 날짜 복구
./helper-scripts/backup_and_restore.sh restore mailcow-2026-01-07

# 3. 서비스 재시작
docker compose restart
```

---

## 4. 성능 최적화

### 4.1 MySQL 설정 (my.cnf)

```ini
[mysqld]
# InnoDB 설정
innodb_buffer_pool_size = 512M
innodb_log_file_size = 128M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# 연결 설정
max_connections = 100
wait_timeout = 600
interactive_timeout = 600

# 쿼리 캐시 (MySQL 8.0에서는 제거됨)
# query_cache_type = 1
# query_cache_size = 64M

# 로깅
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

### 4.2 인덱스 권장사항

```sql
-- 자주 조회되는 컬럼에 인덱스 추가
CREATE INDEX idx_mailbox_domain ON mailbox(domain);
CREATE INDEX idx_mailbox_active ON mailbox(active);
CREATE INDEX idx_alias_domain ON alias(domain);
CREATE INDEX idx_alias_address ON alias(address);
```

---

## 5. 모니터링

### 5.1 모니터링 항목

| 항목 | 임계값 | 알림 방법 |
|------|--------|-----------|
| 연결 수 | > 80 | 경고 |
| 쿼리 응답 시간 | > 2초 | 경고 |
| 디스크 사용량 | > 80% | 경고 |
| 디스크 사용량 | > 90% | 긴급 |
| 백업 실패 | 1회 | 긴급 |

### 5.2 모니터링 쿼리

```sql
-- 현재 연결 수 확인
SELECT COUNT(*) FROM information_schema.processlist;

-- 느린 쿼리 확인
SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;

-- 테이블별 크기 확인
SELECT
    table_name,
    ROUND(data_length/1024/1024, 2) AS 'Data (MB)',
    ROUND(index_length/1024/1024, 2) AS 'Index (MB)'
FROM information_schema.tables
WHERE table_schema = 'mailcow'
ORDER BY data_length DESC;

-- 메일함 용량 확인
SELECT
    m.username,
    m.quota AS '할당량(MB)',
    ROUND(q.bytes/1024/1024, 2) AS '사용량(MB)',
    ROUND(q.bytes/m.quota*100, 1) AS '사용률(%)'
FROM mailbox m
LEFT JOIN quota2 q ON m.username = q.username
ORDER BY q.bytes DESC;
```

---

## 6. 기존 시스템과의 연계

### 6.1 연동 불필요 항목

Mailcow와 기존 project1_db는 **완전 분리 운영**:
- 사용자 인증: 별도 (메일 계정 ≠ 웹 회원)
- 데이터 공유: 없음
- 네트워크: 별도 (project1_default ≠ mailcow_default)

### 6.2 향후 연동 가능 시나리오

필요 시 API를 통한 연동 가능:

```php
// 예: 웹 회원가입 시 메일 계정 자동 생성 (향후 구현 가능)
// Mailcow API 엔드포인트: https://webmail.cnst.co.kr/api/v1/add/mailbox

$mailcow_api = "https://webmail.cnst.co.kr/api/v1/add/mailbox";
$api_key = "YOUR_MAILCOW_API_KEY";

$data = [
    "local_part" => "newuser",
    "domain" => "cnst.co.kr",
    "password" => "securePassword123!",
    "password2" => "securePassword123!",
    "quota" => 5120,
    "name" => "신규 사용자",
    "active" => 1
];

// 현재는 분리 운영, 필요 시 연동 개발
```

---

## 문서 승인

| 역할 | 이름 | 서명 | 일자 |
|------|------|------|------|
| 작성 | 강민정 | ✓ | 2026-01-07 |
| 검토 | 홍길동 | | |
| 검토 | 임현석 | | |
