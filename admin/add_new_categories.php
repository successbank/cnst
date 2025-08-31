<?php
require_once '../db.php';

// 새로 추가할 카테고리 목록
$new_categories = [
    ['c-beam', 'C형강', 'C형강 제품', 20],
    ['unequal-angle', '부등변ㄱ형강', '부등변 ㄱ형강 제품', 21],
    ['bs-pipe', 'BS파이프', 'BS 규격 파이프', 22],
    ['ks-pipe', 'KS파이프', 'KS 규격 파이프', 23],
    ['structural-pipe', '구조관', '구조용 강관', 24],
    ['steel-pipe-pile', '강관파일', '강관 파일', 25],
    ['temporary-deck', '복공판', '임시 복공판', 26],
    ['sheet-pile', '쉬트파일', '시트 파일', 27],
    ['pressure-pipe', '압력배관', '압력용 배관', 28],
    ['conduit-pipe', '전선관', '전선관', 29],
    ['scaffold-pipe', '단관비계', '비계용 단관', 30]
];

try {
    echo "<pre>\n";
    echo "카테고리 추가 시작...\n\n";
    
    foreach ($new_categories as $category) {
        list($code, $name, $description, $order) = $category;
        
        // 이미 존재하는지 확인
        $check_stmt = $pdo->prepare("SELECT category_code FROM product_categories WHERE category_code = ?");
        $check_stmt->execute([$code]);
        
        if ($check_stmt->fetchColumn()) {
            echo "- {$name}: 이미 존재함\n";
            continue;
        }
        
        // 카테고리 추가
        $insert_stmt = $pdo->prepare("
            INSERT INTO product_categories (category_code, category_name, display_order, is_active)
            VALUES (?, ?, ?, 1)
        ");
        
        $insert_stmt->execute([$code, $name, $order]);
        echo "✓ {$name} 추가 완료\n";
    }
    
    echo "\n카테고리 추가 완료!\n";
    echo "</pre>\n";
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>