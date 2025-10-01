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
    print("컬럼 목록:", df.columns.tolist())
    print(f"총 {len(df)}개의 행 발견")

    # 처음 몇 행 출력
    print("\n처음 5개 행:")
    print(df.head())

    # MySQL 연결
    print("\nMySQL 연결 중...")
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor()

    # I형강 제품 삽입
    insert_count = 0

    for idx, row in df.iterrows():
        try:
            # 규격 정보 파싱
            spec = str(row.iloc[0]) if pd.notna(row.iloc[0]) else ''

            # 제품명 생성
            product_name = f"I형강 {spec}"

            # 제품 코드 생성 (고유하게)
            product_code = f"I-BEAM-{spec.replace('×', 'x').replace(' ', '')}"

            # 규격 상세 정보
            specifications = spec

            # 단위중량 (kg/m)
            unit_weight = None
            if len(row) > 1 and pd.notna(row.iloc[1]):
                try:
                    unit_weight = float(str(row.iloc[1]).replace(',', ''))
                except:
                    unit_weight = None

            # INSERT 쿼리
            insert_query = """
            INSERT INTO products (
                category_code,
                product_name,
                product_code,
                specifications,
                specification,
                specification_weight,
                unit,
                stock_status,
                is_active,
                display_mode,
                has_calculator,
                created_at,
                updated_at
            ) VALUES (
                %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW()
            )
            """

            values = (
                'i-beam',           # category_code
                product_name,       # product_name
                product_code,       # product_code
                specifications,     # specifications
                spec,              # specification
                unit_weight,       # specification_weight
                'M',               # unit
                'in_stock',        # stock_status
                1,                 # is_active
                'by_specification', # display_mode
                1,                 # has_calculator
            )

            cursor.execute(insert_query, values)
            insert_count += 1
            print(f"✓ 삽입됨: {product_name} (규격: {spec}, 단위중량: {unit_weight}kg/m)")

        except mysql.connector.IntegrityError as e:
            if "Duplicate entry" in str(e):
                print(f"! 중복 건너뜀: {product_name}")
            else:
                print(f"! 오류 발생 (행 {idx+1}): {e}")
        except Exception as e:
            print(f"! 오류 발생 (행 {idx+1}): {e}")

    # 커밋
    conn.commit()
    print(f"\n✓ 총 {insert_count}개의 I형강 제품을 데이터베이스에 추가했습니다.")

    # 삽입된 데이터 확인
    cursor.execute("SELECT COUNT(*) FROM products WHERE category_code = 'i-beam'")
    total_count = cursor.fetchone()[0]
    print(f"✓ 현재 데이터베이스에 {total_count}개의 I형강 제품이 있습니다.")

    # 연결 종료
    cursor.close()
    conn.close()

except FileNotFoundError:
    print(f"오류: Excel 파일을 찾을 수 없습니다: {excel_file}")
    sys.exit(1)
except Exception as e:
    print(f"오류 발생: {e}")
    sys.exit(1)