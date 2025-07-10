<?php
// 데이터베이스 초기화 스크립트
require_once 'db.php';

try {
    // 공지사항 테이블 생성
    $sql = "CREATE TABLE IF NOT EXISTS board_notice (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        writer VARCHAR(100) NOT NULL,
        password VARCHAR(255),
        view_count INT DEFAULT 0,
        is_important BOOLEAN DEFAULT false,
        attachment VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "board_notice 테이블 생성 완료<br>";
    
    // 견적문의 테이블 생성
    $sql = "CREATE TABLE IF NOT EXISTS board_quote (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        writer VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        company VARCHAR(200),
        email VARCHAR(200),
        phone VARCHAR(50),
        attachment VARCHAR(255),
        is_answered BOOLEAN DEFAULT false,
        view_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "board_quote 테이블 생성 완료<br>";
    
    // 철강뉴스 테이블 생성
    $sql = "CREATE TABLE IF NOT EXISTS board_news (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        writer VARCHAR(100) NOT NULL,
        password VARCHAR(255),
        source VARCHAR(200),
        view_count INT DEFAULT 0,
        attachment VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "board_news 테이블 생성 완료<br>";
    
    // 샘플 데이터 추가 (공지사항)
    $stmt = $db->prepare("SELECT COUNT(*) FROM board_notice");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $notices = [
            ['title' => '충남스틸 홈페이지 오픈', 'content' => '안녕하세요. 충남스틸 홈페이지가 새롭게 오픈했습니다. 많은 이용 부탁드립니다.', 'writer' => '관리자', 'is_important' => 1],
            ['title' => '2025년 신년 인사', 'content' => '2025년 새해가 밝았습니다. 올 한해도 최선을 다하는 충남스틸이 되겠습니다.', 'writer' => '관리자', 'is_important' => 0],
            ['title' => '영업시간 안내', 'content' => '평일: 09:00 - 18:00\n토요일: 09:00 - 13:00\n일요일/공휴일: 휴무', 'writer' => '관리자', 'is_important' => 0]
        ];
        
        foreach ($notices as $notice) {
            $stmt = $db->prepare("INSERT INTO board_notice (title, content, writer, is_important) VALUES (:title, :content, :writer, :is_important)");
            $stmt->execute($notice);
        }
        echo "샘플 공지사항 추가 완료<br>";
    }
    
    // 샘플 데이터 추가 (철강뉴스)
    $stmt = $db->prepare("SELECT COUNT(*) FROM board_news");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $news = [
            ['title' => '2025년 철강 시장 전망', 'content' => '2025년 철강 시장은 글로벌 경기 회복과 함께 성장세를 보일 것으로 전망됩니다.', 'writer' => '관리자', 'source' => '철강신문'],
            ['title' => '친환경 철강 생산 기술 개발', 'content' => '탄소 중립을 위한 친환경 철강 생산 기술이 활발히 개발되고 있습니다.', 'writer' => '관리자', 'source' => '산업일보'],
            ['title' => '건설 경기 회복에 따른 철강 수요 증가', 'content' => '건설 경기가 회복되면서 철강 제품의 수요가 증가하고 있습니다.', 'writer' => '관리자', 'source' => '경제신문']
        ];
        
        foreach ($news as $item) {
            $stmt = $db->prepare("INSERT INTO board_news (title, content, writer, source) VALUES (:title, :content, :writer, :source)");
            $stmt->execute($item);
        }
        echo "샘플 뉴스 추가 완료<br>";
    }
    
    echo "<br>데이터베이스 초기화 완료!";
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage();
}
?>