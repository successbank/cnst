#!/usr/bin/env python3
import pandas as pd
import pymysql

# 데이터베이스 연결 설정
def import_rebar_bundle_data():
    try:
        # Excel 파일 읽기
        df = pd.read_excel('/home/successbank/projects/docker/project1/html/114/철근.xlsx')

        # 데이터베이스 연결
        conn = pymysql.connect(
            host='localhost',
            database='project1_db',
            user='user',
            password='userpassword'
        )
        cursor = conn.cursor()

        # 기존 데이터 삭제
        cursor.execute("DELETE FROM rebar_length_data")
        conn.commit()
        print("기존 데이터 삭제 완료")

        # 각 규격별 데이터 처리
        specs = []

        # 컬럼 이름에서 규격 추출 (D10, D13, D16 등)
        for col in df.columns:
            if '/' in col and 'D' in col:
                spec_info = col.split('/')
                spec_name = spec_info[0].strip()
                unit_weight = float(spec_info[1].strip())
                specs.append({'name': spec_name, 'unit_weight': unit_weight, 'col_index': df.columns.get_loc(col)})

        print(f"발견된 규격: {[s['name'] for s in specs]}")

        # 각 길이별 데이터 임포트
        imported = 0
        for idx, row in df.iterrows():
            if idx == 0:  # 헤더 행 건너뛰기
                continue

            length = row.iloc[0]  # 첫 번째 컬럼이 길이
            if pd.isna(length):
                continue

            for spec in specs:
                col_idx = spec['col_index']

                # 본중, 본수, 중량 컬럼 위치
                piece_weight = row.iloc[col_idx]
                pieces_count = row.iloc[col_idx + 1]
                total_weight = row.iloc[col_idx + 2]

                # 유효한 데이터만 삽입
                if not pd.isna(piece_weight) and not pd.isna(pieces_count):
                    sql = """
                        INSERT INTO rebar_length_data
                        (spec_name, length, piece_weight, pieces_per_ton, weight_per_ton, unit_weight)
                        VALUES (%s, %s, %s, %s, %s, %s)
                    """

                    values = (
                        spec['name'],
                        float(length),
                        float(piece_weight),
                        int(pieces_count),
                        float(total_weight) if not pd.isna(total_weight) else float(pieces_count) * float(piece_weight),
                        spec['unit_weight']
                    )

                    cursor.execute(sql, values)
                    imported += 1

        conn.commit()
        print(f"\n총 {imported}개의 번들 데이터가 임포트되었습니다.")

        # 확인을 위한 샘플 출력
        cursor.execute("""
            SELECT spec_name, length, piece_weight, pieces_per_ton, weight_per_ton
            FROM rebar_length_data
            WHERE spec_name IN ('D10', 'D16') AND length <= 6.5
            ORDER BY spec_name, length
            LIMIT 10
        """)

        print("\n샘플 데이터:")
        print("규격 | 길이 | 본중 | 본수 | 총중량")
        print("-" * 50)
        for row in cursor.fetchall():
            print(f"{row[0]:4s} | {row[1]:4.1f} | {row[2]:6.2f} | {row[3]:4d} | {row[4]:7.1f}")

        cursor.close()
        conn.close()

    except pymysql.Error as e:
        print(f"데이터베이스 오류: {e}")
    except Exception as e:
        print(f"오류 발생: {e}")

if __name__ == "__main__":
    import_rebar_bundle_data()