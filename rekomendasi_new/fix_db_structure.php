<?php
/**
 * Database Structure Fix Script
 * Mengatasi masalah Duplicate Key pada sekolah table
 */

require_once 'config.php';

$output = [];
$fixed = false;
$tables_info = [];

// ===== Check sekolah table =====
$output[] = "=== CHECKING SEKOLAH TABLE ===\n";

// Get indexes
$indexes = mysqli_query($conn, "SHOW INDEXES FROM sekolah WHERE Column_name = 'id_kecamatan'");
if ($indexes && mysqli_num_rows($indexes) > 0) {
    while ($idx = mysqli_fetch_assoc($indexes)) {
        $tables_info['sekolah_indexes'][] = $idx;
        
        if ($idx['Non_unique'] == 0 && $idx['Key_name'] !== 'PRIMARY') {
            $output[] = "⚠️  DITEMUKAN: UNIQUE constraint pada id_kecamatan (Key: {$idx['Key_name']})";
            
            // Attempt to drop the constraint
            $drop_query = "ALTER TABLE sekolah DROP INDEX {$idx['Key_name']}";
            if (mysqli_query($conn, $drop_query)) {
                $output[] = "✓ BERHASIL: Menghapus constraint {$idx['Key_name']}";
                $fixed = true;
            } else {
                $output[] = "✗ GAGAL: " . mysqli_error($conn);
            }
        }
    }
} else {
    $output[] = "✓ Tidak ditemukan UNIQUE constraint pada id_kecamatan";
}

// Get current table structure
$result = mysqli_query($conn, "SHOW COLUMNS FROM sekolah");
$output[] = "\n=== SEKOLAH TABLE STRUCTURE ===";
if ($result) {
    $output[] = sprintf("%-20s %-20s %-15s %-10s", "Field", "Type", "Key", "Default");
    $output[] = str_repeat("-", 65);
    
    while ($col = mysqli_fetch_assoc($result)) {
        $output[] = sprintf("%-20s %-20s %-15s %-10s", 
            $col['Field'],
            $col['Type'],
            $col['Key'] ?: 'N/A',
            $col['Default'] ?: 'NULL'
        );
    }
}

// Check for duplicate schools
$output[] = "\n=== CHECKING FOR DUPLICATE ENTRIES ===";
$duplicates = mysqli_query($conn, "
    SELECT id_kecamatan, COUNT(*) as count 
    FROM sekolah 
    GROUP BY id_kecamatan 
    HAVING count > 1
");

if ($duplicates && mysqli_num_rows($duplicates) > 0) {
    $output[] = "⚠️  DITEMUKAN: Beberapa id_kecamatan dengan multiple entries:";
    while ($dup = mysqli_fetch_assoc($duplicates)) {
        $output[] = "  - id_kecamatan {$dup['id_kecamatan']}: {$dup['count']} sekolah";
        
        // Show schools with same id_kecamatan
        $schools = mysqli_query($conn, "SELECT id_sekolah, nama_sekolah, id_kecamatan FROM sekolah WHERE id_kecamatan = {$dup['id_kecamatan']} ORDER BY id_sekolah");
        while ($school = mysqli_fetch_assoc($schools)) {
            $output[] = "    → #{$school['id_sekolah']}: {$school['nama_sekolah']}";
        }
    }
    $output[] = "\nINI NORMAL! Bisa ada multiple sekolah dalam satu kecamatan.";
} else {
    $output[] = "✓ Tidak ditemukan duplicate id_kecamatan";
}

// ===== SQL Commands =====
$output[] = "\n=== SQL FIX COMMANDS (Jika diperlukan) ===";
$output[] = "1. Jika masih ada error, jalankan command ini di phpMyAdmin:";
$output[] = "\n-- Show current indexes:";
$output[] = "SHOW INDEXES FROM sekolah;";
$output[] = "\n-- If found UNIQUE on id_kecamatan, drop it:";
$output[] = "ALTER TABLE sekolah DROP INDEX index_name; -- ganti dengan nama index yang sebenarnya";
$output[] = "\n-- Check PRIMARY KEY structure:";
$output[] = "SHOW CREATE TABLE sekolah;";

// ===== Summary =====
$output[] = "\n=== SUMMARY ===";
if ($fixed) {
    $output[] = "✓ Database fixes applied successfully!";
} else {
    $output[] = "✓ No automatic fixes needed or already fixed.";
}
$output[] = "Silakan coba submit form kembali.";

$html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Database Diagnostic & Fix</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container { 
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #1a3a5c;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .status { 
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid;
            font-weight: 600;
        }
        .success { 
            background: #d4edda;
            color: #155724;
            border-left-color: #2ecc71;
        }
        .warning { 
            background: #fff3cd;
            color: #856404;
            border-left-color: #f39c12;
        }
        .error { 
            background: #f8d7da;
            color: #721c24;
            border-left-color: #e74c3c;
        }
        pre { 
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            border: 1px solid #dee2e6;
            font-size: 13px;
            line-height: 1.6;
            color: #333;
        }
        .options {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        a, button {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #e9ecef;
            color: #333;
            border: 2px solid #dee2e6;
        }
        .btn-secondary:hover {
            background: #dee2e6;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 15px 0;
            border-radius: 6px;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Diagnostic & Fix Tool</h1>
        <p style="color: #666; margin-bottom: 20px;">Tools untuk membantu diagnosa dan memperbaiki masalah struktur database</p>
        
        <div class="status {status_class}">
            {status_message}
        </div>

        <div class="info-box">
            <strong>ℹ️ Informasi:</strong><br>
            Masalah "Duplicate entry for key 'id_kecamatan'" terjadi karena tabel sekolah memiliki constraint UNIQUE pada id_kecamatan, 
            padahal bisa ada multiple sekolah dalam satu kecamatan.
        </div>
        
        <h3 style="margin-top: 25px; margin-bottom: 15px; color: #1a3a5c;">Diagnostic Output:</h3>
        <pre>{output}</pre>

        <div class="options">
            <a href="input_data.php" class="btn-primary">← Kembali ke Form Input</a>
            <button onclick="location.reload()" class="btn-secondary">🔄 Refresh Diagnostic</button>
        </div>
    </div>
</body>
</html>
HTML;

// Determine status
if ($fixed) {
    $status_message = "✅ Berhasil! Database structure telah diperbaiki. Silakan kembali ke form input dan coba submit data sekolah lagi.";
    $status_class = "success";
} else {
    $status_message = "ℹ️ Diagnostic selesai. Jika masih ada error saat submit, jalankan SQL commands di bagian atas phpMyAdmin.";
    $status_class = "warning";
}

$output_text = implode("\n", $output);
$html = str_replace(
    ['{status_class}', '{status_message}', '{output}'],
    [$status_class, $status_message, htmlspecialchars($output_text)],
    $html
);

echo $html;

mysqli_close($conn);
?>
