#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import pandas as pd
import mysql.connector
import sys
import os
import json
from datetime import datetime

# Excel 파일 경로
excel_file = "114/product/I형강(빔).xlsx"

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
    print(f"Excel 파일 읽는 중: {excel_file}")
    df = pd.read_excel(excel_file, engine='openpyxl')

    # 컬럼명 출력
    print("원본 컬럼 목록:", df.columns.tolist())
    print(f"총 {len(df)}개의 행 발견")

    # 처음 몇 행 출력
    print("\n원본 데이터 (처음 10개 행):")
    print(df.head(10))

    # 첫 번째 행이 헤더인 경우 처리
    # 실제 데이터는 2번째 행부터 시작
    data_df = df.iloc[1:].copy()  # 첫 번째 행(헤더) 제외
    data_df.reset_index(drop=True, inplace=True)

    print(f"\n실제 데이터 {len(data_df)}개의 제품")

    # MySQL 연결
    print("\nMySQL 연결 중...")
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor()

    # 기존 I형강 제품 모두 삭제
    cursor.execute("DELETE FROM products WHERE category_code = 'i-beam'")
    print(f"기존 I형강 제품 {cursor.rowcount}개 삭제됨")

    # I형강 제품 삽입
    insert_count = 0

    for idx, row in data_df.iterrows():
        try:
            # 컬럼 인덱스로 접근
            # Unnamed: 1 = 품명
            # Unnamed: 2 = 규격
            # Unnamed: 3 = 단위중량
            # Unnamed: 5 = 재질

            product_type = str(row.iloc[1]) if pd.notna(row.iloc[1]) else ''
            spec = str(row.iloc[2]) if pd.notna(row.iloc[2]) else ''

            # 빈 규격은 건너뛰기
            if not spec or spec == 'nan':
                continue

            # 제품명 생성
            product_name = f"I형강 {spec}"

            # 제품 코드 생성 (고유하게)
            product_code = f"I-BEAM-{spec.replace('×', 'x').replace('*', 'x').replace(' ', '')}"

            # 단위중량 (kg/m)
            unit_weight = None
            if pd.notna(row.iloc[3]):
                try:
                    unit_weight = float(str(row.iloc[3]).replace(',', '').replace('kg', ''))
                except:
                    unit_weight = None

            # 재질
            material = str(row.iloc[5]) if pd.notna(row.iloc[5]) and str(row.iloc[5]) != 'nan' else 'SS400'

            # 규격 파싱 (예: 100*75*5*8)
            description = f"규격: {spec}"
            if unit_weight:
                description += f", 단위중량: {unit_weight}kg/m"
            if material:
                description += f", 재질: {material}"

            # INSERT 쿼리
            insert_query = """
            INSERT INTO products (
                category_code,
                product_name,
                product_code,
                specifications,
                specification,
                specification_weight,
                description,
                material,
                unit,
                stock_status,
                is_active,
                display_mode,
                has_calculator,
                created_at,
                updated_at
            ) VALUES (
                %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW()
            )
            """

            values = (
                'i-beam',           # category_code
                product_name,       # product_name
                product_code,       # product_code
                spec,              # specifications
                spec,              # specification
                unit_weight,       # specification_weight
                description,       # description
                material,          # material
                'M',               # unit
                'in_stock',        # stock_status
                1,                 # is_active
                'by_specification', # display_mode
                1,                 # has_calculator
            )

            cursor.execute(insert_query, values)
            insert_count += 1
            print(f"✓ 삽입됨: {product_name} (단위중량: {unit_weight}kg/m, 재질: {material})")

        except mysql.connector.IntegrityError as e:
            if "Duplicate entry" in str(e):
                print(f"! 중복 건너뜀: {product_name}")
            else:
                print(f"! 오류 발생 (행 {idx+2}): {e}")
        except Exception as e:
            print(f"! 오류 발생 (행 {idx+2}): {e}")

    # 커밋
    conn.commit()
    print(f"\n✓ 총 {insert_count}개의 I형강 제품을 데이터베이스에 추가했습니다.")

    # 삽입된 데이터 확인
    cursor.execute("SELECT COUNT(*) FROM products WHERE category_code = 'i-beam'")
    total_count = cursor.fetchone()[0]
    print(f"✓ 현재 데이터베이스에 {total_count}개의 I형강 제품이 있습니다.")

    # 몇 개 샘플 출력
    cursor.execute("""
        SELECT product_name, specifications, specification_weight, material
        FROM products
        WHERE category_code = 'i-beam'
        ORDER BY id
        LIMIT 5
    """)

    print("\n데이터베이스에 저장된 샘플 데이터:")
    for row in cursor.fetchall():
        print(f"  - {row[0]}: 규격={row[1]}, 단위중량={row[2]}kg/m, 재질={row[3]}")

    # 연결 종료
    cursor.close()
    conn.close()

except FileNotFoundError:
    print(f"오류: Excel 파일을 찾을 수 없습니다: {excel_file}")
    sys.exit(1)
except Exception as e:
    print(f"오류 발생: {e}")
    import traceback
    traceback.print_exc()
    sys.exit(1)