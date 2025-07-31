# 재고 상태 선택 기능 업데이트

## 변경 사항

### product_detail.php (제품 상세 페이지)

#### 기존
- 재고 상태를 텍스트로만 표시
- 예: "재고상태: 일반재고, 장기재고"

#### 변경 후
- **단일 재고 상태**: 텍스트로 표시 (변경 없음)
- **복수 재고 상태**: 드롭다운 선택 가능

### 구현 내용

1. **HTML 구조**
   ```php
   <?php if (count($stock_types) > 1): ?>
   <select id="stock-type-select" name="stock_type" class="stock-type-select">
       <?php foreach ($stock_types as $stock_type): ?>
       <option value="<?php echo escape($stock_type); ?>">
           <?php echo escape($stock_type); ?>
       </option>
       <?php endforeach; ?>
   </select>
   <?php else: ?>
   <span style="color: #333;">
       <?php echo escape($stock_types[0]); ?>
   </span>
   <?php endif; ?>
   ```

2. **CSS 스타일링**
   - 원산지 선택과 동일한 스타일 적용
   - 재고 상태별 색상 구분
     - 일반재고: 기본 색상 (#333)
     - 장기재고: 주황색 (#ff6b35)
     - 중고: 회색 (#6c757d)

3. **JavaScript 기능**
   - 재고 상태 선택 시 sessionStorage에 저장
   - 페이지 재방문 시 이전 선택 복원
   - 선택한 재고 상태에 따른 색상 변경

### 사용자 경험

1. **제품에 단일 재고 상태만 있는 경우**
   - 텍스트로 표시 (예: "일반재고")
   - 선택 불가

2. **제품에 복수 재고 상태가 있는 경우**
   - 드롭다운으로 선택 가능
   - 선택한 상태는 브라우저에 저장
   - 재고 상태별로 다른 색상 표시

### 테스트 방법

1. http://localhost:1112/test_stock_type_selection.php 에서 테스트 데이터 설정
2. 각 제품 상세 페이지에서 재고 상태 선택 기능 확인
3. 페이지 새로고침 후에도 선택이 유지되는지 확인

### 주요 특징

- 원산지 선택과 동일한 UX 패턴 적용
- 시각적 피드백으로 재고 상태 구분
- 사용자 선택 저장 및 복원
- 관리자가 설정한 재고 상태 중에서만 선택 가능