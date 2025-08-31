<?php
require_once '../db.php';

// 샘플 데이터 - 평철
$sample_data = [
    'flat-bar' => [
        'name' => '평철',
        'type' => 'linear',
        'data' => [
            '13*3T' => ['SS400' => 0.306, 'SS400/A36' => 0.306],
            '16*3T' => ['SS400' => 0.377, 'SS400/A36' => 0.377],
            '16*4.5T' => ['SS400' => 0.565, 'SS400/A36' => 0.565],
            '19*3T' => ['SS400' => 0.448, 'SS400/A36' => 0.448],
            '19*4.5T' => ['SS400' => 0.671, 'SS400/A36' => 0.671],
            '19*6T' => ['SS400' => 0.895, 'SS400/A36' => 0.895],
            '22*3T' => ['SS400' => 0.518, 'SS400/A36' => 0.518],
            '22*4.5T' => ['SS400' => 0.777, 'SS400/A36' => 0.777],
            '22*6T' => ['SS400' => 1.04, 'SS400/A36' => 1.04],
            '25*3T' => ['SS400' => 0.589, 'SS400/A36' => 0.589],
            '25*4.5T' => ['SS400' => 0.883, 'SS400/A36' => 0.883],
            '25*6T' => ['SS400' => 1.18, 'SS400/A36' => 1.18],
            '25*9T' => ['SS400' => 1.77, 'SS400/A36' => 1.77],
            '25*12T' => ['SS400' => 2.36, 'SS400/A36' => 2.36]
        ]
    ],
    'round-bar' => [
        'name' => '환봉',
        'type' => 'linear',
        'data' => [
            'RB 6' => ['SS400' => 0.222, 'SM45C' => 0.222],
            'RB 9' => ['SS400' => 0.499, 'SM45C' => 0.499],
            'RB 10' => ['SS400' => 0.617, 'SM45C' => 0.617],
            'RB 12' => ['SS400' => 0.888, 'SM45C' => 0.888],
            'RB 13' => ['SS400' => 1.04, 'SM45C' => 1.04],
            'RB 16' => ['SS400' => 1.58, 'SM45C' => 1.58],
            'RB 19' => ['SS400' => 2.23, 'SM45C' => 2.23],
            'RB 22' => ['SS400' => 2.98, 'SM45C' => 2.98],
            'RB 25' => ['SS400' => 3.85, 'SM45C' => 3.85],
            'RB 28' => ['SS400' => 4.83, 'SM45C' => 4.83],
            'RB 32' => ['SS400' => 6.31, 'SM45C' => 6.31],
            'RB 35' => ['SS400' => 7.55, 'SM45C' => 7.55],
            'RB 38' => ['SS400' => 8.9, 'SM45C' => 8.9],
            'RB 42' => ['SS400' => 10.9, 'SM45C' => 10.9],
            'RB 45' => ['SS400' => 12.5, 'SM45C' => 12.5],
            'RB 50' => ['SS400' => 15.4, 'SM45C' => 15.4]
        ]
    ],
    'steel-plate' => [
        'name' => 'HR철판',
        'type' => 'sheet',
        'data' => [
            "1.6T*3'*6'" => ['SPHC' => 22.7, 'SS400' => 22.7],
            "2.0T*3'*6'" => ['SPHC' => 28.4, 'SS400' => 28.4],
            "2.3T*3'*6'" => ['SPHC' => 32.6, 'SS400' => 32.6],
            "2.6T*3'*6'" => ['SPHC' => 36.8, 'SS400' => 36.8],
            "3.2T*3'*6'" => ['SS400' => 45.3],
            "3.2T*4'*8'" => ['SS400' => 80.5],
            "4.5T*3'*6'" => ['SS400' => 63.7],
            "4.5T*4'*8'" => ['SS400' => 113.3],
            "4.5T*5'*10'" => ['SS400' => 177.1],
            "6.0T*3'*6'" => ['SS400' => 85.0],
            "6.0T*4'*8'" => ['SS400' => 151.1],
            "6.0T*5'*10'" => ['SS400' => 236.1],
            "9.0T*3'*6'" => ['SS400' => 127.5],
            "9.0T*4'*8'" => ['SS400' => 226.6],
            "9.0T*5'*10'" => ['SS400' => 354.1],
            "12T*3'*6'" => ['SS400' => 169.9],
            "12T*4'*8'" => ['SS400' => 302.2],
            "12T*5'*10'" => ['SS400' => 472.2]
        ]
    ],
    'angle' => [
        'name' => 'ㄱ형강',
        'type' => 'linear',
        'data' => [
            '25*25*3T' => ['SS400' => 1.12],
            '30*30*3T' => ['SS400' => 1.36],
            '40*40*3T' => ['SS400' => 1.83],
            '40*40*5T' => ['SS400' => 2.95],
            '50*50*3T' => ['SS400' => 2.29],
            '50*50*5T' => ['SS400' => 3.73],
            '50*50*6T' => ['SS400' => 4.43],
            '65*65*6T' => ['SS400' => 5.91],
            '65*65*8T' => ['SS400' => 7.66],
            '75*75*6T' => ['SS400' => 6.85],
            '75*75*9T' => ['SS400' => 10.0],
            '90*90*7T' => ['SS400' => 9.61],
            '90*90*10T' => ['SS400' => 13.3],
            '100*100*10T' => ['SS400' => 14.9],
            '100*100*13T' => ['SS400' => 19.1]
        ]
    ],
    'channel' => [
        'name' => 'ㄷ형강',
        'type' => 'linear',
        'data' => [
            '75*40*5*7' => ['SS400' => 6.92],
            '100*50*5*7.5' => ['SS400' => 9.36],
            '125*65*6*8' => ['SS400' => 13.4],
            '150*75*6.5*10' => ['SS400' => 18.6],
            '150*75*9*12.5' => ['SS400' => 24.0],
            '180*75*7*10.5' => ['SS400' => 21.4],
            '200*80*7.5*11' => ['SS400' => 24.6],
            '200*90*8*13.5' => ['SS400' => 30.3],
            '250*90*9*13' => ['SS400' => 34.6],
            '250*90*11*14.5' => ['SS400' => 40.2],
            '300*90*9*13' => ['SS400' => 38.1],
            '300*90*10*15.5' => ['SS400' => 43.8],
            '380*100*10.5*16' => ['SS400' => 54.0]
        ]
    ]
];

try {
    echo "<pre>\n";
    echo "샘플 데이터 임포트 시작...\n\n";
    
    foreach ($sample_data as $category_code => $info) {
        // 단위중량 데이터 준비
        $unit_weight_data = $info['data'];
        
        // 재질 목록 추출
        $materials = [];
        foreach ($unit_weight_data as $spec => $material_data) {
            foreach ($material_data as $material => $weight) {
                if (!in_array($material, $materials)) {
                    $materials[] = $material;
                }
            }
        }
        
        // 규격 목록
        $specifications = array_keys($unit_weight_data);
        
        // 기존 제품 확인
        $check_stmt = $pdo->prepare("SELECT id FROM products WHERE category_code = ? LIMIT 1");
        $check_stmt->execute([$category_code]);
        $existing_id = $check_stmt->fetchColumn();
        
        if ($existing_id) {
            // 업데이트
            $stmt = $pdo->prepare("
                UPDATE products SET
                    product_name = ?,
                    calculation_type = ?,
                    unit_weight_data = ?,
                    available_materials = ?,
                    available_sizes = ?,
                    has_calculator = 1
                WHERE id = ?
            ");
            
            $stmt->execute([
                $info['name'],
                $info['type'],
                json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE),
                json_encode($materials, JSON_UNESCAPED_UNICODE),
                json_encode($specifications, JSON_UNESCAPED_UNICODE),
                $existing_id
            ]);
            echo "✓ {$info['name']} 업데이트 완료: " . count($specifications) . "개 규격\n";
        } else {
            // 신규 생성
            $stmt = $pdo->prepare("
                INSERT INTO products (
                    category_code, 
                    product_name, 
                    calculation_type,
                    unit_weight_data,
                    available_materials,
                    available_sizes,
                    has_calculator,
                    is_active
                ) VALUES (?, ?, ?, ?, ?, ?, 1, 1)
            ");
            
            $stmt->execute([
                $category_code,
                $info['name'],
                $info['type'],
                json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE),
                json_encode($materials, JSON_UNESCAPED_UNICODE),
                json_encode($specifications, JSON_UNESCAPED_UNICODE)
            ]);
            echo "✓ {$info['name']} 생성 완료: " . count($specifications) . "개 규격\n";
        }
    }
    
    echo "\n샘플 데이터 임포트 완료!\n";
    echo "</pre>\n";
    
    if (php_sapi_name() !== 'cli') {
        echo "<a href='../products.php'>제품 목록 페이지로 이동</a>";
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>