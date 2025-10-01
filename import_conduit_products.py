#!/usr/bin/env python3
"""
전선관 제품 임포트 스크립트
파일: /home/successbank/projects/docker/project1/html/114/8/전선관.xlsx
제품 수: 9개
"""

import pandas as pd
import mysql.connector
import json
import sys

# 데이터베이스 연결
conn = mysql.connector.connect(
    host='127.0.0.1',
    user='root',
    password='rootpassword',
    database='project1_db',
    charset='utf8mb4'
)
cursor = conn.cursor()

# 표준 12개 재질
standard_materials = [
    'SS400', 'SS400/A36', 'SHN400', 'SS490', 'SS540',
    'SM400A', 'SM400B', 'SHN490', 'SM490A', 'SM490B',
    'SM490YA', 'SM490YB'
]
materials_json = json.dumps(standard_materials, ensure_ascii=False)

# Excel 파일 읽기
excel_file = '/home/successbank/projects/docker/project1/html/114/8/전선관.xlsx'
df = pd.read_excel(excel_file, engine='openpyxl', header=1)

print("=== 전선관 제품 임포트 시작 ===\n")
print(f"엑셀 파일: {excel_file}")
print(f"총 행 수: {len(df)}\n")

added_count = 0
error_count = 0

for idx, row in df.iterrows():
    try:
        # 규격과 단위중량이 있는지 확인
        if pd.isna(row['규격']) or pd.isna(row['단위중량(kg)']):
            continue

        spec = str(row['규격']).strip()
        unit_weight = float(row['단위중량(kg)'])

        # 제품명 생성
        product_name = f"전선관 {spec}"
        specification = spec

        # DB에 삽입
        cursor.execute("""
            INSERT INTO products
            (product_name, specification, specification_weight,
             category_code, calculation_type, available_materials, has_calculator)
            VALUES (%s, %s, %s, 'conduit', 'linear', %s, 1)
        """, (product_name, specification, unit_weight, materials_json))

        added_count += 1
        product_id = cursor.lastrowid
        print(f"ADD {added_count:3d}: {spec:20s} | {unit_weight:8.3f} kg/m | ID: {product_id}")

    except Exception as e:
        error_count += 1
        print(f"ERROR {idx + 1}: {e}")

# 커밋
conn.commit()
cursor.close()
conn.close()

print(f"\n=== 임포트 완료 ===")
print(f"추가된 제품: {added_count}개")
print(f"오류 발생: {error_count}개")

sys.exit(0 if error_count == 0 else 1)