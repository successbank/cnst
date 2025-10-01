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

    cursor = connection.cursor(dictionary=True)

    # Test query
    query = "SELECT COUNT(*) as count FROM rebar_length_data WHERE spec_name = %s AND ABS(length - %s) < 0.01"
    cursor.execute(query, ('D10', 6.0))
    result = cursor.fetchone()
    print(f"Result: {result}")
    print(f"Count: {result['count']}")
    print(f"Exists: {result['count'] > 0}")

    # Insert a test record
    insert_query = """
        INSERT INTO rebar_length_data
        (spec_name, length, pieces_per_length, created_at)
        VALUES (%s, %s, %s, NOW())
    """
    cursor.execute(insert_query, ('D10', 6.0, 300))
    connection.commit()
    print("Test record inserted")

    # Check again
    cursor.execute(query, ('D10', 6.0))
    result = cursor.fetchone()
    print(f"After insert - Count: {result['count']}")

    connection.close()

except Exception as e:
    print(f"Error: {e}")
    import traceback
    traceback.print_exc()