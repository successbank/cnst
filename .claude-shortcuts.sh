#!/bin/bash
# Claude Code 작업용 단축 명령어

# 경량H형강 제품만 확인
alias check_light="mysql -u root -pWNtl@akdnj12 bridge -e \"SELECT id, product_name, available_materials FROM products WHERE category_code = 'light-h-beam' LIMIT 10;\""

# 특정 제품 재질 확인
check_product_material() {
    php -r "require_once 'db.php'; \$pdo = getDB(); \$stmt = \$pdo->prepare('SELECT id, product_name, category_code, available_materials, material_price_data FROM products WHERE id = ?'); \$stmt->execute([$1]); \$row = \$stmt->fetch(); echo 'Product $1:' . PHP_EOL; print_r(\$row);"
}

# 파일 백업 생성
backup_file() {
    if [ -f "$1" ]; then
        cp "$1" "$1.bak.$(date +%Y%m%d_%H%M%S)"
        echo "백업 생성됨: $1.bak.$(date +%Y%m%d_%H%M%S)"
    else
        echo "파일이 존재하지 않습니다: $1"
    fi
}

# Docker 로그 확인
alias check_logs="docker logs project1_php --tail 50"

# 경량H형강 관련 파일 찾기
alias find_light_files="grep -r 'light-h-beam' /home/successbank/projects/docker/project1/html --include='*.php' -l"

# 작업 전 체크리스트 표시
work_checklist() {
    echo "===== 작업 전 체크리스트 ====="
    echo "✓ 경량H형강 제품군만 작업 대상인가?"
    echo "✓ 백업을 생성했는가?"
    echo "✓ 루트와 html 디렉토리 둘 다 확인했는가?"
    echo "✓ WHERE 조건을 확인했는가?"
    echo "✓ 테스트 계획이 있는가?"
    echo "=============================="
}

echo "Claude 작업 단축 명령어가 로드되었습니다."
echo "사용 가능한 명령어:"
echo "  - check_light: 경량H형강 제품 목록 보기"
echo "  - check_product_material [ID]: 특정 제품 재질 확인"
echo "  - backup_file [파일명]: 파일 백업 생성"
echo "  - check_logs: Docker 로그 확인"
echo "  - find_light_files: 경량H형강 관련 파일 찾기"
echo "  - work_checklist: 작업 전 체크리스트"