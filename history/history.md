# 충남스틸 웹사이트 개발 History

## 2025-07-10 AM Session (Continued from previous context)

### Session Overview
This session continued from a previous conversation that ran out of context. The session included multiple feature implementations and bug fixes.

### Major Implementations

#### 1. AJAX-Based Inline Forms
- Implemented page-less quote inquiries and consignment applications in mypage
- Created AJAX handlers for form submissions
- Modified UI to eliminate duplicate buttons

#### 2. Admin Panel Enhancements
- Fixed multiple errors in admin_news.php
- Implemented modal popups for viewing content
- Standardized button designs across all admin pages
- Added drag-and-drop image upload functionality
- Created comprehensive access statistics dashboard

#### 3. Navigation and Responsive Design
- Fixed hamburger menu for mobile screens
- Resolved navigation breakpoints at 837px, 926px, 997px, 1042px
- Ensured consistent layout width across pages

#### 4. Kakao Talk Integration
- Added tab navigation to Kakao management pages
- Implemented Chart.js visualizations for message statistics
- Created extended test data (501 records)
- Updated chart colors to Samsung style

#### 5. Statistics Dashboard
- Created admin_statistics.php with:
  - Visitor statistics (daily, monthly)
  - Time-based charts (hourly, daily, monthly)
  - Page-by-page analytics
  - Device and browser statistics
  - Regional access data for Korean provinces
  - Day of week and hourly patterns
  - Referrer source analysis

#### 6. Image Upload System
- Drag-and-drop functionality
- Progress bar during upload
- Image preview with removal
- Auto-insertion into content
- Server-side resizing (max 1200x1200)
- Support for JPG, PNG, GIF (max 5MB)

### Files Created/Modified
- admin/admin_notices.php (major updates)
- admin/admin_statistics.php (new)
- admin/upload_image.php (new)
- admin/upload_image_debug.php (new)
- admin/ajax/get_notice.php (new)
- admin/ajax/get_news.php (new)
- board_view.php (updated for HTML content)
- css/board-style.css (added image styles)

### Backup Created
- Location: /history/20250710_am1030/
- Full backup: backup_full.tar.gz
- Individual directories and files copied

---

# 충남스틸 웹사이트 개발 History

## 프로젝트 정보
- **URL**: http://211.248.112.67:1112/
- **개발 환경**: Docker (nginx + PHP-FPM + MySQL)
- **디자인 컨셉**: Samsung Service Center 스타일
- **개발 기간**: 2025년 7월 9일

## 1. 초기 설정 및 문제 해결

### 1.1 403 Forbidden 오류 해결
- **문제**: nginx 서버에서 403 Forbidden 오류 발생
- **해결**: 
  - index.html 파일 생성
  - nginx.conf 설정 파일 생성 및 PHP-FPM 연동
  - Docker 컨테이너에 설정 복사: `docker cp nginx.conf project1_web:/etc/nginx/conf.d/default.conf`

### 1.2 PHP 설정
- **PHP 버전**: 8.3.6 (PHP-FPM)
- **Docker 구성**:
  - nginx 컨테이너: project1_web
  - PHP-FPM 컨테이너: project1_php
  - MySQL 컨테이너: project1_mysql

## 2. 데이터베이스 설정

### 2.1 MySQL 연결 정보
- **파일**: `/home/successbank/projects/docker/project1/html/db.php`
- **설정**:
  ```php
  DB_HOST: project1_mysql
  DB_PORT: 3306
  DB_NAME: project1_db
  DB_USER: root
  DB_PASS: [REMOVED]
  ```

### 2.2 데이터베이스 테이블
- board_quote (견적문의)
- board_notice (공지사항)
- board_news (철강뉴스)

## 3. 파일 구조

### 3.1 주요 파일
```
/home/successbank/projects/docker/project1/html/
├── index.php          # 메인 페이지
├── head.php           # 공통 헤더 (Samsung 스타일)
├── tail.php           # 공통 푸터
├── db.php             # DB 연결 및 공통 함수
├── about.php          # 회사소개
├── location.php       # 오시는길
├── products.php       # 제품소개
├── quote.php          # 견적문의 목록
├── notice.php         # 공지사항 목록
├── news.php           # 철강뉴스 목록
├── board_write.php    # 게시글 작성
├── board_view.php     # 게시글 보기
├── board_edit.php     # 게시글 수정
├── board_delete.php   # 게시글 삭제
├── init_db.php        # DB 초기화 스크립트
├── css/
│   ├── samsung-style.css  # Samsung 스타일 CSS
│   └── board-style.css    # 게시판 공통 CSS
├── board/
│   └── board_template.php # 게시판 템플릿 클래스
├── uploads/           # 파일 업로드 디렉토리
│   ├── quote/
│   ├── notice/
│   └── news/
└── history/
    └── history.md     # 개발 히스토리 (현재 파일)
```

### 3.2 디자인 시스템
- **컬러 스키마**:
  - Primary Blue: #1428A0
  - Light Blue: #E8F0FE
  - Text Primary: #1D1D1F
  - Border: #E5E5E7

## 4. 주요 기능 구현

### 4.1 게시판 시스템
- **템플릿 기반 설계**: BoardTemplate 클래스로 3개 게시판 통합 관리
- **기능**:
  - 게시글 작성/수정/삭제
  - 파일 첨부 (최대 10MB)
  - 비밀글 기능 (견적문의)
  - 검색 기능
  - 페이지네이션
  - 중요공지 표시

### 4.2 반응형 디자인
- 모바일/태블릿 대응
- Samsung Service Center 디자인 적용
- 카드 기반 레이아웃

### 4.3 보안 기능
- 비밀번호 기반 수정/삭제
- 파일 업로드 확장자 제한
- XSS 방지 (escape 함수)
- SQL Injection 방지 (PDO prepared statements)

## 5. 문제 해결 내역

### 5.1 파일 업로드 권한 문제
- **문제**: uploads 디렉토리 쓰기 권한 오류
- **해결**:
  ```bash
  docker exec project1_web mkdir -p /var/www/html/uploads/quote
  docker exec project1_web chmod -R 777 /var/www/html/uploads
  docker exec project1_php chmod -R 777 /var/www/html/uploads
  ```

### 5.2 Header Already Sent 오류
- **문제**: 파일 업로드 시 리다이렉션 오류
- **해결**: ob_start() / ob_end_clean() 사용

## 6. 데이터 입력 도구

### 6.1 공지사항 입력 스크립트
- `import_notices.php`: 샘플 공지사항 자동 입력
- `manual_import_notice.php`: 수동 입력 폼

## 7. 환경 설정 정보

### 7.1 서버 정보
- **IP**: 211.248.112.67
- **Port**: 1112
- **OS**: Linux 6.8.0-63-generic
- **sudo 패스워드**: manpass!@#4

### 7.2 Docker 컨테이너
- project1_web (nginx)
- project1_php (PHP-FPM)
- project1_mysql (MySQL)

## 8. 추가 개발 필요 사항

### 8.1 미구현 기능
- 회원가입/로그인 시스템
- 관리자 페이지
- 댓글 시스템
- 답변 기능 (견적문의)
- 실제 지도 API 연동
- 이메일 발송 기능

### 8.2 개선 필요 사항
- 파일 업로드 진행률 표시
- 이미지 썸네일 생성
- 게시글 임시저장
- 통계/분석 기능

## 9. 참고 사항

### 9.1 테스트 계정
- 게시글 비밀번호: admin123 (테스트용)

### 9.2 주요 명령어
```bash
# Docker 로그 확인
docker logs project1_web --tail 50

# 파일 권한 설정
docker exec project1_web chmod -R 777 /var/www/html/uploads

# nginx 재시작
docker exec project1_web nginx -s reload

# DB 접속
docker exec -it project1_mysql mysql -u root -p'[PASSWORD]' project1_db
```

### 9.3 디버깅 페이지
- `/test_upload.php`: 업로드 디렉토리 테스트
- `/test_upload2.php`: 상세 업로드 테스트
- `/test_db.php`: DB 연결 테스트
- `/phpinfo.php`: PHP 설정 정보

## 10. 코드 스니펫

### 10.1 게시글 작성 예제
```php
$board = new BoardTemplate($db, 'notice');
$data = [
    'title' => '제목',
    'content' => '내용',
    'writer' => '작성자',
    'password' => '비밀번호',
    'attachment' => ''
];
$board->writePost($data);
```

### 10.2 파일 업로드 예제
```php
if (!empty($_FILES['attachment']['name'])) {
    $uploadDir = 'uploads/' . $boardType . '/';
    $uploadedFile = uploadFile($_FILES['attachment'], $uploadDir);
    if ($uploadedFile) {
        $data['attachment'] = $uploadedFile;
    }
}
```

---

**마지막 업데이트**: 2025년 7월 9일
**작성자**: Claude Code Assistant