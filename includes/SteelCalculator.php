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
     */
    public function calculateBeamWeight($unitWeight, $length, $quantity = 1) {
        // 중량(kg) = 단위중량(kg/m) × 길이(m) × 수량
        return $unitWeight * $length * $quantity;
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
     */
    public function calculateAngleWeight($width1, $width2, $thickness, $length, $quantity = 1) {
        // 중량(kg) = [(A + B - t) × t × 0.00785] × 길이(m) × 수량
        $weight_per_meter = ($width1 + $width2 - $thickness) * $thickness * 0.00785;
        return $weight_per_meter * $length * $quantity;
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
                return $this->calculateAngleWeight(
                    $specifications['width1'] ?? 0,
                    $specifications['width2'] ?? $specifications['width1'] ?? 0,
                    $specifications['thickness'] ?? 0,
                    $length,
                    $quantity
                );
                
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
}
?>