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
    print("=" * 80)
    print("H형강 엑셀 파일 분석")
    print("=" * 80)

    df_excel = pd.read_excel(excel_file, engine='openpyxl')

    print(f"\n파일: {excel_file}")
    print(f"총 행 수: {len(df_excel)}")
    print(f"컬럼: {df_excel.columns.tolist()}")

    # 엑셀 데이터 정리 (헤더 행 제외 등)
    df_clean = df_excel.copy()

    # 엑셀의 규격 목록
    excel_specs = df_clean['규격'].dropna().unique().tolist()
    print(f"\n엑셀에 있는 규격 수: {len(excel_specs)}개")

    # MySQL 연결
    print("\n" + "=" * 80)
    print("데이터베이스 H형강 제품 분석")
    print("=" * 80)

    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor()

    # 데이터베이스의 H형강 제품 조회
    cursor.execute("""
        SELECT id, product_name, specification, specification_weight, available_materials, origin
        FROM products
        WHERE category_code = 'h-beam'
        ORDER BY id
    """)

    db_products = cursor.fetchall()
    db_specs = [row[2] for row in db_products if row[2]]

    print(f"데이터베이스 H형강 제품 수: {len(db_products)}개")

    # 엑셀에는 있지만 DB에는 없는 규격 찾기
    missing_in_db = []
    for spec in excel_specs:
        if spec not in db_specs:
            missing_in_db.append(spec)

    # DB에는 있지만 엑셀에는 없는 규격 찾기
    missing_in_excel = []
    for spec in db_specs:
        if spec not in excel_specs:
            missing_in_excel.append(spec)

    print("\n" + "=" * 80)
    print("데이터 비교 결과")
    print("=" * 80)

    if missing_in_db:
        print(f"\n⚠️ 엑셀에는 있지만 DB에 없는 규격 ({len(missing_in_db)}개):")
        for spec in missing_in_db:
            excel_row = df_clean[df_clean['규격'] == spec].iloc[0]
            weight = excel_row['단위중량(kg)'] if pd.notna(excel_row['단위중량(kg)']) else 'N/A'
            material = excel_row['재질'] if pd.notna(excel_row['재질']) else 'N/A'
            print(f"  - {spec} (단위중량: {weight}kg, 재질: {material})")
    else:
        print("\n✅ 모든 엑셀 데이터가 DB에 존재합니다.")

    if missing_in_excel:
        print(f"\n⚠️ DB에는 있지만 엑셀에 없는 규격 ({len(missing_in_excel)}개):")
        for spec in missing_in_excel:
            db_row = next((row for row in db_products if row[2] == spec), None)
            if db_row:
                print(f"  - ID {db_row[0]}: {spec}")

    # 상세 데이터 표시
    print("\n" + "=" * 80)
    print("엑셀 데이터 상세 (처음 10개)")
    print("=" * 80)
    print(df_clean[['품명', '규격', '단위중량(kg)', '재질']].head(10).to_string(index=False))

    print("\n" + "=" * 80)
    print("데이터베이스 제품 상세 (처음 10개)")
    print("=" * 80)
    print(f"{'ID':<6} {'규격':<20} {'단위중량':<10} {'재질':<15}")
    print("-" * 60)
    for row in db_products[:10]:
        print(f"{row[0]:<6} {row[2]:<20} {row[3]:<10} {row[4] or 'N/A':<15}")

    # 특정 규격 확인
    check_specs = ['100*100*6*8', '125*125*6.5*9', '150*75*5*7']
    print("\n" + "=" * 80)
    print("특정 규격 확인")
    print("=" * 80)

    for spec in check_specs:
        print(f"\n규격: {spec}")

        # 엑셀에서 찾기
        excel_match = df_clean[df_clean['규격'] == spec]
        if not excel_match.empty:
            row = excel_match.iloc[0]
            print(f"  엑셀: 단위중량={row['단위중량(kg)']}kg, 재질={row['재질']}")
        else:
            print(f"  엑셀: 없음")

        # DB에서 찾기
        db_match = next((row for row in db_products if row[2] == spec), None)
        if db_match:
            print(f"  DB: ID={db_match[0]}, 단위중량={db_match[3]}kg")
        else:
            print(f"  DB: 없음")

    cursor.close()
    conn.close()

    print("\n" + "=" * 80)
    print("분석 완료")
    print("=" * 80)

except Exception as e:
    print(f"오류 발생: {e}")
    import traceback
    traceback.print_exc()