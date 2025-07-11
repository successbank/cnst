#!/usr/bin/env python3
import pandas as pd
import sys
import json

def extract_h_beam_data(file_path):
    try:
        # Read the Excel file
        df = pd.read_excel(file_path, sheet_name=None)
        
        # Print sheet names
        print("Available sheets:", list(df.keys()))
        print("\n")
        
        # Process each sheet
        all_data = {}
        for sheet_name, sheet_data in df.items():
            print(f"=== Sheet: {sheet_name} ===")
            print(f"Shape: {sheet_data.shape}")
            print(f"Columns: {list(sheet_data.columns)}")
            print("\nFirst 10 rows:")
            print(sheet_data.head(10))
            print("\n")
            
            # Store the data
            all_data[sheet_name] = sheet_data.to_dict('records')
        
        # Save to JSON for structured output
        with open('h_beam_data.json', 'w', encoding='utf-8') as f:
            json.dump(all_data, f, ensure_ascii=False, indent=2)
        
        print("\nData saved to h_beam_data.json")
        
    except Exception as e:
        print(f"Error reading Excel file: {e}")
        sys.exit(1)

if __name__ == "__main__":
    file_path = "/home/successbank/projects/docker/project1/html/114/3/경량 H 형강.xlsx"
    extract_h_beam_data(file_path)