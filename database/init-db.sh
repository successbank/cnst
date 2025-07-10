#!/bin/bash

# MySQL 접속 정보
MYSQL_USER="root"
MYSQL_PASSWORD="manpass!@#4"
MYSQL_HOST="localhost"
MYSQL_PORT="3306"

# Docker MySQL 컨테이너로 접속
echo "충남스틸 데이터베이스 초기화 중..."

# Docker 컨테이너 이름으로 MySQL 명령 실행
docker exec -i project1_mysql mysql -u${MYSQL_USER} -p${MYSQL_PASSWORD} < ./database/schema.sql

if [ $? -eq 0 ]; then
    echo "데이터베이스 초기화 완료!"
else
    echo "데이터베이스 초기화 실패!"
    exit 1
fi