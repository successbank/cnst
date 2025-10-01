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

# Get existing products
cursor.execute("SELECT specification FROM products WHERE category_code = 'angle'")
existing_specs = set()
for row in cursor.fetchall():
    # Normalize: convert × to *
    spec = row[0].replace('×', '*')
    existing_specs.add(spec)

print(f"기존 제품: {len(existing_specs)}개\n")

# Read Excel file
excel_file = '/home/successbank/projects/docker/project1/html/114/5/ㄱ형강.xlsx'
df = pd.read_excel(excel_file, engine='openpyxl', header=1)

added_count = 0
skipped_count = 0

print("=== ㄱ형강 제품 임포트 시작 ===\n")

for idx, row in df.iterrows():
    if pd.isna(row['규격']) or pd.isna(row['단위중량(kg)']):
        continue

    spec = str(row['규격']).strip()
    unit_weight = float(row['단위중량(kg)'])

    # Check if already exists
    if spec in existing_specs:
        skipped_count += 1
        print(f"SKIP: {spec:20s} - 이미 존재")
        continue

    # Create product name
    product_name = f"ㄱ형강 {spec}"

    try:
        cursor.execute("""
            INSERT INTO products
            (product_name, specification, specification_weight,
             category_code, calculation_type, available_materials, has_calculator)
            VALUES (%s, %s, %s, 'angle', 'linear', %s, 1)
        """, (product_name, spec, unit_weight, materials_json))

        added_count += 1
        print(f"ADD:  {spec:20s} | {unit_weight:7.2f} kg/m | ID: {cursor.lastrowid}")

    except mysql.connector.Error as e:
        print(f"ERROR: {spec} - {e}")

conn.commit()

print(f"\n=== 임포트 완료 ===")
print(f"추가된 제품: {added_count}개")
print(f"건너뛴 제품: {skipped_count}개")
print(f"총 제품 수: {len(existing_specs) + added_count}개")

cursor.close()
conn.close()