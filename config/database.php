<?php
/**
 * Database Configuration & Helper
 * SPMI System - Final Fixed Version
 */

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'spmi_system');
define('DB_CHARSET', 'utf8mb4');

// Koneksi Global
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
$conn->set_charset(DB_CHARSET);

/*
|--------------------------------------------------------------------------
| FUNGSI LAMA - Backward Compatible (RAW QUERY)
| HATI-HATI: Tidak menggunakan prepared statement
|--------------------------------------------------------------------------
*/

/**
 * Query SELECT lama - return array
 * Contoh: query("SELECT * FROM users WHERE id = 1")
 */
function query(string $sql): array {
    global $conn;
    $result = $conn->query($sql);
    $rows = [];
    
    if ($result && $result !== true) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
    }
    
    return $rows;
}

/**
 * Execute INSERT/UPDATE/DELETE lama
 * Contoh: execute("INSERT INTO users (nama) VALUES ('John')")
 */
function execute(string $sql): bool {
    global $conn;
    return $conn->query($sql);
}

/**
 * Escape string lama
 * Contoh: escape($_POST['nama'])
 */
function escape(string $string): string {
    global $conn;
    return $conn->real_escape_string($string);
}

/*
|--------------------------------------------------------------------------
| FUNGSI BARU - Prepared Statements (RECOMMENDED)
| Gunakan fungsi ini untuk keamanan lebih baik
|--------------------------------------------------------------------------
*/

/**
 * SELECT dengan Prepared Statement
 * Contoh: db_select("SELECT * FROM users WHERE id = ?", [5])
 */
function db_select(string $sql, array $params = []): array {
    global $conn;
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return [];
    }
    
    if (!empty($params)) {
        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_double($p) ? 'd' : 's');
        }
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    $stmt->close();
    return $rows;
}

/**
 * SELECT Single Row
 * Contoh: db_select_one("SELECT * FROM users WHERE email = ?", [$email])
 */
function db_select_one(string $sql, array $params = []): ?array {
    $result = db_select($sql, $params);
    return $result[0] ?? null;
}

/**
 * INSERT dengan Prepared Statement
 * Contoh: db_insert('users', ['nama' => 'John', 'email' => 'john@mail.com'])
 */
function db_insert(string $table, array $data) {
    global $conn;
    
    if (empty($data)) return false;
    
    $columns = implode('`, `', array_keys($data));
    $placeholders = rtrim(str_repeat('?, ', count($data)), ', ');
    $sql = "INSERT INTO `{$table}` (`{$columns}`) VALUES ({$placeholders})";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    
    $types = '';
    $values = [];
    foreach ($data as $val) {
        $types .= is_int($val) ? 'i' : (is_double($val) ? 'd' : 's');
        $values[] = $val;
    }
    
    $stmt->bind_param($types, ...$values);
    $success = $stmt->execute();
    $insertId = $stmt->insert_id;
    $stmt->close();
    
    return $success ? $insertId : false;
}

/**
 * UPDATE dengan Prepared Statement
 * Contoh: db_update('users', ['nama' => 'Jane'], 'id = ?', [5])
 */
function db_update(string $table, array $data, string $where, array $whereParams = []): int|false {
    global $conn;
    
    if (empty($data)) return false;
    
    $setParts = [];
    foreach (array_keys($data) as $col) {
        $setParts[] = "`{$col}` = ?";
    }
    $setClause = implode(', ', $setParts);
    
    $sql = "UPDATE `{$table}` SET {$setClause} WHERE {$where}";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    
    $allValues = array_merge(array_values($data), $whereParams);
    $types = '';
    foreach ($allValues as $val) {
        $types .= is_int($val) ? 'i' : (is_double($val) ? 'd' : 's');
    }
    
    $stmt->bind_param($types, ...$allValues);
    $success = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return $success ? $affected : false;
}

/**
 * DELETE dengan Prepared Statement
 * Contoh: db_delete('users', 'id = ?', [5])
 */
function db_delete(string $table, string $where, array $params = []): int|false {
    global $conn;
    
    $sql = "DELETE FROM `{$table}` WHERE {$where}";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    
    if (!empty($params)) {
        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_double($p) ? 'd' : 's');
        }
        $stmt->bind_param($types, ...$params);
    }
    
    $success = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return $success ? $affected : false;
}

/**
 * Get last insert ID
 */
function db_last_id(): int {
    global $conn;
    return (int)$conn->insert_id;
}
?>