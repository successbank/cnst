import pandas as pd
import warnings
warnings.filterwarnings('ignore')

# 엑셀 파일 읽기
df = pd.read_excel('114/철근.xlsx')

# 각 규격별 컬럼 정의
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

# SQL 생성
for spec_name, (weight_col, pieces_col, total_col) in specs.items():
    print(f'-- {spec_name} 데이터 업데이트')
    
    for idx in range(1, len(df)):
        length = df.iloc[idx]['길이']
        if pd.notna(length) and 'RED FONT' not in str(length):
            pieces = df.iloc[idx][pieces_col]
            total = df.iloc[idx][total_col]
            
            # 길이를 소수점 1자리로 반올림
            length_val = round(float(length), 1)
            
            if pd.notna(pieces) and pd.notna(total):
                # 데이터가 있는 경우
                pieces_val = int(pieces) if pieces == int(pieces) else pieces
                total_val = float(total)
                print(f"UPDATE rebar_length_info SET pieces_per_ton = {pieces_val}, total_weight = {total_val} WHERE spec_id = (SELECT id FROM rebar_specifications WHERE spec_name = '{spec_name}') AND length = {length_val};")
            else:
                # 데이터가 없는 경우 - pieces_per_ton을 0으로, total_weight를 NULL로
                print(f"UPDATE rebar_length_info SET pieces_per_ton = 0, total_weight = NULL WHERE spec_id = (SELECT id FROM rebar_specifications WHERE spec_name = '{spec_name}') AND length = {length_val};")
    
    print()