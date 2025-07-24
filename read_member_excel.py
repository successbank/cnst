#!/usr/bin/env python3
import pandas as pd
import sys

try:
    # Try reading as Excel file
    df = pd.read_excel('./114/member.xls')
    print("Excel file loaded successfully!")
    print(f"Shape: {df.shape}")
    print("\nColumns:", list(df.columns))
    print("\nFirst 10 rows:")
    print(df.head(10))
    
    # Check if id and password columns exist
    if 'id' in df.columns and 'password' in df.columns:
        print("\n✓ Found 'id' and 'password' columns")
        print(f"Total records: {len(df)}")
        # Show sample data (first 5 rows)
        print("\nSample data:")
        print(df[['id', 'password']].head())
    else:
        print("\nAvailable columns:", list(df.columns))
        print("Looking for id/password related columns...")
        for col in df.columns:
            if 'id' in col.lower() or 'pass' in col.lower():
                print(f"  - {col}")
                
except Exception as e:
    print(f"Error reading as Excel: {e}")
    
    # Try reading as HTML table
    try:
        print("\nTrying to read as HTML...")
        dfs = pd.read_html('./114/member.xls')
        print(f"Found {len(dfs)} tables in HTML")
        for i, df in enumerate(dfs):
            print(f"\nTable {i+1}:")
            print(f"Shape: {df.shape}")
            print("Columns:", list(df.columns))
            print(df.head())
    except Exception as e2:
        print(f"Error reading as HTML: {e2}")