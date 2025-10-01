<?php
// 전자책 목차 테이블 생성 및 데이터 마이그레이션
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';

echo "=== 전자책 목차 테이블 생성 시작 ===\n\n";

try {
    $pdo = getDB();

    // 1. 테이블 생성
    echo "1. ebook_toc 테이블 생성 중...\n";
    $sql = "CREATE TABLE IF NOT EXISTS ebook_toc (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(100) NOT NULL COMMENT '카테고리명',
        category_icon VARCHAR(50) DEFAULT '🟢' COMMENT '카테고리 아이콘',
        title VARCHAR(500) NOT NULL COMMENT '목차 제목',
        page_number INT NOT NULL COMMENT '페이지 번호',
        display_order INT NOT NULL DEFAULT 0 COMMENT '표시 순서',
        is_active BOOLEAN DEFAULT TRUE COMMENT '활성화 여부',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_display_order (display_order),
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "✓ 테이블 생성 완료\n\n";

    // 2. 기존 데이터 확인
    $stmt = $pdo->query("SELECT COUNT(*) FROM ebook_toc");
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo "⚠ 이미 {$count}개의 데이터가 존재합니다.\n";
        echo "데이터를 초기화하시겠습니까? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) !== 'y') {
            echo "작업을 취소했습니다.\n";
            exit;
        }
        $pdo->exec("TRUNCATE TABLE ebook_toc");
        echo "✓ 기존 데이터 삭제 완료\n\n";
    }

    // 3. 초기 데이터 삽입
    echo "2. 초기 목차 데이터 삽입 중...\n";

    $tocData = [
        // 철근류
        ['철근류', '🟢', '철근 테이블에 따른 ....................... 7', 7, 1],
        ['철근류', '🟢', '10철 및표 .............................. 10', 10, 2],
        ['철근류', '🟢', '철근 ................................... 11', 11, 3],

        // 형강류
        ['형강류', '🟢', 'KS표준 형강 ............................ 11', 11, 4],
        ['형강류', '🟢', 'H형강 ................................. 21', 21, 5],
        ['형강류', '🟢', 'I형강 .................................. 24', 24, 6],
        ['형강류', '🟢', '찬넬 ㄷ형강, U형강 ..................... 26', 26, 7],
        ['형강류', '🟢', '앵글 L형강 ............................. 29', 29, 8],
        ['형강류', '🟢', '평철 ................................... 32', 32, 9],
        ['형강류', '🟢', '강판(철판) ............................. 36', 36, 10],
        ['형강류', '🟢', '무늬철판 ............................... 37', 37, 11],
        ['형강류', '🟢', '체크레이트플레이트 ..................... 38', 38, 12],
        ['형강류', '🟢', '사각후레임 ............................. 39', 39, 13],
        ['형강류', '🟢', '디프레이트 ............................. 41', 41, 14],

        // 강관류
        ['강관류', '🟢', '원형스틸 탄소강관 ...................... 42', 42, 15],
        ['강관류', '🟢', '갈바이징강 탄소강관 .................... 44', 44, 16],
        ['강관류', '🟢', '사각파이프 원형파이프 비교표 ........... 46', 46, 17],
        ['강관류', '🟢', '일반구조용 각형강관 .................... 47', 47, 18],
        ['강관류', '🟢', '일반구조용사각파이프, 휀스용, 인테리어용,가벼 ... 48', 48, 19],

        // 창호류
        ['창호류', '🟢', '엘보(엠보판철) ......................... 49', 49, 20],

        // 스테인리스
        ['스테인리스', '🟢', '스테인리스 관련사 시공사 목록표 ........ 57', 57, 21],
        ['스테인리스', '🟢', '스테인리스(SUS) 일반사 사각파이프 특별가 ... 58', 58, 22],
        ['스테인리스', '🟢', '스테인리스 최신자료 중국표 ............. 60', 60, 23],
        ['스테인리스', '🟢', '중급중량 .................................. 61', 61, 24],

        // 기타
        ['기타', '🟢', '환산표 ................................ 61', 61, 25],
        ['기타', '🟢', '규격 및 약호 .......................... 67', 67, 26],
        ['기타', '🟢', '각종 단위 대비표 ...................... 68', 68, 27],
        ['기타', '🟢', '볼트 규격 체널 범용 용표 .............. 71', 71, 28],
        ['기타', '🟢', '반합수용적 기종규사, 대장리, 앵글소, 쌍볼표 .... 75', 75, 29],

        // 토목자재
        ['토목자재', '🟢', '클럽파일, ㅁ구형강 H빔사, 조각파뿔, 쌓플레이트 .... 77', 76, 30],

        // 시스크레트(고장력)
        ['시스크레트(고장력)', '🟢', '건물 안전에 사용 고활용 ............... 79', 79, 31],

        // 철근 표시법
        ['철근 표시법', '🟢', 'KS D 3504 철근 콘크리트 보강용 표시법 .... 80', 80, 32],
    ];

    $stmt = $pdo->prepare("
        INSERT INTO ebook_toc (category, category_icon, title, page_number, display_order, is_active)
        VALUES (?, ?, ?, ?, ?, 1)
    ");

    $inserted = 0;
    foreach ($tocData as $data) {
        try {
            $stmt->execute($data);
            $inserted++;
        } catch (PDOException $e) {
            echo "✗ 오류: " . $e->getMessage() . "\n";
        }
    }

    echo "✓ {$inserted}개의 목차 항목 삽입 완료\n\n";

    // 4. 카테고리별 데이터 확인
    echo "3. 카테고리별 목차 현황:\n";
    echo str_repeat("-", 60) . "\n";

    $stmt = $pdo->query("
        SELECT category, COUNT(*) as count
        FROM ebook_toc
        WHERE is_active = 1
        GROUP BY category
        ORDER BY MIN(display_order)
    ");

    $total = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-25s : %2d개\n", $row['category'], $row['count']);
        $total += $row['count'];
    }
    echo str_repeat("-", 60) . "\n";
    echo sprintf("%-25s : %2d개\n", "전체", $total);

    echo "\n✅ 전자책 목차 테이블 생성 및 데이터 마이그레이션 완료!\n";
    echo "\n📌 다음 단계:\n";
    echo "1. 관리자 페이지에서 목차 관리: /admin/admin_ebook_toc.php\n";
    echo "2. 전자책 모바일 페이지 확인: /ebook/mobile/index.html\n";

} catch (PDOException $e) {
    echo "\n❌ 데이터베이스 오류: " . $e->getMessage() . "\n";
    exit(1);
}
?>
