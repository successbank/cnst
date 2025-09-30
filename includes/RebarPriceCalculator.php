<?php
/**
 * 철근 가격 계산기
 *
 * 계산식: 가격 = (기준단가 + 원산지단가 + 재질단가) × 길이 × 갯수
 *
 * 데이터 소스:
 * - rebar_prices: 규격별/원산지별 기준단가
 * - rebar_length_data: 길이별 중량 정보
 */

class RebarPriceCalculator {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * 철근 가격 계산
     *
     * @param string $specName 규격 (D10, D13 등)
     * @param float $length 길이 (m)
     * @param int $quantity 수량 (번들 수)
     * @param string $origin 원산지 (기본: 포항)
     * @param string $material 재질 (기본: SD400)
     * @return array 계산 결과
     */
    public function calculate($specName, $length, $quantity = 1, $origin = '포항', $material = 'SD400') {
        // 1. 기준단가 조회
        try {
            $basePrice = $this->getBasePrice($specName, $origin);
            if (!$basePrice) {
                throw new Exception("기준단가를 찾을 수 없습니다: {$specName}, {$origin}");
            }
        } catch (Exception $e) {
            throw $e;
        }

        // 2. 원산지 추가 단가 (현재는 기준단가에 포함되어 있음)
        $originSurcharge = 0;

        // 3. 재질 추가 단가 (SD400 기준, 다른 재질은 추가금)
        $materialSurcharge = $this->getMaterialSurcharge($material);

        // 4. 길이별 정보 조회
        $lengthData = $this->getLengthData($specName, $length);

        // 5. 가격 계산
        // 단가 = 기준단가 + 원산지 추가 + 재질 추가
        $unitPrice = $basePrice + $originSurcharge + $materialSurcharge;

        // 중량 계산
        $weightPerPiece = $lengthData ? floatval($lengthData['piece_weight']) : 0;
        $piecesPerBundle = $lengthData ? intval($lengthData['pieces_per_length']) : 0;
        $weightPerBundle = $lengthData ? floatval($lengthData['weight_per_ton']) : 0;

        // 총 중량 (톤)
        $totalWeight = ($weightPerBundle * $quantity) / 1000;

        // 총 가격 = 단가 × 총 중량 (톤 단위)
        $totalPrice = $unitPrice * $totalWeight;

        return [
            'success' => true,
            'spec_name' => $specName,
            'length' => $length,
            'quantity' => $quantity,
            'origin' => $origin,
            'material' => $material,
            'pricing' => [
                'base_price' => $basePrice,
                'origin_surcharge' => $originSurcharge,
                'material_surcharge' => $materialSurcharge,
                'unit_price' => $unitPrice,
                'unit' => '원/톤'
            ],
            'weight' => [
                'weight_per_piece' => $weightPerPiece,
                'pieces_per_bundle' => $piecesPerBundle,
                'weight_per_bundle' => $weightPerBundle,
                'total_weight_kg' => $totalWeight * 1000,
                'total_weight_ton' => $totalWeight
            ],
            'total_price' => round($totalPrice),
            'price_per_bundle' => round($totalPrice / $quantity)
        ];
    }

    /**
     * 기준단가 조회
     */
    private function getBasePrice($specName, $origin) {
        $stmt = $this->pdo->prepare("
            SELECT price
            FROM rebar_prices
            WHERE spec_name = ? AND origin = ? AND is_active = 1
            ORDER BY price_date DESC
            LIMIT 1
        ");
        $stmt->execute([$specName, $origin]);
        $result = $stmt->fetch();

        // 가격 정보가 없으면 다른 원산지도 확인
        if (!$result) {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM rebar_prices WHERE spec_name = ? AND is_active = 1
            ");
            $stmt->execute([$specName]);
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                throw new Exception("해당 규격({$specName})의 가격 정보가 없습니다. 전화 문의: 010-9820-0495");
            }
        }

        return $result ? floatval($result['price']) : null;
    }

    /**
     * 재질 추가 단가
     */
    private function getMaterialSurcharge($material) {
        $surcharges = [
            'SD400' => 0,
            'SD500' => 10000,
            'SD600' => 20000
        ];

        return $surcharges[$material] ?? 0;
    }

    /**
     * 길이별 데이터 조회
     */
    private function getLengthData($specName, $length) {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM rebar_length_data
            WHERE spec_name = ? AND ABS(length - ?) < 0.01
            LIMIT 1
        ");
        $stmt->execute([$specName, $length]);
        return $stmt->fetch();
    }

    /**
     * 사용 가능한 원산지 목록
     */
    public function getAvailableOrigins($specName = null) {
        $sql = "SELECT DISTINCT origin FROM rebar_prices WHERE is_active = 1";
        if ($specName) {
            $sql .= " AND spec_name = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$specName]);
        } else {
            $stmt = $this->pdo->query($sql);
        }

        $origins = [];
        while ($row = $stmt->fetch()) {
            $origins[] = $row['origin'];
        }

        return $origins;
    }

    /**
     * 사용 가능한 길이 목록
     */
    public function getAvailableLengths($specName) {
        $stmt = $this->pdo->prepare("
            SELECT length, piece_weight, pieces_per_length, weight_per_ton
            FROM rebar_length_data
            WHERE spec_name = ?
            ORDER BY length
        ");
        $stmt->execute([$specName]);
        return $stmt->fetchAll();
    }

    /**
     * 규격 목록 (모든 규격 반환)
     */
    public function getAvailableSpecs() {
        $stmt = $this->pdo->query("
            SELECT DISTINCT spec_name
            FROM rebar_length_data
            ORDER BY
                CAST(SUBSTRING(spec_name, 2) AS UNSIGNED)
        ");

        $specs = [];
        while ($row = $stmt->fetch()) {
            $specs[] = $row['spec_name'];
        }

        return $specs;
    }

    /**
     * 가격 정보가 있는 규격만 반환
     */
    public function getSpecsWithPrice() {
        $stmt = $this->pdo->query("
            SELECT DISTINCT spec_name
            FROM rebar_prices
            WHERE is_active = 1
            ORDER BY
                CAST(SUBSTRING(spec_name, 2) AS UNSIGNED)
        ");

        $specs = [];
        while ($row = $stmt->fetch()) {
            $specs[] = $row['spec_name'];
        }

        return $specs;
    }

    /**
     * 견적서 형식 출력
     */
    public function generateQuote($specName, $length, $quantity, $origin = '포항', $material = 'SD400') {
        $result = $this->calculate($specName, $length, $quantity, $origin, $material);

        if (!$result['success']) {
            return null;
        }

        return [
            '제품' => "철근 {$specName}",
            '규격' => $specName,
            '길이' => "{$length}m",
            '수량' => "{$quantity}번들",
            '원산지' => $origin,
            '재질' => $material,
            '단위중량' => number_format($result['weight']['weight_per_piece'], 2) . 'kg/본',
            '번들당 본수' => number_format($result['weight']['pieces_per_bundle']) . '본',
            '번들당 중량' => number_format($result['weight']['weight_per_bundle'], 1) . 'kg',
            '총 중량' => number_format($result['weight']['total_weight_kg'], 1) . 'kg (' . number_format($result['weight']['total_weight_ton'], 3) . '톤)',
            '단가' => number_format($result['pricing']['unit_price']) . '원/톤',
            '번들당 가격' => number_format($result['price_per_bundle']) . '원',
            '총 금액' => number_format($result['total_price']) . '원',
            '부가세 별도' => number_format($result['total_price'] * 0.1) . '원',
            '부가세 포함' => number_format($result['total_price'] * 1.1) . '원'
        ];
    }
}