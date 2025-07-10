<?php
// 견적문의 샘플 데이터 생성 스크립트
require_once 'db.php';

// 관리자 권한 확인 (임시)
$admin_password = "admin123";
$input_password = $_POST['password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input_password === $admin_password) {
    try {
        // 기존 데이터 삭제
        $stmt = $pdo->prepare("DELETE FROM board_quote");
        $stmt->execute();
        
        // AUTO_INCREMENT 값 리셋
        $stmt = $pdo->prepare("ALTER TABLE board_quote AUTO_INCREMENT = 1");
        $stmt->execute();
        
        // 샘플 데이터 생성
        $companies = [
            '한국건설(주)', '대한철강', '삼성물산', '현대건설', '포스코건설', 
            '대림산업', '롯데건설', '두산중공업', '한화건설', '태영건설',
            '금강건설', '대우건설', '코오롱건설', '반도건설', '벽산건설',
            '극동건설', '현대엔지니어링', '삼성엔지니어링', '한국전력공사', '한국철도공사',
            '중소기업진흥공단', '소상공인시장진흥공단', '한국산업단지공단', '한국토지주택공사', 'LH공사',
            '한국수자원공사', '한국도로공사', '한국공항공사', '한국가스공사', '한국석유공사'
        ];
        
        $writers = [
            '김철수', '이영희', '박민수', '정수진', '최현우', '강미영', '조성호', '윤지혜',
            '장동건', '한소희', '서준호', '김나영', '이상훈', '박지민', '최우식', '강하늘',
            '조여정', '윤아', '김태희', '송중기', '전지현', '현빈', '손예진', '이종석',
            '박보영', '김수현', '아이유', '박서준', '김고은', '이민호'
        ];
        
        $products = [
            '철근(특판)', 'H형강(H빔)', '철강(강판)', '메탈라스(망철판)', '경량H형강',
            'I형강(빔)', 'ㄱ형강(앵글)', 'ㄷ형강(찬넬)', '환봉(원형강)', '평철',
            'C형강', '테크플레이트', '사각파이프(각관)', '원형파이프(강관)', '레일',
            '강널말뚝(쉬트파일)', '스테인레스(STS)', '기타'
        ];
        
        $titles = [
            '신축 공사용 철근 견적 요청',
            'H형강 대량 구매 문의',
            '건축자재 철강 견적 요청',
            '메탈라스 납기 및 가격 문의',
            '경량H형강 공급 가능 여부',
            'I형강 규격별 단가 문의',
            '앵글 철강 월간 계약 문의',
            '찬넬 철강 긴급 납품 가능 여부',
            '원형강 대량 주문 견적',
            '평철 커스텀 제작 문의',
            'C형강 공장 직접 구매',
            '테크플레이트 기술 사양 문의',
            '사각파이프 정기 공급 계약',
            '원형파이프 품질 인증서 요청',
            '레일 철강 특수 규격 제작',
            '강널말뚝 공사 현장 납품',
            '스테인레스 제품 견적 요청',
            '기타 철강 제품 상담'
        ];
        
        $content_templates = [
            "안녕하세요. %s에서 %s 담당자입니다.\n\n현재 진행 중인 건설 프로젝트에 필요한 %s 견적을 요청드립니다.\n\n- 필요 수량: %s\n- 납기: %s\n- 현장 위치: %s\n\n빠른 답변 부탁드립니다.",
            "%s 구매팀입니다.\n\n%s 제품에 대한 견적 문의드립니다.\n\n상세 사양:\n- 규격: %s\n- 수량: %s\n- 납기 희망일: %s\n\n경쟁력 있는 가격으로 제안해 주시기 바랍니다.",
            "안녕하세요.\n\n%s 관련 견적 요청드립니다.\n\n프로젝트 개요:\n- 공사명: %s\n- 필요 자재: %s\n- 예상 물량: %s\n\n상세한 견적서와 납기일정을 알려주시면 감사하겠습니다.",
            "%s입니다.\n\n%s 제품 구매를 검토 중입니다.\n\n요구사항:\n- 품질 인증서 포함\n- 수량: %s\n- 납품 장소: %s\n\n가격 경쟁력과 품질을 고려하여 검토하겠습니다.",
            "견적 요청 드립니다.\n\n%s 프로젝트용 %s이 필요합니다.\n\n- 사용 용도: %s\n- 필요 수량: %s\n- 예산 범위: 협의 가능\n\n기술 지원과 A/S도 함께 문의드립니다."
        ];
        
        $quantities = [
            '100톤', '50톤', '200톤', '30톤', '150톤', '80톤', '120톤', '250톤',
            '500개', '1000개', '2000개', '300개', '800개', '1500개',
            '10m', '20m', '50m', '100m', '200m', '15m', '35m'
        ];
        
        $delivery_dates = [
            '2024년 1월 말', '2024년 2월 중순', '2024년 3월 초', '2024년 4월 말',
            '긴급 (1주일 이내)', '2주 이내', '1개월 이내', '협의 가능'
        ];
        
        $locations = [
            '서울 강남구', '부산 해운대구', '대구 수성구', '인천 연수구', '광주 서구',
            '대전 유성구', '울산 남구', '세종시', '경기 성남시', '경기 수원시',
            '강원 춘천시', '충북 청주시', '충남 천안시', '전북 전주시', '전남 목포시',
            '경북 포항시', '경남 창원시', '제주 제주시'
        ];
        
        $specifications = [
            'SS400 규격', 'SM490 규격', 'KS D 3503 표준', '두께 10mm',
            '길이 6m', '폭 200mm', '지름 25mm', '맞춤 제작'
        ];
        
        $purposes = [
            '아파트 건설', '공장 건설', '교량 공사', '지하 구조물',
            '상업시설 건설', '도로 공사', '항만 공사', '산업단지 조성'
        ];
        
        $admin_replies = [
            "안녕하세요. 견적 문의 주셔서 감사합니다.\n\n요청하신 철강 제품에 대한 견적을 다음과 같이 제출드립니다:\n\n- 단가: 시중 시세 대비 5% 할인\n- 납기: 주문 후 7-10일\n- 품질보증: KS 인증 제품\n- 운송: 무료 (100톤 이상)\n\n추가 문의사항이 있으시면 언제든 연락 주시기 바랍니다.\n\n감사합니다.",
            
            "견적 요청 감사합니다.\n\n상세 견적서를 첨부하여 이메일로 발송해 드렸습니다.\n\n주요 내용:\n- 경쟁력 있는 가격 제안\n- 빠른 납기 (5일 이내)\n- 품질 인증서 포함\n- 현장 직접 납품 가능\n\n검토 후 연락 주시면 더 자세히 상담해 드리겠습니다.",
            
            "안녕하세요.\n\n요청하신 철강 자재 견적을 검토했습니다.\n\n제안 내용:\n- 최적화된 가격 제공\n- 정확한 납기 준수\n- 24시간 고객 지원\n- 품질 보증 1년\n\n대량 주문 시 추가 할인 혜택도 있습니다.\n궁금한 점이 있으시면 언제든 문의해 주세요.",
            
            "견적 문의 주셔서 감사합니다.\n\n당사는 30년간 철강 업계에서 쌓은 노하우로 최고 품질의 제품을 공급하고 있습니다.\n\n제안 사항:\n- 합리적인 가격\n- 신속한 납품\n- 완벽한 품질 관리\n- 전문 기술 지원\n\n상담을 위해 전화 또는 방문 일정을 잡을 수 있습니다.",
            
            "안녕하세요.\n\n견적 요청 건에 대해 상세히 검토했습니다.\n\n특별 제안:\n- 시장 최저가 보장\n- 무료 샘플 제공\n- 기술 자료 완비\n- 맞춤형 솔루션\n\n프로젝트 성공을 위해 최선을 다하겠습니다.\n추가 협의를 위해 연락 주시기 바랍니다."
        ];
        
        // 30개 샘플 데이터 생성
        for ($i = 1; $i <= 30; $i++) {
            $company = $companies[array_rand($companies)];
            $writer = $writers[array_rand($writers)];
            $product = $products[array_rand($products)];
            $title = $titles[array_rand($titles)];
            $quantity = $quantities[array_rand($quantities)];
            $delivery = $delivery_dates[array_rand($delivery_dates)];
            $location = $locations[array_rand($locations)];
            $spec = $specifications[array_rand($specifications)];
            $purpose = $purposes[array_rand($purposes)];
            
            $content = sprintf(
                $content_templates[array_rand($content_templates)],
                $company, $writer, $product, $quantity, $delivery, $location, $spec, $purpose
            );
            
            // 답변 상태 결정 (처음 10개는 대기중, 나머지 20개는 답변완료)
            $is_answered = $i > 10 ? 1 : 0;
            $admin_reply = $is_answered ? $admin_replies[array_rand($admin_replies)] : null;
            $replied_at = $is_answered ? date('Y-m-d H:i:s', strtotime("-" . rand(1, 30) . " days")) : null;
            
            // 생성일 (과거 1-90일 사이)
            $created_at = date('Y-m-d H:i:s', strtotime("-" . rand(1, 90) . " days"));
            
            $sql = "INSERT INTO board_quote (
                title, content, writer, password, company, email, phone, 
                is_answered, admin_reply, replied_at, created_at, view_count
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $title,
                $content,
                $writer,
                'password123', // 기본 비밀번호
                $company,
                strtolower(str_replace(' ', '', $writer)) . '@' . strtolower(str_replace(['(주)', '(', ')'], '', $company)) . '.com',
                '010-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                $is_answered,
                $admin_reply,
                $replied_at,
                $created_at,
                rand(1, 50) // 조회수
            ]);
        }
        
        $message = "30개의 샘플 견적문의 데이터가 생성되었습니다. (대기중: 10개, 답변완료: 20개)";
        $success = true;
        
    } catch (PDOException $e) {
        $message = "데이터 생성 중 오류가 발생했습니다: " . $e->getMessage();
        $success = false;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>견적문의 샘플 데이터 생성</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #0056b3;
        }
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>견적문의 샘플 데이터 생성</h2>
        
        <div class="info">
            <strong>생성 내용:</strong><br>
            - 총 30개의 샘플 데이터<br>
            - 대기중: 10개<br>
            - 답변완료: 20개<br>
            - 다양한 업체와 담당자 정보<br>
            - 실제와 유사한 견적 내용
        </div>
        
        <?php if (isset($message)): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!isset($success) || !$success): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="password">관리자 비밀번호:</label>
                <input type="password" id="password" name="password" required placeholder="admin123">
            </div>
            <button type="submit">샘플 데이터 생성</button>
        </form>
        <?php endif; ?>
        
        <p style="margin-top: 30px;">
            <a href="quote.php">견적문의 페이지로 이동</a> | 
            <a href="admin/admin_quotes.php">관리자 페이지로 이동</a>
        </p>
    </div>
</body>
</html>