# 제품 견적요청(장바구니) 시스템 개발문서

- 문서번호: PRD-2026-0911-QUOTEV2
- 작성일: 2026-09-11
- 대상: 충남스틸 웹사이트 (PHP 8.3 + MariaDB 10.11 + Nginx, Docker)
- 상태: 설계 확정 / 구현 착수

---

## 1. 요구사항 원문

1. 제품 상세페이지(`product_detail.php?id=9` 등)에서 **원산지, 재질, 길이, 단위**를 선택하고
   **견적요청** 버튼을 누르면 해당 내용이 **회원 장바구니**에 담긴다.
2. 담긴 내용은 **관리자 페이지에서 관리**한다.
3. **회원 장바구니 페이지**에서 항목을 선택하고 견적요청을 누르면 **관리자 페이지로 전송**된다.
4. **비회원도 동작**해야 하며, 이 경우 임시로 **이름·전화번호**를 입력받아 동작시킨다.
5. 관리자 페이지에서 **답변 내용을 작성하고 상태를 변경하면 해당 회원의 이메일로 발송**된다.
6. **기존 자동계산 방식은 삭제하지 않고**, 관리자 > 사이트 관리에서
   **신규 견적요청 방식 ↔ 기존 자동계산 방식**으로 전환 가능해야 한다.

---

## 2. 현황 분석 결과 (2026-09-11 실측)

### 2.1 기존 "제품견적서" 시스템은 전 구간이 고장나 있음

본 개발에 앞서 기존 코드를 실측한 결과, 겉으로는 메뉴가 노출되지만 실제로는 동작하지 않는다.
원인은 **코드가 기대하는 스키마와 실제 DB 스키마의 불일치**이며, 모든 오류가 `try/catch`에
삼켜져 "데이터 없음"으로만 보이기 때문에 장애로 인지되지 않았다.

| 파일 | 증상 | 원인 |
|---|---|---|
| `my_quote_cart.php` | 담긴 내용이 서버에 없음 | 브라우저 `sessionStorage`에만 저장 |
| `my_quote_cart.php:31` | "최근 견적 요청 현황" 영역이 렌더되지 않음 | `WHERE member_id = ?` → 컬럼 없음(1054) |
| `product_quote_form.php` | 제출 시 항상 403 | 폼에 `csrf_token` 필드 없음 |
| `ajax/submit_product_quote.php:68` | 제출 시 저장 실패 | `zipcode`·`notes`·`member_id` 컬럼 없음 |
| `ajax/submit_product_quote.php:86` | 항목 저장 실패 | `specifications` 컬럼 없음(실제는 `product_spec`) |
| `admin/admin_product_quote_view.php:16` | **페이지 자체가 500** | `LEFT JOIN members ON pq.member_id` → 컬럼 없음, try/catch 없음 |
| `admin/admin_product_quote_view.php:37` | 상태변경 실패 | `admin_notes` 컬럼 없음(실제는 `admin_note`) |
| `my_product_quotes.php` | 항상 0건 + 유입 링크 0개(고아) | `WHERE member_id = ?` |

`admin/create_product_quotes_tables.php`는 `CREATE TABLE IF NOT EXISTS`라 이미 존재하는
실 테이블을 고치지 못한다. 이것이 스키마 괴리가 방치된 근본 원인이다.

### 2.2 실제 DB 스키마 (SHOW CREATE TABLE 실측)

```
product_quotes       : id, customer_name, company, phone, email, address, address_detail,
                       products(text), total_amount, status(enum 4), admin_note, created_at, updated_at
                       → member_id / zipcode / notes / admin_notes / admin_reply 없음
product_quote_items  : id, quote_id(FK CASCADE), product_id(FK SET NULL), product_name,
                       product_spec, quantity(int), unit_price, subtotal, note, created_at
                       → origin / material / length / unit / specifications 없음
```

기존 데이터는 3건이며 모두 `2025-09-18 03:43:53` 동일 시각에 생성된 데모 시드(김철수/이영희/박민수)다.
실사용 이력이 아니므로 스키마 확장에 따른 데이터 위험은 없다.

### 2.3 기타 확인 사항

- `login.php:114`가 `$_SESSION = []`로 세션을 통째로 비운다.
  → **서버 세션에 비회원 장바구니를 담으면 로그인 순간 유실된다.** 세션 방식 배제 근거.
- `getSetting()`은 행이 존재하면 빈 문자열도 그대로 반환한다(`$default` 미적용).
  → 호출부에서 반드시 `getSetting($k) ?: '기본값'` 형태로 사용해야 한다.
- `admin_site.php`는 POST 처리가 `admin_head.php` 뒤에 있어 `header()` 리다이렉트가 불가능하다.
  → 기존 `$success_msg` / `$error` 패턴을 그대로 따른다.
- `tail.php`의 CSRF 인터셉터는 페이지 최하단에서 등록된다.
  → 본문 중간 인라인 스크립트가 즉시 실행하는 `fetch(POST)`는 토큰이 붙지 않아 403이 된다.
- 카카오 알림톡은 `KAKAO_TEST_MODE='1'`, API 키가 플레이스홀더 상태라 실발송이 불가능하다.
  → 본 개발 범위에서 제외하고 **이메일 단독**으로 구현한다.
- 관리자 목록 화면 복사 원본은 `admin_consignment.php`를 사용한다.
  `admin_product_quotes.php`는 인라인 `<style>`로 공통 CSS를 재정의한 이탈 사례다.

---

## 3. 설계 결정 사항

| # | 쟁점 | 결정 | 근거 |
|---|---|---|---|
| D1 | 신규 방식에서 금액을 표시·저장할 것인가 | **표시하지 않음** | 요구사항이 "견적요청"이며, 관리자가 답변을 작성해 회신하는 흐름이다. 자동 산출 금액은 기존 방식(calc)의 역할이다. 현행 계산은 100% 클라이언트 계산이라 조작 가능하고, 자식 제품 30건은 가격이 NULL이라 오산출된다. |
| D2 | 장바구니 저장 위치 | **DB 테이블 `quote_cart_items`** + 비회원 쿠키 토큰 | 요구사항 2("관리자에서 관리")를 만족하려면 서버 저장이 필수다. 서버 세션은 `login.php:114` 때문에 비회원→회원 승계가 불가능하다. |
| D3 | 비회원 식별 | `cart_token` 랜덤 64자, HttpOnly·Secure·SameSite=Lax 쿠키 30일 | 담기 단계에서는 개인정보를 받지 않아 이탈을 줄이고, 제출 단계에서만 이름·전화를 받는다. |
| D4 | 비회원 이메일 | **선택 입력**. 입력 시에만 답변 메일 발송 | 요구사항은 이름·전화만 명시했다. 이메일이 없으면 관리자 화면에 "이메일 없음"으로 표시해 전화 회신을 유도한다. |
| D5 | 관리자 메모와 고객 답변 | **분리** (`admin_note` 유지 + `admin_reply` 신설) | 내부 메모가 실수로 고객에게 발송되는 사고를 구조적으로 차단한다. |
| D6 | 컬럼명 불일치 해소 방법 | **코드를 실 컬럼명으로 교정**. `admin_notes`·`specifications` 컬럼은 만들지 않음 | 같은 뜻의 컬럼이 2개가 되면 괴리가 영구화된다. |
| D7 | 모드 전환 범위 | **전역 1개 값** (`site_settings.product_detail_mode`) | `setting_key`가 전역 UNIQUE라 제품별 값은 이 테이블에 맞지 않는다. |
| D8 | 기존 파일 처리 | **수정하지 않고 보존**. 링크만 모드에 따라 분기 | CLAUDE.md의 "별도 요청 없이 수정 금지" 원칙. calc 모드에서는 기존 동작이 100% 유지되어야 한다. |
| D9 | 데모 데이터 3건 | **보존**. `source='legacy'`로 표시 | 삭제는 되돌릴 수 없다. 금액이 포함된 유일한 화면 검증용 샘플이다. |
| D10 | 카카오 알림톡 | **범위 제외** | 테스트 모드 + 키 미설정으로 실발송 불가. |
| D11 | 레거시 `?category=` 계산기 | **손대지 않음** | 요청 범위 밖. 모드와 무관하게 항상 기존 파일로 라우팅한다. |

---

## 4. 아키텍처

### 4.1 라우팅

```
product_detail.php  (얇은 라우터, URL 불변)
   │
   ├─ ?category=... ................................ 항상 → product_detail_calc.php
   ├─ ?id=N & mode=calc  (기본값) .................. → product_detail_calc.php   [기존 동작 100% 보존]
   └─ ?id=N & mode=quote ........................... → product_detail_v2.php     [신규]

관리자 미리보기: 관리자 로그인 상태에서 ?preview=calc | ?preview=quote 로 설정값 무시
```

`git mv product_detail.php product_detail_calc.php` 로 기존 파일은 **내용 변경 없이 이름만** 바뀐다.

### 4.2 신규 방식 전체 흐름

```
product_detail_v2.php
   [원산지][재질][길이+단위][수량+단위]  ─ 견적요청 ─▶ ajax/cart_add.php
                                                          │ 서버에서 제품 재조회·검증
                                                          ▼
                                                   quote_cart_items  (member_id 또는 cart_token)
                                                          │
quote_cart.php (회원·비회원 공용)  ◀───────────────────────┘
   항목 체크 → [견적요청]
      회원   : 회원정보 자동 사용
      비회원 : 이름·전화 필수 / 이메일 선택
                     │
                     ▼
          ajax/submit_quote_cart.php
                     │ 트랜잭션: product_quotes + product_quote_items INSERT
                     │ 성공 후 카트 행 삭제
                     ▼
          ProductQuoteMailer::sendReceipt()  ─▶ 관리자(contact_email) + 고객(있으면)
                     │
                     ▼
   admin/admin_product_quotes.php (목록)
                     │
                     ▼
   admin/admin_product_quote_view.php (상세)
      답변 작성 + 상태 변경 → 저장
                     │
                     ▼
          ProductQuoteMailer::sendReply() ─▶ 고객 이메일
```

### 4.3 비회원 → 회원 승계

`login.php`에서 세션 설정이 끝난 직후(반드시 `$_SESSION = []` 이후) 다음 1줄을 추가한다.

```php
QuoteCart::mergeGuestCart($pdo, $member['id']);   // 쿠키 토큰 카트를 회원 카트로 이관 후 쿠키 폐기
```

---

## 5. DB 스키마 변경

모든 DDL은 `sql/20260911_product_quote_v2.sql` 에 저장한다.

### 5.1 신규 테이블

```sql
CREATE TABLE IF NOT EXISTS quote_cart_items (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  member_id     INT NULL            COMMENT '회원 ID (비회원이면 NULL)',
  cart_token    CHAR(64) NULL       COMMENT '비회원 카트 토큰',
  product_id    INT NULL,
  product_name  VARCHAR(200) NOT NULL,
  product_spec  VARCHAR(200) NULL,
  origin        VARCHAR(50) NULL    COMMENT '원산지',
  material      VARCHAR(50) NULL    COMMENT '재질',
  length_value  DECIMAL(10,2) NULL  COMMENT '길이',
  length_unit   VARCHAR(10) NOT NULL DEFAULT 'M',
  quantity      DECIMAL(12,3) NOT NULL DEFAULT 1,
  quantity_unit VARCHAR(10) NOT NULL DEFAULT 'EA' COMMENT 'EA/본/TON/장/SET',
  note          VARCHAR(500) NULL   COMMENT '항목별 요청사항',
  created_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_member (member_id),
  KEY idx_token (cart_token),
  KEY idx_created (created_at),
  CONSTRAINT fk_qci_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='제품 견적 장바구니';
```

`member_id`와 `cart_token` 중 정확히 하나만 채운다(애플리케이션 레벨 보장).

### 5.2 기존 테이블 확장 (컬럼 추가만, 기존 컬럼 변경·삭제 없음)

```sql
ALTER TABLE product_quotes
  ADD COLUMN member_id   INT NULL         COMMENT '회원 ID (비회원 NULL)' AFTER id,
  ADD COLUMN notes       TEXT NULL        COMMENT '고객 요청사항' AFTER products,
  ADD COLUMN source      VARCHAR(20) NOT NULL DEFAULT 'legacy' COMMENT 'v2=신규 견적요청' AFTER status,
  ADD COLUMN admin_reply TEXT NULL        COMMENT '고객 공개 답변' AFTER admin_note,
  ADD COLUMN replied_at  DATETIME NULL    AFTER admin_reply,
  ADD KEY idx_member (member_id);

ALTER TABLE product_quote_items
  ADD COLUMN origin        VARCHAR(50) NULL   AFTER product_spec,
  ADD COLUMN material      VARCHAR(50) NULL   AFTER origin,
  ADD COLUMN length_value  DECIMAL(10,2) NULL AFTER material,
  ADD COLUMN length_unit   VARCHAR(10) NOT NULL DEFAULT 'M' AFTER length_value,
  ADD COLUMN quantity_unit VARCHAR(10) NOT NULL DEFAULT 'EA' AFTER quantity;

ALTER TABLE product_quote_items
  MODIFY COLUMN quantity DECIMAL(12,3) NOT NULL DEFAULT 1;   -- TON 등 소수 수량 대응
```

**금지 사항**: `admin_notes`, `specifications` 컬럼을 만들지 않는다. 실제 컬럼은 `admin_note`, `product_spec`이다.

### 5.3 설정 키

```sql
INSERT INTO site_settings (setting_key, setting_value, setting_type, setting_group, description)
VALUES ('product_detail_mode', 'calc', 'text', 'product',
        '제품 상세페이지 모드: calc=기존 자동계산, quote=신규 견적요청')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
```

기본값은 `calc`다. **배포 직후 사이트는 기존과 완전히 동일하게 동작한다.**

---

## 6. 파일 목록

### 6.1 신규 파일

| 경로 | 역할 |
|---|---|
| `sql/20260911_product_quote_v2.sql` | 5장의 DDL |
| `includes/QuoteCart.php` | 장바구니 서비스 (정적 클래스) |
| `includes/ProductQuoteMailer.php` | 접수/답변 메일 |
| `product_detail_v2.php` | 신규 견적요청형 상세 화면 |
| `quote_cart.php` | 회원·비회원 공용 장바구니 |
| `ajax/cart_add.php` | 담기 |
| `ajax/cart_update.php` | 수량·길이 수정 |
| `ajax/cart_remove.php` | 삭제 |
| `ajax/cart_count.php` | 헤더 뱃지 카운트 |
| `ajax/submit_quote_cart.php` | 견적 제출 |
| `cron/quote_cart_cleanup.php` | 30일 초과 비회원 카트 정리 |

### 6.2 이름 변경

| 변경 전 | 변경 후 | 내용 |
|---|---|---|
| `product_detail.php` | `product_detail_calc.php` | **내용 무변경**. `git mv`만 수행 |

### 6.3 수정 파일 (최소 범위)

| 경로 | 수정 내용 |
|---|---|
| `product_detail.php` | 신규 작성. 얇은 라우터 |
| `admin/admin_site.php` | 모드 전환 카드 1개 추가 (switch case 1개, 설정 로드 1줄, 폼 1개) |
| `admin/admin_product_quote_view.php` | 500 오류 수정 + 답변·상태변경 + 메일 발송 |
| `admin/admin_product_quotes.php` | 회원/비회원·답변여부 컬럼 추가 (인라인 style은 건드리지 않음) |
| `head.php` | 장바구니 버튼 링크·카운트를 모드에 따라 분기 |
| `includes/sub_layout.php` | 마이페이지 사이드바 링크를 모드에 따라 분기 |
| `login.php` | 비회원 카트 승계 1줄 추가 |

### 6.4 손대지 않는 파일

`my_quote_cart.php`, `product_quote_form.php`, `ajax/submit_product_quote.php`,
`my_product_quotes.php`, `quote_write.php`, `product_detail_calc.php`(이름 변경만),
`includes/CalculationEngine.php`, `includes/SteelCalculator.php`, `api/calculate_weight.php`

---

## 7. API 계약

### 7.1 `includes/QuoteCart.php`

```php
class QuoteCart {
    const COOKIE_NAME = 'qcart';
    const COOKIE_DAYS = 30;
    const MAX_ITEMS   = 50;

    /** 현재 소유자 반환. ['member_id'=>int|null, 'cart_token'=>string|null] */
    public static function owner($createToken = false);

    /** 담기. 성공 시 insert id, 실패 시 예외 */
    public static function add(PDO $pdo, array $item);

    /** 목록 (created_at DESC) */
    public static function items(PDO $pdo);

    /** 건수 */
    public static function count(PDO $pdo);

    /** 수량·길이·비고 수정. 소유자 검증 포함 */
    public static function update(PDO $pdo, $id, array $fields);

    /** 삭제. $ids는 배열. 소유자 검증 포함. 삭제 건수 반환 */
    public static function remove(PDO $pdo, array $ids);

    /** 지정 id들만 조회. 소유자 검증 포함 */
    public static function itemsByIds(PDO $pdo, array $ids);

    /** 로그인 직후 비회원 카트를 회원 카트로 이관 후 쿠키 폐기 */
    public static function mergeGuestCart(PDO $pdo, $memberId);
}
```

`$item` 키: `product_id`, `product_name`, `product_spec`, `origin`, `material`,
`length_value`, `length_unit`, `quantity`, `quantity_unit`, `note`

### 7.2 `includes/ProductQuoteMailer.php`

```php
class ProductQuoteMailer {
    /** 접수 알림: 관리자(contact_email) + 고객(이메일 있을 때만) */
    public static function sendReceipt(PDO $pdo, $quoteId);

    /** 답변 알림: 고객에게만 */
    public static function sendReply(PDO $pdo, $quoteId);
}
```

두 메서드 모두 내부에서 `try { } catch (\Throwable $e) { error_log(...); }`로 감싸
**메일 실패가 저장 트랜잭션을 깨지 않도록** 한다.

### 7.3 AJAX 엔드포인트 공통 규약

- 경로: `/ajax/*.php`
- 선두: `session_start()` → `require_once '../db.php'` → `require_once '../includes/csrf.php'`
- CSRF: `verifyCsrfToken(false)` 실패 시 HTTP 403 + `{"success":false,"message":"..."}`
- Rate limit: `checkRateLimit()` 사용
- 응답: `{"success": bool, "message": string, ...}` **키 이름을 `message`로 통일**
- 예외: `error_log()` + 일반 메시지. `$e->getMessage()`를 응답에 담지 않는다

| 엔드포인트 | 메서드 | 입력 | 출력 |
|---|---|---|---|
| `ajax/cart_add.php` | POST | product_id, origin, material, length_value, length_unit, quantity, quantity_unit, note | success, message, cart_count |
| `ajax/cart_update.php` | POST | id, quantity, quantity_unit, length_value, length_unit, note | success, message |
| `ajax/cart_remove.php` | POST | ids[] | success, message, cart_count |
| `ajax/cart_count.php` | GET | - | success, cart_count |
| `ajax/submit_quote_cart.php` | POST | ids[], customer_name, phone, email, company, notes | success, message, quote_id |

**서버 검증 원칙**: 클라이언트가 보낸 제품명·규격을 신뢰하지 않는다.
`product_id`로 `products`를 재조회해 `product_name`·`specification`을 서버에서 채운다.
`origin`·`material`은 해당 제품의 `available_origins`·`available_materials` 화이트리스트로 검증한다.

---

## 8. 화면 설계

### 8.1 `product_detail_v2.php`

기존 `product_detail_calc.php`의 상단 구조(제품 이미지, 기본정보, 상세 탭)는 동일하게 유지하고,
계산기 영역만 아래로 교체한다.

```
┌─ 견적요청 ────────────────────────────┐
│ 원산지   [국산          ▼]            │  available_origins
│ 재질     [SS275         ▼]            │  available_materials (부모 상속 규칙 동일)
│ 길이     [6.0          ▼] [M     ▼]   │  카테고리별 분기 (철근=rebar_length_data)
│ 수량     [        10   ] [EA    ▼]    │  EA/본/TON/장/SET
│ 요청사항 [                        ]   │  선택, 500자
│                                       │
│        [  견적요청 (장바구니 담기)  ]  │
└───────────────────────────────────────┘
※ 금액은 표시하지 않는다. 담기 후 "장바구니로 이동" 안내를 노출한다.
```

담기 스크립트는 **`tail.php` 이후**에 배치한다(CSRF 인터셉터 등록 순서 때문).

### 8.2 `quote_cart.php`

```
견적 장바구니
[전체선택] 제품 | 규격 | 원산지 | 재질 | 길이 | 수량 | 요청사항 | 삭제
─────────────────────────────────────────────────────────
[v] ㄱ형강 50×50×5T | 50×50×5T | 국산 | SS275 | 6.0M | 10 EA | - | [X]

── 요청자 정보 ────────────────────────
회원   : 이름/연락처/이메일 자동 표시 (회원정보 기준)
비회원 : 이름* [    ] 연락처* [    ] 이메일 [    ] (이메일 입력 시 답변 메일 수신)
         회사명 [    ]
요청사항 [                              ]

                     [ 선택 항목 견적요청 ]
```

비회원 안내 문구: "이메일을 입력하시면 답변을 메일로 받아보실 수 있습니다."

### 8.3 `admin/admin_product_quote_view.php`

기존 화면의 500 오류를 수정하고 아래를 추가한다.

```
견적 정보 (요청자: 회원 홍길동(hong) / 비회원)
요청 항목 테이블: 제품 | 규격 | 원산지 | 재질 | 길이 | 수량

┌─ 관리자 메모 (고객에게 발송되지 않음) ─┐
│ [                                    ] │
└────────────────────────────────────────┘
┌─ 고객 답변 (저장 시 고객 이메일로 발송) ┐
│ [                                    ] │
└────────────────────────────────────────┘
상태 [접수 ▼]   [ ] 저장 시 고객에게 이메일 발송
                                [ 저장 ]
```

- 이메일 발송 체크박스는 **답변 내용이 있고 수신 이메일이 있을 때만** 활성화한다.
- 수신 이메일이 없으면 "이 요청자는 이메일이 없어 발송할 수 없습니다"를 표시한다.
- 폼에 `csrfField()`를 반드시 포함한다.
- POST 처리는 `admin_head.php` require **이전**에 두어 `header()` 리다이렉트를 가능하게 한다.

### 8.4 관리자 > 사이트 관리 모드 전환 카드

```
┌─ 제품 상세페이지 모드 ──────────────────────────┐
│ ( ) 자동계산 방식 (기존)                        │
│     중량·금액을 실시간 계산해 보여줍니다.        │
│ (•) 견적요청 방식 (신규)                        │
│     원산지·재질·길이·단위를 선택해 장바구니에    │
│     담고 견적을 요청합니다.                      │
│                                    [ 저장 ]     │
└─────────────────────────────────────────────────┘
```

값은 `in_array($v, ['calc','quote'], true)` 화이트리스트로 검증한다.

---

## 9. 보안 요구사항

| 항목 | 조치 |
|---|---|
| CSRF | 모든 POST 폼에 `csrfField()`. 프론트 AJAX는 `verifyCsrfToken(false)` 직접 호출 |
| 권한 | 카트 조회·수정·삭제는 항상 `member_id` 또는 `cart_token` 조건을 WHERE에 포함 |
| 입력 검증 | `origin`·`material`은 제품의 허용 목록 화이트리스트. 길이·수량은 숫자 범위 검증 |
| 값 위조 | 제품명·규격은 클라이언트 값을 버리고 서버에서 `products` 재조회 |
| Rate limit | 담기 30회/5분, 제출 5회/10분 |
| 쿠키 | `httponly=true, secure=true, samesite=Lax`, 토큰 `random_bytes(32)` |
| 오류 노출 | `$e->getMessage()`를 화면·JSON에 노출하지 않음. `error_log()` 사용 |
| XSS | 모든 출력에 `htmlspecialchars()` |
| 개인정보 | 비회원 이름·전화는 제출 시에만 수집. 카트 단계에서는 수집하지 않음 |

---

## 10. 구현 순서

| 단계 | 작업 | 검증 |
|---|---|---|
| 1 | DB 마이그레이션 실행 | `SHOW COLUMNS` 로 컬럼 확인, 기존 3건 유지 확인 |
| 2 | `includes/QuoteCart.php`, `includes/ProductQuoteMailer.php` | `php -l` |
| 3 | `ajax/*.php` 5개 | `php -l`, CSRF 403 동작 확인 |
| 4 | `product_detail_v2.php`, `quote_cart.php` | `php -l` |
| 5 | `git mv product_detail.php product_detail_calc.php` + 라우터 신규 작성 | **calc 모드에서 기존 화면 동일 확인 (최우선)** |
| 6 | `admin/admin_site.php` 모드 전환 카드 | 토글 저장·반영 확인 |
| 7 | `admin/admin_product_quote_view.php` 수정 | 500 해소 확인, 답변 저장 확인 |
| 8 | `admin/admin_product_quotes.php` 목록 컬럼 | 목록 정상 확인 |
| 9 | `head.php`, `includes/sub_layout.php`, `login.php` 링크·승계 | 모드별 링크 확인 |
| 10 | 전체 시나리오 테스트 | 11장 |

**단계 5가 가장 위험하다.** 이름 변경 직후 `calc` 모드에서 기존 상세페이지가
완전히 동일하게 동작하는지 먼저 확인한 뒤 다음 단계로 진행한다.

---

## 11. 테스트 시나리오

### 11.1 회귀 (기존 기능 보존) — 최우선

| # | 항목 | 기대 |
|---|---|---|
| R1 | `product_detail.php?id=9` (calc 모드) | 기존 자동계산 화면이 그대로 표시 |
| R2 | 재질·길이·수량 변경 | 중량·금액이 기존과 동일하게 재계산 |
| R3 | `product_detail.php?category=rebar` | 기존 계산기 페이지 표시 (모드 무관) |
| R4 | 철근 제품 상세 | 길이별 본수·단가 기존과 동일 |
| R5 | `my_quote_cart.php` 직접 접근 | 기존 화면 그대로 (수정하지 않았음) |
| R6 | 기존 견적 3건 | 관리자 목록에 그대로 표시 |

### 11.2 신규 기능

| # | 항목 | 기대 |
|---|---|---|
| N1 | quote 모드 전환 후 `?id=9` | 견적요청 폼 표시, 금액 미표시 |
| N2 | 회원 담기 | `quote_cart_items`에 `member_id`로 저장 |
| N3 | 비회원 담기 | `cart_token`으로 저장, 쿠키 발급 |
| N4 | 비회원 담기 후 로그인 | 카트가 회원 카트로 승계, 쿠키 폐기 |
| N5 | 회원 제출 | `product_quotes`에 `member_id`, `source='v2'` 저장 |
| N6 | 비회원 제출 (이름·전화만) | 저장됨, 고객 메일 미발송, 관리자 메일 발송 |
| N7 | 비회원 제출 (이메일 포함) | 고객 접수 메일 발송 |
| N8 | 관리자 상세 진입 | 500 없이 정상 표시 |
| N9 | 답변 작성 + 발송 체크 | 고객 이메일 수신, `replied_at` 기록 |
| N10 | 답변 없이 상태만 변경 | 메일 미발송 |
| N11 | 헤더 장바구니 뱃지 | 서버 건수와 일치 |

### 11.3 보안

| # | 항목 | 기대 |
|---|---|---|
| S1 | CSRF 토큰 없이 담기 POST | 403 |
| S2 | 타인 카트 id로 삭제 시도 | 0건 삭제, 오류 없음 |
| S3 | 허용되지 않은 재질 값 전송 | 거부 |
| S4 | 수량에 음수·문자 전송 | 거부 |
| S5 | 담기 31회 연속 | Rate limit 차단 |
| S6 | 관리자 답변에 `<script>` 입력 | 이스케이프되어 표시 |

---

## 12. 롤백 절차

| 상황 | 조치 |
|---|---|
| 신규 화면에 문제 발생 | 관리자 > 사이트 관리에서 **자동계산 방식** 선택. 즉시 기존 화면으로 복귀 |
| 라우터 자체에 문제 발생 | `git revert` 또는 `product_detail_calc.php`를 `product_detail.php`로 되돌림 |
| DB 변경 되돌리기 | 추가한 컬럼만 `DROP COLUMN`. 기존 컬럼·데이터는 변경하지 않았으므로 손실 없음 |

DB 변경은 **추가 전용(additive only)** 이라 기존 코드에 영향이 없다.

---

## 13. 범위 밖 (별도 승인 필요)

- `product_detail_backup_*.php` 3개 파일이 웹에서 직접 접근 가능하다. 웹루트 밖 이동 권장.
- `my_product_quotes.php`, `quote_write.php`, `ajax/submit_quote_inquiry.php` 등 유입 링크가 없는 고아 페이지 정리.
- 레거시 `?category=` 계산기의 JS 오류(`#specification` 미존재로 TypeError) 수정.
- 카카오 알림톡 연동 (테스트 모드 해제 + API 키 설정 선행 필요).
- `admin_product_quotes.php` 인라인 CSS를 공통 CSS로 정리.
- `board_quote` 기반 "견적문의"와 본 시스템의 통합.

---

## 14. 구현 완료 기록 (2026-09-11)

### 14.1 검증 결과

4개 관점(회귀/보안/정합성/견고성) 병렬 리뷰 후 각 지적을 적대적으로 재검증했다.
제기 20건 중 4건은 기각, 16건 확정(중복 제외 시 고유 11건)이며 전부 수정했다.

| 결함 | 심각도 | 조치 |
|---|---|---|
| `quote_cart.php`가 세션 시작 전에 로그인 판정 → 회원이 비회원으로 처리 | high | require 직후 `session_start()` 추가 |
| 헤더 뱃지 AJAX가 nginx `/ajax/` 요청 제한을 소진 → 담기 503 실패 | medium | `head.php`에서 서버사이드 렌더로 전환, 페이지당 AJAX 호출 제거 |
| 견적 제출 Rate limit이 세션 기준뿐이라 쿠키 폐기로 우회 가능 | medium | `checkIpRateLimit()` 병행 적용 |
| 자식 제품 30건에서 원산지 선택지가 비어 담기 불가 | medium | 조회 SQL에 `parent_available_origins` 추가 후 폴백 |
| `product_detail_v2.php` / `_calc.php` 직접 접근으로 모드 전환 무력화 | low | nginx에 `product_detail_(v2\|calc\|backup_)` 404 규칙 추가 |
| 같은 값으로 수량 저장 시 오류 알림 | low | `QuoteCart::update()`가 '변경 없음'과 '권한 없음'을 구분 |
| 길이는 미터 목록인데 단위에서 mm 선택 가능 | low | 목록 모드에서 단위 M 고정 + 서버에서도 강제 |
| `cart_add.php`가 DB 오류 원문을 JSON에 노출 | low | `PDOException`을 `RuntimeException`보다 먼저 catch |
| 관리자 목록에서 PHP 8.3 Deprecated 경고 | low | 널 병합 연산자 적용 |
| 상태 라벨이 목록('대기중')과 상세('접수')에서 불일치 | low | '접수'로 통일 |
| 정리 크론 미등록 | low | 파일만 제공. 운영 등록은 14.3 참조 |

`PDOException`이 `RuntimeException`의 하위 클래스라는 점이 오류 노출의 원인이었다.
catch 절 순서가 곧 보안 경계가 된다.

### 14.2 최종 상태

- `site_settings.product_detail_mode = 'calc'` (기존 자동계산 방식)
- 신규 방식은 관리자 > 사이트 관리에서 전환한다. 전환 즉시 반영되며 배포가 필요 없다.
- 기존 데모 견적 3건 유지. 테스트로 만든 데이터는 모두 삭제했다.
- `nginx.conf` 백업: `nginx.conf.bak_20260911_quotev2`

### 14.3 운영 반영 시 남은 작업

1. 비회원 장바구니 정리 크론 등록 (`sudo crontab -u cnst -e`)
   ```
   45 3 * * * docker exec project1_php php /var/www/html/cron/quote_cart_cleanup.php >> /home/cnst/www/html/webservice/logs/quote_cart_cleanup.log 2>&1
   ```
2. 신규 방식 전환 전 관리자 미리보기로 확인 권장
   관리자 로그인 후 `product_detail.php?id=9&preview=quote`
