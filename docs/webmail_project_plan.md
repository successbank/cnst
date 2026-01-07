# 충남스틸 웹메일 서비스 구축 프로젝트 기획서

## 문서 정보
- **작성일**: 2026년 1월 7일
- **작성자**: 박성호 (수석 기획자)
- **검토자**: 이수진 (기술 PL), 장현우 (BE팀장), 홍길동 (DBA팀장)
- **승인자**: 김동현 (총괄 PM)
- **버전**: 1.0

---

## 1. 프로젝트 개요

### 1.1 목적
충남스틸 자체 도메인(cnst.co.kr)을 사용하는 기업용 웹메일 서비스 구축

### 1.2 범위
- 웹메일 서비스 설치 및 구성 (webmail.cnst.co.kr)
- 메일 서버 (MTA/MDA) 구축
- 관리자 계정 설정 (cnst@cnst.co.kr)
- SSL/TLS 보안 구성
- 스팸/바이러스 필터링

### 1.3 목표
- 안정적인 기업용 메일 서비스 운영
- 보안이 강화된 메일 환경 구축
- 간편한 관리자 인터페이스 제공

---

## 2. 선정 시스템: Mailcow-dockerized

### 2.1 선정 사유

| 평가 항목 | 점수 | 비고 |
|----------|------|------|
| Docker 환경 호환성 | ⭐⭐⭐⭐⭐ | 기존 인프라와 동일 관리 |
| 올인원 솔루션 | ⭐⭐⭐⭐⭐ | 메일서버+웹메일+관리패널 |
| 관리 용이성 | ⭐⭐⭐⭐⭐ | 웹 기반 관리자 UI |
| 보안 기능 | ⭐⭐⭐⭐⭐ | 스팸/바이러스/DKIM 내장 |
| 커뮤니티 지원 | ⭐⭐⭐⭐ | 활발한 GitHub 커뮤니티 |
| 한글 지원 | ⭐⭐⭐⭐ | UI 한글화 지원 |

### 2.2 Mailcow 구성 요소

```
┌─────────────────────────────────────────────────────────────────┐
│                        Mailcow Stack                             │
├─────────────────────────────────────────────────────────────────┤
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐           │
│  │  Postfix │ │ Dovecot  │ │   SOGo   │ │  Nginx   │           │
│  │  (MTA)   │ │  (MDA)   │ │(웹메일) │ │ (Proxy)  │           │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘           │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐           │
│  │  MySQL   │ │  Redis   │ │ Rspamd   │ │  ClamAV  │           │
│  │  (DB)    │ │ (Cache)  │ │ (스팸)   │ │(바이러스)│           │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘           │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐                        │
│  │  Unbound │ │  ACME    │ │ Watchdog │                        │
│  │  (DNS)   │ │  (SSL)   │ │ (모니터) │                        │
│  └──────────┘ └──────────┘ └──────────┘                        │
└─────────────────────────────────────────────────────────────────┘
```

### 2.3 장단점 분석

#### 장점
1. **완전한 메일 스택**: Postfix, Dovecot, SOGo 통합
2. **보안 내장**: Rspamd(스팸), ClamAV(바이러스), Fail2ban
3. **SSL 자동화**: Let's Encrypt 자동 발급/갱신
4. **관리자 UI**: 도메인, 사용자, 별칭 웹 관리
5. **Docker 기반**: 설치/업데이트/백업 용이
6. **DKIM/SPF/DMARC**: 메일 인증 자동 설정

#### 단점
1. **리소스 소비**: 최소 6GB RAM 권장
2. **포트 충돌**: 기존 서비스와 포트 조정 필요
3. **학습 곡선**: 초기 설정 복잡
4. **저장 공간**: 메일 데이터 증가에 따른 용량 관리

---

## 3. 시스템 아키텍처

### 3.1 네트워크 구성도

```
                          ┌─────────────────┐
                          │    Internet     │
                          │   (사용자 접속)  │
                          └────────┬────────┘
                                   │
                    ┌──────────────┼──────────────┐
                    │              │              │
              cnst.co.kr    webmail.cnst.co.kr    mail.cnst.co.kr
              (웹사이트)      (웹메일 UI)         (SMTP/IMAP)
                    │              │              │
                    ▼              ▼              ▼
           ┌───────────────────────────────────────────────┐
           │           103.124.103.229 (서버)              │
           │  ┌─────────────────┐ ┌─────────────────┐     │
           │  │ project1 Stack  │ │  Mailcow Stack  │     │
           │  │  (기존 웹서비스) │ │  (메일 서비스)   │     │
           │  │                 │ │                 │     │
           │  │  - Nginx (:80)  │ │  - Nginx (:8443)│     │
           │  │  - PHP-FPM      │ │  - Postfix      │     │
           │  │  - MariaDB      │ │  - Dovecot      │     │
           │  │                 │ │  - MySQL (분리) │     │
           │  └─────────────────┘ └─────────────────┘     │
           └───────────────────────────────────────────────┘
```

### 3.2 포트 매핑 계획

| 서비스 | 프로토콜 | 기존 포트 | Mailcow 포트 | 조정 방안 |
|--------|----------|-----------|--------------|-----------|
| HTTP | TCP | 80 | 80 | 리버스 프록시로 분기 |
| HTTPS | TCP | 443 | 443 | 리버스 프록시로 분기 |
| SMTP | TCP | - | 25 | Mailcow 전용 |
| SMTPS | TCP | - | 465 | Mailcow 전용 |
| Submission | TCP | - | 587 | Mailcow 전용 |
| IMAP | TCP | - | 143 | Mailcow 전용 |
| IMAPS | TCP | - | 993 | Mailcow 전용 |
| POP3S | TCP | - | 995 | Mailcow 전용 |
| MySQL | TCP | 3306 | 13306 | 포트 변경 |

### 3.3 DNS 레코드 요구사항

```dns
; A 레코드 (이미 설정됨)
webmail.cnst.co.kr.    IN    A       103.124.103.229
mail.cnst.co.kr.       IN    A       103.124.103.229

; MX 레코드 (필요)
cnst.co.kr.            IN    MX  10  mail.cnst.co.kr.

; SPF 레코드 (필요)
cnst.co.kr.            IN    TXT     "v=spf1 mx a ip4:103.124.103.229 ~all"

; DKIM 레코드 (설치 후 생성)
dkim._domainkey.cnst.co.kr.  IN    TXT    "v=DKIM1; k=rsa; p=..."

; DMARC 레코드 (권장)
_dmarc.cnst.co.kr.     IN    TXT     "v=DMARC1; p=quarantine; rua=mailto:dmarc@cnst.co.kr"

; 역방향 DNS (PTR - 호스팅 업체에 요청)
229.103.124.103.in-addr.arpa.  IN    PTR    mail.cnst.co.kr.
```

---

## 4. 데이터베이스 설계

### 4.1 DB 분리 전략

```
┌─────────────────────────────────────────────────────────────┐
│                    데이터베이스 분리 구조                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│   project1_mysql (기존)          mailcow_mysql (신규)       │
│   ┌─────────────────────┐       ┌─────────────────────┐    │
│   │  project1_db        │       │  mailcow_db         │    │
│   │  ├─ members         │       │  ├─ domain          │    │
│   │  ├─ products        │       │  ├─ mailbox         │    │
│   │  ├─ board_*         │       │  ├─ alias           │    │
│   │  └─ ...             │       │  ├─ quota2          │    │
│   │                     │       │  └─ ...             │    │
│   │  포트: 3306         │       │  포트: 13306        │    │
│   │  볼륨: mysql_data   │       │  볼륨: mailcow_mysql│    │
│   └─────────────────────┘       └─────────────────────┘    │
│                                                             │
│   백업: 독립적 운영                                          │
│   복구: 서로 영향 없음                                       │
└─────────────────────────────────────────────────────────────┘
```

### 4.2 Mailcow 주요 테이블

| 테이블명 | 용도 | 비고 |
|----------|------|------|
| domain | 도메인 관리 | cnst.co.kr |
| mailbox | 메일함 계정 | cnst@cnst.co.kr 등 |
| alias | 메일 별칭 | 포워딩 설정 |
| alias_domain | 도메인 별칭 | 도메인 포워딩 |
| quota2 | 용량 관리 | 메일함 사용량 |
| sender_acl | 발신 권한 | 발신 제한 설정 |
| logs | 로그 기록 | 감사 로그 |

---

## 5. 폴더 구조 설계

### 5.1 Mailcow 설치 경로

```
/home/cnst/www/html/
├── webservice/                    # 기존 프로젝트
│   ├── docker-compose.yml         # 기존 웹서비스
│   ├── html/                      # 웹 파일
│   └── nginx.conf                 # 기존 Nginx
│
└── mailcow/                       # 새로운 메일 서비스
    ├── docker-compose.yml         # Mailcow 설정
    ├── mailcow.conf               # 메인 설정 파일
    ├── data/                      # 데이터 디렉토리
    │   ├── conf/                  # 설정 파일들
    │   │   ├── nginx/             # Nginx 설정
    │   │   ├── postfix/           # Postfix 설정
    │   │   ├── dovecot/           # Dovecot 설정
    │   │   └── rspamd/            # 스팸 필터 설정
    │   ├── db/                    # MySQL 데이터
    │   │   └── mysql/             # MariaDB 볼륨
    │   ├── vmail/                 # 메일 저장소
    │   │   └── cnst.co.kr/        # 도메인별 폴더
    │   │       └── cnst/          # 사용자별 폴더
    │   └── crypt/                 # 암호화 키
    └── backup/                    # 백업 디렉토리
        ├── daily/                 # 일간 백업
        └── weekly/                # 주간 백업
```

### 5.2 디렉토리 권한 설정

```bash
# 메일 데이터 디렉토리
/home/cnst/www/html/mailcow/data/vmail     chmod 755, owner: vmail:vmail
/home/cnst/www/html/mailcow/data/db        chmod 755, owner: mysql:mysql
/home/cnst/www/html/mailcow/data/crypt     chmod 700, owner: root:root
/home/cnst/www/html/mailcow/backup         chmod 750, owner: root:root
```

---

## 6. 초기 계정 설정

### 6.1 관리자 계정

| 항목 | 값 |
|------|-----|
| 관리자 이메일 | cnst@cnst.co.kr |
| 관리자 비밀번호 | manpass!@#4 |
| 관리자 UI 접속 | https://webmail.cnst.co.kr/admin |
| 웹메일 접속 | https://webmail.cnst.co.kr/SOGo |

### 6.2 추가 계정 계획

| 계정 | 용도 | 용량 |
|------|------|------|
| cnst@cnst.co.kr | 대표 관리자 | 10GB |
| info@cnst.co.kr | 일반 문의 | 5GB |
| sales@cnst.co.kr | 영업 문의 | 5GB |
| support@cnst.co.kr | 기술 지원 | 5GB |
| noreply@cnst.co.kr | 발신 전용 | 1GB |

---

## 7. 보안 설정

### 7.1 SSL/TLS 인증서

```yaml
# Let's Encrypt 자동 발급
도메인:
  - mail.cnst.co.kr
  - webmail.cnst.co.kr
  - autodiscover.cnst.co.kr
  - autoconfig.cnst.co.kr

갱신: 자동 (ACME 컨테이너)
```

### 7.2 메일 인증 (SPF/DKIM/DMARC)

| 항목 | 설정 | 효과 |
|------|------|------|
| SPF | ip4:103.124.103.229 | 발신 서버 인증 |
| DKIM | 2048bit RSA | 메일 서명 |
| DMARC | p=quarantine | 인증 실패 시 격리 |

### 7.3 스팸/바이러스 필터

- **Rspamd**: 스팸 점수 기반 필터링
- **ClamAV**: 바이러스 검사
- **Fail2ban**: 로그인 실패 IP 차단

---

## 8. 백업 계획

### 8.1 백업 대상

| 대상 | 주기 | 보관 기간 |
|------|------|-----------|
| MySQL DB | 매일 | 30일 |
| 메일 데이터 (vmail) | 매일 | 30일 |
| 설정 파일 | 매주 | 90일 |
| 전체 스냅샷 | 매월 | 1년 |

### 8.2 백업 스크립트

```bash
#!/bin/bash
# /home/cnst/www/html/mailcow/backup/backup.sh

BACKUP_DIR="/home/cnst/www/html/mailcow/backup/daily"
DATE=$(date +%Y%m%d)

# Mailcow 내장 백업 사용
cd /home/cnst/www/html/mailcow
./helper-scripts/backup_and_restore.sh backup all

# 오래된 백업 삭제 (30일)
find $BACKUP_DIR -type f -mtime +30 -delete
```

---

## 9. 참고 자료

- Mailcow 공식 문서: https://docs.mailcow.email/
- GitHub: https://github.com/mailcow/mailcow-dockerized
- 커뮤니티 포럼: https://community.mailcow.email/

---

**문서 승인**

| 역할 | 이름 | 서명 | 일자 |
|------|------|------|------|
| 작성 | 박성호 | ✓ | 2026-01-07 |
| 기술검토 | 이수진 | | |
| DBA검토 | 홍길동 | | |
| 최종승인 | 김동현 | | |
