#!/bin/bash

echo "=== Samba 설치 및 Docker 프로젝트 공유 설정 스크립트 ==="
echo ""

# 1. Samba 설치
echo "1. Samba 설치 중..."
sudo apt update
sudo apt install -y samba samba-common

# 2. 설정 파일 백업
echo ""
echo "2. Samba 설정 파일 백업 중..."
sudo cp /etc/samba/smb.conf /etc/samba/smb.conf.backup

# 3. Samba 설정 추가
echo ""
echo "3. Samba 공유 설정 추가 중..."
sudo tee -a /etc/samba/smb.conf > /dev/null << 'EOF'

# Docker Projects Share
[Docker Projects]
   comment = Docker Projects Share
   path = /home/successbank/projects/docker
   browseable = yes
   read only = no
   writable = yes
   valid users = successbank
   create mask = 0755
   directory mask = 0755
   force user = successbank
   force group = successbank

[Project1]
   comment = Docker Project 1
   path = /home/successbank/projects/docker/project1
   browseable = yes
   read only = no
   writable = yes
   valid users = successbank
   create mask = 0755
   directory mask = 0755

[Project2]
   comment = Docker Project 2
   path = /home/successbank/projects/docker/project2
   browseable = yes
   read only = no
   writable = yes
   valid users = successbank
   create mask = 0755
   directory mask = 0755

[Project3]
   comment = Docker Project 3
   path = /home/successbank/projects/docker/project3
   browseable = yes
   read only = no
   writable = yes
   valid users = successbank
   create mask = 0755
   directory mask = 0755
EOF

# 4. Samba 사용자 설정
echo ""
echo "4. Samba 사용자 설정..."
echo "Samba 비밀번호를 입력하세요 (Windows 접속 시 사용):"
sudo smbpasswd -a successbank

# 5. 서비스 재시작
echo ""
echo "5. Samba 서비스 재시작 중..."
sudo systemctl restart smbd
sudo systemctl enable smbd

# 6. 방화벽 설정
echo ""
echo "6. 방화벽 설정 중..."
sudo ufw allow samba

# 7. 상태 확인
echo ""
echo "7. Samba 상태 확인..."
sudo systemctl status smbd --no-pager

# 8. 접속 정보 출력
echo ""
echo "=== 설정 완료 ==="
echo ""
echo "Windows에서 접속 방법:"
echo "1. 파일 탐색기에서: \\\\192.168.1.251"
echo "2. 또는 실행(Win+R)에서: \\\\192.168.1.251\\Docker Projects"
echo ""
echo "사용자명: successbank"
echo "비밀번호: 위에서 설정한 Samba 비밀번호"
echo ""
echo "공유 폴더:"
echo "- Docker Projects (전체)"
echo "- Project1"
echo "- Project2" 
echo "- Project3"