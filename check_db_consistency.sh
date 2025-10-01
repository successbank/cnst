#!/bin/bash

echo "=========================================="
echo "데이터베이스 일관성 검사"
echo "=========================================="
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check PHP files
echo "1. PHP 파일 검사 중..."
echo "------------------------"
php_errors=0
for file in $(find . -name "*.php" -type f 2>/dev/null | grep -v vendor | grep -v venv | grep -v node_modules); do
    if grep -q "DB_NAME" "$file" 2>/dev/null; then
        db_name=$(grep "DB_NAME" "$file" | grep -v "project1_db" | head -1)
        if [ ! -z "$db_name" ]; then
            echo -e "${RED}❌ $file${NC}"
            echo "   $db_name"
            php_errors=$((php_errors + 1))
        fi
    fi
done

if [ $php_errors -eq 0 ]; then
    echo -e "${GREEN}✅ 모든 PHP 파일이 project1_db를 사용합니다.${NC}"
else
    echo -e "${RED}⚠️  $php_errors 개의 PHP 파일에서 잘못된 DB 이름 발견${NC}"
fi
echo ""

# Check Python files
echo "2. Python 파일 검사 중..."
echo "------------------------"
py_errors=0
for file in $(find . -name "*.py" -type f 2>/dev/null | grep -v venv | grep -v __pycache__); do
    if grep -q "database" "$file" 2>/dev/null; then
        db_name=$(grep "database" "$file" | grep -v "project1_db" | grep -v "information_schema" | grep -v "#" | head -1)
        if [ ! -z "$db_name" ] && [[ "$db_name" == *"project"* ]]; then
            echo -e "${RED}❌ $file${NC}"
            echo "   $db_name"
            py_errors=$((py_errors + 1))
        fi
    fi
done

if [ $py_errors -eq 0 ]; then
    echo -e "${GREEN}✅ 모든 Python 파일이 project1_db를 사용합니다.${NC}"
else
    echo -e "${RED}⚠️  $py_errors 개의 Python 파일에서 잘못된 DB 이름 발견${NC}"
fi
echo ""

# Check for wrong database references
echo "3. 잘못된 DB 참조 검사 중..."
echo "------------------------"
wrong_refs=$(grep -r "project5_db\|project2_db\|project3_db\|project4_db" --include="*.php" --include="*.py" --include="*.sql" --include="*.js" 2>/dev/null | grep -v "docker-compose.yml" | grep -v ".git" | grep -v "check_db_consistency.sh")

if [ -z "$wrong_refs" ]; then
    echo -e "${GREEN}✅ 잘못된 DB 참조가 없습니다.${NC}"
else
    echo -e "${RED}❌ 다음 파일들에서 잘못된 DB 참조 발견:${NC}"
    echo "$wrong_refs" | while IFS= read -r line; do
        echo -e "${YELLOW}   $line${NC}"
    done
fi
echo ""

# Summary
echo "=========================================="
echo "검사 완료"
echo "=========================================="

total_errors=$((php_errors + py_errors))
if [ $total_errors -eq 0 ] && [ -z "$wrong_refs" ]; then
    echo -e "${GREEN}✅ 모든 파일이 project1_db를 올바르게 사용하고 있습니다!${NC}"
else
    echo -e "${RED}⚠️  수정이 필요한 파일들이 있습니다.${NC}"
    echo -e "${YELLOW}   CLAUDE.md 파일의 데이터베이스 지침을 참고하세요.${NC}"
fi