#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import pandas as pd
import sys

excel_file = "html/114/10/H형강.xlsx"

try:
    # Excel 파일 읽기
    print(f"Excel 파일 읽는 중: {excel_file}")
    df = pd.read_excel(excel_file, engine='openpyxl')

    print("\n컬럼 목록:")
    print(df.columns.tolist())

    print(f"\n총 {len(df)}개의 행 발견")

    print("\n처음 10개 행:")
    print(df.head(10))

    print("\n918*303*19*37 규격 찾기:")
    # 모든 데이터를 문자열로 변환하여 검색
    for idx, row in df.iterrows():
        row_str = ' '.join([str(val) for val in row.values if pd.notna(val)])
        if '918' in row_str and '303' in row_str:
            print(f"행 {idx}: {row.tolist()}")
            break

    # 단위중량 컬럼 확인
    print("\n단위중량 관련 데이터:")
    for col in df.columns:
        if '중량' in str(col) or '단위' in str(col) or 'kg' in str(col).lower():
            print(f"컬럼 '{col}'의 처음 5개 값:")
            print(df[col].head())

except Exception as e:
    print(f"오류 발생: {e}")
    import traceback
    traceback.print_exc()