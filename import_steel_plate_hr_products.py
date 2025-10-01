#!/usr/bin/env python3
import openpyxl
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
excel_file = '/home/successbank/projects/docker/project1/html/114/7/철판.xlsx'
wb = openpyxl.load_workbook(excel_file)
ws = wb.active

print("=== HR철판 제품 임포트 시작 ===\n")
print(f"엑셀 파일: {excel_file}")

added_count = 0
error_count = 0

# Process data starting from row 3 (header is row 2)
for row_idx, row in enumerate(ws.iter_rows(min_row=3, values_only=True), 3):
    # Skip empty rows
    if not any(cell for cell in row):
        continue

    # Extract data from columns (B=1, C=2, D=3, E=4, F=5 - 0-indexed)
    product_type = row[1]  # 품명 (B column)
    spec = row[2]          # 규격 (C column)
    unit_weight = row[3]   # 단위중량(장) (D column)
    # material = row[5]    # 재질 (F column) - 표시용이므로 사용안함

    # Skip if essential data is missing
    if not product_type or not spec or not unit_weight:
        continue

    # Clean and format data
    spec = str(spec).strip()
    unit_weight = float(unit_weight)

    # Create product name with × symbol
    product_name = f"HR철판 {spec.replace('*', '×')}"
    # Store specification with × symbol
    specification = spec.replace('*', '×')

    try:
        cursor.execute("""
            INSERT INTO products
            (product_name, specification, specification_weight,
             category_code, calculation_type, available_materials, has_calculator)
            VALUES (%s, %s, %s, 'steel-plate-hr', 'linear', %s, 1)
        """, (product_name, specification, unit_weight, materials_json))

        added_count += 1
        print(f"ADD {added_count:3d}: {spec:20s} | {unit_weight:7.1f} kg/장 | ID: {cursor.lastrowid}")

    except mysql.connector.Error as e:
        error_count += 1
        print(f"ERROR: {spec} - {e}")

conn.commit()

print(f"\n=== 임포트 완료 ===")
print(f"추가된 제품: {added_count}개")
print(f"오류 발생: {error_count}개")
print(f"총 제품 수: {added_count}개")

cursor.close()
conn.close()