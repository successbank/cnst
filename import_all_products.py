#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import json
import os
import glob

# 제품 카테고리별 설정
product_configs = [
    {
        'json_file': '114/3/lightweight_h_beam_data.json',
        'category_code': 'light-h-beam',
        'product_prefix': '경량H형강',
        'description_prefix': '경량H형강',
        'material': 'SS400'
    },
    {
        'json_file': '114/4/i_beam_data.json',
        'category_code': 'i-beam',
        'product_prefix': 'I형강',
        'description_prefix': 'I형강(빔)',
        'material': 'SS400'
    },
    {
        'json_file': '114/5/angle_steel_data.json',
        'category_code': 'angle',
        'product_prefix': 'ㄱ형강',
        'description_prefix': 'ㄱ형강(앵글)',
        'material': 'SS400'
    },
    {
        'json_file': '114/6/channel_steel_data.json',
        'category_code': 'channel',
        'product_prefix': 'ㄷ형강',
        'description_prefix': 'ㄷ형강(찬넬)',
        'material': 'SS400'
    },
    {
        'json_file': '114/6/c_beam_data.json',
        'category_code': 'c-beam',
        'product_prefix': 'C형강',
        'description_prefix': 'C형강',
        'material': 'SS400'
    },
    {
        'json_file': '114/6/round_bar_data.json',
        'category_code': 'round-bar',
        'product_prefix': '환봉',
        'description_prefix': '환봉',
        'material': 'SS400'
    },
    {
        'json_file': '114/6/flat_bar_data.json',
        'category_code': 'flat-bar',
        'product_prefix': '평철',
        'description_prefix': '평철',
        'material': 'SS400'
    },
    {
        'json_file': '114/7/square_pipe_data.json',
        'category_code': 'square-pipe',
        'product_prefix': '사각파이프',
        'description_prefix': '사각파이프',
        'material': 'SPSR400'
    },
    {
        'json_file': '114/7/steel_plate_data.json',
        'category_code': 'steel-plate',
        'product_prefix': '철판',
        'description_prefix': '철판(강판)',
        'material': 'SS400'
    },
    {
        'json_file': '114/7/deck_plate_data.json',
        'category_code': 'deck-plate',
        'product_prefix': '데크플레이트',
        'description_prefix': '데크플레이트',
        'material': 'SS400'
    },
    {
        'json_file': '114/7/rail_data.json',
        'category_code': 'rail',
        'product_prefix': '레일',
        'description_prefix': '레일',
        'material': 'SS400'
    },
    {
        'json_file': '114/7/sheet_pile_data.json',
        'category_code': 'sheet-pile',
        'product_prefix': '강널말뚝',
        'description_prefix': '강널말뚝(쉬트파일)',
        'material': 'SY295'
    },
    {
        'json_file': '114/8/ks_pipe_data.json',
        'category_code': 'round-pipe',
        'product_prefix': 'KS파이프',
        'description_prefix': 'KS규격 파이프',
        'material': 'STK400'
    },
    {
        'json_file': '114/8/structural_pipe_data.json',
        'category_code': 'round-pipe',
        'product_prefix': '구조관',
        'description_prefix': '구조용 강관',
        'material': 'STK400'
    }
]

# SQL 파일 생성
sql_file = open('insert_all_products.sql', 'w', encoding='utf-8')
sql_file.write('-- 모든 제품 데이터 삽입\n\n')

# 단위중량 테이블 생성
sql_file.write('-- 단위중량 데이터 삽입\n')
sql_file.write('INSERT IGNORE INTO unit_weights (specification, unit_weight, is_active) VALUES\n')

unit_weight_values = []

for config in product_configs:
    try:
        with open(config['json_file'], 'r', encoding='utf-8') as f:
            products = json.load(f)
            
        for product in products:
            spec = product['specification'].replace('*', '×').replace("'", "''")  # SQL escape single quotes
            unit_weight = product['unit_weight']
            unit_weight_values.append(f"('{spec}', {unit_weight}, 1)")
    except Exception as e:
        print(f"Error processing {config['json_file']}: {e}")

sql_file.write(',\n'.join(unit_weight_values))
sql_file.write(';\n\n')

# 제품 데이터 생성
sql_file.write('-- 제품 데이터 삽입\n')
sql_file.write('INSERT INTO products (category_code, product_name, specifications, description, weight, material, unit, min_order_qty, stock_status, is_active) VALUES\n')

product_values = []

for config in product_configs:
    try:
        with open(config['json_file'], 'r', encoding='utf-8') as f:
            products = json.load(f)
            
        for product in products:
            spec = product['specification'].replace('*', '×').replace('T', 't').replace("'", "''")  # SQL escape single quotes
            unit_weight = product['unit_weight']
            
            # 제품명 생성
            if config['category_code'] in ['round-bar', 'flat-bar']:
                product_name = f"{config['product_prefix']} {spec}"
            else:
                # 처음 두 숫자를 사용
                parts = spec.split('×')
                if len(parts) >= 2:
                    # 숫자만 추출
                    first_num = ''.join(filter(str.isdigit, parts[0]))
                    second_num = ''.join(filter(str.isdigit, parts[1]))
                    if first_num and second_num:
                        product_name = f"{config['product_prefix']} {first_num}×{second_num}"
                    else:
                        product_name = f"{config['product_prefix']} {spec}"
                else:
                    product_name = f"{config['product_prefix']} {spec}"
            
            description = f"{config['description_prefix']} 규격: {spec}, 단위중량: {unit_weight}kg/m"
            weight_str = f"{unit_weight}kg/m"
            
            # 가격은 임의로 설정 (추후 업데이트 필요)
            min_price = 800000  # 톤당 80만원
            max_price = 850000  # 톤당 85만원
            
            # Escape single quotes in all string fields
            escaped_product_name = product_name.replace("'", "''")
            escaped_description = description.replace("'", "''")
            
            product_values.append(
                f"('{config['category_code']}', '{escaped_product_name}', '{spec}', '{escaped_description}', "
                f"'{weight_str}', '{config['material']}', 'TON', 1, 'in_stock', 1)"
            )
            
    except Exception as e:
        print(f"Error processing {config['json_file']}: {e}")

sql_file.write(',\n'.join(product_values))
sql_file.write(';\n\n')

# H형강 데이터 추가 (하드코딩)
sql_file.write('-- H형강 데이터 추가\n')
sql_file.write('INSERT INTO products (category_code, product_name, specifications, description, weight, material, unit, min_order_qty, stock_status, is_active) VALUES\n')

h_beam_specs = [
    ('100×100×6×8', 17.2),
    ('125×125×6.5×9', 23.8),
    ('150×150×7×10', 31.5),
    ('175×175×7.5×11', 40.2),
    ('200×200×8×12', 49.9),
    ('250×250×9×14', 72.4),
    ('300×300×10×15', 94.0),
    ('350×350×12×19', 137.0),
    ('400×400×13×21', 172.0),
    ('450×450×15×24', 219.0),
    ('500×500×16×28', 285.0),
    ('600×600×17×32', 375.0)
]

h_beam_values = []
for spec, weight in h_beam_specs:
    parts = spec.split('×')
    product_name = f"H형강 {parts[0]}×{parts[1]}"
    description = f"H형강 규격: {spec}, 단위중량: {weight}kg/m"
    weight_str = f"{weight}kg/m"
    
    h_beam_values.append(
        f"('h-beam', '{product_name}', '{spec}', '{description}', "
        f"'{weight_str}', 'SS400', 'TON', 1, 'in_stock', 1)"
    )

sql_file.write(',\n'.join(h_beam_values))
sql_file.write(';\n')

sql_file.close()

print("SQL file 'insert_all_products.sql' has been created successfully!")