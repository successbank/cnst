#!/usr/bin/env python3
import pandas as pd
import mysql.connector
import warnings
warnings.filterwarnings('ignore')

# MySQL 연결
conn = mysql.connector.connect(
    host='localhost',
    port=3306,
    user='root',
    password='rootpassword',
    database='project1_db'
)
cursor = conn.cursor()

# 엑셀 파일 읽기
df = pd.read_excel('/home/successbank/projects/docker/project1/html/114/철근.xlsx')

# 각 규격별로 처리
specs = {
    'D10': ('D10 / 0.56', 'Unnamed: 2', 'Unnamed: 3'),
    'D13': ('D13 / 0.995', 'Unnamed: 5', 'Unnamed: 6'),
    'D16': ('D16 / 1.56', 'Unnamed: 8', 'Unnamed: 9'),
    'D19': ('D19 / 2.25', 'Unnamed: 11', 'Unnamed: 12'),
    'D22': ('D22 / 3.04', 'Unnamed: 14', 'Unnamed: 15'),
    'D25': ('D25 / 3.98', 'Unnamed: 17', 'Unnamed: 18'),
    'D29': ('D29 / 5.04', 'Unnamed: 20', 'Unnamed: 21'),
    'D32': ('D32 / 6.23', 'Unnamed: 23', 'Unnamed: 24'),
    'D35': ('D35 / 7.51', 'Unnamed: 26', 'Unnamed: 27'),
    'D38': ('D38 / 8.95', 'Unnamed: 29', 'Unnamed: 30'),
    'D41': ('D41 / 10.5', 'Unnamed: 32', 'Unnamed: 33'),
    'D51': ('D51 / 15.9', 'Unnamed: 35', 'Unnamed: 36')
}

update_count = 0

for spec_name, (weight_col, pieces_col, total_col) in specs.items():
    # 해당 규격의 ID 가져오기
    cursor.execute("SELECT id FROM rebar_specifications WHERE spec_name = %s", (spec_name,))
    result = cursor.fetchone()
    
    if not result:
        print(f"{spec_name} 규격을 찾을 수 없습니다.")
        continue
    
    spec_id = result[0]
    
    # 엑셀 데이터 처리
    for idx in range(1, len(df)):
        length = df.iloc[idx]['길이']
        total_weight = df.iloc[idx][total_col]
        
        if pd.notna(length) and pd.notna(total_weight):
            # total_weight 업데이트
            cursor.execute("""
                UPDATE rebar_length_info 
                SET total_weight = %s 
                WHERE spec_id = %s AND length = %s
            """, (float(total_weight), spec_id, float(length)))
            
            if cursor.rowcount > 0:
                update_count += 1
                print(f"{spec_name} {length}m: {total_weight}kg 업데이트")

# 변경사항 커밋
conn.commit()
print(f"\n총 {update_count}개 레코드 업데이트 완료")

cursor.close()
conn.close()