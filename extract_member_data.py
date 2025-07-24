#!/usr/bin/env python3
import pandas as pd

# Read HTML table
dfs = pd.read_html('./114/member.xls')
df = dfs[0]

# First row contains column names
column_names = df.iloc[0].tolist()

# Set proper column names and remove header row
df.columns = column_names
df = df.iloc[1:].reset_index(drop=True)

print(f"Total records: {len(df)}")

# Extract ID and Password
id_col = 'MB_ID'
pass_col = 'MB_PW'

print(f"\n✓ Found columns: {id_col} and {pass_col}")

# Show sample data
print("\nSample data (first 20 rows):")
sample_df = df[[id_col, pass_col]].head(20)
for idx, row in sample_df.iterrows():
    print(f"ID: {row[id_col]:<20} Password: {row[pass_col]}")

# Check for non-empty passwords
non_empty_passwords = df[df[pass_col].notna() & (df[pass_col] != '')].shape[0]
print(f"\nRecords with non-empty passwords: {non_empty_passwords}")

# Save to CSV
export_df = df[[id_col, pass_col]].copy()
export_df.columns = ['id', 'password']
# Remove rows with empty IDs
export_df = export_df[export_df['id'].notna() & (export_df['id'] != '')]
export_df.to_csv('member_id_password.csv', index=False)
print(f"\nExported {len(export_df)} records to member_id_password.csv")

# Show statistics
print("\nPassword statistics:")
print(f"- Total records: {len(export_df)}")
print(f"- Records with passwords: {export_df['password'].notna().sum()}")
print(f"- Records without passwords: {export_df['password'].isna().sum()}")