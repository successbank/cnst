# 중계판매 게시판 프라이버시 보호 기능 구현
날짜: 2025-07-14 09:33:56

## 구현 내용

### 1. 업체명 마스킹 처리
- 한글: 첫 글자만 표시, 나머지는 * (예: "삼성철강" → "삼**")
- 영문: 첫 두 글자만 표시, 나머지는 * (예: "Samsung" → "Sa*****")

### 2. 게시글 접근 권한 관리
- 작성자 본인과 관리자만 게시글 내용 확인 가능
- 로그인한 경우: member_id로 본인 확인
- 비로그인 작성: 비밀번호로 확인
- 다른 사용자: 접근 불가

### 3. 게시글 목록 표시
- 제목: 모든 사용자가 확인 가능
- 내용: 작성자와 관리자만 확인 가능

### 4. 비밀번호 확인
- 중계판매 게시판에서는 비밀번호 오류 메시지 미표시
- 비밀번호 확인 기능은 유지

## 변경된 파일

1. **consignment.php**
   - maskCompanyName() 함수 추가
   - 업체명 마스킹 적용
   - member_check.php 포함
   - 접근 권한 오류 메시지 추가

2. **board_write.php**
   - member_check.php 포함
   - 로그인 사용자 비밀번호 선택 입력
   - member_id 저장 로직 추가

3. **board_view.php**
   - member_check.php 포함
   - 중계판매 게시판 접근 권한 확인 로직
   - 비밀번호 오류 메시지 조건부 표시

4. **board/board_template.php**
   - member_id 필드 저장 로직 추가

5. **member_check.php**
   - isAdmin() 함수 추가

## 데이터베이스 변경사항

### 추가된 컬럼
- board_consignment.member_id: 작성자 회원 ID 저장

### SQL 스크립트
- add_member_id_column.php: member_id 컬럼 추가 스크립트
- add_post_password_columns.php: post_password 컬럼 추가 스크립트 (미사용)

## 사용 방법

1. **member_id 컬럼 추가**
   ```
   http://211.248.112.67:1112/add_member_id_column.php
   ```

2. **로그인 사용자**
   - 게시글 작성 시 자동으로 member_id 저장
   - 본인 글은 비밀번호 없이 확인 가능

3. **비로그인 사용자**
   - 게시글 작성 시 비밀번호 필수
   - 조회 시 비밀번호 입력

4. **관리자**
   - 모든 게시글 확인 가능