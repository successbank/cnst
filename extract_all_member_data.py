#!/usr/bin/env python3
import pandas as pd
import json

# Read HTML table
dfs = pd.read_html('./114/member.xls')
df = dfs[0]

# First row contains column names
column_names = df.iloc[0].tolist()

# Set proper column names and remove header row
df.columns = column_names
df = df.iloc[1:].reset_index(drop=True)

print(f"Total records: {len(df)}")
print("\nAll columns:")
for i, col in enumerate(column_names):
    print(f"{i}: {col}")

# Save all member data to JSON for import
members = []

for idx, row in df.iterrows():
    member = {
        'user_id': str(row.get('MB_ID', '')),
        'password': str(row.get('MB_PW', '')),
        'name': str(row.get('MB_NM', '')),
        'email': str(row.get('MB_EMAIL', '')),
        'phone': str(row.get('MB_HP', '')),
        'company': str(row.get('MB_COM_NM', '')),
        'address': str(row.get('MB_ADDR1', '')),
        'address_detail': str(row.get('MB_ADDR2', '')),
        'homepage': str(row.get('MB_URL', '')),
        'zip_code': str(row.get('MB_ZIP', '')),
        'memo': str(row.get('MB_MEMO', '')),
        'join_date': str(row.get('MB_IN_DT', '')),
        'last_login': str(row.get('MB_LOG_DT', '')),
        'level': str(row.get('MB_LEVEL', ''))
    }
    
    # Clean up the data
    for key in member:
        if member[key] == 'nan' or member[key] == 'None':
            member[key] = ''
            
    # Skip if no user_id
    if not member['user_id']:
        continue
        
    members.append(member)

print(f"\nTotal valid members: {len(members)}")

# Save to JSON
with open('all_members_data.json', 'w', encoding='utf-8') as f:
    json.dump(members, f, ensure_ascii=False, indent=2)

print("Saved to all_members_data.json")

# Show sample data
print("\nSample member data:")
for member in members[:5]:
    print(f"ID: {member['user_id']:<15} Name: {member['name']:<10} Company: {member['company']:<20} Email: {member['email']}")