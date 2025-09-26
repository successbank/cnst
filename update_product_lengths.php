<?php
require_once 'db.php';

// 제품별 길이 제한 데이터 (이미지 테이블 기준)
$length_limits = [
    // 철근
    ['name_pattern' => '철근%', 'not_pattern' => '나사철근%', 'min' => 6.0, 'max' => 12.0, 'standard' => 8],

    // 나사철근
    ['name_pattern' => '나사철근%', 'min' => 6.0, 'max' => 12.0, 'standard' => 8],

    // H형강
    ['name_pattern' => 'H형강%', 'not_pattern' => '경량H형강%', 'min' => 8.0, 'max' => 25.0, 'standard' => 10],

    // 경량H형강
    ['name_pattern' => '경량H형강%', 'min' => 5.0, 'max' => 15.0, 'standard' => 10],

    // I형강
    ['name_pattern' => 'I형강%', 'min' => 10.0, 'max' => 10.0, 'standard' => 10],

    // ㄱ형강
    ['name_pattern' => 'ㄱ형강%', 'not_pattern' => '부등변%', 'min' => 8.0, 'max' => 16.0, 'standard' => 10],
    ['name_pattern' => 'L형강%', 'not_pattern' => '부등변%', 'min' => 8.0, 'max' => 16.0, 'standard' => 10],

    // 부등변 ㄱ형강
    ['name_pattern' => '부등변%ㄱ형강%', 'min' => 10.0, 'max' => 10.0, 'standard' => 10],
    ['name_pattern' => '부등변%L형강%', 'min' => 10.0, 'max' => 10.0, 'standard' => 10],

    // ㄷ형강
    ['name_pattern' => 'ㄷ형강%', 'min' => 8.0, 'max' => 16.0, 'standard' => 10],
    ['name_pattern' => 'U형강%', 'min' => 8.0, 'max' => 16.0, 'standard' => 10],
    ['name_pattern' => '채널%', 'min' => 8.0, 'max' => 16.0, 'standard' => 10],

    // 환봉(원형강)
    ['name_pattern' => '환봉%', 'min' => 6.0, 'max' => 10.0, 'standard' => 6],
    ['name_pattern' => '원형강%', 'min' => 6.0, 'max' => 10.0, 'standard' => 6],

    // 평철
    ['name_pattern' => '평철%', 'min' => 6.0, 'max' => 6.0, 'standard' => 6],
    ['name_pattern' => '플랫바%', 'min' => 6.0, 'max' => 6.0, 'standard' => 6],

    // 사각강
    ['name_pattern' => '사각강%', 'min' => 6.0, 'max' => 6.0, 'standard' => 6],
    ['name_pattern' => '각봉%', 'min' => 6.0, 'max' => 6.0, 'standard' => 6],

    // 육각강
    ['name_pattern' => '육각강%', 'min' => 6.0, 'max' => 6.0, 'standard' => 6],
    ['name_pattern' => '육각봉%', 'min' => 6.0, 'max' => 6.0, 'standard' => 6],

    // 팔각강
    ['name_pattern' => '팔각강%', 'min' => 6.0, 'max' => 6.0, 'standard' => 6],
    ['name_pattern' => '팔각봉%', 'min' => 6.0, 'max' => 6.0, 'standard' => 6],

    // 강널말뚝(쉬트파일)
    ['name_pattern' => '강널말뚝%', 'min' => 8.0, 'max' => 25.0, 'standard' => 10],
    ['name_pattern' => '쉬트파일%', 'min' => 8.0, 'max' => 25.0, 'standard' => 10],

    // 레일
    ['name_pattern' => '레일%', 'min' => 8.0, 'max' => 20.0, 'standard' => 10],

    // C형강
    ['name_pattern' => 'C형강%', 'min' => 5.0, 'max' => 12.0, 'standard' => 10],

    // 데크플레이트
    ['name_pattern' => '데크플레이트%', 'min' => 2.4, 'max' => 12.0, 'standard' => 6],

    // 사각 파이프(각관)
    ['name_pattern' => '사각%파이프%', 'min' => 5.0, 'max' => 12.0, 'standard' => 6],
    ['name_pattern' => '각관%', 'min' => 5.0, 'max' => 12.0, 'standard' => 6],

    // 원형 파이프(구조관)
    ['name_pattern' => '원형%파이프%구조%', 'min' => 5.0, 'max' => 12.0, 'standard' => 6],

    // 원형 파이프(KS-배관용)
    ['name_pattern' => '원형%파이프%KS%', 'min' => 5.0, 'max' => 12.0, 'standard' => 6],
    ['name_pattern' => '원형%파이프%배관%', 'min' => 5.0, 'max' => 12.0, 'standard' => 6],

    // 원형 파이프(BS-구조용)
    ['name_pattern' => '원형%파이프%BS%', 'min' => 5.0, 'max' => 12.0, 'standard' => 6],

    // 원형 파이프(SCH-압력 배관)
    ['name_pattern' => '원형%파이프%SCH%', 'min' => 5.0, 'max' => 12.0, 'standard' => 6],
    ['name_pattern' => '원형%파이프%압력%', 'min' => 5.0, 'max' => 12.0, 'standard' => 6],

    // 원형 파이프(전선관)
    ['name_pattern' => '원형%파이프%전선%', 'min' => 3.6, 'max' => 3.6, 'standard' => 3.6],
    ['name_pattern' => '전선관%', 'min' => 3.6, 'max' => 3.6, 'standard' => 3.6],

    // 원형 파이프(단관비계)
    ['name_pattern' => '원형%파이프%단관%', 'min' => 2.0, 'max' => 6.0, 'standard' => 6],
    ['name_pattern' => '단관비계%', 'min' => 2.0, 'max' => 6.0, 'standard' => 6],
];

echo "제품별 길이 제한 업데이트 시작...\n\n";

$total_updated = 0;

foreach ($length_limits as $limit) {
    $sql = "UPDATE products SET
            min_length = :min_length,
            max_length = :max_length,
            standard_length = :standard_length
            WHERE product_name LIKE :name_pattern";

    $params = [
        ':min_length' => $limit['min'],
        ':max_length' => $limit['max'],
        ':standard_length' => $limit['standard'],
        ':name_pattern' => $limit['name_pattern']
    ];

    // NOT 조건이 있는 경우
    if (isset($limit['not_pattern'])) {
        $sql .= " AND product_name NOT LIKE :not_pattern";
        $params[':not_pattern'] = $limit['not_pattern'];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $affected = $stmt->rowCount();
    $total_updated += $affected;

    if ($affected > 0) {
        echo "✓ {$limit['name_pattern']}: {$affected}개 제품 업데이트 (길이: {$limit['min']}m ~ {$limit['max']}m)\n";
    }
}

echo "\n총 {$total_updated}개 제품의 길이 제한이 업데이트되었습니다.\n";

// 업데이트 결과 확인
echo "\n\n=== 업데이트 결과 확인 ===\n";
$check_sql = "SELECT product_name, min_length, max_length, standard_length
              FROM products
              WHERE min_length IS NOT NULL
              ORDER BY product_name
              LIMIT 20";

$stmt = $pdo->query($check_sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    echo sprintf("%-30s: %.1fm ~ %.1fm (표준: %.1fm)\n",
        $row['product_name'],
        $row['min_length'],
        $row['max_length'],
        $row['standard_length']
    );
}

echo "\n업데이트가 완료되었습니다.\n";
?>