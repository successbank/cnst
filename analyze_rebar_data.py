#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
철근 Excel 파일 분석 스크립트
충남스틸 철근 제품 데이터를 분석하고 DB 입력용 SQL을 생성합니다.
"""

import pandas as pd
import json
import sys

def analyze_rebar_excel():
    """철근 Excel 파일을 분석하여 구조를 파악합니다."""
    
    excel_file = './114/철근.xlsx'
    
    try:
        # Excel 파일 읽기
        df = pd.read_excel(excel_file, sheet_name=0)
        
        print("=== 철근 Excel 파일 분석 ===")
        print(f"전체 행 수: {len(df)}")
        print(f"전체 열 수: {len(df.columns)}")
        print("\n컬럼 목록:")
        for i, col in enumerate(df.columns):
            print(f"  {i}: {col}")
        
        print("\n상위 5개 데이터:")
        print(df.head())
        
        print("\n하위 5개 데이터:")
        print(df.tail())
        
        # 데이터 타입 확인
        print("\n데이터 타입:")
        print(df.dtypes)
        
        # NULL 값 확인
        print("\nNULL 값 개수:")
        print(df.isnull().sum())
        
        # 고유값 확인 (카테고리 파악용)
        print("\n각 컬럼의 고유값 개수:")
        for col in df.columns:
            unique_count = df[col].nunique()
            print(f"  {col}: {unique_count}개")
            if unique_count < 20:  # 고유값이 적으면 출력
                print(f"    값: {df[col].unique()}")
        
    except Exception as e:
        print(f"오류 발생: {str(e)}")
        return

if __name__ == "__main__":
    analyze_rebar_excel()