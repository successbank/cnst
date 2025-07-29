# 충남스틸 웹사이트 작업 내역

## 2025-07-29 작업 내역

### 1. 구조관 제품 상세페이지 규격 선택 기능 추가
- **대상 제품**: 구조관 (structural-pipe) 카테고리
- **작업 내용**:
  - 규격 선택 드롭다운 추가 (규격과 단위중량 함께 표시)
  - 선택한 규격에 따른 단위중량 자동 업데이트
  - 가격 계산 기능 연동
  - addToQuoteCart 함수에 규격 정보 저장 기능 추가
- **파일**: `/home/successbank/projects/docker/project1/html/product_detail.php`
- **문제 해결**: PHP 구문 오류 수정 (endif 누락)

### 2. 전선관 제품 상세페이지 기능 추가
- **대상 제품**: 전선관 (conduit-pipe) 카테고리
- **작업 내용**:
  - 재질 선택 버튼 추가 (흑관, 백관, HGI)
  - 규격 선택 드롭다운 추가 (16BC ~ 104BC)
  - 각 규격별 단위중량(kg/m) 표시
  - JavaScript로 선택 상태 관리 및 계산 기능 구현
  - 견적 카트 연동 시 재질/규격 정보 저장
- **파일**: `/home/successbank/projects/docker/project1/html/product_detail.php`

### 3. 병합 충돌 해결
- **문제**: Git 병합 충돌 표시 (>>>>>>> 4779a5cf4f27bbf1862cfc06a4f3b51bbbb26bb7)
- **해결**: 충돌 표시 제거 및 코드 정리
- **파일**: `/home/successbank/projects/docker/project1/html/product_detail.php`

### 4. Footer 이미지 추가
- **작업 내용**:
  - footer에 below_banner.fw.png 이미지 추가
  - 가운데 정렬 및 반응형 처리
  - 이미지 크기 50%로 축소
- **파일**: `/home/successbank/projects/docker/project1/html/tail.php`
- **스타일**: `max-width: 50%; height: auto; display: block; margin: 0 auto 20px;`

## 기술 스택
- PHP 7.x
- MySQL (PDO 사용)
- JavaScript (Vanilla JS)
- HTML5/CSS3

## 데이터베이스 구조
### products 테이블
- id, product_name, specifications, category_code, price, unit 등

### unit_weights 테이블
- specification, unit_weight, is_active

### 카테고리 코드
- `structural-pipe`: 구조관
- `conduit-pipe`: 전선관
- `rebar`: 철근

## 주요 함수
### JavaScript
- `updateStructuralWeight()`: 구조관 규격 선택 시 단위중량 업데이트
- `updateConduitWeight()`: 전선관 규격 선택 시 단위중량 업데이트
- `calculateConduitPrice()`: 전선관 가격 계산
- `addToQuoteCart()`: 견적 카트에 제품 추가

## 참고사항
- 재질 선택은 참고용이며 가격에 영향을 주지 않음
- 계산식: 단위중량(kg/m) × 길이(m) × 수량(본) × 기준단가(원/TON)
- 다른 카테고리 제품에는 영향 없도록 조건문으로 처리

## 생성된 테스트 파일
- show_structural_matching.php
- show_structural_unit_weights.php
- check_structural_pipe.php
- test_structural_pipe_950.php
- check_conduit_pipe.php
- check_conduit_calculations.php
- show_conduit_pipe_specs.php