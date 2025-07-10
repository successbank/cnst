<?php
require_once 'db.php';

echo "Starting to import dummy quote data...\n";

try {
    $sql = "INSERT INTO board_quote (title, content, writer, password, company, email, phone, attachment, created_at) 
            VALUES (:title, :content, :writer, :password, :company, :email, :phone, :attachment, :created_at)";
    $stmt = $db->prepare($sql);

    // 답변대기 13개 생성
    for ($i = 0; $i < 13; $i++) {
        $data = [
            ':title' => '철근 및 H형강 대량 구매 견적 문의합니다.',
            ':content' => '안녕하세요. 첨부파일과 같이 철근과 H형강 대량 구매를 희망합니다. 상세 규격 및 수량은 첨부된 PDF 파일을 참고해 주시고, 경쟁력 있는 단가와 납기일을 포함하여 견적 부탁드립니다.',
            ':writer' => '김철수',
            ':password' => 'password123',
            ':company' => '튼튼건설',
            ':email' => 'test@example.com',
            ':phone' => '010-1234-5678',
            ':attachment' => null,
            ':created_at' => date('Y-m-d H:i:s', time() - rand(0, 30*24*60*60))
        ];
        $stmt->execute($data);
    }
    echo "Imported 13 '답변대기' quotes.\n";

    // 답변완료 17개 생성
    for ($i = 0; $i < 17; $i++) {
        $data = [
            ':title' => '[답변완료] 철근 및 H형강 대량 구매 견적 문의합니다.',
            ':content' => '안녕하세요. 첨부파일과 같이 철근과 H형강 대량 구매를 희망합니다. 상세 규격 및 수량은 첨부된 PDF 파일을 참고해 주시고, 경쟁력 있는 단가와 납기일을 포함하여 견적 부탁드립니다.',
            ':writer' => '김철수',
            ':password' => 'password123',
            ':company' => '튼튼건설',
            ':email' => 'test@example.com',
            ':phone' => '010-1234-5678',
            ':attachment' => null,
            ':created_at' => date('Y-m-d H:i:s', time() - rand(0, 30*24*60*60))
        ];
        $stmt->execute($data);
    }
    echo "Imported 17 '답변완료' quotes.\n";

    echo "----------------------------------------\n";
    echo "Successfully imported a total of 30 quotes.\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
