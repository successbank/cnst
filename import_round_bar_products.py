#!/usr/bin/env python3
import pandas as pd
import mysql.connector
import json

# Database connection
conn = mysql.connector.connect(
    host='127.0.0.1',
    user='root',
    password='rootpassword',
    database='project1_db',
    charset='utf8mb4'
)
cursor = conn.cursor()

# Standard 12 materials
standard_materials = [
    'SS400', 'SS400/A36', 'SHN400', 'SS490', 'SS540',
    'SM400A', 'SM400B', 'SHN490', 'SM490A', 'SM490B',
    'SM490YA', 'SM490YB'
]
materials_json = json.dumps(standard_materials, ensure_ascii=False)

# Read Excel file
excel_file = '/home/successbank/projects/docker/project1/html/114/6/환봉.xlsx'
df = pd.read_excel(excel_file, engine='openpyxl', header=1)

print("=== 환봉 제품 임포트 시작 ===\n")
print(f"엑셀 파일: {excel_file}")
print(f"총 행 수: {len(df)}\n")

added_count = 0
error_count = 0

for idx, row in df.iterrows():
    if pd.isna(row['규격']) or pd.isna(row['단위중량(kg)']):
        continue

    spec = str(row['규격']).strip()
    unit_weight = float(row['단위중량(kg)'])

    # Create product name
    product_name = f"환봉 {spec}"
    # Store specification as is
    specification = spec

    try:
        cursor.execute("""
            INSERT INTO products
            (product_name, specification, specification_weight,
             category_code, calculation_type, available_materials, has_calculator)
            VALUES (%s, %s, %s, 'round-bar', 'linear', %s, 1)
        """, (product_name, specification, unit_weight, materials_json))

        added_count += 1
        print(f"ADD {added_count:3d}: {spec:15s} | {unit_weight:9.3f} kg/m | ID: {cursor.lastrowid}")

    except mysql.connector.Error as e:
        error_count += 1
        print(f"ERROR: {spec} - {e}")

conn.commit()

print(f"\n=== 임포트 완료 ===")
print(f"추가된 제품: {added_count}개")
print(f"오류 발생: {error_count}개")

cursor.close()
conn.close()