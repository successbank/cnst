<?php
require_once 'db.php';

try {
    // 깨진 한글을 수정
    $stmt = $pdo->prepare("UPDATE member_addresses SET address_name = '기본주소' WHERE address_name LIKE '%ê¸°ë³¸%' OR address_name LIKE '%기본 주소%'");
    $result = $stmt->execute();
    
    echo "주소명 인코딩 수정 완료\n";
    echo "영향받은 행 수: " . $stmt->rowCount() . "\n";
    
    // 현재 주소 목록 확인
    $stmt = $pdo->prepare("SELECT id, member_id, address_name FROM member_addresses LIMIT 10");
    $stmt->execute();
    $addresses = $stmt->fetchAll();
    
    echo "\n현재 주소 목록:\n";
    foreach ($addresses as $addr) {
        echo "ID: {$addr['id']}, Member ID: {$addr['member_id']}, 주소명: {$addr['address_name']}\n";
    }
    
} catch (PDOException $e) {
    echo "오류: " . $e->getMessage() . "\n";
}
?>