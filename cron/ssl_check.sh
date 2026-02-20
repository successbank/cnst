#\!/bin/bash
# SSL 인증서 만료 모니터링
# crontab: 0 6 * * * /home/cnst/www/html/webservice/html/html/cron/ssl_check.sh

DOMAIN="cnst.co.kr"
WARN_DAYS=14
LOG_PREFIX="[$(date "+%Y-%m-%d %H:%M:%S")]"

# 인증서 만료일 확인
EXPIRY_DATE=$(echo | openssl s_client -servername "$DOMAIN" -connect "$DOMAIN":443 2>/dev/null | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2)

if [ -z "$EXPIRY_DATE" ]; then
    echo "$LOG_PREFIX [ERROR] SSL 인증서 정보를 가져올 수 없습니다: $DOMAIN"
    exit 1
fi

# 남은 일수 계산
EXPIRY_EPOCH=$(date -d "$EXPIRY_DATE" +%s 2>/dev/null)
NOW_EPOCH=$(date +%s)
DAYS_LEFT=$(( (EXPIRY_EPOCH - NOW_EPOCH) / 86400 ))

if [ "$DAYS_LEFT" -le 0 ]; then
    echo "$LOG_PREFIX [CRITICAL] SSL 인증서 만료됨\! Domain=$DOMAIN, 만료일=$EXPIRY_DATE"
elif [ "$DAYS_LEFT" -le "$WARN_DAYS" ]; then
    echo "$LOG_PREFIX [WARNING] SSL 인증서 만료 임박\! Domain=$DOMAIN, 남은일수=${DAYS_LEFT}일, 만료일=$EXPIRY_DATE"
    # certbot 자동 갱신 시도
    if command -v certbot &> /dev/null; then
        echo "$LOG_PREFIX certbot 갱신 시도 중..."
        certbot renew --quiet 2>&1
    fi
else
    echo "$LOG_PREFIX [OK] SSL 인증서 정상. Domain=$DOMAIN, 남은일수=${DAYS_LEFT}일"
fi
