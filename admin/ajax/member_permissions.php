<?php
/**
 * 회원 권한 관리 AJAX 엔드포인트
 * 
 * 지원하는 액션:
 * - get_stats: 등급별 회원 수 조회
 * - get_grade_settings: 등급 설정 조회
 * - save_grade_settings: 등급 설정 저장
 * - get_permission_matrix: 권한 매트릭스 조회
 * - save_permissions: 등급별 권한 저장
 * - preview_bulk_change: 일괄 변경 미리보기
 * - execute_bulk_change: 일괄 변경 실행
 * - get_admin_list: 관리자 목록 조회
 * - toggle_admin: 관리자 권한 토글
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../db.php';
require_once '../admin_check.php';

// 응답 헬퍼 함수
function jsonResponse($success, $data = [], $message = '') {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

// GET 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_stats':
            // 등급별 회원 수 조회
            try {
                $stmt = $pdo->query("
                    SELECT 
                        member_grade,
                        COUNT(*) as cnt
                    FROM members
                    WHERE is_active = 1
                    GROUP BY member_grade
                ");
                $results = $stmt->fetchAll();
                
                $stats = [
                    'normal' => 0,
                    'silver' => 0,
                    'gold' => 0,
                    'vip' => 0
                ];
                
                foreach ($results as $row) {
                    $grade = $row['member_grade'] ?? 'normal';
                    if (isset($stats[$grade])) {
                        $stats[$grade] = (int)$row['cnt'];
                    }
                }
                
                jsonResponse(true, ['stats' => $stats]);
            } catch (PDOException $e) {
                jsonResponse(false, [], '통계 조회 실패: ' . $e->getMessage());
            }
            break;
            
        case 'get_grade_settings':
            // 등급 설정 조회
            try {
                $stmt = $pdo->query("SELECT * FROM member_grade_settings ORDER BY sort_order ASC");
                $grades = $stmt->fetchAll();
                jsonResponse(true, ['grades' => $grades]);
            } catch (PDOException $e) {
                jsonResponse(false, [], '등급 설정 조회 실패: ' . $e->getMessage());
            }
            break;
            
        case 'get_permission_matrix':
            // 권한 매트릭스 조회
            try {
                // 권한 목록
                $stmt = $pdo->query("SELECT * FROM permissions WHERE is_active = 1 ORDER BY sort_order ASC");
                $permissions = $stmt->fetchAll();
                
                // 등급별 권한 매핑
                $stmt = $pdo->query("SELECT grade_code, permission_code FROM grade_permissions");
                $mappings = $stmt->fetchAll();
                
                $matrix = [];
                foreach ($mappings as $map) {
                    if (!isset($matrix[$map['grade_code']])) {
                        $matrix[$map['grade_code']] = [];
                    }
                    $matrix[$map['grade_code']][] = $map['permission_code'];
                }
                
                jsonResponse(true, ['permissions' => $permissions, 'matrix' => $matrix]);
            } catch (PDOException $e) {
                jsonResponse(false, [], '권한 매트릭스 조회 실패: ' . $e->getMessage());
            }
            break;
            
        case 'preview_bulk_change':
            // 일괄 변경 미리보기
            $from = $_GET['from'] ?? '';
            $to = $_GET['to'] ?? '';
            
            if (empty($to)) {
                jsonResponse(false, [], '변경할 등급을 선택해주세요.');
            }
            
            try {
                if ($from === 'all') {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM members WHERE member_grade != ?");
                    $stmt->execute([$to]);
                } else {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM members WHERE member_grade = ?");
                    $stmt->execute([$from]);
                }
                $result = $stmt->fetch();
                jsonResponse(true, ['count' => (int)$result['cnt']]);
            } catch (PDOException $e) {
                jsonResponse(false, [], '미리보기 조회 실패: ' . $e->getMessage());
            }
            break;
            
        case 'get_admin_list':
            // 관리자 목록 및 회원 목록 조회
            try {
                $stmt = $pdo->query("
                    SELECT id, user_id, name, company, is_admin 
                    FROM members 
                    WHERE is_active = 1
                    ORDER BY is_admin DESC, name ASC
                    LIMIT 50
                ");
                $members = $stmt->fetchAll();
                jsonResponse(true, ['members' => $members]);
            } catch (PDOException $e) {
                jsonResponse(false, [], '회원 목록 조회 실패: ' . $e->getMessage());
            }
            break;
            
        default:
            jsonResponse(false, [], '알 수 없는 액션입니다.');
    }
}

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'save_grade_settings':
            // 등급 설정 저장
            $grades = $input['grades'] ?? [];
            
            if (empty($grades)) {
                jsonResponse(false, [], '저장할 데이터가 없습니다.');
            }
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE member_grade_settings 
                    SET grade_name = ?, discount_rate = ?, sort_order = ?, is_active = ?, updated_at = NOW()
                    WHERE grade_code = ?
                ");
                
                foreach ($grades as $grade) {
                    $stmt->execute([
                        $grade['grade_name'],
                        $grade['discount_rate'],
                        $grade['sort_order'],
                        $grade['is_active'],
                        $grade['grade_code']
                    ]);
                }
                
                jsonResponse(true, [], '등급 설정이 저장되었습니다.');
            } catch (PDOException $e) {
                jsonResponse(false, [], '등급 설정 저장 실패: ' . $e->getMessage());
            }
            break;
            
        case 'save_permissions':
            // 등급별 권한 저장
            $permissions = $input['permissions'] ?? [];
            
            try {
                $pdo->beginTransaction();
                
                // 기존 권한 삭제
                $pdo->exec("DELETE FROM grade_permissions");
                
                // 새 권한 삽입
                $stmt = $pdo->prepare("INSERT INTO grade_permissions (grade_code, permission_code) VALUES (?, ?)");
                
                foreach ($permissions as $gradeCode => $permCodes) {
                    foreach ($permCodes as $permCode) {
                        $stmt->execute([$gradeCode, $permCode]);
                    }
                }
                
                $pdo->commit();
                jsonResponse(true, [], '권한 설정이 저장되었습니다.');
            } catch (PDOException $e) {
                $pdo->rollBack();
                jsonResponse(false, [], '권한 설정 저장 실패: ' . $e->getMessage());
            }
            break;
            
        case 'execute_bulk_change':
            // 일괄 등급 변경
            $from = $input['from'] ?? '';
            $to = $input['to'] ?? '';
            
            if (empty($to)) {
                jsonResponse(false, [], '변경할 등급을 선택해주세요.');
            }
            
            if ($from === $to) {
                jsonResponse(false, [], '현재 등급과 변경할 등급이 같습니다.');
            }
            
            try {
                if ($from === 'all') {
                    $stmt = $pdo->prepare("
                        UPDATE members 
                        SET member_grade = ?, grade_updated_at = NOW() 
                        WHERE member_grade != ?
                    ");
                    $stmt->execute([$to, $to]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE members 
                        SET member_grade = ?, grade_updated_at = NOW() 
                        WHERE member_grade = ?
                    ");
                    $stmt->execute([$to, $from]);
                }
                
                $count = $stmt->rowCount();
                jsonResponse(true, ['count' => $count], $count . '명의 등급이 변경되었습니다.');
            } catch (PDOException $e) {
                jsonResponse(false, [], '일괄 변경 실패: ' . $e->getMessage());
            }
            break;
            
        case 'toggle_admin':
            // 관리자 권한 토글
            $memberId = $input['member_id'] ?? 0;
            
            if (empty($memberId)) {
                jsonResponse(false, [], '회원 ID가 필요합니다.');
            }
            
            try {
                $stmt = $pdo->prepare("UPDATE members SET is_admin = NOT is_admin WHERE id = ?");
                $stmt->execute([$memberId]);
                
                jsonResponse(true, [], '관리자 권한이 변경되었습니다.');
            } catch (PDOException $e) {
                jsonResponse(false, [], '관리자 권한 변경 실패: ' . $e->getMessage());
            }
            break;
            
        default:
            jsonResponse(false, [], '알 수 없는 액션입니다.');
    }
}
