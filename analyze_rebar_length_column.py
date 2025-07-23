#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
철근 Excel 파일의 길이 칼럼 분석
"""

import pandas as pd

def analyze_length_column():
    """첫 번째 칼럼(길이) 데이터를 분석합니다."""
    
    excel_file = './114/철근.xlsx'
    
    try:
        # Excel 파일 읽기
        df = pd.read_excel(excel_file, sheet_name=0)
        
        print("=== 첫 번째 칼럼(길이) 분석 ===")
        
        # 첫 번째 칼럼 데이터
        length_column = df.iloc[:, 0]
        
        print(f"\n첫 번째 칼럼명: {df.columns[0]}")
        print(f"전체 행 수: {len(length_column)}")
        
        # 유효한 길이 데이터만 추출 (숫자인 것만)
        valid_lengths = []
        for idx, value in enumerate(length_column):
            if pd.notna(value) and isinstance(value, (int, float)):
                valid_lengths.append(value)
                print(f"행 {idx+1}: {value}m")
        
        print(f"\n유효한 길이 데이터 개수: {len(valid_lengths)}")
        print(f"길이 범위: {min(valid_lengths)}m ~ {max(valid_lengths)}m")
        
        # 모든 길이 값 출력
        print("\n모든 길이 값:")
        print(valid_lengths)
        
    except Exception as e:
        print(f"오류 발생: {str(e)}")

if __name__ == "__main__":
    analyze_length_column()