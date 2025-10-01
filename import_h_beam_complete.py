#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import pandas as pd
import mysql.connector
import json

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
    print("H형강 엑셀 파일 읽는 중...")
    df = pd.read_excel(excel_file, engine='openpyxl')

    print(f"총 {len(df)}개의 제품 발견")

    # MySQL 연결
    print("\nMySQL 연결 중...")
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor()

    # 기존 H형강 제품 모두 삭제
    print("기존 H형강 제품 삭제 중...")
    cursor.execute("DELETE FROM products WHERE category_code = 'h-beam'")
    deleted_count = cursor.rowcount
    print(f"{deleted_count}개의 기존 H형강 제품 삭제됨")

    # 엑셀 데이터 임포트
    print("\n새로운 H형강 제품 임포트 중...")
    insert_count = 0
    failed_count = 0

    for idx, row in df.iterrows():
        try:
            # 데이터 추출
            product_name = f"H형강 {row['규격']}" if pd.notna(row['규격']) else 'H형강'
            specification = str(row['규격']) if pd.notna(row['규격']) else ''

            # 단위중량 파싱
            unit_weight = None
            if pd.notna(row['단위중량(kg)']):
                try:
                    unit_weight = float(str(row['단위중량(kg)']).replace(',', ''))
                except:
                    unit_weight = 0

            # 재질 파싱
            material_raw = str(row['재질']) if pd.notna(row['재질']) else 'SS400'

            # 재질 목록 생성 (복수 재질 처리)
            if '/' in material_raw:
                materials = [m.strip() for m in material_raw.split('/')]
            else:
                materials = [material_raw.strip()] if material_raw != 'nan' else ['SS400']

            # 원산지 목록 (기본값)
            origins = ["국산", "중국산", "일본산"]

            # 재질별 가격 데이터
            material_price_data = {
                "SS400": 0,      # 기준
                "SM490": 20000,
                "SM520": 30000,
                "SM570": 50000,
                "SUS316": 80000,
                "A36": 0,        # SS400과 동일
                "SHN400": 10000,
                "SHN490": 25000,
                "SS490": 15000,
                "SS540": 40000,
                "SM400A": 5000,
                "SM400B": 5000,
                "SM490A": 20000,
                "SM490B": 20000,
                "SM490YA": 25000,
                "SM490YB": 25000,
                "SWH400": 10000,
                "A572": 30000
            }

            # 원산지별 가격 데이터
            origin_price_data = {
                "국산": 0,
                "중국산": -50000,
                "일본산": 100000
            }

            # INSERT 쿼리
            insert_query = """
            INSERT INTO products (
                category_code,
                product_name,
                product_code,
                specification,
                specification_weight,
                available_materials,
                available_origins,
                material_price_data,
                origin_price_data,
                material,
                unit,
                price,
                calculation_type,
                standard_length,
                stock_status,
                is_active,
                has_calculator,
                created_at,
                updated_at
            ) VALUES (
                %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW()
            )
            """

            values = (
                'h-beam',                                      # category_code
                product_name,                                  # product_name
                f"HB-{specification.replace('*', 'x')}",      # product_code
                specification,                                 # specification
                unit_weight,                                   # specification_weight
                json.dumps(materials, ensure_ascii=False),    # available_materials
                json.dumps(origins, ensure_ascii=False),      # available_origins
                json.dumps(material_price_data, ensure_ascii=False),  # material_price_data
                json.dumps(origin_price_data, ensure_ascii=False),    # origin_price_data
                materials[0],                                  # material (기본 재질)
                'kg/m',                                        # unit
                1000,                                          # price (기준가)
                'piece',                                       # calculation_type
                6.0,                                           # standard_length
                'in_stock',                                    # stock_status
                1,                                             # is_active
                1,                                             # has_calculator
            )

            cursor.execute(insert_query, values)
            insert_count += 1

            if insert_count % 10 == 0:
                print(f"  {insert_count}개 임포트 완료...")

        except Exception as e:
            failed_count += 1
            print(f"  ⚠️ 행 {idx+1} 임포트 실패: {e}")

    # 커밋
    conn.commit()

    print(f"\n✅ 임포트 완료!")
    print(f"  - 성공: {insert_count}개")
    print(f"  - 실패: {failed_count}개")

    # 임포트 결과 확인
    print("\n임포트된 데이터 확인:")
    cursor.execute("""
        SELECT id, product_name, specification, specification_weight, available_materials
        FROM products
        WHERE category_code = 'h-beam'
        ORDER BY id
        LIMIT 10
    """)

    print(f"\n{'ID':<6} {'제품명':<30} {'단위중량':<10} {'재질'}")
    print("-" * 80)
    for row in cursor.fetchall():
        materials = json.loads(row[4]) if row[4] else []
        material_str = ', '.join(materials[:2]) + ('...' if len(materials) > 2 else '')
        print(f"{row[0]:<6} {row[1]:<30} {row[3]:<10.1f} {material_str}")

    # 특정 제품 확인
    print("\n특정 제품 상세 확인:")
    check_specs = ['100*100*6*8', '125*125*6.5*9', '918*303*19*37']
    for spec in check_specs:
        cursor.execute("""
            SELECT id, product_name, specification_weight, available_materials, available_origins
            FROM products
            WHERE category_code = 'h-beam' AND specification = %s
        """, (spec,))

        result = cursor.fetchone()
        if result:
            print(f"\n규격: {spec}")
            print(f"  ID: {result[0]}")
            print(f"  단위중량: {result[2]} kg/m")
            print(f"  재질: {result[3]}")
            print(f"  원산지: {result[4]}")

    cursor.close()
    conn.close()

except Exception as e:
    print(f"오류 발생: {e}")
    import traceback
    traceback.print_exc()