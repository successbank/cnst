<?php
/**
 * 철강 제품 계산 클래스
 */
class SteelCalculator {
    private $pdo;
    private $density = 7850; // 철의 밀도 kg/m³
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * H형강, I형강, C형강 등 형강류 중량 계산
     * /114/2/2.txt 계산식 적용: 본당 중량은 소수점 첫째자리 반올림
     */
    public function calculateBeamWeight($unitWeight, $length, $quantity = 1, $roundPerPiece = true) {
        // 1본 중량 계산 (단위중량 × 길이)
        $weightPerPiece = $unitWeight * $length;

        // H형강의 경우 본당 중량을 반올림
        if ($roundPerPiece) {
            $weightPerPiece = round($weightPerPiece);
        }

        // 총 중량 = 1본 중량 × 수량
        return $weightPerPiece * $quantity;
    }
    
    /**
     * 철판/강판 중량 계산
     */
    public function calculatePlateWeight($thickness, $width, $length, $quantity = 1) {
        // 중량(kg) = 두께(mm) × 폭(mm) × 길이(mm) × 7.85 × 10^-6 × 수량
        return $thickness * $width * $length * ($this->density / 1000000000) * $quantity;
    }
    
    /**
     * 사각파이프 중량 계산
     */
    public function calculateSquarePipeWeight($width, $height, $thickness, $length, $quantity = 1) {
        // 중량(kg) = [(외경둘레 - 4×두께) × 두께 × 0.00785] × 길이(m) × 수량
        $perimeter = 2 * ($width + $height);
        $weight_per_meter = ($perimeter - 4 * $thickness) * $thickness * 0.00785;
        return $weight_per_meter * $length * $quantity;
    }
    
    /**
     * 원형파이프 중량 계산
     */
    public function calculateRoundPipeWeight($outerDiameter, $thickness, $length, $quantity = 1) {
        // 중량(kg) = [(외경 - 두께) × 두께 × 0.02466] × 길이(m) × 수량
        $weight_per_meter = ($outerDiameter - $thickness) * $thickness * 0.02466;
        return $weight_per_meter * $length * $quantity;
    }
    
    /**
     * 환봉 중량 계산
     */
    public function calculateRoundBarWeight($diameter, $length, $quantity = 1) {
        // 중량(kg) = 직경² × 0.00617 × 길이(m) × 수량
        return pow($diameter, 2) * 0.00617 * $length * $quantity;
    }
    
    /**
     * 평철 중량 계산
     */
    public function calculateFlatBarWeight($width, $thickness, $length, $quantity = 1) {
        // 중량(kg) = 폭(mm) × 두께(mm) × 0.00785 × 길이(m) × 수량
        return $width * $thickness * 0.00785 * $length * $quantity;
    }
    
    /**
     * 앵글(ㄱ형강) 중량 계산
     * /114/5/ㄱ형강.txt 계산식 적용: 소수점 둘째자리까지 계산
     * @param mixed $unitWeight 단위중량 또는 width1
     * @param float $length 길이
     * @param int $quantity 수량
     * @param bool $useUnitWeight 단위중량 사용 여부
     * @param bool $isUnequal 부등변 여부 (true면 길이제곱 사용)
     */
    public function calculateAngleWeight($unitWeight, $length, $quantity = 1, $useUnitWeight = true, $isUnequal = false) {
        if ($useUnitWeight) {
            // 단위중량이 주어진 경우 (데이터베이스 방식)
            if ($isUnequal) {
                // 부등변ㄱ형강: 단위중량 × 길이² × 수량
                $weightPerPiece = round($unitWeight * $length * $length, 2);
            } else {
                // 일반 ㄱ형강: 단위중량 × 길이 × 수량
                $weightPerPiece = round($unitWeight * $length, 2);
            }
            // 총 중량 = 본당중량 × 수량 (소수점 둘째자리 올림)
            $totalWeight = $weightPerPiece * $quantity;
            return round($totalWeight, 2);
        } else {
            // 기존 계산 방식 (치수로 계산)
            $width1 = func_get_arg(0);
            $width2 = func_get_arg(1);
            $thickness = func_get_arg(2);
            $weight_per_meter = ($width1 + $width2 - $thickness) * $thickness * 0.00785;
            if ($isUnequal) {
                // 부등변ㄱ형강: 길이제곱 사용
                $weightPerPiece = round($weight_per_meter * $length * $length, 2);
            } else {
                $weightPerPiece = round($weight_per_meter * $length, 2);
            }
            $totalWeight = $weightPerPiece * $quantity;
            return round($totalWeight, 2);
        }
    }
    
    /**
     * 철근 중량 계산 (기존 시스템과 호환)
     */
    public function calculateRebarWeight($diameter, $length, $quantity = 1) {
        // 중량(kg) = d² × 0.00617 × 길이(m) × 수량
        return pow($diameter, 2) * 0.00617 * $length * $quantity;
    }
    
    /**
     * 카테고리와 규격에 따른 자동 계산
     */
    public function calculateWeight($categoryCode, $specifications, $length, $quantity = 1) {
        switch ($categoryCode) {
            case 'h-beam':
            case 'light-h-beam':
            case 'i-beam':
            case 'c-beam':
            case 'channel':
                return $this->calculateBeamWeight(
                    $specifications['unit_weight'] ?? 0,
                    $length,
                    $quantity
                );
                
            case 'steel-plate':
                return $this->calculatePlateWeight(
                    $specifications['thickness'] ?? 0,
                    $specifications['width'] ?? 0,
                    $length * 1000, // m to mm
                    $quantity
                );
                
            case 'square-pipe':
                return $this->calculateSquarePipeWeight(
                    $specifications['width'] ?? 0,
                    $specifications['height'] ?? $specifications['width'] ?? 0,
                    $specifications['thickness'] ?? 0,
                    $length,
                    $quantity
                );
                
            case 'round-pipe':
                return $this->calculateRoundPipeWeight(
                    $specifications['outer_diameter'] ?? 0,
                    $specifications['thickness'] ?? 0,
                    $length,
                    $quantity
                );
                
            case 'round-bar':
                return $this->calculateRoundBarWeight(
                    $specifications['diameter'] ?? 0,
                    $length,
                    $quantity
                );
                
            case 'flat-bar':
                return $this->calculateFlatBarWeight(
                    $specifications['width'] ?? 0,
                    $specifications['thickness'] ?? 0,
                    $length,
                    $quantity
                );
                
            case 'angle':
                // 일반 ㄱ형강 - 단위중량 방식 사용
                if (isset($specifications['unit_weight'])) {
                    return $this->calculateAngleWeight(
                        $specifications['unit_weight'],
                        $length,
                        $quantity,
                        true,
                        false // 일반 ㄱ형강
                    );
                } else {
                    return $this->calculateAngleWeight(
                        $specifications['width1'] ?? 0,
                        $specifications['width2'] ?? $specifications['width1'] ?? 0,
                        $specifications['thickness'] ?? 0,
                        $length,
                        $quantity,
                        false,
                        false // 일반 ㄱ형강
                    );
                }
                break;

            case 'unequal-angle':
                // 부등변ㄱ형강 - 길이제곱 사용
                if (isset($specifications['unit_weight'])) {
                    return $this->calculateAngleWeight(
                        $specifications['unit_weight'],
                        $length,
                        $quantity,
                        true,
                        true // 부등변ㄱ형강
                    );
                } else {
                    return $this->calculateAngleWeight(
                        $specifications['width1'] ?? 0,
                        $specifications['width2'] ?? $specifications['width1'] ?? 0,
                        $specifications['thickness'] ?? 0,
                        $length,
                        $quantity,
                        false,
                        true // 부등변ㄱ형강
                    );
                }
                
            case 'rebar':
                return $this->calculateRebarWeight(
                    $specifications['diameter'] ?? 0,
                    $length,
                    $quantity
                );
                
            default:
                // 기본 단위중량 방식
                if (isset($specifications['unit_weight'])) {
                    return $this->calculateBeamWeight(
                        $specifications['unit_weight'],
                        $length,
                        $quantity
                    );
                }
                return 0;
        }
    }
    
    /**
     * 가격 계산
     */
    public function calculatePrice($weight, $pricePerUnit, $priceType = 'per_kg') {
        switch ($priceType) {
            case 'per_kg':
                return $weight * $pricePerUnit;
            case 'per_ton':
                return ($weight / 1000) * $pricePerUnit;
            case 'per_meter':
                // 길이 기준 가격은 별도 처리 필요
                return $pricePerUnit;
            default:
                return 0;
        }
    }
    
    /**
     * 제품 규격 조회
     */
    public function getProductSpecifications($productId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM product_specifications 
            WHERE product_id = ? AND is_active = 1 
            ORDER BY display_order
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 제품 가격 조회
     */
    public function getProductPrice($productId, $specId = null) {
        $query = "
            SELECT * FROM product_prices
            WHERE product_id = ?
            AND is_active = 1
            AND (effective_date <= CURDATE() OR effective_date IS NULL)
            AND (expiry_date >= CURDATE() OR expiry_date IS NULL)
        ";

        $params = [$productId];

        if ($specId) {
            $query .= " AND (spec_id = ? OR spec_id IS NULL)";
            $params[] = $specId;
        }

        $query .= " ORDER BY spec_id DESC, effective_date DESC LIMIT 1";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 제품별 계산 예제 생성
     */
    public function getBeamCalculationExamples($categoryCode = 'h-beam') {
        $examples = [];

        if ($categoryCode == 'h-beam') {
            // H형강 예제 - 반올림
            $examples[] = [
                'specification' => '100×100×6×8',
                'unit_weight' => 17.2,
                'length' => 11,
                'quantity' => 10,
                'weight_per_piece' => 189,
                'total_weight' => 1890
            ];

            $examples[] = [
                'specification' => '125×125×6.5×9',
                'unit_weight' => 23.8,
                'length' => 12,
                'quantity' => 9,
                'weight_per_piece' => 286,
                'total_weight' => 2574
            ];
        } elseif ($categoryCode == 'angle') {
            // ㄱ형강 예제 - 소수점 둘째자리
            $examples[] = [
                'specification' => '25×25×3T',
                'unit_weight' => 1.12,
                'length' => 8,
                'quantity' => 14,
                'weight_per_piece' => 8.96,  // 1.12 × 8 = 8.96
                'total_weight' => 125.44     // 8.96 × 14 = 125.44
            ];
        } elseif ($categoryCode == 'unequal-angle') {
            // 부등변ㄱ형강 예제 (길이제곱 사용)
            $examples[] = [
                'specification' => '50×30×3T',
                'unit_weight' => 1.83,
                'length' => 9,
                'quantity' => 9,
                'weight_per_piece' => 148.23,  // 1.83 × 9² = 1.83 × 81 = 148.23
                'total_weight' => 1334.07      // 148.23 × 9 = 1334.07
            ];
        }

        return $examples;
    }

    /**
     * 계산 결과 포맷팅
     */
    public function formatCalculationResult($unitWeight, $length, $quantity) {
        // 1본 중량 계산
        $rawWeightPerPiece = $unitWeight * $length;
        $roundedWeightPerPiece = round($rawWeightPerPiece);

        // 총 중량
        $totalWeight = $roundedWeightPerPiece * $quantity;

        return [
            'unit_weight' => $unitWeight,
            'length' => $length,
            'quantity' => $quantity,
            'raw_weight_per_piece' => $rawWeightPerPiece,
            'rounded_weight_per_piece' => $roundedWeightPerPiece,
            'total_weight' => $totalWeight,
            'calculation_steps' => [
                '1단계' => sprintf("단위중량 %.1fkg/m × 길이 %dm = %.1fkg", $unitWeight, $length, $rawWeightPerPiece),
                '2단계' => sprintf("소수점 첫째자리 반올림: %.1fkg → %dkg (본당 중량)", $rawWeightPerPiece, $roundedWeightPerPiece),
                '3단계' => sprintf("본당 중량 %dkg × 수량 %d본 = %s kg (총 중량)",
                    $roundedWeightPerPiece, $quantity, number_format($totalWeight))
            ]
        ];
    }
}
?>