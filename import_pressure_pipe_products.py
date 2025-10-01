#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
압력배관(Pressure-pipe) 제품 임포트 스크립트
- 33개 제품 (SCH40 20개, SCH80 13개)
- 선형 계산: 단위중량 × 길이 × 수량
- 12개 재질, 6.0m-12.0m 길이 드롭다운
"""

import mysql.connector
import json
import pandas as pd

# 데이터베이스 연결 설정
DB_CONFIG = {
    'host': '127.0.0.1',
    'port': 3306,
    'user': 'root',
    'password': 'rootpassword',
    'database': 'project1_db'
}

# 12개 표준 재질 목록
MATERIALS = [
    "SS400",
    "SS400/A36",
    "SHN400",
    "SS490",
    "SS540",
    "SM400A",
    "SM400B",
    "SHN490",
    "SM490A",
    "SM490B",
    "SM490YA",
    "SM490YB"
]

def create_category_if_not_exists(cursor):
    """압력배관 카테고리 생성"""
    # 카테고리 존재 여부 확인
    cursor.execute("""
        SELECT id FROM product_categories
        WHERE category_code = 'pressure-pipe'
    """)
    result = cursor.fetchone()

    if result:
        print(f"✅ 카테고리 'pressure-pipe' 이미 존재 (ID: {result[0]})")
        return result[0]
    else:
        # 카테고리 생성
        cursor.execute("""
            INSERT INTO product_categories (category_code, category_name)
            VALUES ('pressure-pipe', '압력배관')
        """)
        category_id = cursor.lastrowid
        print(f"✅ 카테고리 'pressure-pipe' 생성 완료 (ID: {category_id})")
        return category_id

def import_products(cursor):
    """Excel 파일에서 압력배관 제품 임포트"""

    # Excel 파일 읽기
    file_path = '/home/successbank/projects/docker/project1/html/114/8/압력배관.xlsx'
    df = pd.read_excel(file_path)

    # 데이터 정리 (헤더 행 제거)
    df = df[1:]
    df = df.reset_index(drop=True)
    df.columns = ['col0', '품명', '규격', '단위중량', 'col4', '재질']

    # JSON 형식으로 재질 목록 생성
    materials_json = json.dumps(MATERIALS, ensure_ascii=False)

    imported_count = 0

    for idx, row in df.iterrows():
        spec = row['규격']
        unit_weight = float(row['단위중량'])
        product_name = f"압력배관 {spec}"
        specification = spec

        # 제품 삽입
        cursor.execute("""
            INSERT INTO products
            (product_name, specification, specification_weight,
             category_code, calculation_type, available_materials, has_calculator)
            VALUES (%s, %s, %s, 'pressure-pipe', 'linear', %s, 1)
        """, (product_name, specification, unit_weight, materials_json))

        imported_count += 1
        print(f"  {imported_count}. {product_name} - {unit_weight} kg/m 임포트 완료")

    return imported_count

def main():
    """메인 실행 함수"""
    print("=" * 80)
    print("압력배관(Pressure-pipe) 제품 임포트 시작")
    print("=" * 80)

    conn = None
    try:
        # 데이터베이스 연결
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()

        # 1. 카테고리 생성
        print("\n[1단계] 카테고리 생성")
        category_id = create_category_if_not_exists(cursor)

        # 2. 제품 임포트
        print("\n[2단계] 제품 임포트")
        imported_count = import_products(cursor)

        # 커밋
        conn.commit()

        # 3. 결과 확인
        print("\n[3단계] 임포트 결과 확인")
        cursor.execute("""
            SELECT COUNT(*) FROM products
            WHERE category_code = 'pressure-pipe'
        """)
        total_count = cursor.fetchone()[0]

        print("=" * 80)
        print("✅ 압력배관 제품 임포트 완료")
        print("=" * 80)
        print(f"카테고리: 압력배관 (pressure-pipe)")
        print(f"임포트 제품 수: {imported_count}개")
        print(f"DB 총 제품 수: {total_count}개")
        print(f"재질: 12개 (SS400 기본)")
        print(f"계산 방식: linear (단위중량 × 길이 × 수량)")
        print(f"길이 드롭다운: 6.0m-12.0m (0.1m 단위)")
        print("=" * 80)

    except mysql.connector.Error as err:
        print(f"❌ 데이터베이스 오류: {err}")
        if conn:
            conn.rollback()
    except Exception as e:
        print(f"❌ 오류 발생: {e}")
        if conn:
            conn.rollback()
    finally:
        if conn and conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == "__main__":
    main()