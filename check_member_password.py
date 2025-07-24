#!/usr/bin/env python3
import pandas as pd
import sys

try:
    # Excel 파일 읽기 - engine 명시
    df = pd.read_excel('/home/successbank/projects/docker/project1/html/114/member.xls', engine='xlrd')
    
    # 컬럼 정보 출력
    print("Excel 파일 컬럼:", df.columns.tolist())
    print("\n총 회원 수:", len(df))
    
    # test000 사용자 찾기
    # 가능한 ID 컬럼명들 시도
    id_columns = ['id', 'ID', 'user_id', 'USER_ID', 'userId', 'username', 'USERNAME', '아이디', 'member_id']
    
    found = False
    for col in id_columns:
        if col in df.columns:
            print(f"\n'{col}' 컬럼에서 test000 검색 중...")
            test_user = df[df[col] == 'test000']
            if not test_user.empty:
                print(f"\ntest000 사용자 발견!")
                print(test_user.to_string())
                found = True
                break
    
    if not found:
        print("\n모든 데이터 샘플 (처음 5행):")
        print(df.head())
        
except ModuleNotFoundError as e:
    print(f"모듈을 찾을 수 없습니다: {e}")
    print("\n시스템 패키지로 설치 시도:")
    print("sudo apt-get install python3-pandas python3-xlrd")
except Exception as e:
    print(f"오류 발생: {e}")