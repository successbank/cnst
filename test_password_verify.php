<?php
// 패스워드 검증 테스트
$plain_password = 'iloveGod74';

// PHP의 password_hash로 생성한 예시 해시들
$test_hashes = [
    // BCrypt 해시 예시
    '$2y$10$1pPxkMF8NFGEx7MM8GU5aODnU8SAy5PtLzmjWHY5dHzy4wS11zXMu',
    '$2y$10$YourHashHere...',  // 다른 예시
];

echo "=== 패스워드 'iloveGod74' 검증 테스트 ===\n\n";

// 새로운 해시 생성
$new_hash = password_hash($plain_password, PASSWORD_DEFAULT);
echo "새로 생성한 해시: $new_hash\n\n";

// 검증
echo "검증 결과:\n";
if (password_verify($plain_password, $new_hash)) {
    echo "✅ 새로 생성한 해시와 패스워드가 일치합니다.\n\n";
} else {
    echo "❌ 오류: 새로 생성한 해시 검증 실패\n\n";
}

echo "=== 결론 ===\n";
echo "1. Excel의 test000 사용자 패스워드: iloveGod74\n";
echo "2. PHP의 password_verify() 함수로 검증 가능합니다.\n";
echo "3. 현재 DB에 test000이 있다면, 해당 사용자의 password 필드에 있는 해시값과\n";
echo "   'iloveGod74'를 password_verify()로 비교하면 일치 여부를 확인할 수 있습니다.\n";
echo "\n예제 코드:\n";
echo 'if (password_verify("iloveGod74", $db_password_hash)) {' . "\n";
echo '    echo "패스워드 일치";' . "\n";
echo '}' . "\n";
?>