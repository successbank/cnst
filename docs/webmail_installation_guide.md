# 충남스틸 웹메일 서비스 설치 가이드

## 문서 정보
- **작성일**: 2026년 1월 7일
- **작성자**: 이민수 (백엔드 - 인프라)
- **버전**: 1.0

---

## 1. 사전 준비 완료 사항

### 1.1 완료된 작업
- [x] Mailcow 저장소 클론 완료
- [x] mailcow.conf 설정 파일 생성 완료
- [x] docker-compose.override.yml 생성 완료 (포트 충돌 방지)
- [x] 자체 서명 SSL 인증서 생성 완료
- [x] 설치 스크립트 생성 완료
- [x] Nginx 리버스 프록시 설정 파일 생성 완료

### 1.2 서버 리소스 확인 결과
- **RAM**: 62GB (권장 6GB 충족)
- **Disk**: 413GB 여유 (충분)
- **CPU**: 16코어 (충분)
- **OS**: Ubuntu 24.04 LTS

---

## 2. 설치 순서 (root 권한 필요)

### Step 1: root로 로그인
```bash
# SSH로 root 접속 또는
su - root
# 비밀번호: manpass!@#4
```

### Step 2: Mailcow 설치 스크립트 실행
```bash
bash /home/cnst/www/html/mailcow/install_mailcow.sh
```

또는 수동으로:

```bash
cd /home/cnst/www/html/mailcow

# Docker 이미지 다운로드 (약 5-10분 소요)
docker compose pull

# 컨테이너 시작
docker compose up -d

# 상태 확인
docker compose ps
```

### Step 3: 방화벽 포트 개방
```bash
# UFW 사용 시
ufw allow 25/tcp    # SMTP
ufw allow 465/tcp   # SMTPS
ufw allow 587/tcp   # Submission
ufw allow 143/tcp   # IMAP
ufw allow 993/tcp   # IMAPS
ufw allow 995/tcp   # POP3S
ufw allow 4190/tcp  # Sieve
ufw allow 8080/tcp  # HTTP (Mailcow)
ufw allow 8443/tcp  # HTTPS (Mailcow)

ufw status
```

### Step 4: SSL 인증서 발급 (Let's Encrypt)
```bash
# webmail.cnst.co.kr 및 mail.cnst.co.kr 인증서 발급
certbot certonly --webroot -w /usr/share/nginx/html \
  -d webmail.cnst.co.kr \
  -d mail.cnst.co.kr \
  -d autodiscover.cnst.co.kr \
  -d autoconfig.cnst.co.kr

# 또는 기존 인증서에 SAN 추가
certbot certonly --expand --webroot -w /usr/share/nginx/html \
  -d cnst.co.kr \
  -d www.cnst.co.kr \
  -d webmail.cnst.co.kr \
  -d mail.cnst.co.kr
```

### Step 5: Nginx 리버스 프록시 설정
```bash
# 설정 파일을 기존 nginx.conf에 추가
cat /home/cnst/www/html/mailcow/nginx_webmail_proxy.conf >> /home/cnst/www/html/webservice/nginx.conf

# 또는 별도 파일로 복사 (Docker 볼륨 마운트 필요)
# Nginx 재시작
cd /home/cnst/www/html/webservice
docker compose restart web
```

### Step 6: 관리자 UI 접속 및 초기 설정
```
URL: https://103.124.103.229:8443/
또는: https://mail.cnst.co.kr:8443/

초기 로그인:
- 사용자명: admin
- 비밀번호: moohoo (변경 필수!)
```

---

## 3. 관리자 UI 초기 설정

### 3.1 비밀번호 변경
1. 로그인 후 우측 상단 admin 클릭
2. "Edit administrator details" 선택
3. 새 비밀번호 설정: `manpass!@#4`
4. Save 클릭

### 3.2 도메인 추가
1. Configuration → Mail Setup → Domains
2. "Add domain" 클릭
3. 입력:
   - Domain: `cnst.co.kr`
   - Description: `충남스틸`
   - Max. mailboxes: `50`
   - Default mailbox quota: `5120` (5GB)
4. Save 클릭

### 3.3 관리자 메일 계정 생성
1. Configuration → Mail Setup → Mailboxes
2. "Add mailbox" 클릭
3. 입력:
   - Username: `cnst`
   - Domain: `cnst.co.kr`
   - Full name: `충남스틸 관리자`
   - Password: `manpass!@#4`
   - Quota (MiB): `10240` (10GB)
4. Save 클릭

### 3.4 추가 메일 계정 생성
| 계정 | 용도 | 용량 |
|------|------|------|
| info@cnst.co.kr | 일반 문의 | 5GB |
| sales@cnst.co.kr | 영업 문의 | 5GB |
| support@cnst.co.kr | 기술 지원 | 5GB |
| noreply@cnst.co.kr | 발신 전용 | 1GB |

---

## 4. DNS 설정 (호스팅 업체에서 설정)

### 4.1 필수 레코드
```dns
; A 레코드 (이미 설정됨)
mail.cnst.co.kr.       IN    A       103.124.103.229
webmail.cnst.co.kr.    IN    A       103.124.103.229

; MX 레코드 (필수 - 메일 수신)
cnst.co.kr.            IN    MX  10  mail.cnst.co.kr.

; SPF 레코드 (발신자 인증)
cnst.co.kr.            IN    TXT     "v=spf1 mx a ip4:103.124.103.229 ~all"
```

### 4.2 DKIM 레코드 (Mailcow에서 생성)
1. Mailcow 관리자 UI → Configuration → ARC/DKIM keys
2. "dkim" 키 생성/확인
3. 표시된 TXT 레코드를 DNS에 추가:
```dns
dkim._domainkey.cnst.co.kr.  IN    TXT    "v=DKIM1; k=rsa; p=[키값]"
```

### 4.3 DMARC 레코드 (권장)
```dns
_dmarc.cnst.co.kr.     IN    TXT     "v=DMARC1; p=quarantine; rua=mailto:dmarc@cnst.co.kr"
```

### 4.4 PTR 레코드 (역방향 DNS - 호스팅 업체에 요청)
```
229.103.124.103.in-addr.arpa.  PTR  mail.cnst.co.kr.
```

### 4.5 Autodiscover/Autoconfig (이메일 클라이언트 자동 설정)
```dns
autodiscover.cnst.co.kr.  IN    A       103.124.103.229
autoconfig.cnst.co.kr.    IN    A       103.124.103.229
```

---

## 5. 이메일 클라이언트 설정

### 5.1 수신 서버 (IMAP)
| 항목 | 값 |
|------|-----|
| 서버 | mail.cnst.co.kr |
| 포트 | 993 |
| 보안 | SSL/TLS |
| 인증 | 일반 비밀번호 |

### 5.2 발신 서버 (SMTP)
| 항목 | 값 |
|------|-----|
| 서버 | mail.cnst.co.kr |
| 포트 | 587 |
| 보안 | STARTTLS |
| 인증 | 일반 비밀번호 |

---

## 6. 유지보수

### 6.1 로그 확인
```bash
cd /home/cnst/www/html/mailcow

# 전체 로그
docker compose logs -f

# 특정 서비스 로그
docker compose logs -f postfix-mailcow
docker compose logs -f dovecot-mailcow
docker compose logs -f nginx-mailcow
```

### 6.2 서비스 재시작
```bash
cd /home/cnst/www/html/mailcow
docker compose restart
```

### 6.3 업데이트
```bash
cd /home/cnst/www/html/mailcow
./update.sh
```

### 6.4 백업
```bash
cd /home/cnst/www/html/mailcow
./helper-scripts/backup_and_restore.sh backup all
```

### 6.5 복구
```bash
cd /home/cnst/www/html/mailcow
./helper-scripts/backup_and_restore.sh restore
```

---

## 7. 문제 해결

### 7.1 컨테이너가 시작되지 않음
```bash
# 로그 확인
docker compose logs

# 설정 확인
cat mailcow.conf

# 재시작
docker compose down
docker compose up -d
```

### 7.2 메일 발송 실패
1. DNS MX/SPF/DKIM 레코드 확인
2. 방화벽 25, 587 포트 확인
3. Postfix 로그 확인: `docker compose logs postfix-mailcow`

### 7.3 메일 수신 실패
1. DNS MX 레코드 확인
2. 방화벽 25 포트 확인
3. PTR 레코드 확인

### 7.4 웹메일 접속 불가
1. Nginx 로그 확인: `docker compose logs nginx-mailcow`
2. 포트 8443 방화벽 확인
3. SSL 인증서 확인

---

## 8. 체크리스트

### 설치 완료 체크리스트
- [ ] Docker 이미지 다운로드 완료
- [ ] 모든 컨테이너 실행 중
- [ ] 방화벽 포트 개방
- [ ] SSL 인증서 발급
- [ ] Nginx 리버스 프록시 설정
- [ ] 관리자 비밀번호 변경
- [ ] 도메인 추가 (cnst.co.kr)
- [ ] 관리자 계정 생성 (cnst@cnst.co.kr)
- [ ] DNS MX 레코드 설정
- [ ] DNS SPF 레코드 설정
- [ ] DNS DKIM 레코드 설정
- [ ] 메일 발송 테스트 성공
- [ ] 메일 수신 테스트 성공
- [ ] 웹메일 로그인 테스트 성공

---

## 문서 승인

| 역할 | 이름 | 서명 | 일자 |
|------|------|------|------|
| 작성 | 이민수 | ✓ | 2026-01-07 |
| 검토 | 장현우 | | |
| 승인 | 김동현 | | |
