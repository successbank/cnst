#!/bin/bash

echo "=== 회원 패스워드 업데이트 스크립트 ==="
echo ""

# 현재 디렉토리로 이동
cd /home/successbank/projects/docker/project1/html

# 1. member.xls에서 ID/패스워드 추출
echo "1. Excel 파일에서 데이터 추출 중..."
python3 extract_member_data.py

# 2. CSV 파일 확인
if [ -f "member_id_password.csv" ]; then
    echo "✅ CSV 파일 생성 완료"
    echo "   파일: member_id_password.csv"
    line_count=$(wc -l < member_id_password.csv)
    echo "   레코드 수: $((line_count - 1)) 건 (헤더 제외)"
else
    echo "❌ CSV 파일 생성 실패"
    exit 1
fi

echo ""
echo "2. 데이터베이스에 패스워드 업데이트 중..."
echo ""

# 3. 패스워드 업데이트 실행
php import_members_from_csv.php

echo ""
echo "=== 작업 완료 ==="