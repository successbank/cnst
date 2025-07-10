<?php
require_once 'db.php';
require_once 'board/board_template.php';

// 샘플 공지사항 데이터
$notices = [
    [
        'title' => '2025년 신년 인사 및 영업 안내',
        'content' => "안녕하십니까, 충남스틸입니다.\n\n2025년 을사년 새해가 밝았습니다.\n새해에도 고객 여러분의 가정에 건강과 행복이 가득하시길 기원합니다.\n\n저희 충남스틸은 올해도 최고 품질의 철강 제품과 서비스로\n고객 만족을 위해 최선을 다하겠습니다.\n\n■ 2025년 영업 시간 안내\n- 평일: 오전 8:30 ~ 오후 6:00\n- 토요일: 오전 8:30 ~ 오후 1:00\n- 일요일 및 공휴일: 휴무\n\n감사합니다.",
        'writer' => '관리자',
        'password' => 'admin123',
        'is_important' => 1
    ],
    [
        'title' => '설 연휴 영업 안내',
        'content' => "충남스틸을 이용해 주시는 고객 여러분께 감사드립니다.\n\n2025년 설 연휴 영업 일정을 안내해 드립니다.\n\n■ 휴무 기간\n- 2025년 1월 28일(화) ~ 1월 30일(목)\n- 1월 31일(금) 정상 영업\n\n■ 긴급 문의\n- 휴무 기간 중 긴급한 사항은 대표 휴대폰으로 연락 주시기 바랍니다.\n- 긴급 연락처: 010-1234-5678\n\n설 연휴 기간 동안 필요한 자재는 미리 주문해 주시기 바랍니다.\n\n감사합니다.",
        'writer' => '관리자',
        'password' => 'admin123',
        'is_important' => 1
    ],
    [
        'title' => '철강 가격 동향 안내 (2025년 1월)',
        'content' => "2025년 1월 철강 가격 동향을 안내드립니다.\n\n■ 주요 품목 가격 변동\n1. H형강: 전월 대비 2% 상승\n2. 철근: 전월 대비 1.5% 상승\n3. 강관: 전월 대비 변동 없음\n4. 철판: 전월 대비 1% 하락\n\n■ 가격 변동 요인\n- 원자재 가격 상승\n- 계절적 수요 증가\n- 환율 변동\n\n자세한 견적은 영업팀으로 문의해 주시기 바랍니다.\n\n감사합니다.",
        'writer' => '영업팀',
        'password' => 'admin123',
        'is_important' => 0
    ],
    [
        'title' => '신규 직원 채용 공고',
        'content' => "충남스틸과 함께 성장할 인재를 모집합니다.\n\n■ 모집 부문\n1. 영업직 (경력/신입)\n   - 철강 영업 경력자 우대\n   - 운전면허 필수\n\n2. 사무직 (신입)\n   - 컴퓨터 활용 능력 우수자\n   - 회계 관련 자격증 소지자 우대\n\n■ 근무 조건\n- 근무지: 충남 천안시 서북구\n- 근무시간: 평일 08:30 ~ 18:00\n- 급여: 면접 후 협의\n\n■ 제출 서류\n- 이력서 및 자기소개서\n- 관련 자격증 사본\n\n■ 접수 방법\n- 이메일: recruit@chungnamsteel.co.kr\n- 접수 마감: 2025년 2월 15일\n\n많은 지원 바랍니다.",
        'writer' => '인사팀',
        'password' => 'admin123',
        'is_important' => 0
    ],
    [
        'title' => '홈페이지 리뉴얼 안내',
        'content' => "안녕하십니까, 충남스틸입니다.\n\n고객 여러분께 더 나은 서비스를 제공하기 위해\n홈페이지를 새롭게 단장하였습니다.\n\n■ 주요 개선 사항\n1. 모바일 환경 최적화\n2. 제품 검색 기능 강화\n3. 온라인 견적 시스템 개선\n4. 고객 게시판 기능 추가\n\n■ 신규 기능\n- 실시간 재고 확인\n- 온라인 주문 시스템\n- 모바일 앱 연동\n\n이용에 불편한 점이 있으시면 언제든지 문의해 주시기 바랍니다.\n\n감사합니다.",
        'writer' => '관리자',
        'password' => 'admin123',
        'is_important' => 0
    ],
    [
        'title' => '품질 인증서 갱신 완료 안내',
        'content' => "충남스틸의 품질 인증서가 갱신되었음을 알려드립니다.\n\n■ 갱신 인증서\n1. ISO 9001:2015 (품질경영시스템)\n2. KS 인증 (한국산업표준)\n3. 환경경영시스템 인증\n\n■ 인증 유효 기간\n- 2025년 1월 1일 ~ 2027년 12월 31일\n\n충남스틸은 앞으로도 최고 품질의 제품을 공급하기 위해\n지속적인 품질 관리에 최선을 다하겠습니다.\n\n감사합니다.",
        'writer' => '품질관리팀',
        'password' => 'admin123',
        'is_important' => 0
    ],
    [
        'title' => '겨울철 안전 관리 강화 안내',
        'content' => "겨울철 안전사고 예방을 위한 안내말씀 드립니다.\n\n■ 주의 사항\n1. 적재물 결빙 주의\n2. 작업장 바닥 미끄러움 주의\n3. 난방기구 사용 시 화재 예방\n4. 작업 전 충분한 준비운동\n\n■ 안전 수칙\n- 안전모, 안전화 착용 필수\n- 미끄럼 방지 조치 시행\n- 작업 전 안전 점검 실시\n\n모든 직원과 고객 여러분의 안전을 위해\n각별한 주의 부탁드립니다.\n\n감사합니다.",
        'writer' => '안전관리팀',
        'password' => 'admin123',
        'is_important' => 0
    ],
    [
        'title' => '2024년 연말 재고 정리 세일',
        'content' => "2024년 한 해 동안 충남스틸을 이용해 주신 고객 여러분께 감사드립니다.\n\n연말 재고 정리 세일을 진행합니다.\n\n■ 세일 품목\n1. H형강 (일부 규격): 10% 할인\n2. 앵글: 15% 할인\n3. 찬넬: 12% 할인\n\n■ 세일 기간\n- 2024년 12월 15일 ~ 12월 31일\n- 재고 소진 시 조기 종료\n\n■ 주의 사항\n- 현금 결제 시 추가 할인\n- 배송비 별도\n- 반품 불가\n\n많은 이용 바랍니다.",
        'writer' => '영업팀',
        'password' => 'admin123',
        'is_important' => 0
    ],
    [
        'title' => '전화번호 변경 안내',
        'content' => "충남스틸 대표 전화번호가 변경되었습니다.\n\n■ 변경 전\n- 041-123-4567\n\n■ 변경 후\n- 041-567-8900\n\n■ 변경 일시\n- 2025년 1월 2일부터\n\n기존 번호도 당분간 함께 운영되오니\n참고하시기 바랍니다.\n\n감사합니다.",
        'writer' => '관리자',
        'password' => 'admin123',
        'is_important' => 1
    ],
    [
        'title' => '고객 감사 이벤트 당첨자 발표',
        'content' => "2024년 고객 감사 이벤트에 참여해 주신 모든 분들께 감사드립니다.\n\n■ 당첨자 명단\n1등 (상품권 50만원): 김*철 (010-****-5678)\n2등 (상품권 30만원): 이*수 (010-****-1234)\n3등 (상품권 10만원): \n- 박*영 (010-****-9876)\n- 최*호 (010-****-5432)\n- 정*미 (010-****-2468)\n\n■ 경품 수령 안내\n- 개별 연락 예정\n- 신분증 지참 필수\n\n축하드립니다!",
        'writer' => '마케팅팀',
        'password' => 'admin123',
        'is_important' => 0
    ]
];

// 게시판 객체 생성
$board = new BoardTemplate($db, 'notice');

// 공지사항 입력
$successCount = 0;
$failCount = 0;

echo "<h2>공지사항 데이터 입력 중...</h2>";
echo "<pre>";

foreach ($notices as $index => $notice) {
    $data = [
        'title' => $notice['title'],
        'content' => $notice['content'],
        'writer' => $notice['writer'],
        'password' => $notice['password'],
        'attachment' => ''
    ];
    
    // is_important 필드 추가
    if (isset($notice['is_important'])) {
        $data['is_important'] = $notice['is_important'];
    }
    
    echo ($index + 1) . ". " . $notice['title'] . " ... ";
    
    try {
        // writePost 메서드 수정이 필요하므로 직접 SQL 실행
        $sql = "INSERT INTO board_notice 
                (title, content, writer, password, attachment, is_important, created_at, view_count) 
                VALUES (:title, :content, :writer, :password, :attachment, :is_important, NOW(), :view_count)";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':content', $data['content']);
        $stmt->bindParam(':writer', $data['writer']);
        $stmt->bindParam(':password', $data['password']);
        $stmt->bindParam(':attachment', $data['attachment']);
        $stmt->bindValue(':is_important', isset($notice['is_important']) ? $notice['is_important'] : 0);
        $stmt->bindValue(':view_count', rand(10, 200)); // 랜덤 조회수
        
        if ($stmt->execute()) {
            echo "성공\n";
            $successCount++;
        } else {
            echo "실패\n";
            $failCount++;
        }
    } catch (Exception $e) {
        echo "오류: " . $e->getMessage() . "\n";
        $failCount++;
    }
}

echo "\n";
echo "입력 완료!\n";
echo "성공: " . $successCount . "개\n";
echo "실패: " . $failCount . "개\n";
echo "</pre>";

echo '<p><a href="notice.php">공지사항 페이지로 이동</a></p>';
?>