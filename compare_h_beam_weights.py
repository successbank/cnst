#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import pandas as pd
import mysql.connector

excel_file = "html/114/10/H형강.xlsx"

# MySQL 연결 설정
db_config = {
    'host': '127.0.0.1',
    'user': 'root',
    'password': 'rootpassword',
    'database': 'project1_db',
    'port': 3306
}

try:
    # Excel 파일 읽기
    print("Excel 파일 읽는 중...")
    df_excel = pd.read_excel(excel_file, engine='openpyxl')

    # MySQL 연결
    print("데이터베이스 연결 중...")
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor()

    # 데이터베이스에서 H형강 데이터 가져오기
    cursor.execute("""
        SELECT id, product_name, specification, specification_weight
        FROM products
        WHERE category_code = 'h-beam'
        ORDER BY id
    """)

    db_products = cursor.fetchall()

    print(f"\n엑셀: {len(df_excel)}개 제품")
    print(f"데이터베이스: {len(db_products)}개 제품")

    print("\n단위중량 비교 (처음 10개):")
    print("="*80)
    print(f"{'DB ID':<8} {'규격':<20} {'DB 단위중량':<15} {'Excel 단위중량':<15} {'차이':<10}")
    print("-"*80)

    mismatched = []

    for db_row in db_products:
        db_id = db_row[0]
        db_name = db_row[1]
        db_spec = db_row[2] or ''
        db_weight = db_row[3]

        # 엑셀에서 해당 규격 찾기
        excel_match = df_excel[df_excel['규격'] == db_spec]

        if not excel_match.empty:
            excel_weight = excel_match.iloc[0]['단위중량(kg)']

            db_weight_float = float(db_weight) if db_weight else 0
            excel_weight_float = float(excel_weight)

            if abs(db_weight_float - excel_weight_float) > 0.01:
                diff = abs(db_weight_float - excel_weight_float)
                mismatched.append((db_id, db_spec, db_weight_float, excel_weight_float))
                print(f"{db_id:<8} {db_spec:<20} {db_weight_float:<15.1f} {excel_weight_float:<15.1f} {'불일치':<10}")
            else:
                print(f"{db_id:<8} {db_spec:<20} {db_weight_float:<15.1f} {excel_weight_float:<15.1f} {'일치':<10}")
        else:
            print(f"{db_id:<8} {db_spec:<20} {db_weight:<15} {'없음':<15} {'확인필요':10}")

    if mismatched:
        print(f"\n\n불일치 항목 {len(mismatched)}개 발견!")
        print("\n수정이 필요한 항목:")
        for item in mismatched:
            print(f"  ID {item[0]}: {item[1]} - DB:{item[2]} → Excel:{item[3]}")

    # 연결 종료
    cursor.close()
    conn.close()

except Exception as e:
    print(f"오류 발생: {e}")
    import traceback
    traceback.print_exc()