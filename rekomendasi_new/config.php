<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rekomendasi');

define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', 'admin123'); 

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function has_table($table) {
    global $conn;
    $table = mysqli_real_escape_string($conn, $table);
    try {
        $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        return $res && mysqli_num_rows($res) > 0;
    } catch (mysqli_sql_exception $e) {
        return false;
    }
}

function has_column($table, $column) {
    global $conn;
    if (!has_table($table)) return false;
    $table  = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    try {
        $res = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $res && mysqli_num_rows($res) > 0;
    } catch (mysqli_sql_exception $e) {
        return false;
    }
}

function table_exists($table) {
    return has_table($table);
}

// Check which tables actually exist in database
$HAS_TABLE_SEKOLAH   = has_table('sekolah');
$HAS_TABLE_KECAMATAN = has_table('kecamatan');
$HAS_TABLE_PETA      = has_table('peta');
$HAS_TABLE_DESA      = has_table('desa_new'); // Use desa_new instead of desa

/* ================================
   HELPER FUNCTIONS
================================ */

function query($sql) {
    global $conn;
    try {
        return mysqli_query($conn, $sql);
    } catch (mysqli_sql_exception $e) {
        error_log('DB Query Error: ' . $e->getMessage() . " -- SQL: " . $sql);
        return false;
    }
}

function fetch_all($sql) {
    $result = query($sql);
    if (!$result) return [];
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function fetch_row($sql) {
    $result = query($sql);
    if (!$result) return null;
    return mysqli_fetch_assoc($result);
}

function escape($str) {
    global $conn;
    return mysqli_real_escape_string($conn, $str);
}

function count_rows($sql) {
    $result = query($sql);
    if (!$result) return 0;
    $row = mysqli_fetch_assoc($result);
    return isset($row['total']) ? (int)$row['total'] : 0;
}
?>
