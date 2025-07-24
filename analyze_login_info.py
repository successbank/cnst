#!/usr/bin/env python3
import pandas as pd

# HTML 테이블 읽기
dfs = pd.read_html('./114/member.xls')
df = dfs[0]

# 첫 행을 컬럼명으로 설정
column_names = df.iloc[0].tolist()
df.columns = column_names
df = df.iloc[1:].reset_index(drop=True)

print("=== 로그인 관련 컬럼 분석 ===\n")

# 로그인 관련 컬럼 찾기
login_related_columns = []
for idx, col in enumerate(column_names):
    if col and isinstance(col, str):
        col_lower = col.lower()
        if 'log' in col_lower or 'last' in col_lower or 'date' in col_lower:
            login_related_columns.append((idx, col))
            print(f"{idx}: {col}")

print("\n=== 샘플 데이터 확인 ===")

# 주요 로그인 관련 컬럼 데이터 확인
if login_related_columns:
    # MB_LOGNUM (로그인 횟수), MB_LOGDATE (마지막 로그인), MB_NOWLOG (현재 로그인 상태) 등
    sample_cols = ['MB_ID', 'MB_NAME']
    for idx, col in login_related_columns:
        if col in df.columns:
            sample_cols.append(col)
    
    # 로그인 횟수가 많은 상위 10명 확인
    if 'MB_LOGNUM' in df.columns:
        print("\n로그인 횟수 상위 10명:")
        df_sorted = df.copy()
        # 숫자로 변환
        df_sorted['MB_LOGNUM'] = pd.to_numeric(df_sorted['MB_LOGNUM'], errors='coerce').fillna(0)
        df_sorted = df_sorted.sort_values('MB_LOGNUM', ascending=False)
        
        for idx, row in df_sorted.head(10).iterrows():
            print(f"ID: {row['MB_ID']:<15} 이름: {row['MB_NAME']:<10} 로그인횟수: {int(row['MB_LOGNUM']):>5} 마지막로그인: {row.get('MB_LOGDATE', 'N/A')}")
    
    # 최근 로그인 날짜 확인
    if 'MB_LOGDATE' in df.columns:
        print("\n최근 로그인한 회원 10명:")
        df_recent = df[df['MB_LOGDATE'].notna() & (df['MB_LOGDATE'] != '0')].copy()
        # YYYYMMDD 형식을 datetime으로 변환 시도
        df_recent['login_date_parsed'] = pd.to_numeric(df_recent['MB_LOGDATE'], errors='coerce')
        df_recent = df_recent.sort_values('login_date_parsed', ascending=False)
        
        for idx, row in df_recent.head(10).iterrows():
            logdate = str(row['MB_LOGDATE'])
            if len(logdate) >= 8:
                formatted_date = f"{logdate[:4]}-{logdate[4:6]}-{logdate[6:8]}"
            else:
                formatted_date = logdate
            print(f"ID: {row['MB_ID']:<15} 이름: {row['MB_NAME']:<10} 마지막로그인: {formatted_date}")

# 전체 통계
print("\n=== 로그인 통계 ===")
if 'MB_LOGNUM' in df.columns:
    df['MB_LOGNUM_numeric'] = pd.to_numeric(df['MB_LOGNUM'], errors='coerce').fillna(0)
    total_logins = df['MB_LOGNUM_numeric'].sum()
    avg_logins = df['MB_LOGNUM_numeric'].mean()
    max_logins = df['MB_LOGNUM_numeric'].max()
    
    print(f"전체 로그인 횟수 합계: {int(total_logins):,}")
    print(f"평균 로그인 횟수: {avg_logins:.1f}")
    print(f"최대 로그인 횟수: {int(max_logins):,}")
    
    # 로그인한 적이 있는 회원 수
    logged_in_users = df[df['MB_LOGNUM_numeric'] > 0].shape[0]
    print(f"로그인한 적이 있는 회원: {logged_in_users:,}명 / {len(df):,}명 ({logged_in_users/len(df)*100:.1f}%)")