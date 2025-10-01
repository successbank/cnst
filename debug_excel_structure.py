#!/usr/bin/env python3
import pandas as pd

def debug_excel_structure(file_path):
    """Debug Excel file structure"""
    try:
        # Read Excel file
        xl_file = pd.ExcelFile(file_path)
        print(f"Sheet names: {xl_file.sheet_names}")

        # Read first sheet
        df = xl_file.parse(xl_file.sheet_names[0], header=None)
        print(f"\nDataFrame shape: {df.shape}")

        # Show first 10 rows and columns
        print("\nFirst 10 rows and 10 columns:")
        print(df.iloc[:10, :10])

        # Check row 1 (index 0) - might be header
        print("\n\nRow 1 (index 0):")
        for i in range(min(15, len(df.columns))):
            print(f"Col {i}: {df.iloc[0, i]}")

        # Check row 2 (index 1) - spec names
        print("\n\nRow 2 (index 1):")
        for i in range(min(15, len(df.columns))):
            print(f"Col {i}: {df.iloc[1, i]}")

        # Check row 3 (index 2) - headers
        print("\n\nRow 3 (index 2):")
        for i in range(min(15, len(df.columns))):
            print(f"Col {i}: {df.iloc[2, i]}")

        # Check row 4 (index 3) - first data row
        print("\n\nRow 4 (index 3):")
        for i in range(min(15, len(df.columns))):
            print(f"Col {i}: {df.iloc[3, i]}")

        # Check row 5 (index 4) - second data row
        print("\n\nRow 5 (index 4):")
        for i in range(min(15, len(df.columns))):
            print(f"Col {i}: {df.iloc[4, i]}")

    except Exception as e:
        print(f"Error: {e}")
        import traceback
        traceback.print_exc()

if __name__ == "__main__":
    excel_file = "/home/successbank/projects/docker/project1/html/114/2/철근.xlsx"
    debug_excel_structure(excel_file)