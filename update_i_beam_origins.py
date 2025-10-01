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

    # I형강 제품의 원산지 정보와 계산 설정 업데이트
    print("\nI형강 제품 원산지 정보 업데이트 중...")

    # 원산지 옵션
    origins = ["국산", "중국산", "일본산"]
    available_origins_json = json.dumps(origins, ensure_ascii=False)

    # 원산지별 가격 데이터 (예시)
    origin_price_data = {
        "국산": 0,
        "중국산": -50000,  # 국산보다 50,000원 저렴
        "일본산": 100000   # 국산보다 100,000원 비쌈
    }
    origin_price_data_json = json.dumps(origin_price_data, ensure_ascii=False)

    # 모든 I형강 제품에 대해 업데이트
    update_query = """
    UPDATE products
    SET
        available_origins = %s,
        origin_price_data = %s,
        has_calculator = 1,
        calculation_type = 'linear',
        display_mode = 'by_specification'
    WHERE category_code = 'i-beam'
    """

    cursor.execute(update_query, (available_origins_json, origin_price_data_json))
    updated_count = cursor.rowcount
    print(f"✓ {updated_count}개의 I형강 제품에 원산지 정보를 추가했습니다.")

    # 단위중량 데이터를 unit_weight_data 필드에 저장
    print("\nI형강 제품 단위중량 데이터 구성 중...")

    # 먼저 모든 I형강 제품의 규격과 단위중량 가져오기
    cursor.execute("""
        SELECT id, specifications, specification_weight
        FROM products
        WHERE category_code = 'i-beam' AND specification_weight IS NOT NULL
    """)

    i_beam_products = cursor.fetchall()

    # 단위중량 데이터 구성 (모든 규격의 단위중량을 하나의 JSON으로)
    unit_weight_dict = {}
    for product_id, spec, weight in i_beam_products:
        if spec and weight:
            unit_weight_dict[spec] = float(weight)

    unit_weight_json = json.dumps(unit_weight_dict, ensure_ascii=False)

    # 모든 I형강 제품에 동일한 unit_weight_data 설정
    cursor.execute("""
        UPDATE products
        SET unit_weight_data = %s
        WHERE category_code = 'i-beam'
    """, (unit_weight_json,))

    print(f"✓ I형강 제품에 {len(unit_weight_dict)}개 규격의 단위중량 데이터를 추가했습니다.")

    # 재질 정보 추가
    materials = ["SS400"]
    material_price_data = {"SS400": 0}

    cursor.execute("""
        UPDATE products
        SET
            available_materials = %s,
            material_price_data = %s
        WHERE category_code = 'i-beam'
    """, (json.dumps(materials, ensure_ascii=False), json.dumps(material_price_data, ensure_ascii=False)))

    print("✓ I형강 제품에 재질 정보를 추가했습니다.")

    # 커밋
    conn.commit()

    # 결과 확인
    cursor.execute("""
        SELECT
            product_name,
            specifications,
            specification_weight,
            available_origins,
            origin_price_data,
            has_calculator,
            calculation_type
        FROM products
        WHERE category_code = 'i-beam'
        ORDER BY specification_weight
        LIMIT 5
    """)

    print("\n업데이트된 I형강 제품 샘플:")
    for row in cursor.fetchall():
        print(f"  - {row[0]}")
        print(f"    규격: {row[1]}, 단위중량: {row[2]}kg/m")
        print(f"    원산지: {row[3]}")
        print(f"    계산기: {'있음' if row[5] else '없음'}")

    # 연결 종료
    cursor.close()
    conn.close()

    print("\n✓ I형강 제품 원산지 정보 설정이 완료되었습니다!")

except Exception as e:
    print(f"오류 발생: {e}")
    import traceback
    traceback.print_exc()