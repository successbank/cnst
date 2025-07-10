<?php
// 카카오 API 설정
define('KAKAO_API_KEY', 'YOUR_KAKAO_API_KEY'); // 실제 API 키로 변경 필요
define('KAKAO_API_SECRET', 'YOUR_KAKAO_API_SECRET'); // 실제 API 시크릿으로 변경 필요
define('KAKAO_SENDER_KEY', 'YOUR_SENDER_KEY'); // 발신프로필키
define('KAKAO_CHANNEL_ID', '@chungnamsteel'); // 카카오톡 채널 ID

// 카카오 알림톡 API 엔드포인트
define('KAKAO_API_URL', 'https://api-alimtalk.cloud.toast.com/alimtalk/v2.2');

// 알림 설정
define('KAKAO_NOTIFICATION_ENABLED', true); // 알림 전송 활성화 여부
define('KAKAO_TEST_MODE', true); // 테스트 모드 (true일 경우 실제 발송하지 않음)
define('KAKAO_TEST_PHONE', '010-0000-0000'); // 테스트 모드에서 사용할 번호

// 메시지 발송 제한
define('KAKAO_DAILY_LIMIT', 1000); // 일일 발송 제한
define('KAKAO_RETRY_COUNT', 3); // 발송 실패시 재시도 횟수
?>