# 원산지 선택 기능 구현 완료

## 구현 내용

### 1. products_new.php (제품 목록 페이지)
- **변경 사항**:
  - 원산지 선택 UI 제거
  - 원산지 정보를 텍스트로만 표시
  - 모든 제품에 대해 available_origins 파싱 추가
  - CSS 스타일 업데이트 (origin-info 클래스)

- **주요 코드**:
  ```php
  // 모든 제품에 available_origins_array 파싱
  foreach ($products as &$product) {
      if (!empty($product['available_origins'])) {
          $product['available_origins_array'] = json_decode($product['available_origins'], true);
      } else {
          $product['available_origins_array'] = [$product['origin']];
      }
  }
  ```

  ```php
  // 원산지 정보 표시 (선택 UI 없음)
  <?php if (isset($product['available_origins_array']) && !empty($product['available_origins_array'])): ?>
  <div class="origin-info">
      <span class="origin-info-label">원산지: </span>
      <span class="origin-info-text"><?php echo implode(', ', array_map('escape', $product['available_origins_array'])); ?></span>
  </div>
  <?php endif; ?>
  ```

### 2. product_detail.php (제품 상세 페이지)
- **변경 사항**:
  - available_origins 파싱 로직 추가
  - 복수 원산지가 있을 경우 선택 드롭다운 표시
  - 단일 원산지만 있을 경우 텍스트로 표시
  - 선택된 원산지를 sessionStorage에 저장
  - CSS 스타일 추가 (origin-select 클래스)

- **주요 코드**:
  ```php
  // available_origins 파싱
  $available_origins = [];
  if (!empty($product['available_origins'])) {
      $available_origins = json_decode($product['available_origins'], true);
      if (!is_array($available_origins)) {
          $available_origins = [];
      }
  }
  if (empty($available_origins)) {
      $available_origins = [$product['origin']];
  }
  ```

  ```php
  // 원산지 선택 UI
  <?php if (count($available_origins) > 1): ?>
  <select id="origin-select" name="origin" class="origin-select">
      <?php foreach ($available_origins as $origin): ?>
      <option value="<?php echo escape($origin); ?>" <?php echo $origin === $product['origin'] ? 'selected' : ''; ?>>
          <?php echo escape($origin); ?>
      </option>
      <?php endforeach; ?>
  </select>
  <?php else: ?>
  <?php echo escape($available_origins[0]); ?>
  <?php endif; ?>
  ```

### 3. JavaScript 기능
- 원산지 선택 시 sessionStorage에 저장
- 페이지 재방문 시 이전 선택 복원
- 추후 가격 업데이트 등 추가 기능 구현 가능

## 테스트 방법

1. **제품 목록 페이지**: http://localhost:1112/products_new.php?category=rebar
   - 원산지 정보가 텍스트로만 표시되는지 확인

2. **제품 상세 페이지**: http://localhost:1112/product_detail.php?id=728
   - 복수 원산지가 설정된 제품에서 드롭다운이 표시되는지 확인
   - 원산지 선택이 정상 작동하는지 확인

3. **테스트 페이지**: http://localhost:1112/test_origin_display.php
   - 전체적인 구현 상태 확인

## 데이터베이스 구조
- `products` 테이블의 `available_origins` 컬럼에 JSON 형식으로 저장
- 예: `["국산","중국산","일본산"]`