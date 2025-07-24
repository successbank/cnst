#!/usr/bin/env python3
import pandas as pd

# Read HTML table
dfs = pd.read_html('./114/member.xls')
df = dfs[0]

# First row contains column names
column_names = df.iloc[0].tolist()
print("Column names from first row:")
for i, col in enumerate(column_names):
    print(f"{i}: {col}")

# Set proper column names and remove header row
df.columns = column_names
df = df.iloc[1:].reset_index(drop=True)

print(f"\nTotal records: {len(df)}")

# Check for id and password columns
id_col = None
pass_col = None

for col in column_names:
    if col and isinstance(col, str):
        if 'MB_ID' in col:
            id_col = col
        elif 'MB_PASS' in col or 'MB_PWD' in col:
            pass_col = col

print(f"\nID column: {id_col}")
print(f"Password column: {pass_col}")

if id_col and pass_col:
    print(f"\n✓ Found ID and Password columns!")
    # Show sample data
    print("\nSample data (first 10 rows):")
    sample_df = df[[id_col, pass_col]].head(10)
    for idx, row in sample_df.iterrows():
        print(f"ID: {row[id_col]}, Password: {row[pass_col]}")
    
    # Check for empty values
    empty_ids = df[id_col].isna().sum()
    empty_passes = df[pass_col].isna().sum()
    print(f"\nEmpty IDs: {empty_ids}")
    print(f"Empty Passwords: {empty_passes}")
    
    # Save to CSV for easier processing
    export_df = df[[id_col, pass_col]].copy()
    export_df.columns = ['id', 'password']
    export_df.to_csv('member_id_password.csv', index=False)
    print("\nExported to member_id_password.csv")