# 충남스틸 웹메일 서비스 구축 - 태스크 목록

## 문서 정보
- **작성일**: 2026년 1월 7일
- **작성자**: 한지원 (문서/QA 기획자)
- **검토자**: 이수진 (기술 PL)
- **버전**: 1.0

---

## 1. 태스크 개요

### 1.1 담당자 배정

| 파트 | 담당자 | 역할 |
|------|--------|------|
| 인프라/Docker | 이민수 (BE) | Mailcow 설치, Docker 구성 |
| DB | 강민정 (DBA) | DB 설정, 백업 구성 |
| 네트워크 | 이민수 (BE) | DNS, 포트, 리버스프록시 |
| 보안 | 박준혁 (BE) | SSL, 방화벽, 인증 |
| 테스트 | 한지원 (QA) | 시나리오 테스트, 검증 |
| 문서화 | 박성호 (기획) | 사용자 가이드, 운영 매뉴얼 |

---

## 2. 태스크 목록 (개발 순서)

### Phase 1: 사전 준비 (Day 1)

| ID | 태스크 | 담당자 | 선행작업 | 상태 |
|----|--------|--------|----------|------|
| T001 | 서버 리소스 확인 (RAM, Disk) | 이민수 | - | ⬜ 대기 |
| T002 | 필요 포트 방화벽 개방 (25, 465, 587, 993, 995) | 이민수 | T001 | ⬜ 대기 |
| T003 | DNS 레코드 추가 요청 (MX, SPF, PTR) | 이민수 | - | ⬜ 대기 |
| T004 | Mailcow 설치 디렉토리 생성 | 이민수 | T001 | ⬜ 대기 |
| T005 | 기존 Docker 환경 백업 | 이민수 | - | ⬜ 대기 |

### Phase 2: Mailcow 설치 (Day 1-2)

| ID | 태스크 | 담당자 | 선행작업 | 상태 |
|----|--------|--------|----------|------|
| T101 | Mailcow 저장소 클론 | 이민수 | T004 | ⬜ 대기 |
| T102 | mailcow.conf 설정 | 이민수 | T101 | ⬜ 대기 |
| T103 | docker-compose.override.yml 생성 (포트 조정) | 이민수 | T102 | ⬜ 대기 |
| T104 | Docker 이미지 풀 및 컨테이너 시작 | 이민수 | T103 | ⬜ 대기 |
| T105 | 컨테이너 상태 확인 | 이민수 | T104 | ⬜ 대기 |

### Phase 3: 네트워크 구성 (Day 2)

| ID | 태스크 | 담당자 | 선행작업 | 상태 |
|----|--------|--------|----------|------|
| T201 | 기존 Nginx에 webmail 리버스프록시 추가 | 이민수 | T105 | ⬜ 대기 |
| T202 | SSL 인증서 발급 (webmail.cnst.co.kr) | 이민수 | T201 | ⬜ 대기 |
| T203 | 포트 매핑 테스트 | 이민수 | T202 | ⬜ 대기 |
| T204 | DNS 레코드 적용 확인 | 이민수 | T003 | ⬜ 대기 |

### Phase 4: 도메인/계정 설정 (Day 2-3)

| ID | 태스크 | 담당자 | 선행작업 | 상태 |
|----|--------|--------|----------|------|
| T301 | 관리자 UI 접속 확인 | 박준혁 | T203 | ⬜ 대기 |
| T302 | 도메인 추가 (cnst.co.kr) | 박준혁 | T301 | ⬜ 대기 |
| T303 | 관리자 계정 생성 (cnst@cnst.co.kr) | 박준혁 | T302 | ⬜ 대기 |
| T304 | 추가 메일 계정 생성 | 박준혁 | T303 | ⬜ 대기 |
| T305 | DKIM 키 생성 및 DNS 등록 | 박준혁 | T302 | ⬜ 대기 |
| T306 | DMARC 레코드 설정 | 박준혁 | T305 | ⬜ 대기 |

### Phase 5: 보안 설정 (Day 3)

| ID | 태스크 | 담당자 | 선행작업 | 상태 |
|----|--------|--------|----------|------|
| T401 | Rspamd 스팸 필터 설정 | 박준혁 | T302 | ⬜ 대기 |
| T402 | ClamAV 바이러스 검사 활성화 | 박준혁 | T302 | ⬜ 대기 |
| T403 | Fail2ban 설정 확인 | 박준혁 | T302 | ⬜ 대기 |
| T404 | 2단계 인증 설정 (관리자) | 박준혁 | T303 | ⬜ 대기 |
| T405 | SSL/TLS 설정 강화 | 박준혁 | T202 | ⬜ 대기 |

### Phase 6: DB 및 백업 설정 (Day 3)

| ID | 태스크 | 담당자 | 선행작업 | 상태 |
|----|--------|--------|----------|------|
| T501 | DB 분리 확인 (포트 13306) | 강민정 | T105 | ⬜ 대기 |
| T502 | DB 성능 튜닝 적용 | 강민정 | T501 | ⬜ 대기 |
| T503 | 백업 스크립트 작성 | 강민정 | T501 | ⬜ 대기 |
| T504 | 백업 크론잡 설정 | 강민정 | T503 | ⬜ 대기 |
| T505 | 백업 복구 테스트 | 강민정 | T504 | ⬜ 대기 |

### Phase 7: 테스트 (Day 4-5)

| ID | 태스크 | 담당자 | 선행작업 | 상태 |
|----|--------|--------|----------|------|
| T601 | 웹메일 로그인 테스트 | 한지원 | T303 | ⬜ 대기 |
| T602 | 메일 발송 테스트 (내부→외부) | 한지원 | T601 | ⬜ 대기 |
| T603 | 메일 수신 테스트 (외부→내부) | 한지원 | T601 | ⬜ 대기 |
| T604 | 첨부파일 테스트 | 한지원 | T601 | ⬜ 대기 |
| T605 | 스팸 필터 테스트 | 한지원 | T401 | ⬜ 대기 |
| T606 | IMAP/SMTP 클라이언트 테스트 | 한지원 | T601 | ⬜ 대기 |
| T607 | 모바일 접속 테스트 | 한지원 | T601 | ⬜ 대기 |
| T608 | 부하 테스트 | 한지원 | T607 | ⬜ 대기 |
| T609 | 보안 테스트 (SPF/DKIM/DMARC 검증) | 한지원 | T306 | ⬜ 대기 |
| T610 | 백업/복구 통합 테스트 | 한지원 | T505 | ⬜ 대기 |

### Phase 8: 문서화 및 마무리 (Day 5)

| ID | 태스크 | 담당자 | 선행작업 | 상태 |
|----|--------|--------|----------|------|
| T701 | 사용자 가이드 작성 | 박성호 | T610 | ⬜ 대기 |
| T702 | 관리자 운영 매뉴얼 작성 | 박성호 | T610 | ⬜ 대기 |
| T703 | 장애 대응 절차서 작성 | 박성호 | T610 | ⬜ 대기 |
| T704 | 최종 검수 및 승인 | 김동현 | T703 | ⬜ 대기 |
| T705 | 서비스 오픈 공지 | 김동현 | T704 | ⬜ 대기 |

---

## 3. 상세 태스크 설명

### T001: 서버 리소스 확인

```bash
# 실행 명령어
# 메모리 확인
free -h

# 디스크 확인
df -h

# 요구사항: RAM 6GB 이상, Disk 50GB 이상 여유 공간
```

### T002: 방화벽 포트 개방

```bash
# 메일 서비스 필수 포트
sudo ufw allow 25/tcp    # SMTP
sudo ufw allow 465/tcp   # SMTPS
sudo ufw allow 587/tcp   # Submission
sudo ufw allow 143/tcp   # IMAP
sudo ufw allow 993/tcp   # IMAPS
sudo ufw allow 995/tcp   # POP3S
sudo ufw allow 4190/tcp  # Sieve

sudo ufw status
```

### T101: Mailcow 저장소 클론

```bash
cd /home/cnst/www/html
git clone https://github.com/mailcow/mailcow-dockerized.git mailcow
cd mailcow
```

### T102: mailcow.conf 설정

```bash
cd /home/cnst/www/html/mailcow

# 설정 생성 스크립트 실행
./generate_config.sh

# 주요 설정 항목:
# MAILCOW_HOSTNAME=mail.cnst.co.kr
# DBNAME=mailcow
# DBUSER=mailcow
# DBPASS=[자동생성]
# DBROOT=[자동생성]
# HTTP_PORT=8080
# HTTP_BIND=0.0.0.0
# HTTPS_PORT=8443
# HTTPS_BIND=0.0.0.0
# SKIP_LETS_ENCRYPT=n
# SKIP_SOGO=n
# SKIP_CLAMD=n
# ADDITIONAL_SAN=webmail.cnst.co.kr
```

### T103: docker-compose.override.yml 생성

```yaml
# /home/cnst/www/html/mailcow/docker-compose.override.yml

version: '2.1'

services:
  nginx-mailcow:
    ports:
      - "8080:80"
      - "8443:443"
    # 기존 project1_web과 포트 충돌 방지

  mysql-mailcow:
    ports:
      - "13306:3306"
    # 기존 project1_mysql과 포트 충돌 방지
```

### T201: 리버스 프록시 설정

```nginx
# /home/cnst/www/html/webservice/nginx.conf 에 추가

# Webmail 리버스 프록시
server {
    listen 80;
    server_name webmail.cnst.co.kr;

    location /.well-known/acme-challenge/ {
        root /usr/share/nginx/html;
    }

    location / {
        return 301 https://$host$request_uri;
    }
}

server {
    listen 443 ssl http2;
    server_name webmail.cnst.co.kr;

    ssl_certificate /etc/letsencrypt/live/webmail.cnst.co.kr/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/webmail.cnst.co.kr/privkey.pem;

    location / {
        proxy_pass https://127.0.0.1:8443;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

---

## 4. 테스트 시나리오

### 시나리오 1: 웹메일 기본 기능

| 단계 | 테스트 내용 | 예상 결과 | 실제 결과 | 통과 |
|------|------------|-----------|-----------|------|
| 1 | https://webmail.cnst.co.kr 접속 | 로그인 페이지 표시 | | |
| 2 | cnst@cnst.co.kr / manpass!@#4 로그인 | 메일함 진입 | | |
| 3 | 새 메일 작성 (외부 주소로) | 발송 성공 | | |
| 4 | 메일 수신 확인 | 수신함에 표시 | | |
| 5 | 첨부파일 (5MB) 발송 | 발송 성공 | | |
| 6 | 로그아웃 | 로그인 페이지로 이동 | | |

### 시나리오 2: 보안 기능

| 단계 | 테스트 내용 | 예상 결과 | 실제 결과 | 통과 |
|------|------------|-----------|-----------|------|
| 1 | 잘못된 비밀번호 5회 시도 | IP 차단 | | |
| 2 | SPF 검증 (mail-tester.com) | Pass | | |
| 3 | DKIM 검증 | Pass | | |
| 4 | DMARC 검증 | Pass | | |
| 5 | 스팸 메일 수신 테스트 | 스팸함으로 분류 | | |

### 시나리오 3: 메일 클라이언트 연동

| 단계 | 테스트 내용 | 예상 결과 | 실제 결과 | 통과 |
|------|------------|-----------|-----------|------|
| 1 | Outlook 설정 (IMAP) | 연결 성공 | | |
| 2 | Thunderbird 설정 (IMAP) | 연결 성공 | | |
| 3 | iPhone Mail 앱 설정 | 연결 성공 | | |
| 4 | Android Gmail 앱 설정 | 연결 성공 | | |

### 시나리오 4: 관리자 기능

| 단계 | 테스트 내용 | 예상 결과 | 실제 결과 | 통과 |
|------|------------|-----------|-----------|------|
| 1 | /admin 접속 | 관리자 로그인 | | |
| 2 | 새 사용자 생성 | 생성 성공 | | |
| 3 | 용량 설정 변경 | 적용됨 | | |
| 4 | 사용자 비활성화 | 로그인 불가 | | |
| 5 | 별칭 생성 | 별칭으로 수신 가능 | | |

### 시나리오 5: 백업/복구

| 단계 | 테스트 내용 | 예상 결과 | 실제 결과 | 통과 |
|------|------------|-----------|-----------|------|
| 1 | 백업 스크립트 수동 실행 | 백업 파일 생성 | | |
| 2 | 테스트 메일 삭제 | 삭제됨 | | |
| 3 | 백업에서 복구 | 메일 복원됨 | | |
| 4 | 크론잡 백업 확인 | 자동 백업 생성 | | |

---

## 5. 위험 요소 및 대응 방안

| 위험 요소 | 영향도 | 발생확률 | 대응 방안 |
|----------|--------|----------|-----------|
| 서버 RAM 부족 | 높음 | 중간 | 서버 업그레이드 또는 경량화 설정 |
| 포트 충돌 | 높음 | 높음 | docker-compose.override.yml로 포트 변경 |
| DNS 레코드 미적용 | 높음 | 중간 | 사전 DNS 설정, TTL 낮게 설정 |
| SSL 인증서 발급 실패 | 중간 | 낮음 | 수동 발급 또는 기존 인증서 확장 |
| 스팸 필터 오탐 | 중간 | 중간 | 화이트리스트 설정, 임계값 조정 |
| 메일 배달 실패 | 높음 | 중간 | SPF/DKIM/DMARC 정확히 설정 |

---

## 6. 완료 기준

### 필수 완료 항목 (Must Have)
- [ ] 웹메일 로그인 가능
- [ ] 내부/외부 메일 발송/수신 가능
- [ ] SSL/TLS 적용 완료
- [ ] 관리자 계정 설정 완료
- [ ] 백업 설정 완료

### 권장 완료 항목 (Should Have)
- [ ] SPF/DKIM/DMARC 검증 통과
- [ ] 스팸 필터 활성화
- [ ] 바이러스 검사 활성화
- [ ] 사용자 가이드 작성

### 선택 완료 항목 (Nice to Have)
- [ ] 2단계 인증 설정
- [ ] 모바일 앱 연동 테스트
- [ ] 모니터링 대시보드 구성

---

## 문서 승인

| 역할 | 이름 | 서명 | 일자 |
|------|------|------|------|
| 작성 | 한지원 | ✓ | 2026-01-07 |
| 검토 | 이수진 | | |
| 승인 | 김동현 | | |
