# 패스워드 bcrypt 해싱 가이드

## 문제
- member.xls에서 가져온 회원들의 패스워드가 평문으로 저장됨
- 로그인 시스템은 bcrypt 해싱을 사용 (password_verify 함수)
- 평문 패스워드로는 로그인 불가

## 해결 방법
평문 패스워드를 bcrypt로 해싱하여 저장

## 작업 순서

### 1. 현재 상태 확인
```bash
php show_member_passwords.php
```

### 2. 패스워드 백업 (중요!)
```bash
php backup_passwords.php
```
- 백업 파일: `backup_member_passwords_[날짜].csv`

### 3. 로그인 테스트 (해싱 전)
```bash
php test_bcrypt_login.php
```

### 4. 평문 패스워드를 bcrypt로 해싱
```bash
php hash_plain_passwords.php
```
- 프롬프트에서 'yes' 입력하여 확인
- 약 8,946개의 패스워드가 해싱됨

### 5. 로그인 테스트 (해싱 후)
```bash
php test_bcrypt_login.php
```

## 주의사항
- 이 작업은 되돌릴 수 없음 (백업 필수!)
- 해싱 후에는 원본 평문 패스워드를 알 수 없음
- member.xls의 평문 패스워드는 보관 필요

## 테스트 계정
- ID: lovearum / PW: 73370000
- ID: xton11 / PW: ddd000
- ID: a7846289 / PW: a11111