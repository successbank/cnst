<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add_spec':
            $spec_name = $_POST['spec_name'];
            $diameter = $_POST['diameter'];
            $weight_per_meter = $_POST['weight_per_meter'];
            $is_active = $_POST['is_active'];

            $stmt = $pdo->prepare("INSERT INTO rebar_specifications (spec_name, diameter, weight_per_meter, is_active)
                                   VALUES (?, ?, ?, ?)");
            $stmt->execute([$spec_name, $diameter, $weight_per_meter, $is_active]);

            $_SESSION['msg'] = "철근 규격 '$spec_name'이(가) 추가되었습니다.";
            $_SESSION['msgType'] = 'success';
            break;

        case 'delete_spec':
            $id = $_POST['id'];

            // 바로 삭제 처리
            $stmt = $pdo->prepare("DELETE FROM rebar_specifications WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['msg'] = "철근 규격이 삭제되었습니다.";
            $_SESSION['msgType'] = 'success';
            break;

        case 'add_material':
            $material_name = $_POST['material_name'];
            $price_per_kg = $_POST['price_per_kg'] ?? 0;
            $description = $_POST['description'] ?? '';
            $display_order = $_POST['display_order'] ?? 0;
            $is_active = $_POST['is_active'];

            $stmt = $pdo->prepare("INSERT INTO rebar_materials (material_name, price_per_kg, description, display_order, is_active)
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$material_name, $price_per_kg, $description, $display_order, $is_active]);

            $_SESSION['msg'] = "철근 재질 '$material_name'이(가) 추가되었습니다.";
            $_SESSION['msgType'] = 'success';
            break;

        case 'delete_material':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM rebar_materials WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['msg'] = "철근 재질이 삭제되었습니다.";
            $_SESSION['msgType'] = 'success';
            break;

        case 'delete_length_data':
            $spec_name = $_POST['spec_name'] ?? '';

            if (!$spec_name) {
                throw new Exception("규격명이 필요합니다.");
            }

            // 삭제 전 데이터 개수 확인
            $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM rebar_length_data WHERE spec_name = ?");
            $countStmt->execute([$spec_name]);
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];

            // 데이터 삭제
            $deleteStmt = $pdo->prepare("DELETE FROM rebar_length_data WHERE spec_name = ?");
            $deleteStmt->execute([$spec_name]);

            $_SESSION['msg'] = "철근 규격 '{$spec_name}'의 길이별 데이터 {$count}개가 삭제되었습니다.";
            $_SESSION['msgType'] = 'success';
            break;

        case 'execute_query':
            $query = $_POST['query'] ?? '';

            // SELECT 쿼리만 허용
            if (!preg_match('/^\s*SELECT\s+/i', $query)) {
                throw new Exception("SELECT 쿼리만 실행 가능합니다.");
            }

            // 위험한 키워드 차단
            $dangerousKeywords = ['DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE', 'TRUNCATE'];
            foreach ($dangerousKeywords as $keyword) {
                if (stripos($query, $keyword) !== false) {
                    throw new Exception("허용되지 않는 쿼리입니다.");
                }
            }

            $stmt = $pdo->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $_SESSION['query_results'] = $results;
            $_SESSION['msg'] = "쿼리 실행 완료: " . count($results) . "개 결과";
            $_SESSION['msgType'] = 'success';
            break;

        default:
            throw new Exception("알 수 없는 액션입니다.");
    }
} catch (Exception $e) {
    $_SESSION['msg'] = "오류: " . $e->getMessage();
    $_SESSION['msgType'] = 'error';
}

header('Location: admin_rebar_manage.php');
exit;