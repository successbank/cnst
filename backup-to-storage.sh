#!/bin/bash

# Project1 FTP/저장장치 백업 스크립트
# 사용법: ./backup-to-storage.sh [ftp|usb|nas]

set -e

# 색상 코드
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 백업 설정
BACKUP_DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="project1_backup_${BACKUP_DATE}"
TEMP_DIR="/tmp/${BACKUP_NAME}"

# 백업 타입 확인
BACKUP_TYPE=${1:-"usb"}

echo -e "${GREEN}=== Project1 백업 시작 (${BACKUP_TYPE}) ===${NC}"

# 임시 백업 디렉토리 생성
mkdir -p "${TEMP_DIR}"

# 1. Docker 컨테이너 정지 (데이터 일관성)
echo -e "\n${YELLOW}Docker 컨테이너 상태 저장 중...${NC}"
docker-compose ps > "${TEMP_DIR}/container_status.txt"

# 2. 데이터베이스 백업
echo -e "\n${GREEN}데이터베이스 백업 중...${NC}"
docker exec 1a3e4166cbca_project1_mysql mysqldump -uroot -prootpassword \
    --all-databases --single-transaction --routines --triggers \
    > "${TEMP_DIR}/mysql_backup.sql"

# 3. 소스코드 및 설정 파일 백업
echo -e "\n${GREEN}파일 백업 중...${NC}"
tar -czf "${TEMP_DIR}/project_files.tar.gz" \
    --exclude='*.log' \
    --exclude='node_modules' \
    --exclude='vendor' \
    docker-compose.yml \
    nginx.conf \
    nginx-php-config.conf \
    Dockerfile \
    html/

# 4. Docker 볼륨 정보 저장
docker volume inspect project1_mysql_data > "${TEMP_DIR}/volume_info.json"

# 5. 백업 정보 파일 생성
cat > "${TEMP_DIR}/backup_info.txt" << EOF
백업 날짜: $(date)
백업 타입: ${BACKUP_TYPE}
Docker 버전: $(docker --version)
서버 정보: $(hostname)
백업 크기: $(du -sh ${TEMP_DIR} | cut -f1)
EOF

# 6. 전체 백업 압축
cd /tmp
tar -czf "${BACKUP_NAME}.tar.gz" "${BACKUP_NAME}/"
BACKUP_FILE="/tmp/${BACKUP_NAME}.tar.gz"
BACKUP_SIZE=$(du -h ${BACKUP_FILE} | cut -f1)

echo -e "\n${GREEN}백업 파일 생성 완료: ${BACKUP_FILE} (${BACKUP_SIZE})${NC}"

# 7. 백업 대상별 처리
case $BACKUP_TYPE in
    "ftp")
        echo -e "\n${YELLOW}FTP 업로드 설정${NC}"
        cat > "${TEMP_DIR}/ftp_upload.sh" << 'FTP_SCRIPT'
#!/bin/bash
# FTP 업로드 스크립트

FTP_HOST="your-ftp-server.com"
FTP_USER="your-username"
FTP_PASS="your-password"
FTP_DIR="/backups/project1"

# lftp를 사용한 업로드 (설치: apt-get install lftp)
lftp -c "
    open ftp://${FTP_USER}:${FTP_PASS}@${FTP_HOST}
    mkdir -p ${FTP_DIR}
    cd ${FTP_DIR}
    put BACKUP_FILE_PATH
    bye
"

# 또는 curl을 사용한 업로드
# curl -T BACKUP_FILE_PATH ftp://${FTP_HOST}${FTP_DIR}/ --user ${FTP_USER}:${FTP_PASS}
FTP_SCRIPT
        
        echo -e "${YELLOW}FTP 업로드를 위해 ftp_upload.sh 파일을 수정하고 실행하세요.${NC}"
        echo "파일 위치: ${TEMP_DIR}/ftp_upload.sh"
        ;;
        
    "usb")
        echo -e "\n${YELLOW}USB 저장장치 복사${NC}"
        echo "사용 가능한 저장장치:"
        lsblk -o NAME,SIZE,TYPE,MOUNTPOINT | grep -E "disk|part"
        
        echo -e "\n${GREEN}USB 복사 명령어:${NC}"
        echo "sudo cp ${BACKUP_FILE} /media/usb/backups/"
        echo "또는"
        echo "sudo cp ${BACKUP_FILE} /mnt/usb/"
        ;;
        
    "nas")
        echo -e "\n${YELLOW}NAS 저장장치 설정${NC}"
        cat > "${TEMP_DIR}/nas_mount.sh" << 'NAS_SCRIPT'
#!/bin/bash
# NAS 마운트 및 백업 스크립트

NAS_HOST="192.168.1.100"  # NAS IP 주소
NAS_SHARE="backup"         # 공유 폴더명
NAS_USER="username"        # NAS 사용자명
NAS_PASS="password"        # NAS 비밀번호
MOUNT_POINT="/mnt/nas"

# CIFS/SMB 마운트 (설치: apt-get install cifs-utils)
sudo mkdir -p ${MOUNT_POINT}
sudo mount -t cifs //${NAS_HOST}/${NAS_SHARE} ${MOUNT_POINT} \
    -o username=${NAS_USER},password=${NAS_PASS},vers=3.0

# 백업 파일 복사
sudo cp BACKUP_FILE_PATH ${MOUNT_POINT}/project1/

# 마운트 해제
sudo umount ${MOUNT_POINT}
NAS_SCRIPT
        
        echo -e "${YELLOW}NAS 업로드를 위해 nas_mount.sh 파일을 수정하고 실행하세요.${NC}"
        echo "파일 위치: ${TEMP_DIR}/nas_mount.sh"
        ;;
esac

# 8. 자동 백업 크론 설정
cat > "${TEMP_DIR}/setup_cron.sh" << 'CRON_SCRIPT'
#!/bin/bash
# 자동 백업 크론 설정

# 매일 새벽 2시 백업 실행
CRON_CMD="0 2 * * * /home/successbank/projects/docker/project1/backup-to-storage.sh usb"

# 현재 크론탭 확인
crontab -l > mycron 2>/dev/null || true

# 중복 확인 후 추가
if ! grep -q "backup-to-storage.sh" mycron; then
    echo "${CRON_CMD}" >> mycron
    crontab mycron
    echo "크론 작업이 추가되었습니다."
else
    echo "크론 작업이 이미 존재합니다."
fi

rm mycron
CRON_SCRIPT

chmod +x "${TEMP_DIR}/setup_cron.sh"

echo -e "\n${GREEN}=== 백업 완료 ===${NC}"
echo -e "${YELLOW}백업 파일: ${BACKUP_FILE}${NC}"
echo -e "${YELLOW}백업 크기: ${BACKUP_SIZE}${NC}"
echo -e "\n${GREEN}다음 단계:${NC}"
echo "1. 백업 파일을 원하는 저장장치로 복사"
echo "2. 자동 백업 설정: ${TEMP_DIR}/setup_cron.sh"
echo "3. 임시 파일 정리: rm -rf ${TEMP_DIR}"