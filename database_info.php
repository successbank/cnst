<?php
require_once 'db.php';

echo "================================================================================\n";
echo "PROJECT DATABASE INFORMATION\n";
echo "================================================================================\n\n";

// Database connection info
echo "1. DATABASE CONNECTION INFO\n";
echo "----------------------------\n";
echo "Host: " . DB_HOST . "\n";
echo "Port: " . DB_PORT . "\n";
echo "Database: " . DB_NAME . "\n";
echo "User: " . DB_USER . "\n";
echo "Password: " . str_repeat('*', strlen(DB_PASS)) . "\n\n";

try {
    $pdo = getDB();

    // Get all tables
    echo "2. DATABASE TABLES\n";
    echo "----------------------------\n";
    $sql = "SHOW TABLES";
    $stmt = $pdo->query($sql);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $total_rows = 0;
    foreach ($tables as $table) {
        // Get row count
        $count_sql = "SELECT COUNT(*) FROM `$table`";
        $count_stmt = $pdo->query($count_sql);
        $count = $count_stmt->fetchColumn();
        $total_rows += $count;

        echo sprintf("%-30s : %6d rows\n", $table, $count);
    }
    echo "----------------------------\n";
    echo sprintf("Total: %d tables, %d rows\n\n", count($tables), $total_rows);

    // Get detailed table structure
    echo "3. TABLE STRUCTURES\n";
    echo "================================================================================\n\n";

    foreach ($tables as $table) {
        echo "TABLE: $table\n";
        echo str_repeat('-', 80) . "\n";

        // Get columns
        $sql = "SHOW COLUMNS FROM `$table`";
        $stmt = $pdo->query($sql);
        $columns = $stmt->fetchAll();

        echo "Columns:\n";
        foreach ($columns as $col) {
            $nullable = $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
            $key = '';
            if ($col['Key'] === 'PRI') $key = ' [PRIMARY KEY]';
            elseif ($col['Key'] === 'UNI') $key = ' [UNIQUE]';
            elseif ($col['Key'] === 'MUL') $key = ' [INDEX]';

            echo sprintf("  %-25s %-20s %-10s %s%s\n",
                $col['Field'],
                $col['Type'],
                $nullable,
                $col['Default'] ? "DEFAULT: " . $col['Default'] : "",
                $key
            );
        }

        // Get indexes
        $sql = "SHOW INDEXES FROM `$table`";
        $stmt = $pdo->query($sql);
        $indexes = $stmt->fetchAll();

        if (count($indexes) > 0) {
            $index_groups = [];
            foreach ($indexes as $idx) {
                $index_groups[$idx['Key_name']][] = $idx['Column_name'];
            }

            if (count($index_groups) > 0) {
                echo "\nIndexes:\n";
                foreach ($index_groups as $idx_name => $columns) {
                    echo "  - $idx_name: " . implode(', ', $columns) . "\n";
                }
            }
        }

        // Get foreign keys
        $sql = "SELECT
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table
        AND REFERENCED_TABLE_NAME IS NOT NULL";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['db' => DB_NAME, 'table' => $table]);
        $fks = $stmt->fetchAll();

        if (count($fks) > 0) {
            echo "\nForeign Keys:\n";
            foreach ($fks as $fk) {
                echo sprintf("  - %s: %s -> %s.%s\n",
                    $fk['CONSTRAINT_NAME'],
                    $fk['COLUMN_NAME'],
                    $fk['REFERENCED_TABLE_NAME'],
                    $fk['REFERENCED_COLUMN_NAME']
                );
            }
        }

        echo "\n";
    }

    // Database size info
    echo "4. DATABASE SIZE INFO\n";
    echo "================================================================================\n";

    $sql = "SELECT
        table_schema AS 'Database',
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
    FROM information_schema.TABLES
    WHERE table_schema = :db
    GROUP BY table_schema";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['db' => DB_NAME]);
    $size_info = $stmt->fetch();

    echo "Database: " . $size_info['Database'] . "\n";
    echo "Total Size: " . $size_info['Size (MB)'] . " MB\n\n";

    // Get table sizes
    $sql = "SELECT
        table_name AS 'Table',
        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)',
        table_rows AS 'Rows'
    FROM information_schema.TABLES
    WHERE table_schema = :db
    ORDER BY (data_length + index_length) DESC
    LIMIT 10";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['db' => DB_NAME]);
    $table_sizes = $stmt->fetchAll();

    echo "Top 10 Largest Tables:\n";
    echo "----------------------------\n";
    foreach ($table_sizes as $ts) {
        echo sprintf("%-30s : %8.2f MB (%d rows)\n",
            $ts['Table'],
            $ts['Size (MB)'],
            $ts['Rows']
        );
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n================================================================================\n";
echo "END OF DATABASE INFORMATION\n";
echo "================================================================================\n";
?>