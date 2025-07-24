<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Roundcube Webmail Demo</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #f0f0f0;
        }
        .container {
            text-align: center;
            padding: 50px;
        }
        .demo-box {
            background: white;
            border-radius: 10px;
            padding: 40px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .info {
            color: #666;
            margin: 20px 0;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            background: #37beff;
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 18px;
            margin: 10px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #2090cc;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .screenshot {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .screenshot img {
            max-width: 100%;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="demo-box">
            <h1>Roundcube Webmail Demo</h1>
            
            <div class="info">
                <p><strong>현재 상황:</strong></p>
                <p>IMAP 서버가 설치되지 않아 실제 로그인은 불가능합니다.</p>
                <p>하지만 아래 방법으로 UI를 확인할 수 있습니다.</p>
            </div>
            
            <h2>UI 확인 방법</h2>
            
            <div class="info">
                <p><strong>방법 1: 개발자 도구 사용 (권장)</strong></p>
                <ol style="text-align: left; display: inline-block;">
                    <li>아래 "로그인 화면으로 이동" 버튼 클릭</li>
                    <li>브라우저에서 F12를 눌러 개발자 도구 열기</li>
                    <li>Console 탭에서 다음 코드 실행:
                        <pre style="background: #f4f4f4; padding: 10px; border-radius: 5px;">
document.cookie = "roundcube_sessid=demo123; path=/webmail/";
document.cookie = "roundcube_sessauth=demo; path=/webmail/";
window.location.href = "./?_task=mail";</pre>
                    </li>
                </ol>
            </div>
            
            <a href="./" class="btn">로그인 화면으로 이동</a>
            
            <div class="info" style="margin-top: 40px;">
                <p><strong>방법 2: 간단한 메일 서버 설치</strong></p>
                <p>Docker를 사용하여 테스트용 메일 서버를 설치할 수 있습니다:</p>
                <pre style="background: #f4f4f4; padding: 15px; border-radius: 5px; text-align: left;">
docker run -d \
  --name mailserver \
  -p 25:25 -p 143:143 -p 587:587 -p 993:993 \
  -e ENABLE_SPAMASSASSIN=0 \
  -e ENABLE_CLAMAV=0 \
  -e ENABLE_FAIL2BAN=0 \
  -e ENABLE_POSTGREY=0 \
  -e ONE_DIR=1 \
  -e DMS_DEBUG=0 \
  mailserver/docker-mailserver:latest</pre>
            </div>
            
            <div class="screenshot">
                <h3>Roundcube Webmail UI 미리보기</h3>
                <p>실제 로그인 후 표시되는 화면입니다:</p>
                <img src="https://roundcube.net/images/screens/elastic/desktop/mail.jpg" alt="Roundcube Mail Interface">
            </div>
        </div>
    </div>
</body>
</html>