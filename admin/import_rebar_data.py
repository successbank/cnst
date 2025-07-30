#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import pandas as pd
import json
import sys

def extract_rebar_data(excel_file):
    """엑셀 파일에서 철근 데이터 추출"""
    
    # 엑셀 파일 읽기
    df = pd.read_excel(excel_file, sheet_name='Sheet1')
    
    # 철근 규격 정보
    specs = ['D10', 'D13', 'D16', 'D19', 'D22', 'D25', 'D29', 'D32', 'D35', 'D38', 'D41', 'D51']
    unit_weights = [0.56, 0.995, 1.56, 2.25, 3.04, 3.98, 5.04, 6.23, 7.51, 8.95, 10.5, 15.9]
    
    # 결과 데이터 저장
    result_data = []
    
    # 각 행 처리 (2번째 행부터 시작)
    for idx in range(1, len(df)):
        row = df.iloc[idx]
        length = row.iloc[0]
        
        # 길이가 숫자가 아니면 건너뛰기
        if pd.isna(length) or not isinstance(length, (int, float)):
            continue
            
        length = float(length)
        
        # 각 규격별로 데이터 추출
        for spec_idx, (spec, unit_weight) in enumerate(zip(specs, unit_weights)):
            # 각 규격은 3개 컬럼씩 차지 (본중, 톤당 본수, 톤당 중량)
            col_start = 1 + (spec_idx * 3)
            
            piece_weight = row.iloc[col_start] if col_start < len(row) else None
            pieces_per_ton = row.iloc[col_start + 1] if col_start + 1 < len(row) else None
            weight_per_ton = row.iloc[col_start + 2] if col_start + 2 < len(row) else None
            
            # 유효한 데이터만 추가
            if pd.notna(piece_weight) and isinstance(piece_weight, (int, float)):
                data_item = {
                    'spec_name': spec,
                    'unit_weight': unit_weight,
                    'length': length,
                    'piece_weight': float(piece_weight),
                    'pieces_per_ton': int(pieces_per_ton) if pd.notna(pieces_per_ton) else None,
                    'weight_per_ton': float(weight_per_ton) if pd.notna(weight_per_ton) else None
                }
                result_data.append(data_item)
    
    return result_data

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python import_rebar_data.py <excel_file>")
        sys.exit(1)
        
    excel_file = sys.argv[1]
    
    try:
        data = extract_rebar_data(excel_file)
        # JSON 형식으로 출력
        print(json.dumps(data, ensure_ascii=False))
    except Exception as e:
        print(f"Error: {str(e)}", file=sys.stderr)
        sys.exit(1)