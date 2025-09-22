#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
철근 Excel 데이터를 SQL로 변환하는 스크립트
"""

import pandas as pd
import json
import os

# Excel 파일 경로
REBAR_DATA_FILE = '/home/successbank/projects/docker/project1/html/114/9/철근20250730.xlsx'
MATERIAL_DATA_FILE = '/home/successbank/projects/docker/project1/html/114/9/철근재질.xlsx'

def parse_rebar_data():
    """철근 데이터 Excel 파일 파싱"""
    df = pd.read_excel(REBAR_DATA_FILE)

    # 컬럼 정리
    specs = []
    for col in df.columns:
        if 'D' in str(col) and '/' in str(col):
            # 예: 'D10 / 0.56' -> ('D10', 0.56)
            parts = col.split('/')
            spec = parts[0].strip()
            unit_weight = float(parts[1].strip())
            specs.append({
                'spec': spec,
                'unit_weight': unit_weight,
                'col_index': df.columns.get_loc(col)
            })

    # 데이터 추출
    rebar_data = []
    for spec_info in specs:
        spec = spec_info['spec']
        unit_weight = spec_info['unit_weight']
        col_idx = spec_info['col_index']

        # 본중, 톤당 본수, 톤당 중량 컬럼
        piece_weight_col = col_idx
        pieces_per_ton_col = col_idx + 1
        weight_per_ton_col = col_idx + 2

        for idx, row in df.iterrows():
            if idx == 0:  # 헤더 행 스킵
                continue

            length = row['길이']
            if pd.isna(length):
                continue

            piece_weight = row.iloc[piece_weight_col]
            pieces_per_ton = row.iloc[pieces_per_ton_col]
            weight_per_ton = row.iloc[weight_per_ton_col]

            # NaN 값 처리
            if pd.notna(piece_weight):
                rebar_data.append({
                    'spec_name': spec,
                    'length': float(length),
                    'piece_weight': float(piece_weight),
                    'pieces_per_ton': int(pieces_per_ton) if pd.notna(pieces_per_ton) else None,
                    'weight_per_ton': float(weight_per_ton) if pd.notna(weight_per_ton) else None,
                    'unit_weight': unit_weight
                })

    return rebar_data

def parse_material_data():
    """재질 데이터 Excel 파일 파싱"""
    df = pd.read_excel(MATERIAL_DATA_FILE)

    materials = []
    for idx, row in df.iterrows():
        materials.append({
            'material_name': row['강종/재질'],
            'price_per_kg': float(row['kg당 단가'])
        })

    return materials

def generate_rebar_length_sql(rebar_data):
    """rebar_length_data 테이블용 SQL 생성"""
    sql_lines = []
    sql_lines.append("-- rebar_length_data 테이블 데이터 삭제 및 재입력")
    sql_lines.append("TRUNCATE TABLE rebar_length_data;")
    sql_lines.append("")
    sql_lines.append("-- 철근 길이별 데이터 입력")
    sql_lines.append("INSERT INTO rebar_length_data (spec_name, length, piece_weight, pieces_per_ton, weight_per_ton, unit_weight) VALUES")

    values = []
    for item in rebar_data:
        pieces_per_ton = item['pieces_per_ton'] if item['pieces_per_ton'] else 'NULL'
        weight_per_ton = item['weight_per_ton'] if item['weight_per_ton'] else 'NULL'

        value = f"('{item['spec_name']}', {item['length']}, {item['piece_weight']}, {pieces_per_ton}, {weight_per_ton}, {item['unit_weight']})"
        values.append(value)

    sql_lines.append(',\n'.join(values) + ';')

    return '\n'.join(sql_lines)

def generate_materials_sql(materials):
    """rebar_materials 테이블용 SQL 생성"""
    sql_lines = []
    sql_lines.append("\n-- rebar_materials 테이블 데이터 업데이트")
    sql_lines.append("TRUNCATE TABLE rebar_materials;")
    sql_lines.append("")
    sql_lines.append("-- 철근 재질 데이터 입력")
    sql_lines.append("INSERT INTO rebar_materials (material_name, price_per_kg, description, is_active, display_order) VALUES")

    values = []
    for idx, item in enumerate(materials):
        description = f"{item['material_name']} 강종"
        value = f"('{item['material_name']}', {item['price_per_kg']}, '{description}', 1, {idx+1})"
        values.append(value)

    sql_lines.append(',\n'.join(values) + ';')

    return '\n'.join(sql_lines)

def generate_products_sql():
    """products 테이블용 SQL 생성"""
    specs = ['D10', 'D13', 'D16', 'D19', 'D22', 'D25', 'D29', 'D32', 'D35', 'D38', 'D41', 'D51']
    unit_weights = {
        'D10': 0.560, 'D13': 0.995, 'D16': 1.560, 'D19': 2.250,
        'D22': 3.040, 'D25': 3.980, 'D29': 5.040, 'D32': 6.230,
        'D35': 7.510, 'D38': 8.950, 'D41': 10.500, 'D51': 15.900
    }

    sql_lines = []
    sql_lines.append("\n-- products 테이블에 철근 제품 등록")
    sql_lines.append("-- 기존 철근 제품 삭제")
    sql_lines.append("DELETE FROM products WHERE category_code = 'rebar';")
    sql_lines.append("")
    sql_lines.append("-- 12개 철근 제품 입력")

    for spec in specs:
        unit_weight = unit_weights[spec]

        # unit_weight_data JSON 생성
        unit_weight_data = {}
        for length in [6, 6.5, 7, 7.5, 8, 9, 10, 11, 12]:
            unit_weight_data[f"{length}m"] = {
                "weight": round(unit_weight * length, 3)
            }
        unit_weight_json = json.dumps(unit_weight_data, ensure_ascii=False)

        # available_materials JSON
        materials = ["SD300", "SD400", "SD400W", "SD400S", "SD500", "SD500W", "SD500S", "SD600", "SD600S"]
        materials_json = json.dumps(materials, ensure_ascii=False)

        # available_origins JSON
        origins = ["국산", "수입산"]
        origins_json = json.dumps(origins, ensure_ascii=False)

        sql = f"""
INSERT INTO products (
    category_code, product_name, specifications, description,
    unit, min_order_qty, stock_status, is_active, has_calculator,
    calculation_type, unit_weight_data, available_materials, available_origins,
    specification_weight, display_mode
) VALUES (
    'rebar',
    '철근 {spec}',
    '{spec} (Φ{spec[1:]}mm)',
    '건축 구조물의 보강에 사용되는 이형철근입니다. 단중: {unit_weight}kg/m',
    'TON',
    1,
    'in_stock',
    1,
    1,
    'rebar',
    '{unit_weight_json}',
    '{materials_json}',
    '{origins_json}',
    {unit_weight},
    'single'
);"""
        sql_lines.append(sql)

    return '\n'.join(sql_lines)

def main():
    """메인 실행 함수"""
    print("철근 데이터 Excel 파일 파싱 중...")

    # 데이터 파싱
    rebar_data = parse_rebar_data()
    materials = parse_material_data()

    print(f"파싱 완료: {len(rebar_data)}개 철근 데이터, {len(materials)}개 재질 데이터")

    # SQL 생성
    sql_content = []
    sql_content.append("-- 철근 데이터 임포트 SQL")
    sql_content.append("-- 생성일: " + pd.Timestamp.now().strftime("%Y-%m-%d %H:%M:%S"))
    sql_content.append("")

    sql_content.append(generate_rebar_length_sql(rebar_data))
    sql_content.append(generate_materials_sql(materials))
    sql_content.append(generate_products_sql())

    # SQL 파일 저장
    output_file = '/home/successbank/projects/docker/project1/html/import_rebar_data.sql'
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write('\n'.join(sql_content))

    print(f"\nSQL 파일 생성 완료: {output_file}")
    print("\n다음 명령어로 데이터베이스에 임포트할 수 있습니다:")
    print(f"docker exec -i 1a3e4166cbca_project1_mysql mysql -u user -puserpassword project1_db < {output_file}")

if __name__ == "__main__":
    main()