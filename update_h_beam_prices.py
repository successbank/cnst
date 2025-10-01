#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import mysql.connector
import json

# MySQL 연결 설정
db_config = {
    'host': '127.0.0.1',
    'user': 'root',
    'password': 'rootpassword',
    'database': 'project1_db',
    'port': 3306
}

try:
    # MySQL 연결
    print("MySQL 연결 중...")
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor()

    # H형강 제품의 원산지별 가격 데이터 설정
    print("\nH형강 제품 원산지별 가격 데이터 업데이트 중...")

    # 원산지별 가격 데이터 (예시)
    origin_price_data = {
        "국산": 0,         # 기준
        "중국산": -50000,  # 국산보다 50,000원 저렴
        "바레인산": -30000, # 국산보다 30,000원 저렴
        "수입산": -40000,  # 국산보다 40,000원 저렴
        "일본산": 100000   # 국산보다 100,000원 비쌈
    }
    origin_price_data_json = json.dumps(origin_price_data, ensure_ascii=False)

    # 재질별 가격 데이터
    material_price_data = {
        "SS400": 0,      # 기준
        "SM490": 20000,  # SS400보다 20,000원 비쌈
        "SM520": 30000,  # SS400보다 30,000원 비쌈
        "SM570": 50000,  # SS400보다 50,000원 비쌈
        "SUS316": 80000  # SS400보다 80,000원 비쌈
    }
    material_price_data_json = json.dumps(material_price_data, ensure_ascii=False)

    # 모든 H형강 제품에 대해 업데이트
    update_query = """
    UPDATE products
    SET
        origin_price_data = %s,
        material_price_data = %s,
        price = CASE
            WHEN price IS NULL OR price = 0 THEN 1000
            ELSE price
        END
    WHERE category_code = 'h-beam'
    """

    cursor.execute(update_query, (origin_price_data_json, material_price_data_json))
    updated_count = cursor.rowcount
    print(f"✓ {updated_count}개의 H형강 제품에 원산지별 가격 데이터를 추가했습니다.")

    # 커밋
    conn.commit()

    # 결과 확인 - ID 284 제품
    cursor.execute("""
        SELECT
            id,
            product_name,
            specifications,
            available_origins,
            available_materials,
            origin_price_data,
            material_price_data,
            price
        FROM products
        WHERE id = 284
    """)

    print("\nID 284 제품 정보:")
    row = cursor.fetchone()
    if row:
        print(f"  제품명: {row[1]}")
        print(f"  규격: {row[2]}")
        print(f"  원산지: {row[3]}")
        print(f"  재질: {row[4]}")
        print(f"  원산지별 가격: {row[5]}")
        print(f"  재질별 가격: {row[6]}")
        print(f"  기준가: {row[7]}원/kg")

    # 연결 종료
    cursor.close()
    conn.close()

    print("\n✓ H형강 제품 원산지별 가격 설정이 완료되었습니다!")

except Exception as e:
    print(f"오류 발생: {e}")
    import traceback
    traceback.print_exc()