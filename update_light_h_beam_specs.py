#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import mysql.connector
import json
import re

# MySQL 연결 설정
db_config = {
    'host': '127.0.0.1',
    'user': 'user',
    'password': 'userpassword',
    'database': 'project1_db',
    'port': 3306
}

# 부모 제품(ID 285)의 unit_weight_data
unit_weight_data = {
    "LHB 53*104*3.2*3.2": 6.4,
    "LHB 58*104*3.2*3.2": 6.5,
    "LHB 75*75*3.2*3.2": 5.5,
    "LHB 75*75*3.2*4.5": 7,
    "LHB 100*75*3.2*4.5": 7.6,
    "LHB 120*80*3.2*4.5": 8.2,
    "LHB 100*100*3.2*3.2": 7.1,
    "LHB 100*100*3.2*4.5": 8.6,
    "LHB 100*125*3.2*4.5": 9.2,
    "LHB 100*100*4.5*4.5": 10.1,
    "LHB 100*150*3.2*4.5": 9.8,
    "LHB 125*125*3.2*4.5": 10.3,
    "LHB 125*125*4.5*4.5": 12.5,
    "LHB 125*125*4.5*6.0": 15,
    "LHB 125*150*4.5*4.5": 13.2,
    "LHB 125*200*4.5*6.0": 18.2,
    "LHB 150*100*4.5*4.5": 12.5,
    "LHB 150*125*4.5*6.0": 16.3,
    "LHB 150*150*4.5*4.5": 13.8,
    "LHB 150*150*4.5*6.0": 17.5,
    "LHB 175*125*4.5*6.0": 17.5,
    "LHB 175*150*4.5*6.0": 18.8,
    "LHB 175*175*4.5*6.0": 20,
    "LHB 200*100*4.5*6.0": 17.5,
    "LHB 200*125*4.5*6.0": 18.8,
    "LHB 200*150*4.5*6.0": 20,
    "LHB 200*175*6.0*6.0": 23,
    "LHB 200*200*6.0*6.0": 24.3,
    "LHB 200*200*6.0*8.0": 29.2,
    "LHB 250*125*4.5*6.0": 21.3,
    "LHB 250*150*6.0*8.0": 28,
    "LHB 250*175*6.0*8.0": 29.8,
    "LHB 250*200*6.0*8.0": 31.7,
    "LHB 250*250*6.0*8.0": 35.4,
    "LHB 250*250*8.0*10.0": 47.9,
    "LHB 300*100*6.0*8.0": 28,
    "LHB 300*125*6.0*8.0": 29.8,
    "LHB 300*150*6.0*8.0": 31.7,
    "LHB 300*175*6.0*8.0": 33.5,
    "LHB 300*200*8.0*10.0": 47.1,
    "LHB 300*250*8.0*10.0": 50.8,
    "LHB 300*300*8.0*10.0": 54.6,
    "LHB 300*300*8.0*12.0": 61.6,
    "LHB 300*300*10.0*12.0": 67.4,
    "LHB 350*150*8.0*10.0": 44,
    "LHB 350*175*8.0*10.0": 45.8,
    "LHB 350*200*8.0*10.0": 47.7,
    "LHB 350*250*8.0*12.0": 60.5,
    "LHB 350*350*10.0*12.0": 75.1,
    "LHB 350*350*10.0*14.0": 82.9,
    "LHB 400*150*8.0*10.0": 47.7,
    "LHB 400*175*8.0*10.0": 49.6,
    "LHB 400*200*8.0*12.0": 60.5,
    "LHB 400*250*10.0*14.0": 82.4,
    "LHB 400*300*10.0*14.0": 89.4,
    "LHB 400*400*12.0*16.0": 124.9,
    "LHB 450*175*10.0*12.0": 67.8,
    "LHB 450*200*10.0*12.0": 70.3,
    # 추가 규격 (기본값 설정)
    "LHB 80*80*3.2*3.2": 5.8,  # 75*75와 100*100 사이값 추정
    "LHB 75*100*3.2*3.2": 6.3,  # 추정값
    "LHB 90*90*3.2*3.2": 6.4,   # 추정값
    "LHB 225*200*6.0*6.0": 26.5, # 추정값
    "LHB 250*200*6.0*6.0": 28.0, # 추정값
    "LHB 250*250*6.0*6.0": 31.0, # 추정값
    "LHB 300*150*4.5*6.0": 22.0, # 추정값
    "LHB 300*200*6.0*8.0": 35.4, # 추정값
}

try:
    # MySQL 연결
    print("MySQL 연결 중...")
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor()

    # 경량H형강 제품 조회
    cursor.execute("""
        SELECT id, product_name
        FROM products
        WHERE category_code = 'light-h-beam' AND id != 285
        ORDER BY id
    """)
    products = cursor.fetchall()

    print(f"\n총 {len(products)}개의 경량H형강 제품 발견")

    updated = 0
    not_found = []

    for product_id, product_name in products:
        # 제품명에서 규격 추출
        spec_match = re.search(r'(LHB\s*[\d.*/]+)', product_name)
        if spec_match:
            spec = spec_match.group(1).replace(' ', ' ')

            # unit_weight_data에서 단위중량 찾기
            unit_weight = None
            for key, weight in unit_weight_data.items():
                # 공백 정규화하여 비교
                normalized_key = re.sub(r'\s+', ' ', key)
                normalized_spec = re.sub(r'\s+', ' ', spec)
                if normalized_key == normalized_spec:
                    unit_weight = weight
                    break

            if unit_weight:
                # specification과 specification_weight 업데이트
                spec_only = spec.replace('LHB ', '')
                update_sql = """
                    UPDATE products
                    SET specification = %s,
                        specification_weight = %s,
                        calculation_type = 'piece',
                        standard_length = 6.0
                    WHERE id = %s
                """
                cursor.execute(update_sql, (spec_only, unit_weight, product_id))
                updated += 1
                print(f"✓ ID {product_id}: {product_name} → {spec_only}, {unit_weight} kg/m")
            else:
                not_found.append((product_id, product_name, spec))
                print(f"⚠️ ID {product_id}: {product_name} - 단위중량 데이터 없음")

    # 커밋
    conn.commit()

    print(f"\n=== 업데이트 완료 ===")
    print(f"성공: {updated}개")
    print(f"실패: {len(not_found)}개")

    if not_found:
        print("\n단위중량 데이터가 없는 제품:")
        for pid, pname, spec in not_found:
            print(f"  ID {pid}: {pname} ({spec})")

    # 업데이트 결과 확인
    print("\n=== 업데이트 결과 확인 ===")
    cursor.execute("""
        SELECT id, product_name, specification, specification_weight, calculation_type
        FROM products
        WHERE category_code = 'light-h-beam' AND id != 285
        ORDER BY id
        LIMIT 10
    """)

    for row in cursor.fetchall():
        print(f"ID {row[0]}: {row[2]} → {row[3]} kg/m ({row[4]})")

    cursor.close()
    conn.close()

except Exception as e:
    print(f"오류 발생: {e}")
    import traceback
    traceback.print_exc()