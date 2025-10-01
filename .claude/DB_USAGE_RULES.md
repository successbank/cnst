# 🚨 데이터베이스 사용 규칙 (필독)

## 이 프로젝트는 project1_db만 사용합니다!

### ✅ 올바른 사용법

#### PHP 파일
```php
define('DB_NAME', 'project1_db');  // ✅ 올바름
// define('DB_NAME', 'project5_db');  // ❌ 잘못됨!
```

#### Python 파일
```python
connection = mysql.connector.connect(
    database='project1_db',  # ✅ 올바름
    # database='project5_db',  # ❌ 잘못됨!
    host='127.0.0.1',
    port=3306,
    user='root',
    password='rootpassword'
)
```

#### SQL 직접 실행
```sql
USE project1_db;  -- ✅ 올바름
-- USE project5_db;  -- ❌ 잘못됨!
```

### ⚠️ 자주 발생하는 실수

1. **Docker 설정 복사**: docker-compose.yml에 project5_db가 있지만 무시하세요
2. **백업 파일 복원**: 백업 SQL에 다른 DB 이름이 있으면 project1_db로 변경
3. **스크립트 복사**: 다른 프로젝트에서 복사한 스크립트는 DB 이름 확인 필수

### 🔍 빠른 점검 명령어

```bash
# PHP 파일에서 DB 이름 확인
grep -r "DB_NAME" *.php | grep -v project1_db

# Python 파일에서 DB 이름 확인
grep -r "database=" *.py | grep -v project1_db

# 잘못된 DB 참조 찾기
grep -r "project5_db\|project2_db\|project3_db\|project4_db" --include="*.php" --include="*.py"
```

### 📝 체크리스트

작업 시작 전:
- [ ] db.php의 DB_NAME이 'project1_db'인지 확인
- [ ] Python 스크립트의 database가 'project1_db'인지 확인

작업 완료 후:
- [ ] 새로 작성한 코드가 project1_db를 사용하는지 확인
- [ ] 테스트 시 올바른 DB에 연결되었는지 확인

### 🆘 문제 발생 시

"Table doesn't exist" 오류가 나면:
1. 먼저 연결된 DB 이름 확인
2. project1_db가 맞는지 체크
3. 틀렸다면 즉시 수정

---
⚠️ **기억하세요**: 이 프로젝트는 **project1_db**만 사용합니다!