#!/bin/bash

# Database Backup Script for project5_db

# 설정
DB_NAME="project5_db"
DB_USER="root"
DB_PASS="rootpassword"
CONTAINER_NAME="1a3e4166cbca_project1_mysql"
BACKUP_DIR="/home/successbank/projects/docker/project1/database_backup"

# 백업 파일명 생성 (날짜와 시간 포함)
BACKUP_FILE="$BACKUP_DIR/${DB_NAME}_backup_$(date +%Y%m%d_%H%M%S).sql"

echo "==================================="
echo "Database Backup Script"
echo "==================================="
echo "Database: $DB_NAME"
echo "Backup file: $BACKUP_FILE"
echo ""

# 백업 실행
echo "Starting backup..."
docker exec $CONTAINER_NAME mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > "$BACKUP_FILE"

if [ $? -eq 0 ]; then
    # 백업 파일 크기 확인
    FILE_SIZE=$(ls -lh "$BACKUP_FILE" | awk '{print $5}')
    echo "Backup completed successfully!"
    echo "File size: $FILE_SIZE"
    
    # 압축 옵션
    echo ""
    read -p "Do you want to compress the backup file? (y/n): " COMPRESS
    if [ "$COMPRESS" = "y" ] || [ "$COMPRESS" = "Y" ]; then
        echo "Compressing backup file..."
        gzip "$BACKUP_FILE"
        COMPRESSED_SIZE=$(ls -lh "$BACKUP_FILE.gz" | awk '{print $5}')
        echo "Compressed file: $BACKUP_FILE.gz"
        echo "Compressed size: $COMPRESSED_SIZE"
    fi
else
    echo "Backup failed!"
    exit 1
fi

echo ""
echo "==================================="
echo "Backup process completed!"
echo "==================================="