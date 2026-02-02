<?php
/**
 * 이메일 발송 서비스 클래스
 * SMTP 직접 연결 방식 사용
 */

class EmailService {
    private $pdo;
    private $fromEmail;
    private $fromName;

    // SMTP 설정
    private $smtpHost = 'mail.cnst.co.kr';
    private $smtpPort = 25;
    private $smtpUser = 'cnst@cnst.co.kr';
    private $smtpPass = '';
    private $smtpSecure = 'none'; // starttls, ssl, 또는 none
    private $smtpAuth = false; // 인증 사용 여부

    /**
     * 생성자
     * @param PDO $pdo 데이터베이스 연결
     * @param string $fromEmail 발신자 이메일
     * @param string $fromName 발신자 이름
     */
    public function __construct($pdo, $fromEmail = null, $fromName = '충남스틸') {
        $this->pdo = $pdo;
        $this->fromEmail = $fromEmail ?: $this->smtpUser;
        $this->fromName = $fromName;
    }

    /**
     * 이메일 발송
     * @param string|array $recipients 수신자 이메일 (문자열 또는 배열)
     * @param string $subject 제목
     * @param string $body 본문 (HTML)
     * @return array ['success' => bool, 'message' => string, 'log_ids' => array]
     */
    public function send($recipients, $subject, $body) {
        // 수신자를 배열로 변환
        if (is_string($recipients)) {
            $recipients = array_map('trim', explode(',', $recipients));
        }

        // 빈 이메일 제거
        $recipients = array_filter($recipients, function($email) {
            return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
        });

        if (empty($recipients)) {
            return [
                'success' => false,
                'message' => '유효한 수신자 이메일이 없습니다.',
                'log_ids' => []
            ];
        }

        $results = [
            'success' => true,
            'message' => '',
            'log_ids' => [],
            'sent_count' => 0,
            'failed_count' => 0
        ];

        foreach ($recipients as $recipient) {
            $logId = $this->logEmail($recipient, $subject, $body);
            $results['log_ids'][] = $logId;

            $sendResult = $this->sendViaSMTP($recipient, $subject, $body);

            if ($sendResult['success']) {
                $this->updateLogStatus($logId, 'sent');
                $results['sent_count']++;
            } else {
                $this->updateLogStatus($logId, 'failed', $sendResult['error']);
                $results['failed_count']++;
            }
        }

        if ($results['failed_count'] > 0) {
            if ($results['sent_count'] > 0) {
                $results['message'] = "{$results['sent_count']}개 발송 성공, {$results['failed_count']}개 실패";
            } else {
                $results['success'] = false;
                $results['message'] = '모든 이메일 발송 실패';
            }
        } else {
            $results['message'] = "{$results['sent_count']}개 이메일 발송 완료";
        }

        return $results;
    }

    /**
     * SMTP를 통한 이메일 발송
     * @param string $to 수신자 이메일
     * @param string $subject 제목
     * @param string $body HTML 본문
     * @return array ['success' => bool, 'error' => string|null]
     */
    private function sendViaSMTP($to, $subject, $body) {
        $socket = null;

        try {
            // SSL 컨텍스트 (인증서 검증 건너뛰기 - 자체 서명 인증서 지원)
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);

            // 연결
            if ($this->smtpSecure === 'ssl') {
                $host = 'ssl://' . $this->smtpHost;
                $socket = @stream_socket_client("$host:{$this->smtpPort}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
            } else {
                $socket = @stream_socket_client("{$this->smtpHost}:{$this->smtpPort}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
            }

            if (!$socket) {
                throw new Exception("SMTP 연결 실패: $errstr ($errno)");
            }

            // 서버 응답 읽기
            $this->getResponse($socket);

            // EHLO
            $this->sendCommand($socket, "EHLO localhost", 250);

            // STARTTLS (포트 25 또는 587에서)
            if ($this->smtpSecure === 'starttls') {
                $this->sendCommand($socket, "STARTTLS", 220);
                // SSL 옵션 설정 (자체 서명 인증서 허용)
                stream_context_set_option($socket, 'ssl', 'verify_peer', false);
                stream_context_set_option($socket, 'ssl', 'verify_peer_name', false);
                stream_context_set_option($socket, 'ssl', 'allow_self_signed', true);
                $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
                if (!$crypto) {
                    throw new Exception("TLS 암호화 시작 실패");
                }
                $this->sendCommand($socket, "EHLO localhost", 250);
            }

            // 인증 (활성화된 경우에만)
            if ($this->smtpAuth && !empty($this->smtpPass)) {
                $this->sendCommand($socket, "AUTH LOGIN", 334);
                $this->sendCommand($socket, base64_encode($this->smtpUser), 334);
                $this->sendCommand($socket, base64_encode($this->smtpPass), 235);
            }

            // 발신자
            $this->sendCommand($socket, "MAIL FROM:<{$this->fromEmail}>", 250);

            // 수신자
            $this->sendCommand($socket, "RCPT TO:<$to>", 250);

            // 데이터 전송 시작
            $this->sendCommand($socket, "DATA", 354);

            // 이메일 헤더 및 본문 구성
            $headers = $this->buildEmailHeaders($to, $subject);
            $message = $headers . "\r\n" . $body;

            // 본문에서 줄 시작의 점(.) 처리 (SMTP 프로토콜)
            $message = str_replace("\r\n.", "\r\n..", $message);

            // 메시지 전송
            fwrite($socket, $message . "\r\n.\r\n");
            $response = $this->getResponse($socket);

            if (strpos($response, '250') !== 0) {
                throw new Exception("메시지 전송 실패: $response");
            }

            // 연결 종료
            $this->sendCommand($socket, "QUIT", 221);
            fclose($socket);

            return ['success' => true, 'error' => null];

        } catch (Exception $e) {
            if ($socket) {
                @fclose($socket);
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * SMTP 명령 전송 및 응답 확인
     */
    private function sendCommand($socket, $command, $expectedCode) {
        fwrite($socket, $command . "\r\n");
        $response = $this->getResponse($socket);

        if (strpos($response, (string)$expectedCode) !== 0) {
            throw new Exception("SMTP 오류 [$command]: $response");
        }

        return $response;
    }

    /**
     * SMTP 서버 응답 읽기
     */
    private function getResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            // 응답의 4번째 문자가 공백이면 마지막 줄
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return trim($response);
    }

    /**
     * 이메일 헤더 구성
     */
    private function buildEmailHeaders($to, $subject) {
        $headers = "Date: " . date('r') . "\r\n";
        $headers .= "From: " . $this->encodeHeader($this->fromName) . " <{$this->fromEmail}>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: " . $this->encodeHeader($subject) . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "X-Mailer: ChungnamSteel-Mailer/1.0";

        return $headers;
    }

    /**
     * 헤더 인코딩 (한글 지원)
     */
    private function encodeHeader($str) {
        if (preg_match('/[^\x20-\x7E]/', $str)) {
            return '=?UTF-8?B?' . base64_encode($str) . '?=';
        }
        return $str;
    }

    /**
     * UTF-8 변환
     */
    private function convertToUtf8($str) {
        if (mb_detect_encoding($str, 'UTF-8', true) === false) {
            return mb_convert_encoding($str, 'UTF-8', 'auto');
        }
        return $str;
    }

    /**
     * 이메일 로그 기록
     */
    private function logEmail($recipient, $subject, $body) {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO email_logs (recipient, subject, body, status) VALUES (?, ?, ?, 'pending')"
            );
            $stmt->execute([$recipient, $subject, $body]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * 로그 상태 업데이트
     */
    private function updateLogStatus($logId, $status, $errorMessage = null) {
        if ($logId <= 0) return;

        try {
            if ($status === 'sent') {
                $stmt = $this->pdo->prepare(
                    "UPDATE email_logs SET status = 'sent', sent_at = NOW() WHERE id = ?"
                );
                $stmt->execute([$logId]);
            } else {
                $stmt = $this->pdo->prepare(
                    "UPDATE email_logs SET status = 'failed', error_message = ? WHERE id = ?"
                );
                $stmt->execute([$errorMessage, $logId]);
            }
        } catch (PDOException $e) {
            // 로그 실패는 무시
        }
    }

    /**
     * 첨부 파일과 함께 이메일 발송
     * @param string|array $recipients 수신자 이메일 (문자열 또는 배열)
     * @param string $subject 제목
     * @param string $body 본문 (HTML)
     * @param string $attachmentPath 첨부 파일 경로
     * @param string|null $attachmentName 첨부 파일명 (null이면 원본 파일명 사용)
     * @return array ['success' => bool, 'message' => string, 'log_ids' => array]
     */
    public function sendWithAttachment($recipients, $subject, $body, $attachmentPath, $attachmentName = null) {
        // 첨부 파일 검증
        if (!file_exists($attachmentPath)) {
            return [
                'success' => false,
                'message' => '첨부 파일을 찾을 수 없습니다: ' . $attachmentPath,
                'log_ids' => []
            ];
        }

        // 파일 크기 제한 (20MB)
        $maxSize = 20 * 1024 * 1024;
        $fileSize = filesize($attachmentPath);
        if ($fileSize > $maxSize) {
            return [
                'success' => false,
                'message' => '첨부 파일이 너무 큽니다. 최대 20MB까지 허용됩니다. (현재: ' . round($fileSize / 1048576, 2) . 'MB)',
                'log_ids' => []
            ];
        }

        // 수신자를 배열로 변환
        if (is_string($recipients)) {
            $recipients = array_map('trim', explode(',', $recipients));
        }

        // 빈 이메일 제거
        $recipients = array_filter($recipients, function($email) {
            return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
        });

        if (empty($recipients)) {
            return [
                'success' => false,
                'message' => '유효한 수신자 이메일이 없습니다.',
                'log_ids' => []
            ];
        }

        // 첨부 파일명 설정
        if ($attachmentName === null) {
            $attachmentName = basename($attachmentPath);
        }

        $results = [
            'success' => true,
            'message' => '',
            'log_ids' => [],
            'sent_count' => 0,
            'failed_count' => 0
        ];

        foreach ($recipients as $recipient) {
            $logId = $this->logEmail($recipient, $subject, $body . "\n[첨부파일: {$attachmentName}]");
            $results['log_ids'][] = $logId;

            $sendResult = $this->sendViaSMTPWithAttachment($recipient, $subject, $body, $attachmentPath, $attachmentName);

            if ($sendResult['success']) {
                $this->updateLogStatus($logId, 'sent');
                $results['sent_count']++;
            } else {
                $this->updateLogStatus($logId, 'failed', $sendResult['error']);
                $results['failed_count']++;
            }
        }

        if ($results['failed_count'] > 0) {
            if ($results['sent_count'] > 0) {
                $results['message'] = "{$results['sent_count']}개 발송 성공, {$results['failed_count']}개 실패";
            } else {
                $results['success'] = false;
                $results['message'] = '모든 이메일 발송 실패';
            }
        } else {
            $results['message'] = "{$results['sent_count']}개 이메일 발송 완료 (첨부파일 포함)";
        }

        return $results;
    }

    /**
     * SMTP를 통한 이메일 발송 (첨부 파일 포함)
     * @param string $to 수신자 이메일
     * @param string $subject 제목
     * @param string $body HTML 본문
     * @param string $attachmentPath 첨부 파일 경로
     * @param string $attachmentName 첨부 파일명
     * @return array ['success' => bool, 'error' => string|null]
     */
    private function sendViaSMTPWithAttachment($to, $subject, $body, $attachmentPath, $attachmentName) {
        $socket = null;

        try {
            // SSL 컨텍스트 (인증서 검증 건너뛰기 - 자체 서명 인증서 지원)
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);

            // 연결
            if ($this->smtpSecure === 'ssl') {
                $host = 'ssl://' . $this->smtpHost;
                $socket = @stream_socket_client("$host:{$this->smtpPort}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
            } else {
                $socket = @stream_socket_client("{$this->smtpHost}:{$this->smtpPort}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
            }

            if (!$socket) {
                throw new Exception("SMTP 연결 실패: $errstr ($errno)");
            }

            // 서버 응답 읽기
            $this->getResponse($socket);

            // EHLO
            $this->sendCommand($socket, "EHLO localhost", 250);

            // STARTTLS (포트 25 또는 587에서)
            if ($this->smtpSecure === 'starttls') {
                $this->sendCommand($socket, "STARTTLS", 220);
                stream_context_set_option($socket, 'ssl', 'verify_peer', false);
                stream_context_set_option($socket, 'ssl', 'verify_peer_name', false);
                stream_context_set_option($socket, 'ssl', 'allow_self_signed', true);
                $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
                if (!$crypto) {
                    throw new Exception("TLS 암호화 시작 실패");
                }
                $this->sendCommand($socket, "EHLO localhost", 250);
            }

            // 인증 (활성화된 경우에만)
            if ($this->smtpAuth && !empty($this->smtpPass)) {
                $this->sendCommand($socket, "AUTH LOGIN", 334);
                $this->sendCommand($socket, base64_encode($this->smtpUser), 334);
                $this->sendCommand($socket, base64_encode($this->smtpPass), 235);
            }

            // 발신자
            $this->sendCommand($socket, "MAIL FROM:<{$this->fromEmail}>", 250);

            // 수신자
            $this->sendCommand($socket, "RCPT TO:<$to>", 250);

            // 데이터 전송 시작
            $this->sendCommand($socket, "DATA", 354);

            // MIME Multipart 메시지 구성
            $message = $this->buildMultipartMessage($to, $subject, $body, $attachmentPath, $attachmentName);

            // 본문에서 줄 시작의 점(.) 처리 (SMTP 프로토콜)
            $message = str_replace("\r\n.", "\r\n..", $message);

            // 메시지 전송
            fwrite($socket, $message . "\r\n.\r\n");
            $response = $this->getResponse($socket);

            if (strpos($response, '250') !== 0) {
                throw new Exception("메시지 전송 실패: $response");
            }

            // 연결 종료
            $this->sendCommand($socket, "QUIT", 221);
            fclose($socket);

            return ['success' => true, 'error' => null];

        } catch (Exception $e) {
            if ($socket) {
                @fclose($socket);
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * MIME Multipart 메시지 구성
     * @param string $to 수신자 이메일
     * @param string $subject 제목
     * @param string $body HTML 본문
     * @param string $attachmentPath 첨부 파일 경로
     * @param string $attachmentName 첨부 파일명
     * @return string MIME 메시지
     */
    private function buildMultipartMessage($to, $subject, $body, $attachmentPath, $attachmentName) {
        $boundary = "----=_Part_" . md5(uniqid(time()));

        // 헤더 구성
        $headers = "Date: " . date('r') . "\r\n";
        $headers .= "From: " . $this->encodeHeader($this->fromName) . " <{$this->fromEmail}>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: " . $this->encodeHeader($subject) . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
        $headers .= "X-Mailer: ChungnamSteel-Mailer/1.0\r\n";

        // 본문 파트
        $message = $headers . "\r\n";
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($body)) . "\r\n";

        // 첨부 파일 파트
        $fileContent = file_get_contents($attachmentPath);
        $encodedAttachment = chunk_split(base64_encode($fileContent));

        // MIME 타입 결정
        $mimeType = 'application/octet-stream';
        $extension = strtolower(pathinfo($attachmentName, PATHINFO_EXTENSION));
        if ($extension === 'sql') {
            $mimeType = 'application/sql';
        } elseif ($extension === 'txt') {
            $mimeType = 'text/plain';
        } elseif ($extension === 'gz') {
            $mimeType = 'application/gzip';
        }

        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: {$mimeType}; name=\"" . $this->encodeHeader($attachmentName) . "\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n";
        $message .= "Content-Disposition: attachment; filename=\"" . $this->encodeHeader($attachmentName) . "\"\r\n\r\n";
        $message .= $encodedAttachment . "\r\n";

        // 종료 boundary
        $message .= "--{$boundary}--";

        return $message;
    }

    /**
     * 이메일 주소 유효성 검증
     */
    public static function isValidEmail($email) {
        return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * 여러 이메일 주소 검증 (쉼표 구분)
     */
    public static function validateEmails($emails) {
        $result = ['valid' => [], 'invalid' => []];
        $emailList = array_map('trim', explode(',', $emails));

        foreach ($emailList as $email) {
            if (empty($email)) continue;
            if (self::isValidEmail($email)) {
                $result['valid'][] = $email;
            } else {
                $result['invalid'][] = $email;
            }
        }

        return $result;
    }
}
