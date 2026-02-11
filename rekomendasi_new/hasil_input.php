<?php
require_once 'config.php';

// Cek pesan dari URL parameter
$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';
$data_type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'sekolah';
$new_data_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$notification_type = '';
$notification_message = '';

if ($msg === 'added') {
    $notification_type = 'success';
    if ($data_type === 'kantor') {
        $notification_message = '✓ Data Kantor Desa berhasil ditambahkan ke database!';
    } else {
        $notification_message = '✓ Data Sekolah berhasil ditambahkan ke database!';
    }
} elseif ($msg === 'deleted') {
    $notification_type = 'success';
    $notification_message = '✓ Data berhasil dihapus!';
} elseif ($msg === 'updated') {
    $notification_type = 'success';
    $notification_message = '✓ Data berhasil diperbarui!';
}

// Helper: cek apakah tabel ada di database
if (!function_exists('table_exists')) {
    function table_exists($table) {
        global $conn;
        try {
            $result = mysqli_query($conn, "SHOW TABLES LIKE '" . mysqli_real_escape_string($conn, $table) . "'");
            return $result && mysqli_num_rows($result) > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Helper: cari nama kolom pertama yang ada dari daftar kandidat (untuk kompatibilitas schema)
function first_existing_column($table, $candidates) {
    if (!table_exists($table)) {
        return null;
    }
    $cols = fetch_all("SHOW COLUMNS FROM `" . $table . "`");
    $fields = array_column($cols, 'Field');
    foreach ($candidates as $c) {
        if (in_array($c, $fields)) return $c;
    }
    return null;
}

// Tentukan nama kolom koordinat untuk setiap tabel (mendukung variasi seperti 'latitude','lintang', 'longtitude','bujur','longtitude')
$sk_lat_col = first_existing_column('sekolah', ['latitude','lat','lintang']);
$sk_long_col = first_existing_column('sekolah', ['longtitude','lon','bujur','longtitude']);
$kec_lat_col = first_existing_column('kecamatan', ['latitude','lat','lintang']);
$kec_long_col = first_existing_column('kecamatan', ['longtitude','lon','bujur','longtitude']);

$search = escape($_GET['search'] ?? '');
// Support per-page selection and 'all' to show everything
$per_page = isset($_GET['per_page']) ? ($_GET['per_page'] === 'all' ? 0 : max(0, (int)$_GET['per_page'])) : 10;
if ($per_page === 0) {
    $page = 1;
    $offset = 0;
} else {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * max(1, $per_page);
}

// Periksa kolom di tabel kecamatan untuk menambahkan latitude/longtitude jika tersedia
$kec_select_extra = '';
if (table_exists('kecamatan')) {
    $kec_columns = fetch_all("SHOW COLUMNS FROM kecamatan");
    $kec_fields = array_column($kec_columns, 'Field');
    $kec_select_extra .= in_array('latitude', $kec_fields) ? ', k.latitude as kec_latitude' : ', NULL as kec_latitude';
    $kec_select_extra .= in_array('longtitude', $kec_fields) ? ', k.longtitude as kec_longtitude' : ', NULL as kec_longtitude';
} else {
    $kec_select_extra = ', NULL as kec_latitude, NULL as kec_longtitude';
}

// Build query (LEFT JOIN supaya sekolah tanpa kecamatan tetap tampil)
$query = "SELECT s.* , k.nama_kecamatan" . $kec_select_extra . " FROM sekolah s 
          LEFT JOIN kecamatan k ON s.id_kecamatan = k.id_kecamatan";

if ($search) {
    $q = $search; // sudah di-escape
    $query .= " WHERE (k.nama_kecamatan LIKE '%$q%' 
               OR s.nama_desa LIKE '%$q%' 
               OR s.nama_sekolah LIKE '%$q%'
               OR s.id_sekolah = '$q'
               OR s.id_kecamatan = '$q'
               OR s.tingkat_pendidikan LIKE '%$q%'
               OR s.alamat LIKE '%$q%'
               OR s.status LIKE '%$q%'
               OR s.npsn LIKE '%$q%')";
}

// Count total - gunakan LEFT JOIN agar semua sekolah terhitung
$count_sql = "SELECT COUNT(*) as total FROM sekolah s 
              LEFT JOIN kecamatan k ON s.id_kecamatan = k.id_kecamatan";
if ($search) {
    $q = $search;
    $count_sql .= " WHERE (k.nama_kecamatan LIKE '%$q%' 
                   OR s.nama_desa LIKE '%$q%' 
                   OR s.nama_sekolah LIKE '%$q%'
                   OR s.id_sekolah = '$q'
                   OR s.id_kecamatan = '$q'
                   OR s.tingkat_pendidikan LIKE '%$q%'
                   OR s.alamat LIKE '%$q%'
                   OR s.status LIKE '%$q%'
                   OR s.npsn LIKE '%$q%')";
}
$count_result = query($count_sql);
$total_data = mysqli_fetch_assoc($count_result)['total'];
if ($per_page === 0) {
    $total_pages = 1;
} else {
    $total_pages = ceil($total_data / $per_page);
} 

// Prepare ordered query
$order_query = $query . " ORDER BY s.id_sekolah DESC";

// Jika diminta export CSV, kembalikan seluruh data yang sesuai (abaikan limit)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $export_rows = fetch_all($order_query);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sekolah_export.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id_sekolah','id_kecamatan','nama_kecamatan','nama_desa','tingkat_pendidikan','nama_sekolah','npsn','status','alamat','latitude','longtitude']);
    foreach ($export_rows as $r) {
        fputcsv($out, [
            $r['id_sekolah'] ?? '',
            $r['id_kecamatan'] ?? '',
            $r['nama_kecamatan'] ?? '',
            $r['nama_desa'] ?? '',
            $r['tingkat_pendidikan'] ?? '',
            $r['nama_sekolah'] ?? '',
            $r['npsn'] ?? '',
            $r['status'] ?? '',
            $r['alamat'] ?? '',
            $r['latitude'] ?? '',
            $r['longtitude'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}

// Terapkan limit hanya jika per_page > 0
if ($per_page > 0) {
    $order_query .= " LIMIT $offset, $per_page";
}
$result = query($order_query);

// Get all data for statistics
$all_schools = fetch_all("SELECT * FROM sekolah");
$total_sekolah = count($all_schools);
$sekolah_swasta = count(fetch_all("SELECT id_sekolah FROM sekolah WHERE LOWER(COALESCE(status,'')) LIKE '%swasta%'") );
$sekolah_negeri = count(fetch_all("SELECT id_sekolah FROM sekolah WHERE LOWER(COALESCE(status,'')) LIKE '%negeri%'") );
// Count distinct kecamatan names from kecamatan table (trimmed)
$kecamatan_unik = count(fetch_all("SELECT DISTINCT TRIM(nama_kecamatan) as nama FROM kecamatan WHERE nama_kecamatan IS NOT NULL AND TRIM(nama_kecamatan) <> ''"));
// How many kecamatan ids are referenced by sekolah
$kecamatan_in_sekolah = count(fetch_all("SELECT DISTINCT id_kecamatan FROM sekolah WHERE id_kecamatan IS NOT NULL"));
$desa_unik = count(fetch_all("SELECT DISTINCT nama_desa FROM sekolah"));
// Berapa sekolah yang belum punya koordinat (hanya bila tabel sekolah punya kolom koordinat yang diperlukan)
if ($sk_lat_col && $sk_long_col) {
    $missing_coords_count = count(fetch_all("SELECT id_sekolah FROM sekolah WHERE ($sk_lat_col IS NULL OR $sk_long_col IS NULL OR $sk_lat_col = '' OR $sk_long_col = '' OR $sk_lat_col = 0 OR $sk_long_col = 0)"));
} else {
    // Jika kolom koordinat tidak ada di tabel sekolah, anggap semua belum punya koordinat
    $missing_coords_count = $total_sekolah;
}

// Handle delete dengan konfirmasi via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    query("DELETE FROM sekolah WHERE id_sekolah = $id");
    header('Location: hasil_input.php?msg=deleted');
    exit;
}

// Handle delete kantor_desa (disabled - table doesn't exist)
// Gunakan tabel desa_new untuk data desa/kantor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_kantor') {
    // Feature disabled - use desa_new table instead
    header('Location: hasil_input.php?msg=feature_unavailable');
    exit;
}

// Handle edit via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_edit_data') {
    $id = (int)$_POST['id'];
    $data = fetch_row("SELECT s.*, k.nama_kecamatan FROM sekolah s LEFT JOIN kecamatan k ON s.id_kecamatan = k.id_kecamatan WHERE s.id_sekolah = $id");
    // Normalisasi: pastikan bidang latitude/longtitude tersedia di response sebagai 'latitude' dan 'longtitude'
    if ($data) {
        if (!isset($data['latitude']) && $sk_lat_col && isset($data[$sk_lat_col])) $data['latitude'] = $data[$sk_lat_col];
        if (!isset($data['longtitude']) && $sk_long_col && isset($data[$sk_long_col])) $data['longtitude'] = $data[$sk_long_col];
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Handle get kantor_desa data (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_kantor_data') {
    $id = (int)$_POST['id'];
    $data = fetch_row("SELECT kd.*, k.nama_kecamatan FROM kantor_desa kd LEFT JOIN kecamatan k ON kd.id_kecamatan = k.id_kecamatan WHERE kd.id_kantor = $id");
    if ($data) {
        $kd_cols = array_column(fetch_all("SHOW COLUMNS FROM kantor_desa"), 'Field');
        $kd_lat = null; $kd_long = null;
        foreach (['latitude','lat','lintang'] as $c) { if (in_array($c, $kd_cols)) { $kd_lat = $c; break; } }
        foreach (['longtitude','Longtitude','longtitude','lon','bujur'] as $c) { if (in_array($c, $kd_cols)) { $kd_long = $c; break; } }
        if ($kd_lat && isset($data[$kd_lat])) $data['latitude'] = $data[$kd_lat];
        if ($kd_long && isset($data[$kd_long])) $data['longtitude'] = $data[$kd_long];
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Handle update kantor_desa (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_kantor') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    $id_kecamatan = (int)($_POST['id_kecamatan'] ?? 0);
    $nama_titik = escape(trim($_POST['nama_titik'] ?? ''));
    $desa = escape(trim($_POST['desa'] ?? ''));
    $latitude = trim($_POST['latitude'] ?? '');
    $longtitude = trim($_POST['longtitude'] ?? '');

    $errors = [];
    if ($id_kecamatan <= 0) $errors[] = 'Pilih Kecamatan';
    if ($nama_titik === '') $errors[] = 'Nama Titik wajib diisi';
    if ($desa === '') $errors[] = 'Nama Desa wajib diisi';
    if ($latitude !== '' && !is_numeric($latitude)) $errors[] = 'Latitude harus berupa angka';
    if ($longtitude !== '' && !is_numeric($longtitude)) $errors[] = 'Longtitude harus berupa angka';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $cols = [];
    $cols[] = "id_kecamatan = $id_kecamatan";
    $cols[] = "nama_titik = '" . $nama_titik . "'";
    $cols[] = "desa = '" . $desa . "'";

    $kd_cols = array_column(fetch_all("SHOW COLUMNS FROM kantor_desa"), 'Field');
    $kd_lat = null; $kd_long = null;
    foreach (['latitude','lat','lintang'] as $c) { if (in_array($c, $kd_cols)) { $kd_lat = $c; break; } }
    foreach (['longtitude','Longtitude','longtitude','lon','bujur'] as $c) { if (in_array($c, $kd_cols)) { $kd_long = $c; break; } }

    if ($kd_lat !== null) {
        if ($latitude === '') $cols[] = "$kd_lat = NULL"; else $cols[] = "$kd_lat = " . (float)$latitude;
    }
    if ($kd_long !== null) {
        if ($longtitude === '') $cols[] = "$kd_long = NULL"; else $cols[] = "$kd_long = " . (float)$longtitude;
    }

    $sql = "UPDATE kantor_desa SET " . implode(', ', $cols) . " WHERE id_kantor = $id";
    if (query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'errors' => ['Gagal memperbarui data kantor desa']]);
    }
    exit;
}

// Get kecamatan list (unique names only to avoid duplicates in selects)
$kecamatans = fetch_all("SELECT MIN(id_kecamatan) as id_kecamatan, TRIM(nama_kecamatan) as nama_kecamatan FROM kecamatan WHERE COALESCE(TRIM(nama_kecamatan),'') <> '' GROUP BY TRIM(nama_kecamatan) ORDER BY nama_kecamatan");

// Get kantor desa list (untuk rekomendasi)
$kantor_desa_map = [];
if (function_exists('table_exists') && table_exists('kantor_desa')) {
    $kantors_desa = fetch_all('SELECT * FROM kantor_desa');
    foreach ($kantors_desa as $kd) {
        $desa_name = strtoupper(trim($kd['desa']));
        if (!isset($kantor_desa_map[$desa_name])) {
            $kantor_desa_map[$desa_name] = $kd;
        }
    }
} else {
    // tabel kantor_desa tidak tersedia, tetapkan map kosong
    $kantors_desa = [];
}

// ===== QUERY DATA KECAMATAN (gunakan `desa_new` jika tersedia) =====

// Build map id_kecamatan => first nama_desa found in sekolah (if any)
$kec_to_desa = [];
$desa_rows_map = fetch_all("SELECT id_kecamatan, nama_desa FROM sekolah WHERE COALESCE(TRIM(nama_desa),'') <> '' ORDER BY id_kecamatan");
foreach ($desa_rows_map as $dr) {
    $idk = $dr['id_kecamatan'] ?? null;
    if ($idk !== null && !isset($kec_to_desa[$idk])) {
        $kec_to_desa[$idk] = $dr['nama_desa'];
    }
}

$kecamatan_search = escape($_GET['search_kecamatan'] ?? '');
$kecamatan_per_page = isset($_GET['per_page_kecamatan']) ? ($_GET['per_page_kecamatan'] === 'all' ? 0 : max(0, (int)$_GET['per_page_kecamatan'])) : 10;
if ($kecamatan_per_page === 0) {
    $kecamatan_page = 1;
    $kecamatan_offset = 0;
} else {
    $kecamatan_page = isset($_GET['page_kecamatan']) ? max(1, (int)$_GET['page_kecamatan']) : 1;
    $kecamatan_offset = ($kecamatan_page - 1) * max(1, $kecamatan_per_page);
}

// Jika tabel `desa_new` tersedia, gunakan itu untuk menampilkan daftar desa/kecamatan
if (function_exists('table_exists') && table_exists('desa_new')) {
    // Deteksi nama kolom yang relevan di desa_new
    $dn_kec_col = first_existing_column('desa_new', ['nama_kecamatan','kecamatan','nama_kec','nama_kab']);
    $dn_desa_col = first_existing_column('desa_new', ['nama_desa','desa','desa_name','nama_titik']);
    $dn_idwil_col = first_existing_column('desa_new', ['id_wilayah','id_wil','id_wilayah','id']);
    $dn_lat_col = first_existing_column('desa_new', ['latitude','lat','lintang']);
    $dn_long_col = first_existing_column('desa_new', ['longtitude','longitude','lon','bujur']);
    // primary key candidate for desa_new (used for action buttons)
    $dn_pk_col = first_existing_column('desa_new', ['id','id_desa','id_desa_new','id_wilayah']);
    $dn_fk_kec = first_existing_column('desa_new', ['id_kecamatan','id_kec']);

    // Membangun query dengan JOIN ke tabel kecamatan jika ada foreign key
    if ($dn_fk_kec) {
        $kecamatan_query = "SELECT dn.*, k.nama_kecamatan as nama_kecamatan_kec FROM desa_new dn LEFT JOIN kecamatan k ON dn.$dn_fk_kec = k.id_kecamatan";
    } else {
        $kecamatan_query = "SELECT dn.* FROM desa_new dn";
    }

    // Filter pencarian: cari pada nama kecamatan/nama desa/id_wilayah
    if ($kecamatan_search) {
        $q = $kecamatan_search;
        $conds = [];
        if ($dn_kec_col) $conds[] = "dn.$dn_kec_col LIKE '%$q%'";
        if ($dn_desa_col) $conds[] = "dn.$dn_desa_col LIKE '%$q%'";
        if ($dn_idwil_col) $conds[] = "dn.$dn_idwil_col = '$q'";
        if (!empty($conds)) $kecamatan_query .= ' WHERE (' . implode(' OR ', $conds) . ')';
    }

    // Count
    $kecamatan_count_sql = "SELECT COUNT(*) as total FROM desa_new";
    if ($kecamatan_search) {
        $q = $kecamatan_search;
        $conds = [];
        if ($dn_kec_col) $conds[] = "$dn_kec_col LIKE '%$q%'";
        if ($dn_desa_col) $conds[] = "$dn_desa_col LIKE '%$q%'";
        if ($dn_idwil_col) $conds[] = "$dn_idwil_col = '$q'";
        if (!empty($conds)) $kecamatan_count_sql .= ' WHERE (' . implode(' OR ', $conds) . ')';
    }
    $kecamatan_count_result = query($kecamatan_count_sql);
    $kecamatan_total_data = (int)(mysqli_fetch_assoc($kecamatan_count_result)['total'] ?? 0);
    if ($kecamatan_per_page === 0) {
        $kecamatan_total_pages = 1;
    } else {
        $kecamatan_total_pages = ceil($kecamatan_total_data / $kecamatan_per_page);
    }

    // Order and prepare export support
    $kecamatan_order_query_no_limit = $kecamatan_query . ' ORDER BY ' . ($dn_kec_col ? "dn.$dn_kec_col" : "dn.$dn_desa_col") . ' ASC';

    // Export CSV for desa_new if requested
    if (isset($_GET['export_kecamatan']) && $_GET['export_kecamatan'] === 'csv') {
        $export_rows = fetch_all($kecamatan_order_query_no_limit);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=kecamatan_export.csv');
        $out = fopen('php://output', 'w');
        $headers = [];
        $headers[] = 'nama_kecamatan';
        $headers[] = 'nama_desa';
        $headers[] = ($dn_idwil_col ?? 'id_wilayah');
        $headers[] = ($dn_lat_col ?? 'latitude');
        $headers[] = ($dn_long_col ?? 'longtitude');
        fputcsv($out, $headers);
        foreach ($export_rows as $r) {
            $row = [];
            $row[] = $r['nama_kecamatan_kec'] ?? ($r[$dn_kec_col] ?? '');
            $row[] = $r['nama_desa'] ?? ($r[$dn_desa_col] ?? '');
            $row[] = $r[$dn_idwil_col] ?? ($r['id_wilayah'] ?? ($r['id'] ?? ''));
            $row[] = $r[$dn_lat_col] ?? ($r['latitude'] ?? '');
            $row[] = $r[$dn_long_col] ?? ($r['longtitude'] ?? $r['longitude'] ?? '');
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    // compute totals for desa and distinct kecamatan in desa_new
    $desa_new_total = (int)(fetch_row("SELECT COUNT(*) as total FROM desa_new")['total'] ?? 0);
    if ($dn_kec_col) {
        $distinct_kec_in_desa = (int)(fetch_row("SELECT COUNT(DISTINCT TRIM($dn_kec_col)) as total FROM desa_new WHERE COALESCE(TRIM($dn_kec_col),'') <> ''")['total'] ?? 0);
    } else {
        $distinct_kec_in_desa = $kecamatan_unik;
    }

    // Apply limit and run query
    $kecamatan_order_query = $kecamatan_order_query_no_limit;
    if ($kecamatan_per_page > 0) $kecamatan_order_query .= " LIMIT $kecamatan_offset, $kecamatan_per_page";
    $kecamatan_result = query($kecamatan_order_query);

} else {
    // Fallback to tabel kecamatan lama
    $kecamatan_query = "SELECT * FROM kecamatan";
    if ($kecamatan_search) {
        $q = $kecamatan_search;
        $kecamatan_query .= " WHERE nama_kecamatan LIKE '%$q%' OR id_kecamatan = '$q'";
    }
    $kecamatan_count_sql = "SELECT COUNT(*) as total FROM kecamatan";
    if ($kecamatan_search) {
        $q = $kecamatan_search;
        $kecamatan_count_sql .= " WHERE nama_kecamatan LIKE '%$q%' OR id_kecamatan = '$q'";
    }
    $kecamatan_count_result = query($kecamatan_count_sql);
    $kecamatan_total_data = mysqli_fetch_assoc($kecamatan_count_result)['total'];
    if ($kecamatan_per_page === 0) {
        $kecamatan_total_pages = 1;
    } else {
        $kecamatan_total_pages = ceil($kecamatan_total_data / $kecamatan_per_page);
    }

    // desa_new not available: set totals fallback
    $desa_new_total = 0;
    $distinct_kec_in_desa = $kecamatan_unik;

    $kecamatan_order_query = $kecamatan_query . " ORDER BY id_kecamatan ASC";
    if ($kecamatan_per_page > 0) $kecamatan_order_query .= " LIMIT $kecamatan_offset, $kecamatan_per_page";
    $kecamatan_result = query($kecamatan_order_query);
}

// Handle update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = (int)$_POST['id'];
    $id_kecamatan = (int)$_POST['id_kecamatan'];
    $nama_desa = escape(trim($_POST['nama_desa'] ?? ''));
    $alamat = escape(trim($_POST['alamat'] ?? ''));
    $latitude = trim($_POST['latitude'] ?? '');
    $longtitude = trim($_POST['longtitude'] ?? '');
    $nama_sekolah = escape(trim($_POST['nama_sekolah'] ?? ''));
    $tingkat_pendidikan = escape(trim($_POST['tingkat_pendidikan'] ?? ''));
    $npsn = trim($_POST['npsn'] ?? '');
    $status = escape(trim($_POST['status'] ?? ''));

    $errors = [];
    
    if (!$id_kecamatan) $errors[] = 'Pilih Kecamatan';
    if ($nama_sekolah === '') $errors[] = 'Nama sekolah wajib diisi';
    if ($nama_desa === '') $errors[] = 'Nama desa wajib diisi';
    if ($alamat === '') $errors[] = 'Alamat wajib diisi';
    if ($latitude === '' || !is_numeric($latitude)) $errors[] = 'Latitude harus berupa angka';
    if ($longtitude === '' || !is_numeric($longtitude)) $errors[] = 'longtitude harus berupa angka';
    if ($npsn === '' || !ctype_digit(strval($npsn))) $errors[] = 'NPSN harus berupa angka';
    if ($status === '') $errors[] = 'Status wajib diisi';

    header('Content-Type: application/json');
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
    } else {
        $latitude = (float)$latitude;
        $longtitude = (float)$longtitude;
        $npsn = (int)$npsn;

        $sql = "UPDATE sekolah SET 
            id_kecamatan = $id_kecamatan,
            nama_desa = '$nama_desa',
            alamat = '$alamat',
            latitude = $latitude,
            longtitude = $longtitude,
            nama_sekolah = '$nama_sekolah',
            tingkat_pendidikan = '$tingkat_pendidikan',
            npsn = $npsn,
            status = '$status'
            WHERE id_sekolah = $id";

        query($sql);
        echo json_encode(['success' => true, 'message' => 'Data berhasil diperbarui']);
    }
    exit;
}

// ===== KECAMATAN AJAX HANDLERS (get/update/delete) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_kecamatan') {
    $id = $_POST['id'] ?? '';
    $id = is_numeric($id) ? (int)$id : $id;

    // Prefer desa_new if available
    if (function_exists('table_exists') && table_exists('desa_new')) {
        $pk = first_existing_column('desa_new', ['id','id_desa','id_wilayah','id_desa_new']) ?: 'id';
        $row = fetch_row("SELECT * FROM desa_new WHERE `$pk` = '" . escape($id) . "' LIMIT 1");
        if ($row) {
            // Normalize keys
            $out = [];
            $out['id'] = $row[$pk] ?? null;
            $out['nama_kecamatan'] = $row['nama_kecamatan'] ?? $row[$dn_kec_col] ?? null;
            $out['nama_desa'] = $row['nama_desa'] ?? $row[$dn_desa_col] ?? null;
            $out['id_wilayah'] = $row[$dn_idwil_col] ?? ($row['id_wilayah'] ?? null);
            $out['latitude'] = $row[$dn_lat_col] ?? ($row['latitude'] ?? null);
            $out['longtitude'] = $row[$dn_long_col] ?? ($row['longtitude'] ?? $row['longitude'] ?? null);
            header('Content-Type: application/json');
            echo json_encode($out);
            exit;
        }
    }

    // Fallback to kecamatan
    $row = fetch_row("SELECT * FROM kecamatan WHERE id_kecamatan = '" . escape($id) . "' LIMIT 1");
    if ($row) {
        header('Content-Type: application/json');
        echo json_encode(['id' => $row['id_kecamatan'], 'nama_kecamatan' => $row['nama_kecamatan']]);
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(null);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_kecamatan') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? '';
    $nama_kecamatan = escape(trim($_POST['nama_kecamatan'] ?? ''));
    $nama_desa = escape(trim($_POST['nama_desa'] ?? ''));
    $id_wilayah = escape(trim($_POST['id_wilayah'] ?? ''));
    $latitude = trim($_POST['latitude'] ?? '');
    $longtitude = trim($_POST['longtitude'] ?? '');

    if (function_exists('table_exists') && table_exists('desa_new')) {
        $pk = first_existing_column('desa_new', ['id','id_desa','id_wilayah','id_desa_new']) ?: 'id';
        $cols = [];
        if ($dn_kec_col) $cols[] = "$dn_kec_col = '" . $nama_kecamatan . "'";
        if ($dn_desa_col) $cols[] = "$dn_desa_col = '" . $nama_desa . "'";
        if ($dn_idwil_col) $cols[] = "$dn_idwil_col = '" . $id_wilayah . "'";
        if ($dn_lat_col) $cols[] = "$dn_lat_col = " . ($latitude === '' ? 'NULL' : (float)$latitude);
        if ($dn_long_col) $cols[] = "$dn_long_col = " . ($longtitude === '' ? 'NULL' : (float)$longtitude);

        if (empty($cols)) { echo json_encode(['success'=>false,'errors'=>['Tidak ada kolom untuk diperbarui']]); exit; }
        $sql = "UPDATE desa_new SET " . implode(', ', $cols) . " WHERE `$pk` = '" . escape($id) . "'";
        if (query($sql)) echo json_encode(['success'=>true]); else echo json_encode(['success'=>false,'errors'=>['Gagal memperbarui']]);
        exit;
    } else {
        // Fallback to kecamatan table: update nama_kecamatan and possible lat/long
        $cols = [];
        if ($nama_kecamatan !== '') $cols[] = "nama_kecamatan = '" . $nama_kecamatan . "'";
        if ($kec_lat_col && $latitude !== '') $cols[] = "$kec_lat_col = " . (float)$latitude;
        if ($kec_long_col && $longtitude !== '') $cols[] = "$kec_long_col = " . (float)$longtitude;
        if (empty($cols)) { echo json_encode(['success'=>false,'errors'=>['Tidak ada kolom untuk diperbarui']]); exit; }
        $sql = "UPDATE kecamatan SET " . implode(', ', $cols) . " WHERE id_kecamatan = '" . escape($id) . "'";
        if (query($sql)) echo json_encode(['success'=>true]); else echo json_encode(['success'=>false,'errors'=>['Gagal memperbarui']]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_kecamatan') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? '';

    if (function_exists('table_exists') && table_exists('desa_new')) {
        $pk = first_existing_column('desa_new', ['id','id_desa','id_wilayah','id_desa_new']) ?: 'id';
        $sql = "DELETE FROM desa_new WHERE `$pk` = '" . escape($id) . "'";
        if (query($sql)) { echo json_encode(['success' => true]); exit; }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menghapus dari desa_new']);
        exit;
    } else {
        $sql = "DELETE FROM kecamatan WHERE id_kecamatan = '" . escape($id) . "'";
        if (query($sql)) { echo json_encode(['success' => true]); exit; }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menghapus dari kecamatan']);
        exit;
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unknown error']);
    exit;
}
?>

<?php
// Repair coordinates handler: isi koordinat sekolah kosong dari kecamatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'repair_coords') {
    $from_kec = 0;
    
    // update dari kecamatan bila kolom ada
    if ($sk_lat_col && $sk_long_col && $kec_lat_col && $kec_long_col) {
        $sql2 = "UPDATE sekolah s JOIN kecamatan k ON s.id_kecamatan = k.id_kecamatan SET s.$sk_lat_col = k.$kec_lat_col, s.$sk_long_col = k.$kec_long_col WHERE (s.$sk_lat_col IS NULL OR s.$sk_long_col IS NULL OR s.$sk_lat_col = 0 OR s.$sk_long_col = 0 OR s.$sk_lat_col = '' OR s.$sk_long_col = '') AND k.$kec_lat_col IS NOT NULL AND k.$kec_long_col IS NOT NULL";
        query($sql2);
        $from_kec = mysqli_affected_rows($conn);
    }

    // Hitung sisa
    if ($sk_lat_col && $sk_long_col) {
        $remaining_rows = fetch_all("SELECT id_sekolah FROM sekolah WHERE ($sk_lat_col IS NULL OR $sk_long_col IS NULL OR $sk_lat_col = '' OR $sk_long_col = '' OR $sk_lat_col = 0 OR $sk_long_col = 0)");
        $remaining = count($remaining_rows);
    } else {
        $remaining = $total_sekolah;
    }

    $_SESSION['repair_report'] = ['from_kecamatan' => $from_kec, 'remaining' => $remaining];
    header('Location: hasil_input.php?msg=repaired');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Input Data - Sistem Rekomendasi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a3a5c 0%, #0f2643 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .logo {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.1);
            border: 3px solid white;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }

        .sidebar-header h2 {
            font-size: 18px;
            font-weight: 600;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin: 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #b0c4de;
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #ff9f43;
        }

        .sidebar-menu .submenu {
            list-style: none;
            padding: 0;
            display: none;
        }

        .sidebar-menu .submenu.show {
            display: block;
        }

        .sidebar-menu .submenu li a {
            padding-left: 40px;
            font-size: 16px;
        }

        /* icon menu utama & submenu */
        .menu-icon {
            display: inline-flex;
            align-items: center;
            margin-right: 12px;
        }

        .menu-icon img {
            width: 20px;
            height: 20px;
        }

        .menu-text {
            display: inline;
        }

        .dropdown-arrow {
            display: inline-block;
            transition: transform 0.3s ease;
        }

        /* rotate arrow ketika aktif */
        .dropdown-toggle.active .dropdown-arrow {
            transform: rotate(90deg);
        }

        .sidebar-footer {
            position: absolute;
            bottom: 6px;
            width: 100%;
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            background: #ff9f43;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }

        .admin-text h3 {
            font-size: 13px;
            margin-bottom: 2px;
        }

        .admin-text p {
            font-size: 11px;
            color: #b0c4de;
        }

        /* Main content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 50px 40px;
        }

        .page-header {
            margin-bottom: 40px;
            animation: slideInDown 0.6s ease;
        }

        .page-header h1 {
            font-size: 36px;
            color: #1a3a5c;
            margin-bottom: 7px;
            font-weight: 700;
        }

        .page-header p {
            color: #7f8c8d;
            font-size: 14px;
        }

        /* Data Section */
        .data-section {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border-top: 5px solid #ff9f43;
            animation: slideInUp 0.8s ease;
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a3a5c;
            margin-bottom: 12px;
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-desc {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 30px;
        }




        /* Search Bar */
        .search-container {
            margin-bottom: 30px;
        }

        .search-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: #f9f9f9;
            border: 2px solid #bebbbb;
            border-radius: 8px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .search-bar:focus-within {
            border-color: #1a3a5c;
            box-shadow: 0 0 0 4px rgba(26, 58, 92, 0.08);
        }

        .search-bar input {
            flex: 1;
            border: none;
            background: transparent;
            font-size: 14px;
            outline: none;
        }

        .search-bar input::placeholder {
            color: #95a5a6;
        }

        .search-icon {
            font-size: 18px;
            color: #7f8c8d;
        }

        .search-hint {
            font-size: 13px;
            color: #666;
            margin-left: 8px;
            padding: 4px 8px;
            background: rgba(0,0,0,0.03);
            border-radius: 6px;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        /* Smaller two-card variant (use for separated sections) */
        .stats-grid--two {
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #e8f0fe 100%);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #e8e8e8;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #1a3a5c;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 13px;
            color: #7f8c8d;
        }

        /* Table */
        .table-wrapper {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            table-layout: fixed; /* menjaga tabel tidak melebihi wadah */
        }

        .table thead {
            background-color: #1a3a5c;
            color: white;
        }

        .table th {
            padding: 10px;
            text-align: left;
            font-weight: 500;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: normal; /* izinkan wrap pada header */
            word-wrap: break-word;
            word-break: break-word;
        }

        .table td {
            padding: 16px;
            border-bottom: 1px solid #e8e8e8;
            font-size: 13px;
            color: #333;
            white-space: normal; /* izinkan wrap agar teks tidak dipotong */
            word-wrap: break-word;
            word-break: break-word;
            max-width: 250px; /* batas lebar sel agar tabel tidak melebar */
        }

        /* Fallback responsif: pada layar kecil, izinkan lebar sel penuh untuk keterbacaan */
        @media (max-width: 768px) {
            .table th, .table td {
                max-width: none;
            }
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: #f9f9f9;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 14px;
        }

        /* Action Buttons */
        .action-btns {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background-color: #d4edff;
            color: #1088ff;
        }

        .btn-edit:hover {
            background-color: #1088ff;
            color: white;
            transform: translateY(-2px);
        }

        .btn-delete {
            background-color: #ffe0e0;
            color: #f30606;
        }

        .btn-delete:hover {
            background-color: #f30606;
            color: white;
            transform: translateY(-2px);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #e8e8e8;
            border-radius: 6px;
            text-decoration: none;
            color: #1a3a5c;
            transition: all 0.3s ease;
            font-size: 13px;
        }

        .pagination a:hover {
            background-color: #1a3a5c;
            color: white;
        }

        .pagination .active {
            background-color: #1a3a5c;
            color: white;
            border-color: #1a3a5c;
        }

        /* Animations */
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Alert/Notification Styles */
        .alert-notification {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideInDown 0.5s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .alert-notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-notification.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .alert-icon {
            font-size: 20px;
        }

        .alert-message {
            flex: 1;
        }

        .alert-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 18px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .alert-close:hover {
            opacity: 0.8;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .main-content {
                padding: 30px 20px;
            }

            .data-section {
                padding: 30px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 50%;
                height: 100vh;
                position: fixed;
                top: 0;
                left: 0;
                display: block;
                transform: translateX(0);
                transition: all 0.3s ease;
                z-index: 9998;
            }

            .sidebar.hidden {
                transform: translateX(-100%);
                pointer-events: none;
            }

            .sidebar-backdrop {
                display: block;
                z-index: 9997;
            }

            .sidebar-backdrop.show {
                opacity: 1;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .admin-status-top {
                display: none;
            }

            .data-section {
                padding: 20px;
            }

            .main-content {
                padding: 70px 20px 20px !important;
            }

            .page-header {
                padding-left: 64px;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .section-title {
                font-size: 18px;
            }

            .table {
                font-size: 12px;
            }

            .table th,
            .table td {
                padding: 10px;
            }

            .btn-icon {
                width: 30px;
                height: 30px;
                font-size: 14px;
            }
        }

        /* logo bps */
        /* HEADER SIDEBAR */
        .sidebar-header {
            display: flex;
            flex-direction: column;      /* LOGO DI ATAS, TEKS DI BAWAH */
            align-items: center;         /* TENGAH HORIZONTAL */
            justify-content: center;
            text-align: center;
            gap: 17px;
            padding: 16px 0;
            margin-top: 18px;
        }

        /* LINGKARAN LOGO */
        .logo-circle {
            width: 95px;
            height: 95px;
            border-radius: 50%;
            overflow: hidden;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* GAMBAR LOGO */
        .logo-circle img {
            width: 115%;
            height: 115%;
            object-fit: contain;   /* LOGO BPS TIDAK TERPOTONG */
        }

        /* TEKS */
        .sidebar-header h2 {
            font-size: 16px;
            line-height: 1.3;
            margin: 0;
            color: #ffffff;
            margin-bottom: 10px;
        }

        /* css admin online  */
        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* gambar avatar */
        .admin-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* teks */
        .admin-text h3 {
            margin: 0;
            font-size: 14px;
            color: #fff;
        }

        .admin-text p {
            margin: 2px 0 0;
            font-size: 12px;
            color: #b5f5c3;
        }

        /* titik status online */
        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #2ecc71;
            border-radius: 50%;
            margin-right: 6px;
        }

        /* Admin Status Top Right */
        .admin-status-top {
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 12px 20px;
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            animation: slideInDown 0.5s ease;
            white-space: nowrap;
            flex-shrink: 0;
            min-height: 44px;
            -webkit-tap-highlight-color: transparent;
            z-index: 50;
        }

        .admin-status-top .online-dot {
            width: 10px;
            height: 10px;
            background: #2ecc71;
            border-radius: 50%;
            box-shadow: 0 0 6px rgba(46, 204, 113, 0.8);
            animation: pulse 2s infinite;
        }

        .admin-status-top span {
            color: white;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 6px rgba(46, 204, 113, 0.8);
            }
            50% {
                box-shadow: 0 0 12px rgba(46, 204, 113, 1);
            }
        }

        /* Logout Button */
        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none; 
            gap: 8px;
            width: 100%;
            padding: 12px 12px;
            background: linear-gradient(135deg, #ffffff 0%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            color: #1a3a5c;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 12px;
            box-shadow: 0 4px 12px rgba(255, 107, 107, 0.25);
            min-height: 48px;
            -webkit-tap-highlight-color: transparent;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.35);
        }

        .logout-btn:active {
            transform: translateY(0px);
        }

        .logout-btn img {
            width: 20px;
            height: 20px;
        }

        /* Notifikasi Logout Modal - Gaya Modern */
        .notification-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .notification-modal.show {
            display: flex;
        }

        .notification-modal-content {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            border-radius: 12px;
            padding: 32px;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .notification-title::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            background: #3498db;
            border-radius: 50%;
        }

        .notification-message {
            color: #bdc3c7;
            font-size: 18px;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .notification-buttons {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .notification-btn {
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: capitalize;
        }

        .notification-btn-cancel {
            background: rgba(149, 165, 166, 0.3);
            color: #ecf0f1;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .notification-btn-cancel:hover {
            background: rgba(149, 165, 166, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .notification-btn-logout {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
        }

        .notification-btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        .notification-btn-logout:active {
            transform: translateY(0);
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* icon edit dan delete */
        .action-btns {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 4px;
        }

        .btn-icon img {
            width: 20px;
            height: 20px;
            display: block;
        }

        .btn-edit img {
            filter: brightness(0.9);
        }

        .btn-delete img {
            filter: brightness(0.9);
        }

        .btn-icon:hover img {
            transform: scale(1.1);
            transition: 0.2s ease;
        }

        /* ===== MODAL EDIT STYLES ===== */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .modal-header h2 {
            font-size: 26px;
            color: #1a3a5c;
            margin: 0;
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            color: #999;
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background-color: #f0f0f0;
            color: #333;
            transform: rotate(90deg);
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #1a3a5c;
            font-size: 15px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e8e8e8;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1088ff;
            box-shadow: 0 0 0 4px rgba(16, 136, 255, 0.1);
            background-color: #f8fbff;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .btn-modal {
            padding: 12px 28px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-save {
            background: linear-gradient(135deg, #1088ff 0%, #0066cc 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 136, 255, 0.3);
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 136, 255, 0.4);
        }

        .btn-save:active {
            transform: translateY(0);
        }

        .btn-cancel {
            background-color: #f0f0f0;
            color: #333;
            border: 2px solid #e8e8e8;
        }

        .btn-cancel:hover {
            background-color: #e8e8e8;
            border-color: #d0d0d0;
        }

        /* ===== MODAL KONFIRMASI HAPUS ===== */
        .confirm-modal {
            display: none;
            position: fixed;
            z-index: 3000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }

        .confirm-modal.show {
            display: flex;
        }

        .confirm-modal-content {
            background: white;
            padding: 40px;
            border-radius: 16px;
            width: 90%;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        .confirm-icon {
            font-size: 64px;
            margin-bottom: 20px;
            animation: bounce 0.6s ease;
        }

        .confirm-modal-content h3 {
            font-size: 22px;
            color: #1a3a5c;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .confirm-modal-content p {
            color: #666;
            font-size: 15px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .confirm-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .btn-confirm-delete {
            background: linear-gradient(135deg, #f30606 0%, #cc0000 100%);
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(243, 6, 6, 0.3);
            font-size: 15px;
        }

        .btn-confirm-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(243, 6, 6, 0.4);
        }

        .btn-confirm-delete:active {
            transform: translateY(0);
        }

        .btn-confirm-cancel {
            background-color: #f0f0f0;
            color: #333;
            border: 2px solid #e8e8e8;
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 15px;
        }

        .btn-confirm-cancel:hover {
            background-color: #e8e8e8;
            border-color: #d0d0d0;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes bounce {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        /* Alert Messages */
        .alert-msg {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            animation: slideDown 0.3s ease;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Alert Notification */
        .alert-notification {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            animation: slideDown 0.4s ease;
            border-left: 5px solid transparent;
        }

        .alert-notification.success {
            background-color: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }

        .alert-notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }

        .alert-icon {
            font-size: 20px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .alert-message {
            flex: 1;
            font-weight: 500;
        }

        .alert-close {
            background: none;
            border: none;
            font-size: 24px;
            color: inherit;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .alert-close:hover {
            background-color: rgba(0,0,0,0.1);
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn-icon-text {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-icon-text img {
            width: 25px;
            height: 25px;
        }

        /* icon delete */
        .btn-icon-text {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-icon-text img {
            width: 23px;
            height: 23px;
        }

        /* Card box for separating tables */
        .card-box {
            background: #fff;
            padding: 16px;
            border-radius: 8px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            margin-bottom: 22px;
        }

        /* Divider between data sections */
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, rgba(0,0,0,0.06), rgba(0,0,0,0.02));
            border: none;
            margin: 28px 0;
        }

        /* Table adjustments to fit inside card without widening the card */
        .table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* keep table within parent width and let cells wrap */
        }
        .table th, .table td {
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid #eee;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }

        /* Column width hints (smaller min-widths so table doesn't force card wider) */
        .table th.col-no, .table td.col-no { width: 48px; min-width: 40px; max-width: 60px; }
        .table th.col-tingkat, .table td.col-tingkat { min-width: 100px; max-width: 160px; }
        .table th.col-nama, .table td.col-nama { min-width: 140px; max-width: 220px; }
        .table th.col-npsn, .table td.col-npsn { min-width: 80px; max-width: 110px; }
        .table th.col-status, .table td.col-status { min-width: 80px; max-width: 110px; }
        .table th.col-alamat, .table td.col-alamat { min-width: 140px; max-width: 220px; }
        .table th.col-lat, .table td.col-lat, .table th.col-long, .table td.col-long { min-width: 80px; max-width: 120px; }
        .table th.col-aksi, .table td.col-aksi { width: 90px; min-width: 70px; max-width: 110px; text-align: center; }

        /* Mobile Menu Toggle Button */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 14px;
            left: 14px;
            z-index: 10005;
            background: transparent;
            border: 2px solid white;
            width: 40px;
            height: 40px;
            min-height: 40px;
            min-width: 40px;
            border-radius: 6px;
            cursor: pointer;
            color: white;
            flex-direction: column;
            gap: 4px;
            justify-content: center;
            align-items: center;
            box-shadow: none;
            transition: all 0.3s ease;
            pointer-events: auto;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        .mobile-menu-toggle:active {
            transform: scale(0.95);
        }

        .mobile-menu-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .mobile-menu-toggle.active {
            background: white;
            border-color: white;
        }

        .mobile-menu-toggle span {
            width: 20px;
            height: 2.5px;
            background: #000;
            border-radius: 2px;
            transition: all 0.3s ease;
            display: block;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        /* Mobile User Icon */
        .mobile-user-icon {
            display: inline-flex;
            position: fixed;
            top: 10px;
            right: 14px;
            z-index: 10005;
            visibility: visible;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 20px;
            padding: 10px 16px;
            cursor: pointer;
            color: white;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
            pointer-events: auto;
            -webkit-tap-highlight-color: transparent;
            flex-direction: row;
            gap: 8px;
        }

        .mobile-user-icon:hover {
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }

        .mobile-user-icon:active {
            transform: scale(0.95);
        }

        .mobile-user-icon .online-dot {
            width: 10px;
            height: 10px;
            background: #2ecc71;
            border-radius: 50%;
            box-shadow: 0 0 6px rgba(46, 204, 113, 0.8);
            animation: pulse 2s infinite;
        }

        .mobile-user-icon span {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
            color: white;
        }

        .mobile-menu-toggle.active span {
            background: #1a3a5c;
        }

        .mobile-menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translateY(8px);
        }

        .mobile-menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .mobile-menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translateY(-8px);
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
            }

            .mobile-user-icon {
                display: flex !important;
                visibility: visible;
            }
        }

        .mobile-menu-toggle.hidden-on-scroll,
        .admin-status-top.hidden-on-scroll {
            transform: translateY(-80px);
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
        }
        /* Mobile overrides to ensure sidebar shows as half-screen and header isn't overlapped */
        @media (max-width: 768px) {
            .sidebar { width: 50% !important; }
            .sidebar.hidden { transform: translateX(-100%) !important; pointer-events: none !important; }
            .sidebar-backdrop { display: block; z-index: 9997; }
            .main-content { margin-left: 0 !important; padding: 70px 16px 30px !important; }
            .mobile-menu-toggle { top: 14px; left: 14px; z-index: 10005; }
            .mobile-user-icon { top: 10px; right: 14px; z-index: 10005; display: flex !important; visibility: visible; }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle Button -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleMobileMenu()">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- Mobile User Icon -->
    <div class="mobile-user-icon" id="mobileUserIcon" onclick="showLogoutNotification()">
        <div class="online-dot"></div>
        <span>ADMIN</span>
    </div>

    <div class="sidebar-backdrop"></div>

    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-circle">
                    <img src="assets/icons/bps.jpg" alt="Logo BPS">
                </div>
                <h2>BADAN PUSAT STATISTIK</h2>
            </div>

            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php">
                        <span class="menu-icon">
                            <img src="assets/icons/dashboard.png" alt="Dashboard">
                        </span>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0)" class="dropdown-toggle" onclick="toggleDropdown(event)">
                        <span class="menu-icon">
                            <img src="assets/icons/rekomendasi1.png" alt="Rekomendasi">
                        </span>Rekomendasi
                        <span class="dropdown-arrow" style="margin-left: auto;">></span>
                    </a>
                    <ul class="submenu show" id="rekomendasi-menu">
                        <li>
                            <a href="input_data.php">
                                <span class="menu-icon">
                                    <img src="assets/icons/input.png" alt="Input Data">
                                </span>
                                <span class="menu-text">Input Data</span>
                            </a>
                        </li>
                        <li>
                            <a href="hasil_input.php" class="active">
                                <span class="menu-icon">
                                    <img src="assets/icons/hasil.png" alt="Hasil Input Data">
                                </span>
                                <span class="menu-text">Hasil Input Data</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="peta.php">
                        <span class="menu-icon">
                            <img src="assets/icons/peta1.png" alt="Peta">
                        </span>
                        <span class="menu-text">Peta</span>
                    </a>
                </li>
            </ul>

            <!-- Sidebar Footer with Admin Info and Logout -->
            <div class="sidebar-footer">
                <button class="logout-btn" onclick="showLogoutNotification()">
                    <img src="assets/icons/logout.png" alt="Logout Icon">
                    Logout
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">

            <div class="page-header">
                <h1>Hasil Input Data</h1>
                <p>Kelola data sekolah yang telah diinput</p>
            </div>

            <?php if ($notification_type): ?>
            <div class="alert-notification <?php echo htmlspecialchars($notification_type); ?>" id="notificationAlert">
                <span class="alert-icon">
                    <?php if ($notification_type === 'success'): ?>
                        ✓
                    <?php elseif ($notification_type === 'error'): ?>
                        ✕
                    <?php else: ?>
                        ℹ
                    <?php endif; ?>
                </span>
                <span class="alert-message"><?php echo htmlspecialchars($notification_message); ?></span>
                <button class="alert-close" onclick="document.getElementById('notificationAlert').style.display='none';">×</button>
            </div>
            <?php endif; ?>

            <div class="data-section">
                <div class="section-title">
                    <span>🗺️</span>
                    Daftar Data Kecamatan
                </div>
                <div class="section-desc">Kelola, cari, dan lihat data kecamatan yang tersedia</div>

                <!-- Search Bar Kecamatan -->
                <div class="search-container">
                    <form method="GET" style="width: 100%; display:flex; gap:10px; align-items:center;">
                        <div class="search-bar" style="flex:1; display:flex; gap:8px; align-items:center;">
                            <span class="search-icon">🔍</span>
                            <input type="text" name="search_kecamatan" placeholder="Cari berdasarkan nama kecamatan atau desa..." value="<?php echo htmlspecialchars($kecamatan_search); ?>">
                        </div>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <button type="submit" style="padding:11px 12px; background:#2c7be5; color:#fff; border-radius:6px; border:none;">Cari</button>
                            <a class="export-btn" href="?export_kecamatan=csv<?php echo $kecamatan_search ? '&search_kecamatan=' . urlencode($kecamatan_search) : ''; ?>&per_page_kecamatan=<?php echo ($kecamatan_per_page === 0) ? 'all' : $kecamatan_per_page; ?>" style="padding:8px 12px; background:#4caf50; color:#fff; border-radius:6px; text-decoration:none;">Export CSV</a>
                        </div>
                    </form>
                </div>



                <!-- Statistics Cards (Kecamatan) -->
                <div class="stats-grid stats-grid--two" style="margin-top:10px;">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $distinct_kec_in_desa ?? $kecamatan_unik; ?></div>
                        <div class="stat-label">Jumlah Data Kecamatan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $desa_new_total ?? $desa_unik; ?></div>
                        <div class="stat-label">Jumlah Data Desa</div>
                    </div>
                </div>

                <div class="table-wrapper">
                <?php if ($kecamatan_total_data > 0): ?>
                    <table class="table kecamatan-table">
                        <thead>
                            <tr>
                                <th class="col-no">No</th>
                                <th class="col-nama">Nama Kecamatan</th>
                                <th class="col-nama">Nama Desa</th>
                                <th class="col-nama">ID Wilayah</th>
                                <th class="col-lat">Latitude</th>
                                <th class="col-long">Longtitude</th>
                                <th class="col-aksi">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $kec_no = $kecamatan_offset + 1;
                            while ($kec_row = mysqli_fetch_assoc($kecamatan_result)): ?>
                                <tr>
                                    <?php
                                        // Fallback-safe renders for desa_new or kecamatan rows
                                        $kec_display = '';
                                        $desa_display = '';
                                        $id_wil_display = '';
                                        $lat_display = '';
                                        $long_display = '';

                                        if (isset($kec_row['nama_kecamatan_kec'])) $kec_display = $kec_row['nama_kecamatan_kec'];
                                        elseif (isset($kec_row['nama_kecamatan'])) $kec_display = $kec_row['nama_kecamatan'];
                                        elseif (isset($dn_kec_col) && isset($kec_row[$dn_kec_col])) $kec_display = $kec_row[$dn_kec_col];

                                        if (isset($kec_row['nama_desa'])) $desa_display = $kec_row['nama_desa'];
                                        elseif (isset($dn_desa_col) && isset($kec_row[$dn_desa_col])) $desa_display = $kec_row[$dn_desa_col];

                                        if (isset($dn_idwil_col) && isset($kec_row[$dn_idwil_col])) $id_wil_display = $kec_row[$dn_idwil_col];
                                        elseif (isset($kec_row['id_kecamatan'])) $id_wil_display = $kec_row['id_kecamatan'];

                                        if (isset($dn_lat_col) && isset($kec_row[$dn_lat_col]) && is_numeric($kec_row[$dn_lat_col])) $lat_display = number_format($kec_row[$dn_lat_col],6);
                                        elseif (isset($kec_lat_col) && isset($kec_row[$kec_lat_col]) && is_numeric($kec_row[$kec_lat_col])) $lat_display = number_format($kec_row[$kec_lat_col],6);

                                        if (isset($dn_long_col) && isset($kec_row[$dn_long_col]) && is_numeric($kec_row[$dn_long_col])) $long_display = number_format($kec_row[$dn_long_col],6);
                                        elseif (isset($kec_long_col) && isset($kec_row[$kec_long_col]) && is_numeric($kec_row[$kec_long_col])) $long_display = number_format($kec_row[$kec_long_col],6);
                                    ?>

                                    <td class="col-no"><?php echo $kec_no++; ?></td>
                                    <td class="col-nama"><?php echo htmlspecialchars($kec_display); ?></td>
                                    <td class="col-nama"><?php echo htmlspecialchars($desa_display); ?></td>
                                            <td class="col-nama"><?php echo htmlspecialchars($id_wil_display); ?></td>
                                    <td class="col-lat"><?php echo $lat_display; ?></td>
                                    <td class="col-long"><?php echo $long_display; ?></td>
                                    <td class="col-aksi">
                                        <div class="action-btns" style="display:flex; gap:8px; justify-content:center;">
                                            <?php
                                                // Determine a stable id to use for actions
                                                $kec_pk = $kec_row[$dn_pk_col] ?? $kec_row['id_kecamatan'] ?? $kec_row['id'] ?? null;
                                            ?>
                                            <button class="btn-icon btn-edit" onclick="editKecamatan('<?php echo htmlspecialchars($kec_pk); ?>')" title="Edit">
                                                <img src="assets/icons/edit.png" alt="Edit">
                                            </button>

                                            <button class="btn-icon btn-delete" onclick="deleteKecamatan('<?php echo htmlspecialchars($kec_pk); ?>')" title="Hapus">
                                                <img src="assets/icons/delete.png" alt="Hapus">
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    </div>
                    <!-- Pagination Kecamatan -->
                    <?php if ($kecamatan_total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($kecamatan_page > 1): ?>
                                <a href="?page_kecamatan=1<?php echo $kecamatan_search ? '&search_kecamatan=' . urlencode($kecamatan_search) : ''; ?>&per_page_kecamatan=<?php echo ($kecamatan_per_page === 0) ? 'all' : $kecamatan_per_page; ?>">« Pertama</a>
                                <a href="?page_kecamatan=<?php echo $kecamatan_page - 1; ?><?php echo $kecamatan_search ? '&search_kecamatan=' . urlencode($kecamatan_search) : ''; ?>&per_page_kecamatan=<?php echo ($kecamatan_per_page === 0) ? 'all' : $kecamatan_per_page; ?>">‹ Sebelumnya</a>
                            <?php endif; ?>

                            <?php 
                            $kec_start_page = max(1, $kecamatan_page - 2);
                            $kec_end_page = min($kecamatan_total_pages, $kecamatan_page + 2);
                            
                            for ($i = $kec_start_page; $i <= $kec_end_page; $i++): ?>
                                <?php if ($i == $kecamatan_page): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page_kecamatan=<?php echo $i; ?><?php echo $kecamatan_search ? '&search_kecamatan=' . urlencode($kecamatan_search) : ''; ?>&per_page_kecamatan=<?php echo ($kecamatan_per_page === 0) ? 'all' : $kecamatan_per_page; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($kecamatan_page < $kecamatan_total_pages): ?>
                                <a href="?page_kecamatan=<?php echo $kecamatan_page + 1; ?><?php echo $kecamatan_search ? '&search_kecamatan=' . urlencode($kecamatan_search) : ''; ?>&per_page_kecamatan=<?php echo ($kecamatan_per_page === 0) ? 'all' : $kecamatan_per_page; ?>">Selanjutnya ›</a>
                                <a href="?page_kecamatan=<?php echo $kecamatan_total_pages; ?><?php echo $kecamatan_search ? '&search_kecamatan=' . urlencode($kecamatan_search) : ''; ?>&per_page_kecamatan=<?php echo ($kecamatan_per_page === 0) ? 'all' : $kecamatan_per_page; ?>">Terakhir »</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-data">
                        <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                        <p>Belum ada data kecamatan.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- SECTION 1: DATA SEKOLAH -->
            <div class="data-section">
                <div class="section-title">
                    <span>📊</span>
                    Daftar Data Sekolah
                </div>
                <div class="section-desc">Kelola, cari, edit, dan hapus data sekolah yang sudah diinputkan</div>
    
                <!-- Search Bar -->
                <div class="search-container">
                    <form method="GET" style="width: 100%; display:flex; gap:10px; align-items:center;">
                        <div class="search-bar" style="flex:1; display:flex; gap:8px; align-items:center;">
                            <span class="search-icon">🔍</span>
                            <input type="text" name="search" placeholder="Cari berdasarkan nama kecamatan, desa, atau sekolah..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                                <div style="display:flex; gap:8px; align-items:center;">
                            <button type="submit" style="padding:11px 12px; background:#2c7be5; color:#fff; border-radius:6px; border:none;">Cari</button>
                            <a class="export-btn" href="?export=csv<?php echo $search ? '&search=' . urlencode($search) : ''; ?>&per_page=<?php echo ($per_page === 0) ? 'all' : $per_page; ?>" style="padding:8px 12px; background:#4caf50; color:#fff; border-radius:6px; text-decoration:none;">Export CSV</a>
                        </div>
                    </form>
                </div>
    
                <!-- Statistics Cards (Sekolah) -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_sekolah; ?></div>
                        <div class="stat-label">Jumlah Data Sekolah</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $sekolah_swasta; ?></div>
                        <div class="stat-label">Jumlah Sekolah Swasta</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $sekolah_negeri; ?></div>
                        <div class="stat-label">Jumlah Sekolah Negeri</div>
                    </div>
                </div>
    
                <div class="table-wrapper">
                <?php if ($total_data > 0): ?>
                        <table class="table sekolah-table">
                            <thead>
                                <tr>
                                    <th class="col-no">No</th>
                                    <th class="col-tingkat">Tingkat Pendidikan</th>
                                    <th class="col-nama">Nama Sekolah</th>
                                    <th class="col-npsn">NPSN</th>
                                    <th class="col-status">Status</th>
                                    <th class="col-alamat">Alamat</th>
                                    <th class="col-lat">Latitude</th>
                                    <th class="col-long">Longtitude</th>
                                    <th class="col-aksi">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = $offset + 1;
                                while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($row['tingkat_pendidikan']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_sekolah']); ?></td>
                                        <td><?php echo htmlspecialchars($row['npsn']); ?></td>
                                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                                        <td><?php echo htmlspecialchars($row['alamat']); ?></td>
                                        <?php
                                        // Determine school coordinates using multiple possible column names + detected schema names
                                        $school_lat = '';
                                        $school_long = '';
    
                                        $lat_candidates = ['latitude','lat','lintang'];
                                        $long_candidates = ['longtitude','longtitude','longtude','lon','bujur','Longtitude'];
    
                                        foreach ($lat_candidates as $c) {
                                            if (isset($row[$c]) && is_numeric($row[$c])) { $school_lat = number_format($row[$c], 6); break; }
                                        }
                                        foreach ($long_candidates as $c) {
                                            if (isset($row[$c]) && is_numeric($row[$c])) { $school_long = number_format($row[$c], 6); break; }
                                        }
                                        // also respect detected column name variables if present
                                        if (empty($school_lat) && isset($sk_lat_col) && isset($row[$sk_lat_col]) && is_numeric($row[$sk_lat_col])) $school_lat = number_format($row[$sk_lat_col], 6);
                                        if (empty($school_long) && isset($sk_long_col) && isset($row[$sk_long_col]) && is_numeric($row[$sk_long_col])) $school_long = number_format($row[$sk_long_col], 6);
    
                                        // Kecamatan coordinates were aliased to kec_latitude / kec_longtitude earlier
                                        $kec_lat = (isset($row['kec_latitude']) && is_numeric($row['kec_latitude'])) ? number_format($row['kec_latitude'], 6) : '';
                                        $kec_long = (isset($row['kec_longtitude']) && is_numeric($row['kec_longtitude'])) ? number_format($row['kec_longtitude'], 6) : '';
    
                                        // Siap rekom: butuh koordinat sekolah + salah satu referensi kecamatan/kantor_desa
                                        $missing = [];
                                        if ($school_lat === '' || $school_long === '') $missing[] = 'Koordinat Sekolah';
    
                                        $ref_source = null;
                                        
                                        // Cek dari tabel kecamatan (jika ada)
                                        if ($kec_lat !== '' && $kec_long !== '') {
                                            $ref_source = 'Kecamatan';
                                        }
                                        // Cek dari tabel kantor_desa (jika kecamatan tidak tersedia)
                                        else {
                                            $desa_name_upper = strtoupper(trim($row['nama_desa']));
                                            if (isset($kantor_desa_map[$desa_name_upper])) {
                                                $kd = $kantor_desa_map[$desa_name_upper];
                                                // check multiple keys in kantor desa record
                                                $kd_lat = null; $kd_long = null;
                                                foreach (['latitude','lat'] as $c) if (isset($kd[$c])) { $kd_lat = $kd[$c]; break; }
                                                foreach (['longtitude','longtitude','lon','bujur'] as $c) if (isset($kd[$c])) { $kd_long = $kd[$c]; break; }
                                                if (is_numeric($kd_lat) && is_numeric($kd_long) && $kd_lat != 0 && $kd_long != 0) {
                                                    $ref_source = 'Kantor Desa';
                                                }
                                            }
                                        }
    
                                        if ($ref_source === null) $missing[] = 'Koordinat Referensi (Kecamatan/Kantor Desa)';
    
                                        $ready = empty($missing);
                                        ?>
    
                                        <td><?php echo $school_lat; ?></td>
                                        <td><?php echo $school_long; ?></td>
    
                                        <td>
                                            <div class="action-btns">
                                                <button class="btn-icon btn-edit"
                                                    onclick="editData(<?php echo $row['id_sekolah']; ?>)"
                                                    title="Edit">
                                                    <img src="assets/icons/edit.png" alt="Edit">
                                                </button>
    
                                                <button class="btn-icon btn-delete"
                                                    onclick="deleteData(<?php echo $row['id_sekolah']; ?>)"
                                                    title="Hapus">
                                                    <img src="assets/icons/delete.png" alt="Hapus">
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
    
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?>&per_page=<?php echo ($per_page === 0) ? 'all' : $per_page; ?>">« Pertama</a>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>&per_page=<?php echo ($per_page === 0) ? 'all' : $per_page; ?>">‹ Sebelumnya</a>
                                <?php endif; ?>
    
                                <?php 
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <?php if ($i == $page): ?>
                                        <span class="active"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>&per_page=<?php echo ($per_page === 0) ? 'all' : $per_page; ?>"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
    
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>&per_page=<?php echo ($per_page === 0) ? 'all' : $per_page; ?>">Selanjutnya ›</a>
                                    <a href="?page=<?php echo $total_pages; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>&per_page=<?php echo ($per_page === 0) ? 'all' : $per_page; ?>">Terakhir »</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                            <p>Belum ada data sekolah. <a href="input_data.php" style="color: #0066cc; text-decoration: none;">Tambah data baru</a></p>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Edit Data -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Data Sekolah</h2>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editForm" onsubmit="submitEditForm(event)">
                <div class="form-group">
                    <label for="edit_id_kecamatan">Nama Kecamatan <span style="color: red;">*</span></label>
                    <select name="id_kecamatan" id="edit_id_kecamatan" required>
                        <option value="">-- Pilih Kecamatan --</option>
                        <?php foreach ($kecamatans as $k): ?>
                            <option value="<?php echo $k['id_kecamatan']; ?>"><?php echo htmlspecialchars($k['nama_kecamatan']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_nama_desa">Nama Desa <span style="color: red;">*</span></label>
                    <input type="text" name="nama_desa" id="edit_nama_desa" required>
                </div>

                <div class="form-group">
                    <label for="edit_alamat">Alamat <span style="color: red;">*</span></label>
                    <textarea name="alamat" id="edit_alamat" required></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_latitude">Latitude <span style="color: red;">*</span></label>
                    <input type="text" name="latitude" id="edit_latitude" placeholder="Contoh: -6.200000" required>
                </div>

                <div class="form-group">
                    <label for="edit_longtitude">longtitude <span style="color: red;">*</span></label>
                    <input type="text" name="longtitude" id="edit_longtitude" placeholder="Contoh: 106.816666" required>
                </div>

                <div class="form-group">
                    <label for="edit_nama_sekolah">Nama Sekolah <span style="color: red;">*</span></label>
                    <input type="text" name="nama_sekolah" id="edit_nama_sekolah" required>
                </div>

                <div class="form-group">
                    <label for="edit_tingkat_pendidikan">Tingkat Pendidikan <span style="color: red;">*</span></label>
                    <input type="text" name="tingkat_pendidikan" id="edit_tingkat_pendidikan" placeholder="Contoh: SD, SMP, SMA" required>
                </div>

                <div class="form-group">
                    <label for="edit_npsn">NPSN <span style="color: red;">*</span></label>
                    <input type="number" name="npsn" id="edit_npsn" placeholder="Contoh: 12345678" required>
                </div>

                <div class="form-group">
                    <label for="edit_status">Status <span style="color: red;">*</span></label>
                    <input type="text" name="status" id="edit_status" placeholder="Contoh: Negeri/Swasta" required>
                </div>

                <div id="editErrors" style="display: none; margin-bottom: 20px; padding: 12px 16px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 8px;"></div>

                <div class="modal-footer">
                    <button type="submit" class="btn-modal btn-save btn-icon-text">
                        <img src="assets/icons/simpan.png" alt="Simpan">
                        <span>Simpan Perubahan</span>
                    </button>

                    <button type="button" class="btn-modal btn-cancel" onclick="closeEditModal()">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Kantor Desa -->
    <div id="editKantorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Kantor Desa</h2>
                <button class="modal-close" onclick="closeEditKantorModal()">&times;</button>
            </div>
            <form id="editKantorForm" onsubmit="submitEditKantorForm(event)">
                <div class="form-group">
                    <label for="edit_k_id_kecamatan">Nama Kecamatan <span style="color: red;">*</span></label>
                    <select name="id_kecamatan" id="edit_k_id_kecamatan" required>
                        <option value="">-- Pilih Kecamatan --</option>
                        <?php foreach ($kecamatans as $k): ?>
                            <option value="<?php echo $k['id_kecamatan']; ?>"><?php echo htmlspecialchars($k['nama_kecamatan']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_k_nama_titik">Nama Titik <span style="color: red;">*</span></label>
                    <input type="text" name="nama_titik" id="edit_k_nama_titik" required>
                </div>

                <div class="form-group">
                    <label for="edit_k_desa">Desa <span style="color: red;">*</span></label>
                    <input type="text" name="desa" id="edit_k_desa" required>
                </div>

                <div class="form-group">
                    <label for="edit_k_latitude">Latitude</label>
                    <input type="text" name="latitude" id="edit_k_latitude" placeholder="Contoh: -7.154584">
                </div>

                <div class="form-group">
                    <label for="edit_k_longtitude">longtitude</label>
                    <input type="text" name="longtitude" id="edit_k_longtitude" placeholder="Contoh: 112.696071">
                </div>

                <div id="editKantorErrors" style="display: none; margin-bottom: 20px; padding: 12px 16px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 8px;"></div>

                <div class="modal-footer">
                    <button type="submit" class="btn-modal btn-save btn-icon-text">
                        <img src="assets/icons/simpan.png" alt="Simpan">
                        <span>Simpan Perubahan</span>
                    </button>

                    <button type="button" class="btn-modal btn-cancel" onclick="closeEditKantorModal()">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Kecamatan -->
    <div id="editKecModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Kecamatan / Desa</h2>
                <button class="modal-close" onclick="closeEditKecModal()">&times;</button>
            </div>
            <form id="editKecForm" onsubmit="submitEditKecForm(event)">
                <input type="hidden" name="id" id="edit_kec_id">
                <div class="form-group">
                    <label for="edit_kec_nama_kecamatan">Nama Kecamatan</label>
                    <input type="text" name="nama_kecamatan" id="edit_kec_nama_kecamatan">
                </div>
                <div class="form-group">
                    <label for="edit_kec_nama_desa">Nama Desa</label>
                    <input type="text" name="nama_desa" id="edit_kec_nama_desa">
                </div>
                <div class="form-group">
                    <label for="edit_kec_id_wilayah">ID Wilayah</label>
                    <input type="text" name="id_wilayah" id="edit_kec_id_wilayah">
                </div>
                <div class="form-group">
                    <label for="edit_kec_latitude">Latitude</label>
                    <input type="text" name="latitude" id="edit_kec_latitude">
                </div>
                <div class="form-group">
                    <label for="edit_kec_longtitude">Longtitude</label>
                    <input type="text" name="longtitude" id="edit_kec_longtitude">
                </div>

                <div id="editKecErrors" style="display: none; margin-bottom: 20px; padding: 12px 16px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 8px;"></div>

                <div class="modal-footer">
                    <button type="submit" class="btn-modal btn-save btn-icon-text">
                        <img src="assets/icons/simpan.png" alt="Simpan">
                        <span>Simpan Perubahan</span>
                    </button>
                    <button type="button" class="btn-modal btn-cancel" onclick="closeEditKecModal()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="confirmDeleteModal" class="confirm-modal">
        <div class="confirm-modal-content">
            <div class="confirm-icon">⚠️</div>
            <h3>Hapus Data?</h3>
            <p>Apakah Anda yakin ingin menghapus data sekolah ini? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="confirm-buttons">
                <button class="btn-confirm-delete btn-icon-text" onclick="confirmDelete()">
                    <img src="assets/icons/delete1.png" alt="Hapus">
                    <span>Hapus</span>
                </button>

                <button class="btn-confirm-cancel" onclick="cancelDelete()">
                    Batal
                </button>
            </div>
        </div>
    </div>

    <!-- Notifikasi Logout Modal -->
    <div id="notificationModal" class="notification-modal">
        <div class="notification-modal-content">
            <div class="notification-message">Apakah Anda yakin untuk logout?</div>
            <div class="notification-buttons">
                <button class="notification-btn notification-btn-cancel" onclick="cancelLogout()">CANCEL</button>
                <button class="notification-btn notification-btn-logout" onclick="confirmLogout()">OK</button>
            </div>
        </div>
    </div>

    <script>
        // Initialize: Hide sidebar on mobile on page load
        window.addEventListener('DOMContentLoaded', function() {
            if (window.innerWidth <= 768) {
                const sidebar = document.querySelector('.sidebar');
                sidebar.classList.add('hidden');
            }
        });

        let currentDeleteId = null;
        let currentEditId = null;

        // ===== DROPDOWN MENU =====
        function toggleDropdown(event) {
            event.preventDefault();
            const menu = document.getElementById('rekomendasi-menu');
            const arrow = event.target.closest('.dropdown-toggle').querySelector('.dropdown-arrow');
            
            if (menu.classList.contains('show')) {
                menu.classList.remove('show');
                arrow.style.transform = 'rotate(0deg)';
                arrow.style.transition = 'transform 0.3s ease';
            } else {
                menu.classList.add('show');
                arrow.style.transform = 'rotate(90deg)';
                arrow.style.transition = 'transform 0.3s ease';
            }
        }

        // ===== EDIT MODAL FUNCTIONS =====
        function editData(id) {
            currentEditId = id;
            
            // Fetch data dari server
            fetch('hasil_input.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_edit_data&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data) {
                    document.getElementById('edit_id_kecamatan').value = data.id_kecamatan;
                    document.getElementById('edit_nama_desa').value = data.nama_desa;
                    document.getElementById('edit_alamat').value = data.alamat;
                    document.getElementById('edit_latitude').value = (data.latitude !== undefined ? data.latitude : '');
                    document.getElementById('edit_longtitude').value = (data.longtitude !== undefined ? data.longtitude : (data.Longtitude !== undefined ? data.Longtitude : ''));
                    document.getElementById('edit_nama_sekolah').value = data.nama_sekolah;
                    document.getElementById('edit_tingkat_pendidikan').value = data.tingkat_pendidikan;
                    document.getElementById('edit_npsn').value = (data.npsn !== undefined ? data.npsn : '');
                    document.getElementById('edit_status').value = (data.status !== undefined ? data.status : '');
                    document.getElementById('editErrors').style.display = 'none';
                    openEditModal();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal memuat data. Silakan coba lagi.');
            });
        }

        function openEditModal() {
            document.getElementById('editModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            document.getElementById('editForm').reset();
        }

        // ===== EDIT KANTOR DESA =====
        let currentEditKantorId = null;
        function editKantor(id) {
            currentEditKantorId = id;
            fetch('hasil_input.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_kantor_data&id=' + id
            })
            .then(r => r.json())
            .then(data => {
                if (!data) return alert('Data tidak ditemukan');
                document.getElementById('edit_k_id_kecamatan').value = data.id_kecamatan ?? '';
                document.getElementById('edit_k_nama_titik').value = data.nama_titik ?? '';
                document.getElementById('edit_k_desa').value = data.desa ?? '';
                document.getElementById('edit_k_latitude').value = (data.latitude !== undefined ? data.latitude : '');
                document.getElementById('edit_k_longtitude').value = (data.longtitude !== undefined ? data.longtitude : (data.Longtitude !== undefined ? data.Longtitude : ''));
                document.getElementById('editKantorErrors').style.display = 'none';
                openEditKantorModal();
            })
            .catch(err => { console.error(err); alert('Gagal memuat data kantor desa'); });
        }

        function openEditKantorModal() {
            document.getElementById('editKantorModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeEditKantorModal() {
            document.getElementById('editKantorModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            document.getElementById('editKantorForm').reset();
        }

        // Submit Kantor Desa edit form
        function submitEditKantorForm(event) {
            event.preventDefault();
            if (!currentEditKantorId) return;
            const form = document.getElementById('editKantorForm');
            const formData = new FormData(form);
            formData.append('action', 'update_kantor');
            formData.append('id', currentEditKantorId);

            fetch('hasil_input.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showSuccessMessage('Data Kantor Desa berhasil diperbarui');
                        closeEditKantorModal();
                        setTimeout(() => location.reload(), 800);
                    } else {
                        const errDiv = document.getElementById('editKantorErrors');
                        errDiv.innerHTML = '<strong>Error:</strong> ' + (data.errors ? data.errors.join('<br>') : 'Terjadi kesalahan');
                        errDiv.style.display = 'block';
                    }
                })
                .catch(err => { console.error(err); alert('Gagal menyimpan perubahan'); });
        }

        function submitEditForm(event) {
            event.preventDefault();
            
            const formData = new FormData(document.getElementById('editForm'));
            formData.append('action', 'update');
            formData.append('id', currentEditId);

            fetch('hasil_input.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Tampilkan pesan sukses
                    showSuccessMessage('Data berhasil diperbarui!');
                    closeEditModal();
                    
                    // Refresh tabel setelah 1 detik
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    // Tampilkan error
                    const errorDiv = document.getElementById('editErrors');
                    errorDiv.innerHTML = '<strong>Error:</strong> ' + (data.errors ? data.errors.join('<br>') : 'Terjadi kesalahan');
                    errorDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal menyimpan data. Silakan coba lagi.');
            });
        }

        // ===== DELETE MODAL FUNCTIONS =====
        function deleteData(id) {
            currentDeleteId = id;
            openDeleteModal();
        }

        function openDeleteModal() {
            document.getElementById('confirmDeleteModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteModal() {
            document.getElementById('confirmDeleteModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            currentDeleteId = null;
        }

        function confirmDelete() {
            if (!currentDeleteId) return;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', currentDeleteId);

            fetch('hasil_input.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    showSuccessMessage('Data berhasil dihapus!');
                    closeDeleteModal();
                    
                    // Refresh tabel setelah 1 detik
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alert('Gagal menghapus data. Silakan coba lagi.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal menghapus data. Silakan coba lagi.');
            });
        }

        function cancelDelete() {
            closeDeleteModal();
        }

        // ===== SUCCESS MESSAGE =====
        function showSuccessMessage(message) {
            const dataSection = document.querySelector('.data-section');
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert-msg alert-success';
            alertDiv.innerHTML = '✅ ' + message;
            
            const searchContainer = dataSection.querySelector('.search-container');
            searchContainer.parentNode.insertBefore(alertDiv, searchContainer);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }

        // ===== CLOSE MODALS WHEN CLICKING OUTSIDE =====
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('confirmDeleteModal');
            
            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }

        // ===== LOGOUT FUNCTIONS =====
        function showLogoutNotification() {
            const modal = document.getElementById('notificationModal');
            modal.classList.add('show');
        }

        function cancelLogout() {
            const modal = document.getElementById('notificationModal');
            modal.classList.remove('show');
        }

        function confirmLogout() {
            window.location.href = 'logout.php';
        }

        // Close logout modal when clicking outside
        const notifModal = document.getElementById('notificationModal');
        if (notifModal) {
            notifModal.addEventListener('click', function(event) {
                if (event.target === notifModal) {
                    cancelLogout();
                }
            });
        }

        // Delete Kantor Desa (simple confirm + request)
        function deleteKantor(id) {
            if (!confirm('Hapus data Kantor Desa ini?')) return;
            const formData = new FormData();
            formData.append('action', 'delete_kantor');
            formData.append('id', id);
            fetch('hasil_input.php', { method: 'POST', body: formData })
                .then(resp => { if (resp.ok) { showSuccessMessage('Kantor Desa berhasil dihapus.'); setTimeout(() => location.reload(), 800); } else { alert('Gagal menghapus kantor desa'); } })
                .catch(err => { console.error(err); alert('Request error'); });
        }

        // ===== EDIT/DELETE KECAMATAN =====
        let currentEditKecId = null;
        function editKecamatan(id) {
            currentEditKecId = id;
            fetch('hasil_input.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_kecamatan&id=' + encodeURIComponent(id)
            })
            .then(r => r.json())
            .then(data => {
                if (!data) return alert('Data tidak ditemukan');
                document.getElementById('edit_kec_id').value = data.id ?? '';
                document.getElementById('edit_kec_nama_kecamatan').value = data.nama_kecamatan ?? '';
                document.getElementById('edit_kec_nama_desa').value = data.nama_desa ?? '';
                document.getElementById('edit_kec_id_wilayah').value = data.id_wilayah ?? (data.id_wilayah_1 ?? '');
                document.getElementById('edit_kec_latitude').value = (data.latitude !== undefined ? data.latitude : '');
                document.getElementById('edit_kec_longtitude').value = (data.longtitude !== undefined ? data.longtitude : (data.Longtitude !== undefined ? data.Longtitude : ''));
                document.getElementById('editKecErrors').style.display = 'none';
                openEditKecModal();
            })
            .catch(err => { console.error(err); alert('Gagal memuat data kecamatan/desa'); });
        }

        function openEditKecModal() {
            document.getElementById('editKecModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeEditKecModal() {
            document.getElementById('editKecModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            document.getElementById('editKecForm').reset();
            currentEditKecId = null;
        }

        function submitEditKecForm(event) {
            event.preventDefault();
            if (!currentEditKecId) return alert('ID kecamatan tidak ditemukan');

            const form = document.getElementById('editKecForm');
            const formData = new FormData(form);
            formData.append('action', 'update_kecamatan');
            formData.append('id', currentEditKecId);

            fetch('hasil_input.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showSuccessMessage('Data Kecamatan/Desa berhasil diperbarui');
                        closeEditKecModal();
                        setTimeout(() => location.reload(), 800);
                    } else {
                        const errDiv = document.getElementById('editKecErrors');
                        errDiv.innerHTML = '<strong>Error:</strong> ' + (data.errors ? data.errors.join('<br>') : 'Terjadi kesalahan');
                        errDiv.style.display = 'block';
                    }
                })
                .catch(err => { console.error(err); alert('Gagal menyimpan perubahan'); });
        }

        function deleteKecamatan(id) {
            if (!confirm('Hapus data Kecamatan/Desa ini?')) return;
            const formData = new FormData();
            formData.append('action', 'delete_kecamatan');
            formData.append('id', id);

            fetch('hasil_input.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data && data.success) {
                        showSuccessMessage('Kecamatan/Desa berhasil dihapus');
                        setTimeout(() => location.reload(), 800);
                    } else {
                        alert('Gagal menghapus data kecamatan/desa: ' + (data && data.error ? data.error : 'Terjadi kesalahan'));
                    }
                })
                .catch(err => { console.error(err); alert('Request error'); });
        }

        // ===== SHOW SUCCESS MESSAGE IF EXISTS =====
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get('msg');
            if (msg === 'deleted') {
                showSuccessMessage('Data berhasil dihapus!');
                window.history.replaceState({}, document.title, window.location.pathname);
            } else if (msg === 'deleted_kantor') {
                showSuccessMessage('Data Kantor Desa berhasil dihapus!');
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });

        // Mobile Menu Toggle
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            sidebar.classList.toggle('hidden');
            toggle.classList.toggle('active');
        }

        // Scroll detection: hide/show mobile buttons
        let lastScrollTop = 0;
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const adminStatusTop = document.querySelector('.admin-status-top');

        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            
            if (currentScroll > lastScrollTop && currentScroll > 60) {
                // Scrolling DOWN - hide buttons
                if (mobileMenuToggle) mobileMenuToggle.classList.add('hidden-on-scroll');
                if (adminStatusTop) adminStatusTop.classList.add('hidden-on-scroll');
            } else if (currentScroll < lastScrollTop) {
                // Scrolling UP - show buttons
                if (mobileMenuToggle) mobileMenuToggle.classList.remove('hidden-on-scroll');
                if (adminStatusTop) adminStatusTop.classList.remove('hidden-on-scroll');
            }
            lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
        });

        // Auto-hide notification after 5 seconds
        window.addEventListener('load', function() {
            const notificationAlert = document.getElementById('notificationAlert');
            if (notificationAlert) {
                setTimeout(function() {
                    notificationAlert.style.transition = 'opacity 0.5s ease';
                    notificationAlert.style.opacity = '0';
                    setTimeout(function() {
                        notificationAlert.style.display = 'none';
                    }, 500);
                    // Clean up URL parameter
                    window.history.replaceState({}, document.title, window.location.pathname);
                }, 5000);
            }

            // Auto-scroll ke data terbaru setelah data ditambahkan
            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get('msg');
            const dataType = urlParams.get('type');
            const newId = urlParams.get('id');

            if (msg === 'added') {
                setTimeout(function() {
                    if (dataType === 'sekolah') {
                        // Scroll ke section data sekolah
                        const sekolahSection = document.querySelector('.data-section:nth-of-type(2)');
                        if (sekolahSection) {
                            sekolahSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    } else if (dataType === 'kantor') {
                        // Scroll ke section data kantor desa
                        const kantoSection = document.querySelector('.data-section:nth-of-type(3)');
                        if (kantoSection) {
                            kantoSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }
                }, 800);
            }
        });
    </script>
</body>
</html>
