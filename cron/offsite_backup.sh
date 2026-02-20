#\!/bin/bash
# 오프사이트 백업 스크립트
# rclone이 설정된 후 실행 (rclone config로 remote 설정 필요)
# crontab: 30 1,13 * * * /home/cnst/www/html/webservice/html/html/cron/offsite_backup.sh

BACKUP_DIR="/home/cnst/www/html/webservice/html/html/backups"
LOG_DIR="/home/cnst/www/html/webservice/logs"
REMOTE_NAME="offsite"
REMOTE_PATH="cnst-backups"
RETENTION_DAYS=30

mkdir -p "$LOG_DIR"

log() {
    echo "[$(date "+%Y-%m-%d %H:%M:%S")] $1"
}

# rclone 설치 확인
if \! command -v rclone &> /dev/null; then
    log "ERROR: rclone이 설치되어 있지 않습니다. curl https://rclone.org/install.sh | bash 로 설치하세요."
    exit 1
fi

# rclone remote 설정 확인
if \! rclone listremotes 2>/dev/null | grep -q "^${REMOTE_NAME}:"; then
    log "ERROR: rclone remote ${REMOTE_NAME} 이 설정되지 않았습니다. rclone config 로 설정하세요."
    exit 1
fi

# 최신 백업 파일 찾기
LATEST_BACKUP=$(ls -t "$BACKUP_DIR"/project1_db_*.sql 2>/dev/null | head -1)

if [ -z "$LATEST_BACKUP" ]; then
    log "ERROR: 백업 파일을 찾을 수 없습니다: $BACKUP_DIR"
    exit 1
fi

log "최신 백업 파일: $LATEST_BACKUP"

# rclone으로 원격 저장소에 복사
log "원격 저장소로 복사 시작..."
if rclone copy "$LATEST_BACKUP" "${REMOTE_NAME}:${REMOTE_PATH}/" --log-level INFO 2>&1; then
    log "SUCCESS: 원격 복사 완료 - $(basename $LATEST_BACKUP)"
else
    log "ERROR: 원격 복사 실패"
    exit 1
fi

# 오래된 원격 백업 정리
log "오래된 원격 백업 정리 (${RETENTION_DAYS}일 이상)..."
rclone delete "${REMOTE_NAME}:${REMOTE_PATH}/" --min-age "${RETENTION_DAYS}d" --log-level INFO 2>&1

log "오프사이트 백업 완료"
