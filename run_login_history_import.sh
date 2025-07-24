#!/bin/bash

echo "=== 로그인 이력 가져오기 스크립트 ==="
echo ""

# 현재 디렉토리로 이동
cd /home/successbank/projects/docker/project1/html

# 1. 로그인 로그 테이블 생성
echo "1. 로그인 로그 테이블 생성 중..."
php create_login_logs_table.php

echo ""
echo "2. member.xls에서 로그인 정보 분석 중..."
python3 analyze_login_info.py

echo ""
echo "3. 로그인 이력 데이터 가져오기 중..."
php import_login_history.php

echo ""
echo "=== 작업 완료 ==="
echo ""
echo "회원 상세 페이지에서 로그인 이력을 확인할 수 있습니다:"
echo "http://211.248.112.67:1112/admin/admin_members.php?action=view&id=11230"