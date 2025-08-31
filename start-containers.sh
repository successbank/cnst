#!/bin/bash

# Docker가 완전히 시작될 때까지 대기
echo "Docker 서비스가 시작될 때까지 대기 중..."
until docker info >/dev/null 2>&1; do
    echo "Docker를 기다리는 중..."
    sleep 2
done

echo "Docker가 준비되었습니다. 컨테이너를 시작합니다..."

# Project1 디렉토리로 이동
cd /home/successbank/projects/docker/project1

# Docker Compose로 컨테이너 시작
docker compose up -d

# 컨테이너 상태 확인
echo "컨테이너 상태:"
docker compose ps

# 포트 확인
echo ""
echo "포트 1112 상태:"
netstat -tln | grep 1112 || echo "포트 1112가 아직 열리지 않았습니다."