# 재부팅 후 실행할 명령어

## 1. 시스템 재부팅 후 자동으로 Docker 컨테이너 시작하기

### 방법 1: 수동으로 스크립트 실행
```bash
cd /home/successbank/projects/docker/project1
./start-containers.sh
```

### 방법 2: Docker Compose 직접 실행
```bash
cd /home/successbank/projects/docker/project1
docker compose up -d
```

### 방법 3: 시스템 서비스로 자동 시작 설정 (한 번만 실행)
```bash
# 서비스 파일을 시스템 디렉토리로 복사
sudo cp /home/successbank/projects/docker/project1/project1-docker.service /etc/systemd/system/

# 서비스 활성화
sudo systemctl enable project1-docker.service

# 서비스 시작
sudo systemctl start project1-docker.service
```

## 2. 컨테이너 상태 확인
```bash
# Docker 컨테이너 상태 확인
docker compose ps

# 포트 1112 확인
netstat -tln | grep 1112

# 웹사이트 접속 테스트
curl http://localhost:1112
```

## 3. 문제 해결

### Docker 데몬이 연결되지 않을 때:
```bash
# Docker 서비스 재시작
sudo systemctl restart docker

# Docker 소켓 권한 설정
sudo chmod 666 /var/run/docker.sock

# 사용자를 docker 그룹에 추가 (이미 추가됨)
# sudo usermod -aG docker successbank
```

### 컨테이너가 시작되지 않을 때:
```bash
# Docker 로그 확인
sudo journalctl -u docker.service -n 50

# Docker Compose 로그 확인
docker compose logs

# 컨테이너 재시작
docker compose restart
```

## 4. 서비스 URL
재부팅 후 컨테이너가 정상적으로 시작되면:
- 웹사이트: http://211.248.112.67:1112/
- phpMyAdmin: http://211.248.112.67:8080/
- pgAdmin: http://211.248.112.67:8081/