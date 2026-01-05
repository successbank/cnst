<?php
/**
 * 업로드 디렉토리 권한 수정 스크립트
 * 이 파일을 웹 브라우저에서 한 번 실행하면 디렉토리가 올바른 권한으로 생성됩니다.
 */

$uploadDir = '../uploads/product_icons/';

echo "<h2>업로드 디렉토리 권한 수정</h2>";

// 디렉토리가 없으면 생성
if (!is_dir($uploadDir)) {
    if (mkdir($uploadDir, 0777, true)) {
        echo "<p style='color: green;'>✓ 디렉토리 생성 성공: $uploadDir</p>";
        chmod($uploadDir, 0777);
        echo "<p style='color: green;'>✓ 권한 설정 완료 (777)</p>";
    } else {
        echo "<p style='color: red;'>✗ 디렉토리 생성 실패</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ 디렉토리가 이미 존재합니다: $uploadDir</p>";

    // 권한 변경 시도
    if (chmod($uploadDir, 0777)) {
        echo "<p style='color: green;'>✓ 권한 변경 성공 (777)</p>";
    } else {
        echo "<p style='color: orange;'>⚠ 권한 변경 실패 (이미 올바른 권한일 수 있습니다)</p>";
    }
}

// 현재 디렉토리 정보 표시
if (is_dir($uploadDir)) {
    $perms = fileperms($uploadDir);
    $perms_str = substr(sprintf('%o', $perms), -4);

    echo "<h3>현재 디렉토리 정보:</h3>";
    echo "<ul>";
    echo "<li>경로: " . realpath($uploadDir) . "</li>";
    echo "<li>권한: $perms_str</li>";
    echo "<li>쓰기 가능: " . (is_writable($uploadDir) ? '예 ✓' : '아니오 ✗') . "</li>";
    echo "<li>실행 사용자: " . get_current_user() . " (UID: " . getmyuid() . ")</li>";
    echo "<li>프로세스 사용자: " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'Unknown') . "</li>";
    echo "</ul>";
}

echo "<p><a href='admin_product_icons.php'>← 아이콘 관리로 돌아가기</a></p>";
?>
