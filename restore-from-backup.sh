#!/bin/bash

# Project1 백업 복원 스크립트
# 사용법: ./restore-from-backup.sh [백업파일경로]

set -e

# 색상 코드
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 백업 파일 확인
BACKUP_FILE=${1}

if [ -z "$BACKUP_FILE" ]; then
    echo -e "${RED}사용법: $0 <백업파일경로>${NC}"
    echo "예: $0 /mnt/usb/project1_backup_20250808.tar.gz"
    exit 1
fi

if [ ! -f "$BACKUP_FILE" ]; then
    echo -e "${RED}백업 파일을 찾을 수 없습니다: $BACKUP_FILE${NC}"
    exit 1
fi

echo -e "${GREEN}=== Project1 복원 시작 ===${NC}"
echo -e "${YELLOW}백업 파일: $BACKUP_FILE${NC}"

# 작업 디렉토리 설정
WORK_DIR="/tmp/restore_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$WORK_DIR"

# 1. 백업 파일 압축 해제
echo -e "\n${GREEN}1. 백업 파일 압축 해제 중...${NC}"
tar -xzf "$BACKUP_FILE" -C "$WORK_DIR"
BACKUP_DIR=$(ls -d ${WORK_DIR}/project1_backup_* | head -1)

if [ ! -d "$BACKUP_DIR" ]; then
    echo -e "${RED}백업 디렉토리를 찾을 수 없습니다.${NC}"
    exit 1
fi

# 2. 기존 Docker 컨테이너 중지
echo -e "\n${GREEN}2. 기존 Docker 컨테이너 중지 중...${NC}"
if [ -f "docker-compose.yml" ]; then
    docker-compose down || true
fi

# 3. 백업된 파일 복원
echo -e "\n${GREEN}3. 프로젝트 파일 복원 중...${NC}"
cd "$BACKUP_DIR"

# Docker 설정 파일 복원
cp docker-compose.yml ../../
cp nginx.conf ../../ 2>/dev/null || true
cp nginx-php-config.conf ../../ 2>/dev/null || true
cp Dockerfile ../../ 2>/dev/null || true

# 소스코드 복원
if [ -f "project_files.tar.gz" ]; then
    cd ../../
    tar -xzf "$BACKUP_DIR/project_files.tar.gz"
    echo "소스코드 복원 완료"
fi

# 4. Docker 컨테이너 시작
echo -e "\n${GREEN}4. Docker 컨테이너 시작 중...${NC}"
docker-compose up -d

# 5. MySQL 서비스 대기
echo -e "\n${GREEN}5. MySQL 서비스 시작 대기 중...${NC}"
echo -n "MySQL 준비 중"
for i in {1..30}; do
    if docker-compose exec -T mysql mysql -uroot -prootpassword -e "SELECT 1" &>/dev/null; then
        echo -e "\n${GREEN}MySQL 서비스 준비 완료${NC}"
        break
    fi
    echo -n "."
    sleep 2
done

# 6. 데이터베이스 복원
if [ -f "$BACKUP_DIR/mysql_backup.sql" ]; then
    echo -e "\n${GREEN}6. 데이터베이스 복원 중...${NC}"
    docker-compose exec -T mysql mysql -uroot -prootpassword < "$BACKUP_DIR/mysql_backup.sql"
    echo "데이터베이스 복원 완료"
else
    echo -e "${YELLOW}데이터베이스 백업 파일이 없습니다.${NC}"
fi

# 7. 서비스 상태 확인
echo -e "\n${GREEN}7. 서비스 상태 확인${NC}"
docker-compose ps

# 8. 정리
echo -e "\n${GREEN}8. 임시 파일 정리 중...${NC}"
rm -rf "$WORK_DIR"

echo -e "\n${GREEN}=== 복원 완료 ===${NC}"
echo -e "${YELLOW}서비스 URL:${NC}"
echo -e "  웹 서비스: http://localhost:8080"
echo -e "  pgAdmin: http://localhost:8081"
echo -e "\n${YELLOW}복원 정보:${NC}"
if [ -f "$BACKUP_DIR/backup_info.txt" ]; then
    cat "$BACKUP_DIR/backup_info.txt"
fi