#!/usr/bin/env python3
import mysql.connector

try:
    connection = mysql.connector.connect(
        host='127.0.0.1',
        port=3306,
        database='project1_db',
        user='root',
        password='rootpassword'
    )

    cursor = connection.cursor()

    # Count all records
    cursor.execute("SELECT COUNT(*) FROM rebar_length_data")
    count = cursor.fetchone()[0]
    print(f"Total records in rebar_length_data: {count}")

    # Get first 10 records
    cursor.execute("SELECT * FROM rebar_length_data LIMIT 10")
    records = cursor.fetchall()

    if records:
        print("\nFirst 10 records:")
        for record in records:
            print(f"  ID: {record[0]}, Spec: {record[1]}, Length: {record[2]}, Pieces: {record[4]}")
    else:
        print("\nNo records found")

    connection.close()

except Exception as e:
    print(f"Error: {e}")