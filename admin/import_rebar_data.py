#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import pandas as pd
import mysql.connector
import sys
import warnings
warnings.filterwarnings('ignore')

# 데이터베이스 연결 정보
DB_CONFIG = {
    'host': '127.0.0.1',
    'port': 3306,
    'user': 'user',
    'password': 'userpassword',
    'database': 'project1_db'
}

def parse_excel_data(file_path):
    """엑셀 파일에서 철근 데이터 추출"""
    print("엑셀 파일 읽기 중...")

    # 엑셀 파일 읽기 - 헤더 없이
    df = pd.read_excel(file_path, header=None)

    print(f"데이터프레임 shape: {df.shape}")

    # 실제 규격이 있는 행 확인 (2행 = index 2)
    header_row = df.iloc[2]
    print(f"헤더 행(2): {[str(x) for x in header_row[:20] if pd.notna(x)]}")

    # 규격 정보 추출 (2행 = index 2)
    specs = []
    for col_idx in range(2, len(df.columns)):
        cell_value = df.iloc[2, col_idx]  # 2행(index 2)의 값
        if pd.notna(cell_value) and '/' in str(cell_value):
            parts = str(cell_value).split('/')
            spec_name = parts[0].strip()
            weight_per_meter = float(parts[1].strip())
            specs.append({
                'name': spec_name,
                'weight_per_meter': weight_per_meter,
                'col_idx': col_idx
            })
            print(f"  발견된 규격: {spec_name} ({weight_per_meter}kg/m) - 컬럼 {col_idx}")

    # 길이별 데이터 추출
    all_data = []
    for spec in specs:
        col_idx = spec['col_idx']
        spec_name = spec['name']
        unit_weight = spec['weight_per_meter']

        print(f"\n{spec_name} 데이터 추출 중...")

        # 데이터 행은 4행(index 4)부터 시작
        for row_idx in range(4, len(df)):
            length = df.iloc[row_idx, 1]  # 길이 컬럼

            if pd.notna(length):
                try:
                    length_val = float(length)
                    piece_weight = df.iloc[row_idx, col_idx]      # 본중 (1본의 무게)
                    pieces_per_length = df.iloc[row_idx, col_idx + 1] # 길이당 본수
                    weight_per_ton = df.iloc[row_idx, col_idx + 2] # 톤당 중량

                    # 값이 모두 유효한 경우만 추가
                    if pd.notna(piece_weight) and pd.notna(pieces_per_length) and pd.notna(weight_per_ton):
                        data_row = {
                            'spec_name': spec_name,
                            'length': length_val,
                            'piece_weight': float(piece_weight),
                            'pieces_per_length': int(float(pieces_per_length)) if pd.notna(pieces_per_length) else None,
                            'weight_per_ton': float(weight_per_ton),
                            'unit_weight': unit_weight
                        }
                        all_data.append(data_row)
                        print(f"    길이 {length_val}m: 본중={piece_weight}, 길이당본수={pieces_per_length}, 톤당중량={weight_per_ton}")
                except (ValueError, TypeError) as e:
                    continue

    print(f"\n총 {len(all_data)}개 데이터 추출 완료")
    return all_data

def import_to_database(data):
    """추출된 데이터를 데이터베이스에 저장"""
    try:
        # 데이터베이스 연결
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()

        print("\n데이터베이스 연결 성공")

        # 기존 데이터 백업을 위한 테이블 생성
        backup_table = """
        CREATE TABLE IF NOT EXISTS rebar_length_data_backup AS
        SELECT * FROM rebar_length_data
        """
        cursor.execute(backup_table)
        print("백업 테이블 생성 완료")

        # 기존 데이터 삭제
        cursor.execute("DELETE FROM rebar_length_data")
        print("기존 데이터 삭제 완료")

        # 새 데이터 삽입
        insert_query = """
        INSERT INTO rebar_length_data
        (spec_name, length, piece_weight, pieces_per_length, weight_per_ton, unit_weight)
        VALUES (%s, %s, %s, %s, %s, %s)
        """

        for row in data:
            cursor.execute(insert_query, (
                row['spec_name'],
                row['length'],
                row['piece_weight'],
                row['pieces_per_length'],
                row['weight_per_ton'],
                row['unit_weight']
            ))

        # 커밋
        conn.commit()
        print(f"\n{len(data)}개 데이터 삽입 완료")

        # 결과 확인
        cursor.execute("SELECT spec_name, COUNT(*) as cnt FROM rebar_length_data GROUP BY spec_name")
        results = cursor.fetchall()
        print("\n규격별 데이터 수:")
        for spec_name, count in results:
            print(f"  {spec_name}: {count}개")

        cursor.close()
        conn.close()

        return True

    except mysql.connector.Error as err:
        print(f"데이터베이스 오류: {err}")
        return False
    except Exception as e:
        print(f"오류 발생: {e}")
        return False

def main():
    excel_file = '/home/successbank/projects/docker/project1/html/114/2/철근.xlsx'

    print("=" * 50)
    print("철근 데이터 임포트 시작")
    print("=" * 50)

    # 엑셀 데이터 파싱
    data = parse_excel_data(excel_file)

    if data:
        # 데이터베이스에 임포트
        success = import_to_database(data)

        if success:
            print("\n✅ 임포트 완료!")
            print("admin_rebar_manage.php 페이지에서 확인 가능합니다.")
        else:
            print("\n❌ 임포트 실패!")
    else:
        print("데이터를 추출할 수 없습니다.")

if __name__ == "__main__":
    main()