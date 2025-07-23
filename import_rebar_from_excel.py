#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
철근 Excel 데이터를 SQL INSERT 문으로 변환하는 스크립트
"""

import pandas as pd
import re
from datetime import datetime

def parse_rebar_excel():
    """철근 Excel 파일을 파싱하여 SQL INSERT 문을 생성합니다."""
    
    excel_file = './114/철근.xlsx'
    
    try:
        # Excel 파일 읽기
        df = pd.read_excel(excel_file, sheet_name=0)
        
        # 철근 규격 정보 추출
        rebar_specs = []
        spec_columns = []
        
        for col in df.columns:
            if isinstance(col, str) and '/' in col:
                # D10 / 0.56 형식에서 정보 추출
                match = re.match(r'(D\d+)\s*/\s*([\d.]+)', col)
                if match:
                    spec_name = match.group(1)
                    unit_weight = float(match.group(2))
                    
                    # 직경 추출 (D 뒤의 숫자)
                    diameter_match = re.match(r'D(\d+)', spec_name)
                    if diameter_match:
                        diameter = float(diameter_match.group(1))
                        
                        rebar_specs.append({
                            'spec_name': spec_name,
                            'diameter': diameter,
                            'unit_weight': unit_weight,
                            'column': col
                        })
                        spec_columns.append(col)
        
        # SQL 파일 생성
        with open('import_rebar_data.sql', 'w', encoding='utf-8') as f:
            f.write("-- 철근 데이터 임포트 SQL\n")
            f.write("-- 생성일: {}\n\n".format(datetime.now().strftime('%Y-%m-%d %H:%M:%S')))
            
            # 철근 규격 INSERT
            f.write("-- 철근 규격 데이터 입력\n")
            f.write("INSERT INTO rebar_specifications (spec_name, diameter, unit_weight, description, display_order) VALUES\n")
            
            spec_values = []
            for idx, spec in enumerate(rebar_specs):
                desc = f"건축용 이형철근 {spec['spec_name']} (SD400)"
                spec_values.append(f"('{spec['spec_name']}', {spec['diameter']}, {spec['unit_weight']}, '{desc}', {idx+1})")
            
            f.write(',\n'.join(spec_values) + ';\n\n')
            
            # 길이별 정보 INSERT
            f.write("-- 철근 길이별 정보 입력\n")
            
            for spec_idx, spec in enumerate(rebar_specs):
                f.write(f"\n-- {spec['spec_name']} 길이별 정보\n")
                f.write("INSERT INTO rebar_length_info (spec_id, length, weight_per_piece, pieces_per_ton) VALUES\n")
                
                col_idx = df.columns.get_loc(spec['column'])
                length_values = []
                
                for row_idx in range(1, len(df)):  # 첫 행은 헤더
                    length = df.iloc[row_idx, 0]  # 길이 컬럼
                    
                    if pd.notna(length) and isinstance(length, (int, float)):
                        weight_per_piece = df.iloc[row_idx, col_idx]  # 본중
                        pieces_per_ton = df.iloc[row_idx, col_idx + 1]  # 본수
                        
                        if pd.notna(weight_per_piece) and pd.notna(pieces_per_ton):
                            # spec_id는 삽입 순서대로 1부터 시작
                            length_values.append(f"({spec_idx+1}, {length}, {weight_per_piece}, {int(pieces_per_ton)})")
                
                if length_values:
                    f.write(',\n'.join(length_values) + ';\n')
            
            # 초기 가격 데이터 (관리자가 나중에 수정)
            f.write("\n-- 초기 가격 데이터 (관리자가 수정 필요)\n")
            f.write("INSERT INTO rebar_prices (spec_id, unit_price, effective_date) VALUES\n")
            
            price_values = []
            for idx in range(len(rebar_specs)):
                # 임시 가격 설정 (실제로는 관리자가 입력)
                price_values.append(f"({idx+1}, 1000, CURDATE())")
            
            f.write(',\n'.join(price_values) + ';\n')
            
        print("SQL 파일이 생성되었습니다: import_rebar_data.sql")
        
        # 간단한 요약 출력
        print(f"\n총 {len(rebar_specs)}개의 철근 규격이 발견되었습니다:")
        for spec in rebar_specs:
            print(f"  - {spec['spec_name']}: 직경 {spec['diameter']}mm, 단위중량 {spec['unit_weight']}kg/m")
            
    except Exception as e:
        print(f"오류 발생: {str(e)}")

if __name__ == "__main__":
    parse_rebar_excel()