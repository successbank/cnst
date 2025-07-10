<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>CSS 적용 테스트</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/steel.css">
    <style>
        .test-box {
            padding: 20px;
            margin: 20px;
            border: 2px solid #333;
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <h1>CSS 적용 테스트</h1>
    
    <div class="test-box">
        <h2>CSS 파일 체크</h2>
        <?php
        $css_files = [
            'css/main.css',
            'css/steel.css',
            'css/board.css',
            'css/about.css'
        ];
        
        foreach($css_files as $file) {
            if(file_exists($file)) {
                $size = filesize($file);
                echo "✅ $file - 존재함 ({$size} bytes)<br>";
            } else {
                echo "❌ $file - 없음<br>";
            }
        }
        ?>
    </div>
    
    <div class="test-box">
        <h2>CSS 변수 테스트</h2>
        <div style="color: var(--primary-color);">Primary Color (파란색이어야 함)</div>
        <div style="color: var(--secondary-color);">Secondary Color</div>
        <div style="background-color: var(--bg-light); padding: 10px;">Light Background</div>
    </div>
    
    <div class="test-box">
        <h2>클래스 테스트</h2>
        <button class="btn btn-primary">Primary 버튼</button>
        <button class="btn btn-secondary">Secondary 버튼</button>
        <div class="card" style="max-width: 300px; margin-top: 20px;">
            <div style="padding: 20px;">
                <h3>카드 컴포넌트</h3>
                <p>Samsung 스타일 카드</p>
            </div>
        </div>
    </div>
    
    <div class="test-box">
        <h2>네트워크 체크</h2>
        <p>브라우저 개발자 도구(F12) > Network 탭에서 CSS 파일이 정상적으로 로드되는지 확인하세요.</p>
        <p>Status Code가 200이어야 합니다.</p>
    </div>
</body>
</html>