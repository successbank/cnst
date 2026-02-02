<?php
/**
 * ProductImportExportService v2
 * 제품 CSV 임포트/엑스포트 핵심 서비스 클래스
 * - 42컬럼 v2 포맷 + 36컬럼 v1 하위 호환
 * - 트랜잭션 기반 임포트
 * - 변경 미리보기(diff) 지원
 * - 임포트 이력 기록
 */
class ProductImportExportService
{
    private PDO $pdo;

    // 삭제 건수 제한
    const MAX_DELETE_COUNT = 50;

    // 파일 크기 제한 (10MB)
    const MAX_FILE_SIZE = 10 * 1024 * 1024;

    // v2 헤더 (42컬럼)
    const HEADERS_V2 = [
        'ID',                    // 0
        '카테고리코드',          // 1
        '카테고리명',            // 2 (읽기전용)
        '제품명',                // 3 (필수)
        '제품코드',              // 4
        '규격(텍스트)',          // 5 (필수)
        '규격(코드)',            // 6 (신규)
        '설명',                  // 7
        '가격',                  // 8
        '가격단위',              // 9
        '단위',                  // 10
        '최소주문수량',          // 11
        '재고상태',              // 12
        '원산지',                // 13
        '제조사',                // 14
        '치수',                  // 15
        '중량',                  // 16
        '재질',                  // 17
        '단위중량(kg/m)',        // 18
        '계산기유형',            // 19
        '계산기보유',            // 20
        '사용가능재질',          // 21
        '사용가능원산지',        // 22
        '재질별추가가격',        // 23
        '원산지별추가가격',      // 24
        '특징',                  // 25
        '배송정보',              // 26
        '추천제품',              // 27
        '활성화',                // 28
        '기본길이',              // 29
        '최소길이',              // 30
        '최대길이',              // 31
        '표준길이',              // 32
        '홈페이지표시',          // 33
        '홈페이지순서',          // 34 (신규)
        '부모제품ID',            // 35 (신규)
        '표시방식',              // 36 (신규)
        '철근최소가격',          // 37 (신규)
        '철근최대가격',          // 38 (신규)
        '조회수',                // 39 (읽기전용)
        '등록일',                // 40 (읽기전용)
        '삭제(Y)',               // 41
    ];

    // 컬럼 수 상수
    const V1_COLUMN_COUNT = 36;
    const V2_COLUMN_COUNT = 42;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ─────────────────────────────────────────────
    // 헬퍼 메서드
    // ─────────────────────────────────────────────

    private function jsonArrayToComma(?string $json): string
    {
        if (empty($json)) return '';
        $arr = json_decode($json, true);
        return is_array($arr) ? implode(',', $arr) : '';
    }

    private function jsonObjectToSemicolon(?string $json): string
    {
        if (empty($json)) return '';
        $obj = json_decode($json, true);
        if (!is_array($obj)) return '';
        $parts = [];
        foreach ($obj as $k => $v) {
            $parts[] = $k . ':' . $v;
        }
        return implode(';', $parts);
    }

    private function commaToJsonArray(string $str): ?string
    {
        $str = trim($str);
        if ($str === '') return null;
        $arr = array_filter(array_map('trim', explode(',', $str)), fn($v) => $v !== '');
        return !empty($arr) ? json_encode(array_values($arr), JSON_UNESCAPED_UNICODE) : null;
    }

    private function semicolonToJsonObject(string $str): ?string
    {
        $str = trim($str);
        if ($str === '') return null;
        $obj = [];
        foreach (explode(';', $str) as $pair) {
            $pair = trim($pair);
            if (empty($pair)) continue;
            $parts = explode(':', $pair, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim($parts[1]);
                if ($key !== '') $obj[$key] = $val;
            }
        }
        return !empty($obj) ? json_encode($obj, JSON_UNESCAPED_UNICODE) : null;
    }

    private function ynToBool(string $val, int $default = 0): int
    {
        $val = strtoupper(trim($val));
        if ($val === 'Y') return 1;
        if ($val === 'N') return 0;
        return $default;
    }

    private function boolToYn($val): string
    {
        return $val ? 'Y' : 'N';
    }

    private function getValidCategories(): array
    {
        $stmt = $this->pdo->query("SELECT category_code, category_name FROM product_categories");
        $map = [];
        while ($row = $stmt->fetch()) {
            $map[$row['category_code']] = $row['category_name'];
        }
        return $map;
    }

    private function getExistingProducts(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM products ORDER BY id ASC");
        $map = [];
        while ($row = $stmt->fetch()) {
            $map[(int)$row['id']] = $row;
        }
        return $map;
    }

    private function openCsv(string $filePath)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException('파일을 열 수 없습니다.');
        }
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        return $handle;
    }

    private function detectFormat(int $columnCount): string
    {
        if ($columnCount >= self::V2_COLUMN_COUNT) return 'v2';
        if ($columnCount >= self::V1_COLUMN_COUNT) return 'v1';
        if ($columnCount >= 23) return 'legacy';
        throw new \RuntimeException("지원되지 않는 CSV 포맷입니다 (컬럼 수: {$columnCount}).");
    }

    /**
     * CSV 행 파싱 - 포맷별 매핑
     */
    private function parseRow(array $data, string $format): array
    {
        $parsed = [];

        if ($format === 'v2') {
            $parsed['id']                      = trim($data[0] ?? '');
            $parsed['category_code']           = trim($data[1] ?? '');
            $parsed['category_name_readonly']  = trim($data[2] ?? '');
            $parsed['product_name']            = trim($data[3] ?? '');
            $parsed['product_code']            = trim($data[4] ?? '');
            $parsed['specifications']          = trim($data[5] ?? '');
            $parsed['specification']           = trim($data[6] ?? '');
            $parsed['description']             = str_replace(['{{NEWLINE}}', '{{BR}}'], ["\n", "\n"], trim($data[7] ?? ''));
            $parsed['price']                   = ($data[8] ?? '') !== '' ? $data[8] : null;
            $parsed['price_unit']              = trim($data[9] ?? 'kg');
            $parsed['unit']                    = trim($data[10] ?? 'TON');
            $parsed['min_order_qty']           = ($data[11] ?? '') !== '' ? (int)$data[11] : 1;
            $parsed['stock_status']            = trim($data[12] ?? 'in_stock');
            $parsed['origin']                  = trim($data[13] ?? '');
            $parsed['manufacturer']            = trim($data[14] ?? '');
            $parsed['dimensions']              = trim($data[15] ?? '');
            $parsed['weight']                  = trim($data[16] ?? '');
            $parsed['material']                = trim($data[17] ?? '');
            $parsed['specification_weight']    = ($data[18] ?? '') !== '' ? (float)$data[18] : null;
            $parsed['calculation_type']        = trim($data[19] ?? 'linear');
            $parsed['has_calculator']          = $this->ynToBool($data[20] ?? 'N');
            $parsed['available_materials']     = $this->commaToJsonArray($data[21] ?? '');
            $parsed['available_origins']       = $this->commaToJsonArray($data[22] ?? '');
            $parsed['material_price_data']     = $this->semicolonToJsonObject($data[23] ?? '');
            $parsed['origin_price_data']       = $this->semicolonToJsonObject($data[24] ?? '');
            $parsed['features']                = trim($data[25] ?? '');
            $parsed['delivery_info']           = trim($data[26] ?? '');
            $parsed['is_featured']             = $this->ynToBool($data[27] ?? 'N');
            $parsed['is_active']               = $this->ynToBool($data[28] ?? 'Y', 1);
            $parsed['base_length']             = ($data[29] ?? '') !== '' ? (float)$data[29] : 6;
            $parsed['min_length']              = ($data[30] ?? '') !== '' ? (float)$data[30] : null;
            $parsed['max_length']              = ($data[31] ?? '') !== '' ? (float)$data[31] : null;
            $parsed['standard_length']         = ($data[32] ?? '') !== '' && is_numeric(trim($data[32] ?? '')) ? (float)$data[32] : null;
            $parsed['show_on_homepage']        = $this->ynToBool($data[33] ?? 'Y', 1);
            $parsed['homepage_display_order']  = ($data[34] ?? '') !== '' ? (int)$data[34] : 0;
            $parsed['parent_product_id']       = ($data[35] ?? '') !== '' && is_numeric(trim($data[35] ?? '')) ? (int)$data[35] : null;
            $parsed['display_mode']            = trim($data[36] ?? 'single');
            $parsed['rebar_manual_min_price']  = ($data[37] ?? '') !== '' ? (float)$data[37] : null;
            $parsed['rebar_manual_max_price']  = ($data[38] ?? '') !== '' ? (float)$data[38] : null;
            $parsed['delete_flag']             = strtoupper(trim($data[41] ?? ''));

        } elseif ($format === 'v1') {
            $parsed['id']                      = trim($data[0] ?? '');
            $parsed['category_code']           = trim($data[1] ?? '');
            $parsed['category_name_readonly']  = trim($data[2] ?? '');
            $parsed['product_name']            = trim($data[3] ?? '');
            $parsed['product_code']            = trim($data[4] ?? '');
            $parsed['specifications']          = trim($data[5] ?? '');
            $parsed['specification']           = '';
            $parsed['description']             = str_replace(['{{NEWLINE}}', '{{BR}}'], ["\n", "\n"], trim($data[6] ?? ''));
            $parsed['price']                   = ($data[7] ?? '') !== '' ? $data[7] : null;
            $parsed['price_unit']              = trim($data[8] ?? 'kg');
            $parsed['unit']                    = trim($data[9] ?? 'TON');
            $parsed['min_order_qty']           = ($data[10] ?? '') !== '' ? (int)$data[10] : 1;
            $parsed['stock_status']            = trim($data[11] ?? 'in_stock');
            $parsed['origin']                  = trim($data[12] ?? '');
            $parsed['manufacturer']            = trim($data[13] ?? '');
            $parsed['dimensions']              = trim($data[14] ?? '');
            $parsed['weight']                  = trim($data[15] ?? '');
            $parsed['material']                = trim($data[16] ?? '');
            $parsed['specification_weight']    = ($data[17] ?? '') !== '' ? (float)$data[17] : null;
            $parsed['calculation_type']        = trim($data[18] ?? 'linear');
            $parsed['has_calculator']          = $this->ynToBool($data[19] ?? 'N');
            $parsed['available_materials']     = $this->commaToJsonArray($data[20] ?? '');
            $parsed['available_origins']       = $this->commaToJsonArray($data[21] ?? '');
            $parsed['material_price_data']     = $this->semicolonToJsonObject($data[22] ?? '');
            $parsed['origin_price_data']       = $this->semicolonToJsonObject($data[23] ?? '');
            $parsed['features']                = trim($data[24] ?? '');
            $parsed['delivery_info']           = trim($data[25] ?? '');
            $parsed['is_featured']             = $this->ynToBool($data[26] ?? 'N');
            $parsed['is_active']               = $this->ynToBool($data[27] ?? 'Y', 1);
            $parsed['base_length']             = ($data[28] ?? '') !== '' ? (float)$data[28] : 6;
            $parsed['min_length']              = ($data[29] ?? '') !== '' ? (float)$data[29] : null;
            $parsed['max_length']              = ($data[30] ?? '') !== '' ? (float)$data[30] : null;
            $parsed['standard_length']         = ($data[31] ?? '') !== '' && is_numeric(trim($data[31] ?? '')) ? (float)$data[31] : null;
            $parsed['show_on_homepage']        = $this->ynToBool($data[32] ?? 'Y', 1);
            $parsed['homepage_display_order']  = 0;
            $parsed['parent_product_id']       = null;
            $parsed['display_mode']            = 'single';
            $parsed['rebar_manual_min_price']  = null;
            $parsed['rebar_manual_max_price']  = null;
            $parsed['delete_flag']             = strtoupper(trim($data[35] ?? ''));

        } else {
            // legacy (23+ 컬럼)
            $parsed['id']                      = trim($data[0] ?? '');
            $parsed['category_code']           = trim($data[1] ?? '');
            $parsed['category_name_readonly']  = trim($data[2] ?? '');
            $parsed['product_name']            = trim($data[3] ?? '');
            $parsed['product_code']            = trim($data[4] ?? '');
            $parsed['specifications']          = trim($data[5] ?? '');
            $parsed['specification']           = '';
            $parsed['description']             = str_replace(['{{NEWLINE}}', '{{BR}}'], ["\n", "\n"], trim($data[6] ?? ''));
            $parsed['price']                   = ($data[7] ?? '') !== '' ? $data[7] : null;
            $parsed['price_unit']              = 'kg';
            $parsed['unit']                    = trim($data[8] ?? 'TON');
            $parsed['min_order_qty']           = ($data[9] ?? '') !== '' ? (int)$data[9] : 1;
            $parsed['stock_status']            = trim($data[10] ?? 'in_stock');
            $parsed['origin']                  = trim($data[11] ?? '');
            $parsed['manufacturer']            = trim($data[12] ?? '');
            $parsed['dimensions']              = trim($data[13] ?? '');
            $parsed['weight']                  = trim($data[14] ?? '');
            $parsed['material']                = trim($data[15] ?? '');
            $parsed['specification_weight']    = null;
            $parsed['calculation_type']        = 'linear';
            $parsed['has_calculator']          = 0;
            $parsed['available_materials']     = null;
            $parsed['available_origins']       = null;
            $parsed['material_price_data']     = null;
            $parsed['origin_price_data']       = null;
            $parsed['features']                = trim($data[17] ?? '');
            $parsed['delivery_info']           = trim($data[18] ?? '');
            $parsed['is_featured']             = $this->ynToBool($data[19] ?? 'N');
            $parsed['is_active']               = $this->ynToBool($data[20] ?? 'Y', 1);
            $parsed['base_length']             = 6;
            $parsed['min_length']              = null;
            $parsed['max_length']              = null;
            $parsed['standard_length']         = null;
            $parsed['show_on_homepage']        = 1;
            $parsed['homepage_display_order']  = 0;
            $parsed['parent_product_id']       = null;
            $parsed['display_mode']            = 'single';
            $parsed['rebar_manual_min_price']  = null;
            $parsed['rebar_manual_max_price']  = null;
            $parsed['delete_flag']             = '';
        }

        // 공통 정규화
        $parsed['product_code'] = $parsed['product_code'] !== '' ? $parsed['product_code'] : null;
        if (!in_array($parsed['price_unit'], ['kg', 'piece'])) $parsed['price_unit'] = 'kg';
        if (!in_array($parsed['calculation_type'], ['linear', 'sheet', 'piece'])) $parsed['calculation_type'] = 'linear';
        if (!in_array($parsed['stock_status'], ['in_stock', 'out_of_stock', 'on_order'])) $parsed['stock_status'] = 'in_stock';
        if (!in_array($parsed['display_mode'], ['single', 'by_specification'])) $parsed['display_mode'] = 'single';

        return $parsed;
    }

    private function validateRow(array $parsed, array $validCategories): array
    {
        $errors = [];
        if ($parsed['delete_flag'] === 'Y') {
            if (!$parsed['id'] || !is_numeric($parsed['id'])) {
                $errors[] = '삭제하려면 유효한 ID가 필요합니다';
            }
            return $errors;
        }
        if (empty($parsed['category_code'])) {
            $errors[] = '카테고리코드 누락';
        } elseif (!isset($validCategories[$parsed['category_code']])) {
            $errors[] = "존재하지 않는 카테고리코드 ({$parsed['category_code']})";
        }
        if (empty($parsed['product_name'])) {
            $errors[] = '제품명 누락';
        }
        return $errors;
    }

    private function computeDiff(array $parsed, array $existing): array
    {
        $changes = [];
        $compareFields = [
            'category_code', 'product_name', 'product_code', 'specifications',
            'specification', 'description', 'price', 'price_unit', 'unit',
            'min_order_qty', 'stock_status', 'origin', 'manufacturer',
            'dimensions', 'weight', 'material', 'specification_weight',
            'calculation_type', 'has_calculator', 'available_materials',
            'available_origins', 'material_price_data', 'origin_price_data',
            'features', 'delivery_info', 'is_featured', 'is_active',
            'base_length', 'min_length', 'max_length', 'standard_length',
            'show_on_homepage', 'homepage_display_order', 'parent_product_id',
            'display_mode', 'rebar_manual_min_price', 'rebar_manual_max_price'
        ];
        foreach ($compareFields as $field) {
            $oldStr = ($existing[$field] ?? null) === null ? '' : (string)$existing[$field];
            $newStr = ($parsed[$field] ?? null) === null ? '' : (string)$parsed[$field];
            if ($oldStr !== $newStr) {
                $changes[$field] = ['before' => $oldStr, 'after' => $newStr];
            }
        }
        return $changes;
    }

    // ─────────────────────────────────────────────
    // 엑스포트
    // ─────────────────────────────────────────────

    public function exportProducts(string $categoryCode = '', array $selectedIds = []): void
    {
        $where = ["1=1"];
        $params = [];
        if ($categoryCode) {
            $where[] = "p.category_code = ?";
            $params[] = $categoryCode;
        }
        if (!empty($selectedIds)) {
            $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
            $where[] = "p.id IN ($placeholders)";
            $params = array_merge($params, $selectedIds);
        }
        $whereClause = implode(" AND ", $where);

        // specification JOIN 버그 수정: p.specification (코드 필드) 사용
        $stmt = $this->pdo->prepare("
            SELECT p.*, pc.category_name, uw.unit_weight
            FROM products p
            JOIN product_categories pc ON p.category_code = pc.category_code
            LEFT JOIN unit_weights uw ON p.specification = uw.specification
            WHERE $whereClause
            ORDER BY p.id ASC
        ");
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        $categoryName = '전체';
        if ($categoryCode) {
            $stmtCat = $this->pdo->prepare("SELECT category_name FROM product_categories WHERE category_code = ?");
            $stmtCat->execute([$categoryCode]);
            $cat = $stmtCat->fetch();
            if ($cat) $categoryName = $cat['category_name'];
        }

        $selectionSuffix = !empty($selectedIds) ? '_선택' . count($selectedIds) . '건' : '';
        $filename = 'products_' . $categoryName . $selectionSuffix . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');
        fputcsv($output, self::HEADERS_V2);

        foreach ($products as $p) {
            fputcsv($output, [
                $p['id'],
                $p['category_code'],
                $p['category_name'],
                $p['product_name'],
                $p['product_code'] ?? '',
                $p['specifications'] ?? '',
                $p['specification'] ?? '',
                str_replace(["\r\n", "\r", "\n"], '{{NEWLINE}}', $p['description'] ?? ''),
                $p['price'] ?? '',
                $p['price_unit'] ?? 'kg',
                $p['unit'] ?? '',
                $p['min_order_qty'] ?? 1,
                $p['stock_status'] ?? 'in_stock',
                $p['origin'] ?? '',
                $p['manufacturer'] ?? '',
                $p['dimensions'] ?? '',
                $p['weight'] ?? '',
                $p['material'] ?? '',
                $p['specification_weight'] ?? '',
                $p['calculation_type'] ?? 'linear',
                $this->boolToYn($p['has_calculator'] ?? 0),
                $this->jsonArrayToComma($p['available_materials'] ?? ''),
                $this->jsonArrayToComma($p['available_origins'] ?? ''),
                $this->jsonObjectToSemicolon($p['material_price_data'] ?? ''),
                $this->jsonObjectToSemicolon($p['origin_price_data'] ?? ''),
                $p['features'] ?? '',
                $p['delivery_info'] ?? '',
                $this->boolToYn($p['is_featured'] ?? 0),
                $this->boolToYn($p['is_active'] ?? 1),
                $p['base_length'] ?? 6,
                $p['min_length'] ?? '',
                $p['max_length'] ?? '',
                $p['standard_length'] ?? '',
                $this->boolToYn($p['show_on_homepage'] ?? 0),
                $p['homepage_display_order'] ?? 0,
                $p['parent_product_id'] ?? '',
                $p['display_mode'] ?? 'single',
                $p['rebar_manual_min_price'] ?? '',
                $p['rebar_manual_max_price'] ?? '',
                $p['view_count'] ?? 0,
                $p['created_at'] ?? '',
                '',
            ]);
        }
        fclose($output);
    }

    // ─────────────────────────────────────────────
    // 템플릿 다운로드
    // ─────────────────────────────────────────────

    public function downloadTemplate(string $categoryCode = '', bool $includeSample = false): void
    {
        $categoryName = '전체';
        if ($categoryCode) {
            $stmt = $this->pdo->prepare("SELECT category_name FROM product_categories WHERE category_code = ?");
            $stmt->execute([$categoryCode]);
            $cat = $stmt->fetch();
            if ($cat) $categoryName = $cat['category_name'];
        }

        $filename = 'products_template_' . $categoryName . '_' . date('Ymd') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');
        fputcsv($output, self::HEADERS_V2);

        if ($includeSample) {
            fputcsv($output, [
                '', $categoryCode ?: 'h-beam', $categoryName ?: 'H빔',
                '샘플 제품명', 'SAMPLE-001', '100x100x6x8', 'H-100x100',
                '샘플 설명입니다.', '100000', 'kg', 'TON', '1', 'in_stock',
                '국산', '현대제철', '100x100', '17.2', 'SS275', '17.2',
                'linear', 'Y', 'SS275,SS400,SM490A', '국산,중국산',
                'SS275:0;SS400:50;SM490A:100', '국산:0;중국산:-100',
                '고품질 제품', '2-3일 내 배송', 'N', 'Y',
                '6', '0.5', '12', '6', 'Y', '0', '', 'single', '', '',
                '0', '', '',
            ]);
        }

        if ($categoryCode && !$includeSample) {
            $templateRow = array_fill(0, self::V2_COLUMN_COUNT, '');
            $templateRow[1] = $categoryCode;
            $templateRow[2] = $categoryName;
            fputcsv($output, $templateRow);
        }
        fclose($output);
    }

    // ─────────────────────────────────────────────
    // 미리보기 (파싱 + 검증 + diff)
    // ─────────────────────────────────────────────

    public function parseAndValidate(string $filePath): array
    {
        $handle = $this->openCsv($filePath);
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new \RuntimeException('CSV 헤더를 읽을 수 없습니다.');
        }

        $columnCount = count($headers);
        $format = $this->detectFormat($columnCount);
        $validCategories = $this->getValidCategories();
        $existingProducts = $this->getExistingProducts();

        $result = [
            'format' => $format,
            'column_count' => $columnCount,
            'total_rows' => 0,
            'preview_data' => [],
            'summary' => ['insert' => 0, 'update' => 0, 'delete' => 0, 'error' => 0],
            'validation_errors' => [],
            'has_errors' => false,
            'delete_count' => 0,
        ];

        $lineNumber = 2;
        $deleteCount = 0;

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === 1 && trim($data[0]) === '') {
                $lineNumber++;
                continue;
            }

            $result['total_rows']++;
            $parsed = $this->parseRow($data, $format);
            $rowErrors = $this->validateRow($parsed, $validCategories);
            $action = '';
            $diffData = [];
            $isDelete = ($parsed['delete_flag'] === 'Y');

            if ($isDelete) {
                if (empty($rowErrors)) {
                    $id = (int)$parsed['id'];
                    if (!isset($existingProducts[$id])) {
                        $rowErrors[] = '존재하지 않는 제품 ID입니다';
                    } else {
                        $deleteCount++;
                        if ($deleteCount > self::MAX_DELETE_COUNT) {
                            $rowErrors[] = '한 번에 최대 ' . self::MAX_DELETE_COUNT . '건까지만 삭제 가능합니다';
                        } else {
                            $action = 'DELETE';
                            $result['summary']['delete']++;
                        }
                    }
                }
            } else {
                if (empty($rowErrors)) {
                    $id = $parsed['id'];
                    if ($id && is_numeric($id)) {
                        $intId = (int)$id;
                        if (isset($existingProducts[$intId])) {
                            $action = 'UPDATE';
                            $result['summary']['update']++;
                            $diffData = $this->computeDiff($parsed, $existingProducts[$intId]);
                        } else {
                            $action = 'INSERT';
                            $result['summary']['insert']++;
                        }
                    } else {
                        $action = 'INSERT';
                        $result['summary']['insert']++;
                    }
                }
            }

            if (!empty($rowErrors)) {
                $result['summary']['error']++;
            }

            $result['preview_data'][] = [
                'line'     => $lineNumber,
                'id'       => $parsed['id'],
                'category' => $parsed['category_code'],
                'name'     => mb_substr($parsed['product_name'], 0, 40),
                'spec'     => mb_substr($parsed['specifications'], 0, 30),
                'action'   => $action ?: 'ERROR',
                'errors'   => $rowErrors,
                'diff'     => $diffData,
            ];

            if (!empty($rowErrors) && count($result['validation_errors']) < 50) {
                $result['validation_errors'][] = [
                    'line' => $lineNumber,
                    'errors' => $rowErrors
                ];
            }

            $lineNumber++;
        }

        fclose($handle);
        $result['delete_count'] = $deleteCount;
        $result['has_errors'] = $result['summary']['error'] > 0;
        return $result;
    }

    // ─────────────────────────────────────────────
    // 임포트 실행 (트랜잭션)
    // ─────────────────────────────────────────────

    public function executeImport(string $filePath, string $adminId, string $adminName, ?string $filename = null, ?int $fileSize = null): array
    {
        $handle = $this->openCsv($filePath);
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new \RuntimeException('CSV 헤더를 읽을 수 없습니다.');
        }

        $columnCount = count($headers);
        $format = $this->detectFormat($columnCount);
        $validCategories = $this->getValidCategories();
        $existingProducts = $this->getExistingProducts();

        $results = [
            'created' => 0, 'updated' => 0, 'deleted' => 0,
            'errors' => [], 'total_rows' => 0, 'change_summary' => [],
        ];

        $historyId = $this->createHistoryRecord($adminId, $adminName, $filename ?? basename($filePath), $fileSize ?? 0);

        $this->pdo->beginTransaction();

        try {
            $lineNumber = 2;
            $deleteCount = 0;

            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) === 1 && trim($data[0]) === '') {
                    $lineNumber++;
                    continue;
                }
                $results['total_rows']++;

                try {
                    $parsed = $this->parseRow($data, $format);
                    $rowErrors = $this->validateRow($parsed, $validCategories);

                    if (!empty($rowErrors)) {
                        $results['errors'][] = "라인 {$lineNumber}: " . implode(', ', $rowErrors);
                        $lineNumber++;
                        continue;
                    }

                    if ($parsed['delete_flag'] === 'Y') {
                        $id = (int)$parsed['id'];
                        if (!isset($existingProducts[$id])) {
                            $results['errors'][] = "라인 {$lineNumber}: 존재하지 않는 제품 ID ({$id})";
                            $lineNumber++;
                            continue;
                        }
                        $deleteCount++;
                        if ($deleteCount > self::MAX_DELETE_COUNT) {
                            $results['errors'][] = "라인 {$lineNumber}: 한 번에 최대 " . self::MAX_DELETE_COUNT . "건까지만 삭제 가능합니다 (ID: {$id})";
                            $lineNumber++;
                            continue;
                        }
                        $this->logDeletion($existingProducts[$id], $adminName);
                        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
                        $stmt->execute([$id]);
                        $results['deleted']++;
                        $results['change_summary'][] = ['action' => 'DELETE', 'id' => $id, 'name' => $existingProducts[$id]['product_name']];

                    } elseif ($parsed['id'] && is_numeric($parsed['id'])) {
                        $intId = (int)$parsed['id'];
                        if (isset($existingProducts[$intId])) {
                            $this->updateProduct($intId, $parsed);
                            $results['updated']++;
                            $diff = $this->computeDiff($parsed, $existingProducts[$intId]);
                            if (!empty($diff)) {
                                $results['change_summary'][] = ['action' => 'UPDATE', 'id' => $intId, 'name' => $parsed['product_name'], 'changes' => $diff];
                            }
                        } else {
                            $this->insertProduct($parsed);
                            $results['created']++;
                            $results['change_summary'][] = ['action' => 'INSERT', 'name' => $parsed['product_name']];
                        }
                    } else {
                        $this->insertProduct($parsed);
                        $results['created']++;
                        $results['change_summary'][] = ['action' => 'INSERT', 'name' => $parsed['product_name']];
                    }
                } catch (\PDOException $e) {
                    $results['errors'][] = "라인 {$lineNumber}: " . $e->getMessage();
                }
                $lineNumber++;
            }

            fclose($handle);
            $this->pdo->commit();
            $this->updateHistoryRecord($historyId, 'completed', $results);

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            if (is_resource($handle)) fclose($handle);
            $results['errors'][] = '트랜잭션 롤백: ' . $e->getMessage();
            $this->updateHistoryRecord($historyId, 'failed', $results);
            throw $e;
        }

        $results['history_id'] = $historyId;
        return $results;
    }

    private function insertProduct(array $parsed): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO products (
                category_code, product_name, product_code, specifications,
                specification, description, price, price_unit, unit,
                min_order_qty, stock_status, origin, manufacturer,
                dimensions, weight, material, specification_weight,
                calculation_type, has_calculator, available_materials,
                available_origins, material_price_data, origin_price_data,
                features, delivery_info, is_featured, is_active,
                base_length, min_length, max_length, standard_length,
                show_on_homepage, homepage_display_order, parent_product_id,
                display_mode, rebar_manual_min_price, rebar_manual_max_price,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $parsed['category_code'], $parsed['product_name'], $parsed['product_code'],
            $parsed['specifications'], $parsed['specification'], $parsed['description'],
            $parsed['price'], $parsed['price_unit'], $parsed['unit'],
            $parsed['min_order_qty'], $parsed['stock_status'], $parsed['origin'],
            $parsed['manufacturer'], $parsed['dimensions'], $parsed['weight'],
            $parsed['material'], $parsed['specification_weight'],
            $parsed['calculation_type'], $parsed['has_calculator'],
            $parsed['available_materials'], $parsed['available_origins'],
            $parsed['material_price_data'], $parsed['origin_price_data'],
            $parsed['features'], $parsed['delivery_info'],
            $parsed['is_featured'], $parsed['is_active'],
            $parsed['base_length'], $parsed['min_length'], $parsed['max_length'],
            $parsed['standard_length'], $parsed['show_on_homepage'],
            $parsed['homepage_display_order'], $parsed['parent_product_id'],
            $parsed['display_mode'], $parsed['rebar_manual_min_price'],
            $parsed['rebar_manual_max_price']
        ]);
    }

    private function updateProduct(int $id, array $parsed): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE products SET
                category_code = ?, product_name = ?, product_code = ?,
                specifications = ?, specification = ?, description = ?,
                price = ?, price_unit = ?, unit = ?,
                min_order_qty = ?, stock_status = ?, origin = ?,
                manufacturer = ?, dimensions = ?, weight = ?,
                material = ?, specification_weight = ?,
                calculation_type = ?, has_calculator = ?,
                available_materials = ?, available_origins = ?,
                material_price_data = ?, origin_price_data = ?,
                features = ?, delivery_info = ?, is_featured = ?, is_active = ?,
                base_length = ?, min_length = ?, max_length = ?,
                standard_length = ?, show_on_homepage = ?,
                homepage_display_order = ?, parent_product_id = ?,
                display_mode = ?, rebar_manual_min_price = ?,
                rebar_manual_max_price = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $parsed['category_code'], $parsed['product_name'], $parsed['product_code'],
            $parsed['specifications'], $parsed['specification'], $parsed['description'],
            $parsed['price'], $parsed['price_unit'], $parsed['unit'],
            $parsed['min_order_qty'], $parsed['stock_status'], $parsed['origin'],
            $parsed['manufacturer'], $parsed['dimensions'], $parsed['weight'],
            $parsed['material'], $parsed['specification_weight'],
            $parsed['calculation_type'], $parsed['has_calculator'],
            $parsed['available_materials'], $parsed['available_origins'],
            $parsed['material_price_data'], $parsed['origin_price_data'],
            $parsed['features'], $parsed['delivery_info'],
            $parsed['is_featured'], $parsed['is_active'],
            $parsed['base_length'], $parsed['min_length'], $parsed['max_length'],
            $parsed['standard_length'], $parsed['show_on_homepage'],
            $parsed['homepage_display_order'], $parsed['parent_product_id'],
            $parsed['display_mode'], $parsed['rebar_manual_min_price'],
            $parsed['rebar_manual_max_price'],
            $id
        ]);
    }

    private function logDeletion(array $product, string $deletedBy): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO product_delete_logs
            (product_id, product_name, category_code, specifications, deleted_data, deleted_by, deleted_via)
            VALUES (?, ?, ?, ?, ?, ?, 'csv_import_v2')
        ");
        $stmt->execute([
            $product['id'], $product['product_name'], $product['category_code'],
            $product['specifications'],
            json_encode($product, JSON_UNESCAPED_UNICODE),
            $deletedBy
        ]);
    }

    // ─────────────────────────────────────────────
    // 임포트 이력
    // ─────────────────────────────────────────────

    private function createHistoryRecord(string $adminId, string $adminName, string $filename, int $fileSize): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO product_import_history
            (admin_id, admin_name, filename, file_size, status, started_at)
            VALUES (?, ?, ?, ?, 'processing', NOW())
        ");
        $stmt->execute([$adminId, $adminName, $filename, $fileSize]);
        return (int)$this->pdo->lastInsertId();
    }

    private function updateHistoryRecord(int $historyId, string $status, array $results): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE product_import_history SET
                status = ?, total_rows = ?, created_count = ?, updated_count = ?,
                deleted_count = ?, error_count = ?, error_details = ?,
                change_summary = ?, completed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $status,
            $results['total_rows'] ?? 0,
            $results['created'] ?? 0,
            $results['updated'] ?? 0,
            $results['deleted'] ?? 0,
            count($results['errors'] ?? []),
            !empty($results['errors']) ? json_encode($results['errors'], JSON_UNESCAPED_UNICODE) : null,
            !empty($results['change_summary']) ? json_encode(array_slice($results['change_summary'], 0, 100), JSON_UNESCAPED_UNICODE) : null,
            $historyId
        ]);
    }

    public function getImportHistory(int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM product_import_history ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getImportHistoryCount(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM product_import_history")->fetchColumn();
    }

    // ─────────────────────────────────────────────
    // 파일 검증
    // ─────────────────────────────────────────────

    public function validateUploadedFile(array $file): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) return '파일 업로드에 실패했습니다.';
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, ['csv', 'txt'])) return 'CSV 파일만 업로드 가능합니다.';
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])) {
            return '허용되지 않은 파일 형식입니다. (감지된 타입: ' . $mimeType . ')';
        }
        if ($file['size'] > self::MAX_FILE_SIZE) return '파일 크기가 너무 큽니다. (최대 10MB)';
        return null;
    }
}
