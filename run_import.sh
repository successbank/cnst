#!/bin/bash
echo "=== 회원 데이터 가져오기 시작 ==="
echo "총 11,235명의 회원 데이터를 처리합니다."
echo "이 작업은 시간이 걸릴 수 있습니다..."
echo ""

# PHP 스크립트 실행하고 로그 파일에 저장
php /home/successbank/projects/docker/project1/html/import_members.php > import_log.txt 2>&1 &

# 프로세스 ID 저장
PID=$!
echo "프로세스 ID: $PID"
echo ""

# 진행 상황 모니터링
while kill -0 $PID 2>/dev/null; do
    if [ -f import_log.txt ]; then
        PROCESSED=$(grep -E "추가:|업데이트:" import_log.txt | wc -l)
        echo -ne "\r진행 중: $PROCESSED 건 처리됨..."
    fi
    sleep 2
done

echo ""
echo "=== 작업 완료 ==="

# 결과 출력
if [ -f import_log.txt ]; then
    tail -20 import_log.txt
fi