#!/usr/bin/env python3
import pandas as pd
import mysql.connector
from mysql.connector import Error
import json

def connect_to_database():
    """Connect to MySQL database"""
    try:
        connection = mysql.connector.connect(
            host='127.0.0.1',
            database='project1_db',
            user='root',
            password='rootpassword'
        )
        if connection.is_connected():
            print("Successfully connected to database")
            return connection
    except Error as e:
        print(f"Error connecting to MySQL: {e}")
        return None

def import_light_hbeam_data():
    """Import light H-beam data from Excel"""
    excel_file = '/home/successbank/projects/docker/project1/html/114/11/경량H형강.xlsx'

    try:
        # Read Excel file
        df = pd.read_excel(excel_file, engine='openpyxl')
        print(f"Read {len(df)} rows from Excel file")
        print("\nColumns:", df.columns.tolist())
        print("\nFirst few rows:")
        print(df.head())

        # Connect to database
        connection = connect_to_database()
        if not connection:
            return

        cursor = connection.cursor()

        # Standard materials for light H-beam (same as H-beam)
        standard_materials = [
            'SS400',
            'SS400/A36',
            'SHN400',
            'SS490',
            'SS540',
            'SM400A',
            'SM400B',
            'SHN490',
            'SM490A',
            'SM490B',
            'SM490YA',
            'SM490YB'
        ]
        materials_json = json.dumps(standard_materials)

        # Light H-beam uses category_code 'light-h-beam'
        print("Using category_code: light-h-beam")

        # Process each row
        inserted = 0
        updated = 0

        for index, row in df.iterrows():
            # Extract specification (second column: 규격)
            spec = str(row['규격']).strip() if pd.notna(row['규격']) else None
            # Extract unit weight (third column: 단위중량(kg))
            unit_weight = float(row['단위중량(kg)']) if pd.notna(row['단위중량(kg)']) and row['단위중량(kg)'] != '' else 0

            if not spec or unit_weight == 0:
                print(f"Skipping row {index}: Missing spec or weight")
                continue

            product_name = f"경량H형강 LHB {spec}"

            # Check if product already exists
            cursor.execute("""
                SELECT id FROM products
                WHERE category_code = 'light-h-beam' AND specification = %s
            """, (spec,))
            existing = cursor.fetchone()

            if existing:
                # Update existing product
                cursor.execute("""
                    UPDATE products
                    SET specification_weight = %s,
                        calculation_type = 'linear',
                        available_materials = %s,
                        product_name = %s
                    WHERE id = %s
                """, (unit_weight, materials_json, product_name, existing[0]))
                updated += 1
            else:
                # Insert new product
                cursor.execute("""
                    INSERT INTO products
                    (product_name, specification, specification_weight,
                     category_code, calculation_type, available_materials, has_calculator)
                    VALUES (%s, %s, %s, 'light-h-beam', 'linear', %s, 1)
                """, (product_name, spec, unit_weight, materials_json))
                inserted += 1

            if (index + 1) % 10 == 0:
                print(f"Processed {index + 1} rows...")

        connection.commit()
        print(f"\n=== Import Complete ===")
        print(f"Inserted: {inserted} products")
        print(f"Updated: {updated} products")
        print(f"Total: {inserted + updated} products")

        # Verify
        cursor.execute("SELECT COUNT(*) FROM products WHERE category_code = 'light-h-beam'")
        total = cursor.fetchone()[0]
        print(f"\nTotal light H-beam products in database: {total}")

        cursor.close()
        connection.close()

    except Exception as e:
        print(f"Error: {e}")
        import traceback
        traceback.print_exc()

if __name__ == "__main__":
    import_light_hbeam_data()