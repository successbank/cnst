# 제품 계산식 관리 시스템 기획서

## 1. 프로젝트 개요

### 1.1 목적
- 관리자가 카테고리별 제품의 계산식을 유연하게 관리할 수 있는 시스템 구축
- 기존 하드코딩된 계산식을 DB 기반으로 전환하여 유지보수성 향상
- 제품마다 다른 계산식을 적용할 수 있는 유연성 제공

### 1.2 현재 시스템 분석

#### 기존 계산 로직 (SteelCalculator.php)
```
- H형강/I형강: 단위중량 × 길이 (소수점 첫째자리 반올림)
- 철판: 두께 × 폭 × 길이 × 7.85 × 10^-6
- 사각파이프: [(외경둘레 - 4×두께) × 두께 × 0.00785] × 길이
- 원형파이프: [(외경 - 두께) × 두께 × 0.02466] × 길이
- 환봉: 직경² × 0.00617 × 길이
- 평철: 폭 × 두께 × 0.00785 × 길이
- 앵글: 단위중량 × 길이 (소수점 둘째자리)
- 철근: 직경² × 0.00617 × 길이
```

#### 현재 관련 페이지
- 일반 페이지: `/html/products_new.php` (카테고리별 제품 목록)
- 관리자 페이지: `/html/admin/admin_products_integrated.php` (통합 관리)
- 계산기 페이지: `/html/product_calculator.php` (카테고리별 계산기)
- 계산 클래스: `/html/includes/SteelCalculator.php`

#### 현재 DB 테이블
```
products - 제품 정보
product_categories - 카테고리 정보
rebar_length_data - 철근 길이별 데이터
```

---

## 2. 요구사항 분석

### 2.1 기능 요구사항

#### FR-01: 계산식 템플릿 관리
- 카테고리별 기본 계산식 템플릿 정의
- 계산식 변수(파라미터) 정의
- 계산식 수식 입력 (사용자 친화적 UI)

#### FR-02: 제품별 계산식 커스터마이징
- 카테고리 기본 계산식 상속
- 제품별 고유 계산식 설정 가능
- 계산식 우선순위: 제품 > 카테고리 > 전역 기본값

#### FR-03: 계산 요소(파라미터) 관리
- 길이, 수량, 단위중량, 두께, 폭, 직경 등
- 계산 요소별 입력 타입(숫자, 선택박스 등)
- 계산 요소별 유효성 검사 규칙

#### FR-04: 계산식 검증 및 테스트
- 계산식 문법 검증
- 샘플 데이터로 계산 테스트
- 계산 결과 미리보기

#### FR-05: 관리자 UI
- 카테고리 선택 시 계산식 관리 버튼 표시
- 계산식 CRUD 인터페이스
- 계산 요소 동적 추가/제거
- 계산식 버전 관리 (히스토리)

#### FR-06: 일반 페이지 적용
- 제품 상세 페이지에서 계산기 자동 생성
- DB 기반 계산식으로 자동 계산
- 기존 UI/UX 유지

### 2.2 비기능 요구사항

#### NFR-01: 성능
- 계산식 조회 시 캐싱 적용
- 페이지 로딩 시간 1초 이내

#### NFR-02: 보안
- 계산식 입력 시 SQL Injection 방지
- eval() 사용 금지 (안전한 수식 파서 사용)

#### NFR-03: 호환성
- 기존 SteelCalculator 클래스와 호환
- 점진적 마이그레이션 가능

---

## 3. 데이터베이스 설계

### 3.1 ERD 구조
```
product_categories (기존)
  └─ category_calculation_formulas (1:N)
       └─ calculation_parameters (1:N)

products (기존)
  └─ product_calculation_overrides (1:1)
```

### 3.2 테이블 설계

#### 3.2.1 calculation_formulas (계산식 마스터)
```sql
CREATE TABLE calculation_formulas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_name VARCHAR(100) NOT NULL COMMENT '계산식 이름',
    category_code VARCHAR(50) COMMENT '카테고리 코드 (NULL이면 공용)',
    product_id INT COMMENT '제품 ID (NULL이면 카테고리 전체 적용)',
    description TEXT COMMENT '계산식 설명',
    formula_expression TEXT NOT NULL COMMENT '계산식 (JSON 형식)',
    rounding_rule VARCHAR(20) DEFAULT 'round_2' COMMENT '반올림 규칙',
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT COMMENT '생성 관리자 ID',

    UNIQUE KEY unique_category_product (category_code, product_id),
    KEY idx_category (category_code),
    KEY idx_product (product_id),
    FOREIGN KEY (category_code) REFERENCES product_categories(category_code) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='제품 계산식 정의';
```

#### 3.2.2 calculation_parameters (계산 파라미터)
```sql
CREATE TABLE calculation_parameters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL COMMENT '계산식 ID',
    parameter_name VARCHAR(50) NOT NULL COMMENT '파라미터 명 (length, quantity 등)',
    parameter_label VARCHAR(100) NOT NULL COMMENT '표시 라벨',
    parameter_type ENUM('number', 'select', 'text') DEFAULT 'number',
    default_value VARCHAR(100) COMMENT '기본값',
    min_value DECIMAL(10,2) COMMENT '최소값',
    max_value DECIMAL(10,2) COMMENT '최대값',
    step_value DECIMAL(10,2) COMMENT '증감 단위',
    unit VARCHAR(20) COMMENT '단위 (m, kg, mm 등)',
    validation_rule TEXT COMMENT '유효성 검사 규칙 (JSON)',
    options TEXT COMMENT '선택 옵션 (JSON, type이 select인 경우)',
    display_order INT DEFAULT 0,
    is_required TINYINT(1) DEFAULT 1,

    KEY idx_formula (formula_id),
    FOREIGN KEY (formula_id) REFERENCES calculation_formulas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='계산식 파라미터 정의';
```

#### 3.2.3 calculation_constants (계산 상수)
```sql
CREATE TABLE calculation_constants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    constant_name VARCHAR(50) NOT NULL UNIQUE COMMENT '상수명 (STEEL_DENSITY 등)',
    constant_value DECIMAL(20,10) NOT NULL COMMENT '상수값',
    description TEXT COMMENT '설명',
    unit VARCHAR(20) COMMENT '단위',
    is_editable TINYINT(1) DEFAULT 1 COMMENT '수정 가능 여부',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='계산 상수 관리';
```

#### 3.2.4 calculation_history (계산 히스토리)
```sql
CREATE TABLE calculation_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL,
    version INT NOT NULL DEFAULT 1,
    formula_expression TEXT NOT NULL,
    changed_by INT COMMENT '변경한 관리자 ID',
    change_description TEXT COMMENT '변경 사유',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    KEY idx_formula (formula_id),
    KEY idx_created_at (created_at),
    FOREIGN KEY (formula_id) REFERENCES calculation_formulas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='계산식 변경 이력';
```

### 3.3 계산식 JSON 구조
```json
{
  "type": "expression",
  "expression": "(unit_weight * length * quantity)",
  "variables": {
    "unit_weight": {
      "source": "product.unit_weight",
      "fallback": 0
    },
    "length": {
      "source": "user_input",
      "required": true
    },
    "quantity": {
      "source": "user_input",
      "required": true
    }
  },
  "constants": {
    "STEEL_DENSITY": 7850
  },
  "rounding": {
    "intermediate": {
      "enabled": true,
      "decimals": 1,
      "method": "round"
    },
    "final": {
      "decimals": 2,
      "method": "round"
    }
  },
  "validation": {
    "length": {
      "min": 0.1,
      "max": 20,
      "step": 0.1
    },
    "quantity": {
      "min": 1,
      "step": 1
    }
  }
}
```

---

## 4. UI/UX 설계

### 4.1 관리자 페이지 - 계산식 관리

#### 4.1.1 진입점
**위치**: `/html/admin/admin_products_integrated.php`
**동선**:
1. 관리자 제품 관리 접근
2. 카테고리 필터 선택 (예: 철근)
3. 제품 목록 상단에 **"계산식 관리"** 버튼 표시
4. 버튼 클릭 시 모달 또는 별도 페이지 오픈

#### 4.1.2 계산식 관리 화면
```
┌─────────────────────────────────────────────────────────┐
│ [철근] 카테고리 계산식 관리                              │
├─────────────────────────────────────────────────────────┤
│                                                           │
│ □ 카테고리 기본 계산식                                    │
│   ┌─────────────────────────────────────────────┐      │
│   │ 계산식 이름: 철근 중량 계산                  │      │
│   │ 설명: 직경 기반 중량 계산                    │      │
│   │                                               │      │
│   │ 계산식: diameter² × 0.00617 × length × qty   │      │
│   │                                               │      │
│   │ 파라미터 설정:                                │      │
│   │  ┌──────────────────────────────────────┐   │      │
│   │  │ + 길이 (length) - 숫자 - 기본값: 8m  │   │      │
│   │  │ + 수량 (quantity) - 숫자 - 기본값: 1 │   │      │
│   │  │ + 직경 (diameter) - 제품정보 참조    │   │      │
│   │  └──────────────────────────────────────┘   │      │
│   │                                               │      │
│   │ 반올림 규칙: ○ 소수점 둘째자리               │      │
│   │                                               │      │
│   │ [테스트 계산] [저장] [삭제]                  │      │
│   └─────────────────────────────────────────────┘      │
│                                                           │
│ ☑ 개별 제품 계산식 재정의                                 │
│   제품 선택: [D10 철근 ▼]                                │
│   ┌─────────────────────────────────────────────┐      │
│   │ (위와 동일한 계산식 입력 UI)                 │      │
│   └─────────────────────────────────────────────┘      │
│                                                           │
│ 계산식 히스토리:                                         │
│ ┌───────────────────────────────────────────────┐      │
│ │ 2025-09-29 14:30 - 관리자 홍길동              │      │
│ │   변경: 반올림 규칙 변경 (2자리 → 3자리)      │      │
│ │ [되돌리기]                                     │      │
│ │                                                 │      │
│ │ 2025-09-28 10:15 - 관리자 김철수              │      │
│ │   변경: 계산식 최초 생성                       │      │
│ └───────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────┘
```

#### 4.1.3 계산식 입력 UI (상세)
```
┌─────────────────────────────────────────────────────────┐
│ 계산식 작성기                                            │
├─────────────────────────────────────────────────────────┤
│                                                           │
│ 계산식 템플릿: [기본 중량 계산 ▼]                        │
│                [H형강 계산]                               │
│                [철판 계산]                                │
│                [파이프 계산]                              │
│                [사용자 정의]                              │
│                                                           │
│ 수식 입력:                                                │
│ ┌─────────────────────────────────────────────────┐    │
│ │ (diameter * diameter) * 0.00617 * length * qty  │    │
│ └─────────────────────────────────────────────────┘    │
│                                                           │
│ 빠른 입력 버튼:                                           │
│ [× 곱하기] [÷ 나누기] [+ 더하기] [- 빼기]                │
│ [( ) 괄호] [² 제곱] [√ 제곱근]                           │
│                                                           │
│ 사용 가능한 변수:                                         │
│ ┌─────────────────────────────────────────────────┐    │
│ │ 제품 정보:                                       │    │
│ │  • unit_weight (단위중량)                       │    │
│ │  • diameter (직경)                              │    │
│ │  • thickness (두께)                             │    │
│ │  • width (폭)                                   │    │
│ │                                                  │    │
│ │ 사용자 입력:                                     │    │
│ │  • length (길이) - 필수                         │    │
│ │  • quantity (수량) - 필수                       │    │
│ │                                                  │    │
│ │ 상수:                                            │    │
│ │  • STEEL_DENSITY (7850)                         │    │
│ │  • PI (3.14159)                                 │    │
│ └─────────────────────────────────────────────────┘    │
│                                                           │
│ 반올림 설정:                                              │
│ ┌─────────────────────────────────────────────────┐    │
│ │ ☑ 중간 계산 반올림 (1본 중량 등)                │    │
│ │   소수점 [1] 자리 [반올림▼]                     │    │
│ │                                                  │    │
│ │ ☑ 최종 결과 반올림                              │    │
│ │   소수점 [2] 자리 [반올림▼]                     │    │
│ └─────────────────────────────────────────────────┘    │
│                                                           │
│ [계산 테스트]                                             │
└─────────────────────────────────────────────────────────┘
```

### 4.2 일반 사용자 페이지

#### 4.2.1 제품 목록 페이지
- 기존 UI 유지
- 계산기는 제품 상세 페이지에서만 표시

#### 4.2.2 제품 상세 페이지 - 계산기
```
┌─────────────────────────────────────────────────────────┐
│ [철근 D10] 상세 정보                                     │
├─────────────────────────────────────────────────────────┤
│ ... 제품 정보 ...                                        │
│                                                           │
│ ▼ 중량 계산기                                             │
│ ┌─────────────────────────────────────────────────┐    │
│ │ 길이 (m):  [     8.0     ] m                   │    │
│ │ 수량:      [      10     ] 본                   │    │
│ │                                                  │    │
│ │ [계산하기]                                       │    │
│ │                                                  │    │
│ │ ─────────────────────────────────────────        │    │
│ │ 결과:                                            │    │
│ │  • 1본 중량: 4.94 kg                            │    │
│ │  • 총 중량:  49.40 kg                           │    │
│ │  • 예상 금액: ₩247,000                          │    │
│ └─────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────┘
```

---

## 5. API 설계

### 5.1 관리자 API

#### 5.1.1 계산식 목록 조회
```
GET /html/admin/ajax/get_calculation_formulas.php
Parameters:
  - category_code: string (optional)
  - product_id: int (optional)

Response:
{
  "success": true,
  "data": {
    "category_formula": {
      "id": 1,
      "formula_name": "철근 중량 계산",
      "formula_expression": {...},
      "parameters": [...]
    },
    "product_formulas": [...]
  }
}
```

#### 5.1.2 계산식 저장/수정
```
POST /html/admin/ajax/save_calculation_formula.php
Body:
{
  "id": 1, // null이면 신규
  "formula_name": "철근 중량 계산",
  "category_code": "rebar",
  "product_id": null,
  "formula_expression": {...},
  "parameters": [...],
  "rounding_rule": "round_2"
}

Response:
{
  "success": true,
  "formula_id": 1,
  "message": "계산식이 저장되었습니다."
}
```

#### 5.1.3 계산식 삭제
```
DELETE /html/admin/ajax/delete_calculation_formula.php
Body:
{
  "formula_id": 1
}

Response:
{
  "success": true,
  "message": "계산식이 삭제되었습니다."
}
```

#### 5.1.4 계산식 테스트
```
POST /html/admin/ajax/test_calculation_formula.php
Body:
{
  "formula_expression": {...},
  "test_data": {
    "length": 8,
    "quantity": 10,
    "diameter": 10
  }
}

Response:
{
  "success": true,
  "result": {
    "intermediate": {
      "weight_per_piece": 4.94
    },
    "final": {
      "total_weight": 49.40
    }
  }
}
```

### 5.2 일반 사용자 API

#### 5.2.1 제품 계산
```
POST /html/ajax/calculate_product_weight.php
Body:
{
  "product_id": 123,
  "parameters": {
    "length": 8,
    "quantity": 10
  }
}

Response:
{
  "success": true,
  "result": {
    "weight_per_piece": 4.94,
    "total_weight": 49.40,
    "estimated_price": 247000,
    "formula_used": "철근 중량 계산"
  }
}
```

---

## 6. 계산식 엔진 설계

### 6.1 안전한 수식 파서
- PHP의 `eval()` 사용 금지 (보안 취약점)
- 대안: 자체 수식 파서 구현 또는 검증된 라이브러리 사용

#### 6.1.1 허용 연산자
```php
// 허용되는 연산자만 사용
$allowed_operators = ['+', '-', '*', '/', '(', ')', '^'];
$allowed_functions = ['sqrt', 'pow', 'abs', 'round', 'ceil', 'floor'];
```

#### 6.1.2 수식 파서 클래스
```php
class FormulaParser {
    public function parse($expression, $variables = []) {
        // 1. 변수 치환
        // 2. 문법 검증
        // 3. 연산자 우선순위 처리
        // 4. 계산 실행
        // 5. 결과 반환
    }

    public function validate($expression) {
        // 수식 문법 검증
    }
}
```

### 6.2 계산 실행 클래스
```php
class CalculationEngine {
    private $pdo;
    private $parser;

    public function calculate($product_id, $user_params) {
        // 1. 계산식 조회 (제품 > 카테고리 > 기본)
        // 2. 제품 정보 조회
        // 3. 변수 바인딩
        // 4. 수식 실행
        // 5. 반올림 적용
        // 6. 결과 반환
    }

    private function getFormula($product_id) {
        // 우선순위: 제품 > 카테고리 > 기본
    }
}
```

---

## 7. 마이그레이션 전략

### 7.1 단계별 마이그레이션

#### Phase 1: 데이터베이스 구축
1. 테이블 생성
2. 기존 SteelCalculator 계산식을 DB로 이관
3. 데이터 검증

#### Phase 2: 관리자 UI 개발
1. 계산식 관리 페이지 개발
2. CRUD API 개발
3. 계산식 테스트 기능

#### Phase 3: 계산 엔진 개발
1. FormulaParser 클래스 개발
2. CalculationEngine 클래스 개발
3. 기존 SteelCalculator와 병행 운영

#### Phase 4: 일반 페이지 적용
1. 제품 상세 페이지에 계산기 통합
2. API 연동
3. 사용자 테스트

#### Phase 5: 레거시 제거
1. 기존 SteelCalculator 단계적 제거
2. 성능 최적화
3. 문서화

### 7.2 호환성 유지
```php
// 기존 SteelCalculator 메서드를 래퍼로 유지
class SteelCalculator {
    private $calculationEngine;

    public function calculateRebarWeight($diameter, $length, $quantity) {
        // 기존 메서드 유지하되, 내부적으로 새 엔진 사용
        return $this->calculationEngine->calculate(
            $product_id,
            ['diameter' => $diameter, 'length' => $length, 'quantity' => $quantity]
        );
    }
}
```

---

## 8. 개발 일정

### 8.1 상세 작업 항목

#### Week 1: 기획 및 설계
- [x] 요구사항 분석
- [x] DB 설계
- [x] UI/UX 설계
- [ ] API 명세 확정
- [ ] 개발 환경 준비

#### Week 2: 백엔드 개발
- [ ] DB 테이블 생성
- [ ] FormulaParser 클래스 개발
- [ ] CalculationEngine 클래스 개발
- [ ] 기존 계산식 데이터 마이그레이션
- [ ] 단위 테스트

#### Week 3: 관리자 UI 개발
- [ ] 계산식 관리 페이지 개발
- [ ] 계산식 입력 UI 개발
- [ ] CRUD API 연동
- [ ] 테스트 기능 개발
- [ ] 히스토리 관리 기능

#### Week 4: 일반 페이지 통합
- [ ] 제품 상세 페이지 계산기 UI
- [ ] API 연동
- [ ] 반응형 디자인 적용
- [ ] 사용자 테스트

#### Week 5: 테스트 및 배포
- [ ] 통합 테스트
- [ ] 성능 테스트
- [ ] 버그 수정
- [ ] 문서화
- [ ] 프로덕션 배포

---

## 9. 테스트 계획

### 9.1 단위 테스트
```php
// FormulaParserTest.php
public function testBasicArithmetic() {
    $parser = new FormulaParser();
    $result = $parser->parse('2 + 3 * 4', []);
    $this->assertEquals(14, $result);
}

public function testVariableSubstitution() {
    $parser = new FormulaParser();
    $result = $parser->parse('length * quantity', [
        'length' => 8,
        'quantity' => 10
    ]);
    $this->assertEquals(80, $result);
}
```

### 9.2 통합 테스트
- 각 카테고리별 제품 계산 검증
- 기존 SteelCalculator와 결과 비교
- 성능 벤치마크

### 9.3 사용자 테스트
- 관리자 계산식 등록/수정 시나리오
- 일반 사용자 계산기 사용 시나리오
- 다양한 브라우저 호환성 테스트

---

## 10. 위험 요소 및 대응 방안

### 10.1 기술적 위험
| 위험 요소 | 영향도 | 대응 방안 |
|----------|--------|----------|
| 수식 파서 보안 취약점 | 높음 | eval() 금지, 화이트리스트 방식 |
| 성능 저하 | 중간 | 캐싱, 쿼리 최적화 |
| 기존 시스템과 충돌 | 중간 | 병행 운영, 점진적 마이그레이션 |

### 10.2 운영 위험
| 위험 요소 | 영향도 | 대응 방안 |
|----------|--------|----------|
| 관리자 실수로 잘못된 계산식 입력 | 높음 | 버전 관리, 롤백 기능 |
| 기존 계산 결과와 차이 | 중간 | 충분한 테스트, 단계적 배포 |

---

## 11. 확장성 고려사항

### 11.1 향후 확장 가능성
- 다양한 단위 변환 (mm ↔ m, kg ↔ ton)
- 여러 통화 지원
- 계산식 템플릿 공유 기능
- AI 기반 계산식 추천

### 11.2 성능 최적화
- Redis를 활용한 계산식 캐싱
- 계산 결과 캐싱 (동일한 입력값)
- DB 인덱스 최적화

---

## 12. 문서화

### 12.1 관리자 매뉴얼
- 계산식 등록 방법
- 수식 작성 가이드
- 문제 해결 가이드

### 12.2 개발자 문서
- API 명세서
- 데이터베이스 스키마
- 코드 주석

---

## 부록 A: 샘플 계산식 데이터

### A.1 철근 (rebar)
```json
{
  "formula_name": "철근 중량 계산",
  "expression": "(diameter * diameter) * 0.00617 * length * quantity",
  "rounding": {
    "intermediate": {"decimals": 2},
    "final": {"decimals": 2}
  },
  "parameters": [
    {"name": "diameter", "label": "직경", "type": "product_field", "unit": "mm"},
    {"name": "length", "label": "길이", "type": "number", "default": 8, "unit": "m"},
    {"name": "quantity", "label": "수량", "type": "number", "default": 1, "unit": "본"}
  ]
}
```

### A.2 H형강 (h-beam)
```json
{
  "formula_name": "H형강 중량 계산",
  "expression": "round(unit_weight * length, 1) * quantity",
  "rounding": {
    "intermediate": {"decimals": 1, "method": "round"},
    "final": {"decimals": 2}
  },
  "parameters": [
    {"name": "unit_weight", "label": "단위중량", "type": "product_field", "unit": "kg/m"},
    {"name": "length", "label": "길이", "type": "number", "default": 6, "unit": "m"},
    {"name": "quantity", "label": "수량", "type": "number", "default": 1, "unit": "본"}
  ]
}
```

### A.3 철판 (steel-plate)
```json
{
  "formula_name": "철판 중량 계산",
  "expression": "thickness * width * (length * 1000) * 7.85e-6 * quantity",
  "parameters": [
    {"name": "thickness", "label": "두께", "type": "product_field", "unit": "mm"},
    {"name": "width", "label": "폭", "type": "product_field", "unit": "mm"},
    {"name": "length", "label": "길이", "type": "number", "unit": "m"},
    {"name": "quantity", "label": "수량", "type": "number", "default": 1}
  ]
}
```

---

## 부록 B: 기술 스택

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Libraries**:
  - jQuery (기존 시스템 호환)
  - MathJS (수식 파싱) - 검토 필요
- **Tools**:
  - PHPUnit (단위 테스트)
  - Docker (개발 환경)