<?php
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = '114/철근.xlsx';

try {
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    
    echo "철근 엑셀 파일 분석\n";
    echo "=================\n\n";
    
    // 첫 10행 출력
    echo "데이터 미리보기 (첫 10행):\n";
    for ($row = 1; $row <= 10; $row++) {
        $rowData = [];
        for ($col = 'A'; $col <= 'Z'; $col++) {
            $value = $worksheet->getCell($col . $row)->getValue();
            if ($value !== null && $value !== '') {
                $rowData[] = $col . ': ' . $value;
            }
        }
        if (!empty($rowData)) {
            echo "행 $row: " . implode(' | ', $rowData) . "\n";
        }
    }
    
    // 전체 데이터 구조 파악
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();
    
    echo "\n총 행 수: $highestRow\n";
    echo "총 열: A ~ $highestColumn\n";
    
} catch (Exception $e) {
    echo "엑셀 파일 읽기 오류: " . $e->getMessage() . "\n";
}
?>