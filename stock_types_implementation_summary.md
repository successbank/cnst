# 재고 상태 관리 기능 구현 완료

## 구현 내용

### 1. 데이터베이스 구조
- `products` 테이블에 `stock_types` 컬럼 추가 (TEXT, JSON 형식)
- 기본값: `["일반재고"]`
- 복수 선택 가능: `["일반재고", "장기재고"]`, `["중고"]` 등

### 2. admin_origin_stock.php (관리자 페이지)
- **기능 추가**:
  - 원산지 관리와 함께 재고 상태 관리
  - 각 제품별로 재고 상태 복수 선택 가능 (체크박스)
  - 일괄 변경 기능 유지

- **UI 변경**:
  ```html
  <!-- 재고 상태 체크박스 -->
  □ 일반재고  □ 장기재고  □ 중고
  ```

- **JavaScript 수정**:
  - loadProductsForOrigin 함수에서 stock_types 처리
  - 현재 재고 상태 표시 및 변경 가능한 체크박스 표시

### 3. admin_origin_stock_action.php (처리 파일)
- 원산지와 재고 상태를 동시에 업데이트
- stock_types를 JSON 형식으로 저장
- 빈 값일 경우 기본값 ["일반재고"] 적용

### 4. product_detail.php (제품 상세 페이지)
- **변경 사항**:
  - stock_status 대신 stock_types 표시
  - 복수 재고 상태를 쉼표로 구분하여 표시
  - 예: "재고상태: 일반재고, 장기재고"

- **코드**:
  ```php
  // stock_types 파싱
  $stock_types = [];
  if (!empty($product['stock_types'])) {
      $stock_types = json_decode($product['stock_types'], true);
      if (!is_array($stock_types)) {
          $stock_types = [];
      }
  }
  if (empty($stock_types)) {
      $stock_types = ['일반재고'];
  }
  ```

### 5. products_new.php (제품 목록 페이지)
- **재고 상태 정보를 표시하지 않음** (요청사항에 따라)
- 원산지 정보만 표시

## 재고 상태 옵션
1. **일반재고**: 기본 재고 상태
2. **장기재고**: 장기간 보관된 재고
3. **중고**: 중고 제품

## 테스트 방법

1. **관리자 페이지**: http://localhost:1112/admin/admin_origin_stock.php
   - 카테고리 선택 후 제품별 재고 상태 관리
   - 복수 선택 가능 확인

2. **제품 상세 페이지**: http://localhost:1112/product_detail.php?id=728
   - 재고 상태가 올바르게 표시되는지 확인
   - 복수 재고 상태가 쉼표로 구분되어 표시되는지 확인

3. **테스트 페이지**: http://localhost:1112/test_stock_types.php
   - 테스트 데이터 설정 및 확인

## 주요 특징
- 한 제품이 여러 재고 상태를 가질 수 있음
- 관리자 페이지에서 편리하게 일괄 관리
- 제품 상세 페이지에서 명확하게 표시
- 제품 목록 페이지에서는 표시하지 않음 (요청사항에 따라)