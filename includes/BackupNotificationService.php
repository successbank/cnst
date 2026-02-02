<?php
/**
 * 백업 알림 서비스 클래스
 * 백업 성공/실패 시 이메일 알림 발송
 */

require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/settings.php';

class BackupNotificationService {
    private $pdo;
    private $emailService;

    /**
     * 생성자
     * @param PDO $pdo 데이터베이스 연결
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->emailService = new EmailService($pdo);
    }

    /**
     * 알림 활성화 여부 확인
     * @param string $backupType 백업 유형 (auto, manual)
     * @return bool
     */
    public function isNotificationEnabled($backupType) {
        if ($backupType === 'auto') {
            return getSetting('backup_email_auto_enabled', '0') === '1';
        } else {
            return getSetting('backup_email_manual_enabled', '0') === '1';
        }
    }

    /**
     * 성공 알림 활성화 여부
     * @return bool
     */
    public function isSuccessNotificationEnabled() {
        return getSetting('backup_email_on_success', '1') === '1';
    }

    /**
     * 실패 알림 활성화 여부
     * @return bool
     */
    public function isFailureNotificationEnabled() {
        return getSetting('backup_email_on_failure', '1') === '1';
    }

    /**
     * 파일 첨부 활성화 여부
     * @return bool
     */
    public function isFileAttachmentEnabled() {
        return getSetting('backup_email_attach_file', '1') === '1';
    }

    /**
     * 수신자 이메일 목록 가져오기
     * @return string 쉼표로 구분된 이메일 목록
     */
    public function getRecipients() {
        return getSetting('backup_email_recipients', '');
    }

    /**
     * 백업 성공 알림 발송
     * @param string $backupType 백업 유형 (auto, manual)
     * @param string $filename 백업 파일명
     * @param int $fileSize 파일 크기 (bytes)
     * @param int $tablesCount 테이블 수
     * @param string|null $backupFilePath 백업 파일 경로 (첨부용)
     * @return array|null 발송 결과 또는 null (발송 안함)
     */
    public function notifyBackupSuccess($backupType, $filename, $fileSize, $tablesCount, $backupFilePath = null) {
        // 알림 활성화 여부 확인
        if (!$this->isNotificationEnabled($backupType)) {
            return null;
        }

        if (!$this->isSuccessNotificationEnabled()) {
            return null;
        }

        $recipients = $this->getRecipients();
        if (empty($recipients)) {
            return null;
        }

        $subject = '[충남스틸] 데이터베이스 백업 완료';

        // 파일 첨부 여부 결정 (설정 활성화 + 파일 존재 + 20MB 이하)
        $maxAttachSize = 20 * 1024 * 1024; // 20MB
        $shouldAttach = $this->isFileAttachmentEnabled()
            && $backupFilePath !== null
            && file_exists($backupFilePath)
            && $fileSize <= $maxAttachSize;

        if ($shouldAttach) {
            $body = $this->buildSuccessEmailBodyWithAttachment($backupType, $filename, $fileSize, $tablesCount);
            return $this->emailService->sendWithAttachment($recipients, $subject, $body, $backupFilePath, $filename);
        }

        $body = $this->buildSuccessEmailBody($backupType, $filename, $fileSize, $tablesCount);
        return $this->emailService->send($recipients, $subject, $body);
    }

    /**
     * 백업 실패 알림 발송
     * @param string $backupType 백업 유형 (auto, manual)
     * @param string $errorMessage 오류 메시지
     * @return array|null 발송 결과 또는 null (발송 안함)
     */
    public function notifyBackupFailure($backupType, $errorMessage) {
        // 알림 활성화 여부 확인
        if (!$this->isNotificationEnabled($backupType)) {
            return null;
        }

        if (!$this->isFailureNotificationEnabled()) {
            return null;
        }

        $recipients = $this->getRecipients();
        if (empty($recipients)) {
            return null;
        }

        $subject = '[충남스틸] 데이터베이스 백업 실패 알림';
        $body = $this->buildFailureEmailBody($backupType, $errorMessage);

        return $this->emailService->send($recipients, $subject, $body);
    }

    /**
     * 테스트 이메일 발송
     * @param string $recipients 수신자 이메일
     * @param bool $attachBackup 백업 파일 첨부 여부
     * @return array 발송 결과
     */
    public function sendTestEmail($recipients, $attachBackup = false) {
        $subject = '[충남스틸] 백업 알림 테스트 이메일';

        if ($attachBackup) {
            $backupFile = $this->getLatestBackupFile();
            if ($backupFile === null) {
                return [
                    'success' => false,
                    'message' => '첨부할 백업 파일이 없습니다. 먼저 백업을 실행하세요.',
                    'log_ids' => []
                ];
            }

            $body = $this->buildTestEmailBodyWithAttachment($backupFile['filename'], $backupFile['size']);
            return $this->emailService->sendWithAttachment(
                $recipients,
                $subject,
                $body,
                $backupFile['path'],
                $backupFile['filename']
            );
        }

        $body = $this->buildTestEmailBody();
        return $this->emailService->send($recipients, $subject, $body);
    }

    /**
     * 최신 백업 파일 정보 가져오기
     * @return array|null ['path' => string, 'filename' => string, 'size' => int] 또는 null
     */
    public function getLatestBackupFile() {
        $backupDir = dirname(__DIR__) . '/backups';

        if (!is_dir($backupDir)) {
            return null;
        }

        $files = glob($backupDir . '/*.sql');
        if (empty($files)) {
            return null;
        }

        // 최신 파일 찾기 (수정 시간 기준)
        $latestFile = null;
        $latestTime = 0;

        foreach ($files as $file) {
            $mtime = filemtime($file);
            if ($mtime > $latestTime) {
                $latestTime = $mtime;
                $latestFile = $file;
            }
        }

        if ($latestFile === null) {
            return null;
        }

        return [
            'path' => $latestFile,
            'filename' => basename($latestFile),
            'size' => filesize($latestFile),
            'created_at' => $latestTime
        ];
    }

    /**
     * 성공 이메일 본문 생성
     */
    private function buildSuccessEmailBody($backupType, $filename, $fileSize, $tablesCount) {
        $backupTypeName = $backupType === 'auto' ? '자동 백업' : '수동 백업';
        $fileSizeFormatted = $this->formatFileSize($fileSize);
        $completedAt = date('Y-m-d H:i:s');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>백업 완료 알림</title>
</head>
<body style="font-family: 'Malgun Gothic', '맑은 고딕', sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <!-- 헤더 -->
        <div style="background: linear-gradient(135deg, #1A237E 0%, #283593 100%); padding: 30px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">데이터베이스 백업 완료</h1>
        </div>

        <!-- 성공 아이콘 -->
        <div style="text-align: center; padding: 30px 20px 10px;">
            <div style="display: inline-block; width: 60px; height: 60px; background-color: #4CAF50; border-radius: 50%; line-height: 60px;">
                <span style="color: white; font-size: 30px;">✓</span>
            </div>
            <p style="color: #4CAF50; font-size: 18px; font-weight: bold; margin: 15px 0 5px;">백업이 성공적으로 완료되었습니다.</p>
        </div>

        <!-- 백업 정보 -->
        <div style="padding: 0 30px 30px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #666; width: 120px;">백업 유형</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #333; font-weight: 500;">{$backupTypeName}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #666;">파일명</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #333; font-family: monospace;">{$filename}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #666;">파일 크기</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #333;">{$fileSizeFormatted}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #666;">테이블 수</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #333;">{$tablesCount}개</td>
                </tr>
                <tr>
                    <td style="padding: 12px; color: #666;">완료 시간</td>
                    <td style="padding: 12px; color: #333;">{$completedAt}</td>
                </tr>
            </table>
        </div>

        <!-- 푸터 -->
        <div style="background-color: #f5f5f5; padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;">
            <p style="color: #999; font-size: 12px; margin: 0;">
                본 메일은 충남스틸 백업 시스템에서 자동 발송되었습니다.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * 성공 이메일 본문 생성 (첨부 파일 안내 포함)
     */
    private function buildSuccessEmailBodyWithAttachment($backupType, $filename, $fileSize, $tablesCount) {
        $backupTypeName = $backupType === 'auto' ? '자동 백업' : '수동 백업';
        $fileSizeFormatted = $this->formatFileSize($fileSize);
        $completedAt = date('Y-m-d H:i:s');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>백업 완료 알림</title>
</head>
<body style="font-family: 'Malgun Gothic', '맑은 고딕', sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <!-- 헤더 -->
        <div style="background: linear-gradient(135deg, #1A237E 0%, #283593 100%); padding: 30px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">데이터베이스 백업 완료</h1>
        </div>

        <!-- 성공 아이콘 -->
        <div style="text-align: center; padding: 30px 20px 10px;">
            <div style="display: inline-block; width: 60px; height: 60px; background-color: #4CAF50; border-radius: 50%; line-height: 60px;">
                <span style="color: white; font-size: 30px;">✓</span>
            </div>
            <p style="color: #4CAF50; font-size: 18px; font-weight: bold; margin: 15px 0 5px;">백업이 성공적으로 완료되었습니다.</p>
        </div>

        <!-- 첨부 파일 안내 -->
        <div style="padding: 0 30px 15px;">
            <div style="background-color: #e8f5e9; border-left: 4px solid #4CAF50; padding: 12px 15px; border-radius: 0 4px 4px 0;">
                <p style="margin: 0; color: #2e7d32; font-size: 14px;">
                    <strong>📎 첨부 파일:</strong> 백업 파일이 이 이메일에 첨부되어 있습니다.
                </p>
            </div>
        </div>

        <!-- 백업 정보 -->
        <div style="padding: 0 30px 30px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #666; width: 120px;">백업 유형</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #333; font-weight: 500;">{$backupTypeName}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #666;">파일명</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #333; font-family: monospace;">{$filename}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #666;">파일 크기</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #333;">{$fileSizeFormatted}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #666;">테이블 수</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #333;">{$tablesCount}개</td>
                </tr>
                <tr>
                    <td style="padding: 12px; color: #666;">완료 시간</td>
                    <td style="padding: 12px; color: #333;">{$completedAt}</td>
                </tr>
            </table>
        </div>

        <!-- 푸터 -->
        <div style="background-color: #f5f5f5; padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;">
            <p style="color: #999; font-size: 12px; margin: 0;">
                본 메일은 충남스틸 백업 시스템에서 자동 발송되었습니다.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * 실패 이메일 본문 생성
     */
    private function buildFailureEmailBody($backupType, $errorMessage) {
        $backupTypeName = $backupType === 'auto' ? '자동 백업' : '수동 백업';
        $failedAt = date('Y-m-d H:i:s');
        $errorMessageEscaped = htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>백업 실패 알림</title>
</head>
<body style="font-family: 'Malgun Gothic', '맑은 고딕', sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <!-- 헤더 -->
        <div style="background: linear-gradient(135deg, #c62828 0%, #d32f2f 100%); padding: 30px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">데이터베이스 백업 실패</h1>
        </div>

        <!-- 실패 아이콘 -->
        <div style="text-align: center; padding: 30px 20px 10px;">
            <div style="display: inline-block; width: 60px; height: 60px; background-color: #f44336; border-radius: 50%; line-height: 60px;">
                <span style="color: white; font-size: 30px;">✕</span>
            </div>
            <p style="color: #f44336; font-size: 18px; font-weight: bold; margin: 15px 0 5px;">백업 중 오류가 발생했습니다.</p>
        </div>

        <!-- 오류 정보 -->
        <div style="padding: 0 30px 30px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #666; width: 120px;">백업 유형</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #333; font-weight: 500;">{$backupTypeName}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #666;">발생 시간</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e0e0e0; color: #333;">{$failedAt}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; color: #666; vertical-align: top;">오류 내용</td>
                    <td style="padding: 12px; color: #c62828;">
                        <div style="background-color: #ffebee; padding: 12px; border-radius: 4px; font-family: monospace; font-size: 13px;">
                            {$errorMessageEscaped}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 안내 메시지 -->
        <div style="padding: 0 30px 20px;">
            <div style="background-color: #fff3e0; border-left: 4px solid #ff9800; padding: 15px; border-radius: 0 4px 4px 0;">
                <p style="margin: 0; color: #e65100; font-size: 14px;">
                    <strong>조치 필요:</strong> 관리자 페이지에서 백업 상태를 확인하시고,
                    필요한 경우 수동 백업을 실행해 주세요.
                </p>
            </div>
        </div>

        <!-- 푸터 -->
        <div style="background-color: #f5f5f5; padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;">
            <p style="color: #999; font-size: 12px; margin: 0;">
                본 메일은 충남스틸 백업 시스템에서 자동 발송되었습니다.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * 첨부 파일 포함 테스트 이메일 본문 생성
     */
    private function buildTestEmailBodyWithAttachment($filename, $fileSize) {
        $sentAt = date('Y-m-d H:i:s');
        $fileSizeFormatted = $this->formatFileSize($fileSize);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>백업 알림 테스트 (첨부파일 포함)</title>
</head>
<body style="font-family: 'Malgun Gothic', '맑은 고딕', sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <!-- 헤더 -->
        <div style="background: linear-gradient(135deg, #1A237E 0%, #283593 100%); padding: 30px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">백업 알림 테스트</h1>
        </div>

        <!-- 테스트 아이콘 -->
        <div style="text-align: center; padding: 30px 20px 10px;">
            <div style="display: inline-block; width: 60px; height: 60px; background-color: #4CAF50; border-radius: 50%; line-height: 60px;">
                <span style="color: white; font-size: 30px;">📎</span>
            </div>
            <p style="color: #4CAF50; font-size: 18px; font-weight: bold; margin: 15px 0 5px;">테스트 이메일 (백업 파일 첨부)</p>
        </div>

        <!-- 안내 -->
        <div style="padding: 0 30px 20px;">
            <div style="background-color: #e8f5e9; border-radius: 8px; padding: 20px; text-align: center;">
                <p style="color: #2e7d32; margin: 0 0 10px; font-size: 15px;">
                    이 이메일을 수신하셨다면 백업 알림 설정이 올바르게 구성되었습니다.
                </p>
                <p style="color: #666; margin: 0; font-size: 13px;">
                    첨부된 백업 파일을 확인하세요.
                </p>
            </div>
        </div>

        <!-- 첨부 파일 정보 -->
        <div style="padding: 0 30px 30px;">
            <table style="width: 100%; border-collapse: collapse; background: #f5f5f5; border-radius: 8px;">
                <tr>
                    <td style="padding: 12px; color: #666; width: 100px;">첨부 파일</td>
                    <td style="padding: 12px; color: #333; font-family: monospace; font-size: 13px;">{$filename}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; color: #666;">파일 크기</td>
                    <td style="padding: 12px; color: #333;">{$fileSizeFormatted}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; color: #666;">발송 시간</td>
                    <td style="padding: 12px; color: #333;">{$sentAt}</td>
                </tr>
            </table>
        </div>

        <!-- 푸터 -->
        <div style="background-color: #f5f5f5; padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;">
            <p style="color: #999; font-size: 12px; margin: 0;">
                본 메일은 충남스틸 백업 시스템에서 자동 발송되었습니다.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * 테스트 이메일 본문 생성
     */
    private function buildTestEmailBody() {
        $sentAt = date('Y-m-d H:i:s');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>백업 알림 테스트</title>
</head>
<body style="font-family: 'Malgun Gothic', '맑은 고딕', sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <!-- 헤더 -->
        <div style="background: linear-gradient(135deg, #1A237E 0%, #283593 100%); padding: 30px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">백업 알림 테스트</h1>
        </div>

        <!-- 테스트 아이콘 -->
        <div style="text-align: center; padding: 30px 20px 10px;">
            <div style="display: inline-block; width: 60px; height: 60px; background-color: #2196F3; border-radius: 50%; line-height: 60px;">
                <span style="color: white; font-size: 30px;">✉</span>
            </div>
            <p style="color: #2196F3; font-size: 18px; font-weight: bold; margin: 15px 0 5px;">테스트 이메일입니다.</p>
        </div>

        <!-- 안내 -->
        <div style="padding: 0 30px 30px;">
            <div style="background-color: #e3f2fd; border-radius: 8px; padding: 20px; text-align: center;">
                <p style="color: #1565c0; margin: 0 0 10px; font-size: 15px;">
                    이 이메일을 수신하셨다면 백업 알림 설정이 올바르게 구성되었습니다.
                </p>
                <p style="color: #666; margin: 0; font-size: 13px;">
                    발송 시간: {$sentAt}
                </p>
            </div>
        </div>

        <!-- 푸터 -->
        <div style="background-color: #f5f5f5; padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;">
            <p style="color: #999; font-size: 12px; margin: 0;">
                본 메일은 충남스틸 백업 시스템에서 자동 발송되었습니다.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * 파일 크기 포맷
     */
    private function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
