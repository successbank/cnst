# 충남스틸 홈페이지 개발 이력

## 프로젝트 정보
- **프로젝트명**: 충남스틸 홈페이지
- **개발 언어**: Node.js + TypeScript
- **데이터베이스**: MySQL
- **최대 화면폭**: 1280px (반응형)
- **구조**: Header, Contents, Footer 모듈화

---

## 개발 이력

### 2024-01-09

#### 초기 설정 완료
- Node.js + TypeScript 환경 구성
- Express 서버 설정 (포트: 1112)
- 기본 폴더 구조 생성
  - `/src`: TypeScript 소스 파일
  - `/dist`: 컴파일된 JavaScript 파일
  - `/css`: 스타일시트
  - `/img`: 이미지 파일
  - `/js`: 클라이언트 JavaScript

#### 프로젝트 구조 변경
- public 폴더 내용을 루트로 이동
- 정적 파일 서빙 경로 수정
- index.html 자동 인식 설정

#### 개발 요구사항 정의
- 반응형 디자인 (최대폭 1280px)
- 컴포넌트 모듈화 구조
- MySQL 데이터베이스 연동 계획
- TypeScript 타입 시스템 적용

#### 게시판 기능 구현
- 견적문의 폼 페이지 개발
- 공지사항 목록 페이지 개발
- 철강뉴스 목록 페이지 개발
- 컴포넌트 모듈화 적용 (header, footer 분리)
- 드롭다운 메뉴 추가
- board.css 스타일시트 생성
- TypeScript 인터페이스 정의 (Board, Quote, Notice, News)

#### 데이터베이스 연동
- MySQL 데이터베이스 설정 파일 생성
- 데이터베이스 스키마 및 테이블 생성 (boards, quotes, notices, news)
- BoardService 클래스 구현 (CRUD 기능)
- API 라우트 구현 (/api/quote, /api/boards/:boardType, /api/board/:id)
- 환경변수 설정 (.env 파일)
- 데이터베이스 연결 테스트 기능 추가
- Docker MySQL 컨테이너 연동 (project1_mysql)
- 데이터베이스 연결 성공 및 테이블 생성 완료

#### UI/UX 개선
- 드롭다운 메뉴 애니메이션 개선
- JavaScript 파일 추가 (main.js)
- 모바일 메뉴 토글 기능
- 견적문의 폼 AJAX 제출 기능

#### Header/Footer 분리
- includes 디렉토리 생성
- header.html, footer.html 파일로 분리
- TemplateService 구현 (템플릿 렌더링 서비스)
- base.html 템플릿 생성
- 게시판 라우트 템플릿 엔진 적용
- index.html, about.html에도 include 시스템 적용
- 모든 정적 페이지를 서버에서 처리하도록 변경

---

## 향후 계획

### 단기 계획
1. CSS 반응형 디자인 수정 (최대폭 1280px 제한)
2. Header, Contents, Footer 컴포넌트 분리
3. TypeScript 인터페이스 정의
4. MySQL 연결 모듈 개발

### 중장기 계획
1. 사용자 인증 시스템
2. 제품 관리 시스템
3. 게시판 기능
4. 관리자 페이지

---

## 기술 스택
- **Backend**: Node.js, Express, TypeScript
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Build Tool**: TypeScript Compiler
- **Package Manager**: npm

---

## 명령어
- `npm run dev`: 개발 서버 실행
- `npm run build`: TypeScript 컴파일
- `npm start`: 프로덕션 서버 실행