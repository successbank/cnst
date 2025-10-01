#!/usr/bin/env python3
import pandas as pd
import mysql.connector
from mysql.connector import Error
import sys

def connect_to_database():
    """Create database connection"""
    try:
        connection = mysql.connector.connect(
            host='127.0.0.1',
            port=3306,
            database='project1_db',
            user='root',
            password='rootpassword'
        )
        return connection
    except Error as e:
        print(f"Error connecting to database: {e}")
        sys.exit(1)

def parse_excel_file(file_path):
    """Parse Excel file and extract rebar specifications with their data"""
    try:
        # Read Excel file with all sheets
        xl_file = pd.ExcelFile(file_path)

        # Assume data is in the first sheet
        df = xl_file.parse(xl_file.sheet_names[0], header=None)

        print(f"Excel shape: {df.shape}")

        # Analyze row 3 (index 2) for spec names
        spec_row = df.iloc[2]

        # Find columns with spec data (format: "D10 / 0.56")
        specs_data = []

        # Check each column for spec pattern
        for col_idx in range(2, len(spec_row)):
            cell_value = str(spec_row[col_idx])
            if '/' in cell_value and 'D' in cell_value:
                # Extract spec name (e.g., "D10" from "D10 / 0.56")
                spec_name = cell_value.split('/')[0].strip()

                # The 본수 (pieces) column is at col_idx + 1
                pieces_col_idx = col_idx + 1

                specs_data.append({
                    'spec_name': spec_name,
                    'pieces_col': pieces_col_idx
                })

                print(f"Found spec: {spec_name} at column {col_idx}, pieces at column {pieces_col_idx}")

        # Extract data for each spec
        all_data = []

        # Data starts from row 5 (index 4)
        for row_idx in range(4, len(df)):
            length_value = df.iloc[row_idx, 1]  # Length is in column 1

            # Skip if length is not a number
            try:
                length = float(length_value)
            except (ValueError, TypeError):
                continue

            # Only process lengths between 6 and 12
            if 6 <= length <= 12:
                for spec_info in specs_data:
                    pieces_value = df.iloc[row_idx, spec_info['pieces_col']]

                    # Skip if pieces is not a number
                    try:
                        pieces = int(float(pieces_value))

                        all_data.append({
                            'spec_name': spec_info['spec_name'],
                            'length': length,
                            'pieces_per_length': pieces
                        })
                    except (ValueError, TypeError):
                        continue

        print(f"Extracted {len(all_data)} data points for lengths 6m-12m")
        return all_data

    except Exception as e:
        print(f"Error parsing Excel file: {e}")
        import traceback
        traceback.print_exc()
        return []

def check_existing_record(cursor, spec_name, length):
    """Check if a record already exists in the database"""
    try:
        query = "SELECT COUNT(*) as count FROM rebar_length_data WHERE spec_name = %s AND ABS(length - %s) < 0.01"
        cursor.execute(query, (spec_name, length))
        result = cursor.fetchone()
        return result['count'] > 0
    except Exception as e:
        print(f"Error checking record: {e}")
        return False

def insert_rebar_data(connection, data):
    """Insert rebar data into database, skipping existing records"""
    cursor = connection.cursor(dictionary=True)

    inserted = 0
    skipped = 0
    errors = 0

    for item in data:
        try:
            # Check if record exists
            if check_existing_record(cursor, item['spec_name'], item['length']):
                skipped += 1
                print(f"Skipped existing: {item['spec_name']} at {item['length']}m")
                continue

            # Insert new record
            insert_query = """
                INSERT INTO rebar_length_data
                (spec_name, length, pieces_per_length, created_at)
                VALUES (%s, %s, %s, NOW())
            """
            cursor.execute(insert_query, (
                item['spec_name'],
                item['length'],
                item['pieces_per_length']
            ))
            inserted += 1
            print(f"Inserted: {item['spec_name']} at {item['length']}m with {item['pieces_per_length']} pieces")

        except Error as e:
            print(f"Error inserting {item}: {e}")
            errors += 1

    # Commit the transaction
    connection.commit()

    return inserted, skipped, errors

def main():
    """Main function to orchestrate the import process"""
    excel_file = "/home/successbank/projects/docker/project1/html/114/2/철근.xlsx"

    print("=" * 60)
    print("Rebar Length Data Import Script")
    print("=" * 60)

    # Parse Excel file
    print("\n1. Parsing Excel file...")
    data = parse_excel_file(excel_file)

    if not data:
        print("No data extracted from Excel file")
        return

    # Connect to database
    print("\n2. Connecting to database...")
    connection = connect_to_database()

    try:
        # Insert data
        print("\n3. Inserting data into database...")
        inserted, skipped, errors = insert_rebar_data(connection, data)

        # Report statistics
        print("\n" + "=" * 60)
        print("IMPORT STATISTICS")
        print("=" * 60)
        print(f"Total records processed: {len(data)}")
        print(f"Records inserted: {inserted}")
        print(f"Records skipped (already exist): {skipped}")
        print(f"Errors: {errors}")
        print("=" * 60)

    finally:
        # Close connection
        if connection.is_connected():
            connection.close()
            print("\nDatabase connection closed")

if __name__ == "__main__":
    main()