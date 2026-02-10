<?php
require_once 'config.php';

// Load kecamatan for selects (unique names only to avoid duplicates)
$kecamatans = fetch_all("SELECT MIN(id_kecamatan) as id_kecamatan, TRIM(nama_kecamatan) as nama_kecamatan FROM kecamatan WHERE COALESCE(TRIM(nama_kecamatan),'') <> '' GROUP BY TRIM(nama_kecamatan) ORDER BY nama_kecamatan");

$error = '';
$success = '';
$success_kantor = '';
$error_kantor = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // === Handle add Kantor Desa form ===
    if (isset($_POST['action']) && $_POST['action'] === 'add_kantor_desa') {
        if (!table_exists('kantor_desa')) {
            $error_kantor = 'Tabel `kantor_desa` belum ada di database.';
        } else {
            $id_kecamatan_k = (int)($_POST['id_kecamatan_k'] ?? 0);
            $nama_titik = escape(trim($_POST['nama_titik'] ?? ''));
            $desa_n = escape(trim($_POST['desa_k'] ?? ''));
            $lat_k = trim($_POST['lat_k'] ?? '');
            $lon_k = trim($_POST['lon_k'] ?? '');

            if ($id_kecamatan_k <= 0 || $nama_titik === '' || $desa_n === '') {
                $error_kantor = 'Isi Kecamatan, Nama Titik, dan Desa terlebih dahulu.';
            } elseif (($lat_k !== '' && !is_numeric($lat_k)) || ($lon_k !== '' && !is_numeric($lon_k))) {
                $error_kantor = 'Koordinat kantor desa harus berupa angka.';
            } else {
                // Insert kantor_desa (check which columns exist)
                $kd_cols = array_column(fetch_all("SHOW COLUMNS FROM kantor_desa"), 'Field');
                $cols = ['id_kecamatan', 'nama_titik', 'desa'];
                $vals = [$id_kecamatan_k, "'$nama_titik'", "'$desa_n'"];
                // detect lat/long column names
                $kd_lat_col = null; $kd_long_col = null;
                foreach (['latitude','lat','lintang'] as $c) { if (in_array($c, $kd_cols)) { $kd_lat_col = $c; break; } }
                foreach (['Longtitude','longtitude','Longtitude','lon','bujur'] as $c) { if (in_array($c, $kd_cols)) { $kd_long_col = $c; break; } }
                if ($kd_lat_col && $lat_k !== '') { $cols[] = $kd_lat_col; $vals[] = (float)$lat_k; }
                if ($kd_long_col && $lon_k !== '') { $cols[] = $kd_long_col; $vals[] = (float)$lon_k; }

                $sql_ins_kd = "INSERT INTO kantor_desa (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
                if (query($sql_ins_kd)) {
                    // redirect to hasil page for kantor desa
                    header('Location: hasil_kantor.php?msg=added'); exit;
                } else {
                    $error_kantor = 'Gagal menyimpan Kantor Desa (cek struktur tabel).';
                }
            }
        }
    }

    // === existing sekolah handling continues ===
    $nama_kecamatan = escape($_POST['nama_kecamatan'] ?? '');
    $nama_desa = escape($_POST['nama_desa'] ?? '');
    $alamat = escape($_POST['alamat'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $Longtitude = trim($_POST['Longtitude'] ?? '');
    $nama_sekolah = escape($_POST['nama_sekolah'] ?? '');
    $tingkat_pendidikan = escape($_POST['tingkat_pendidikan'] ?? '');
    $npsn = trim($_POST['npsn'] ?? '');
    $status = escape($_POST['status'] ?? '');

    // Kantor Desa (opsional)
    $kepala_desa = escape($_POST['kepala_desa'] ?? '');
    $alamat_kantor = escape($_POST['alamat_kantor'] ?? '');
    $desa_latitude = trim($_POST['desa_latitude'] ?? '');
    $desa_Longtitude = trim($_POST['desa_Longtitude'] ?? '');

    // Validation
    if (empty($nama_kecamatan) || empty($nama_desa) || $alamat === '' || $latitude === '' || $Longtitude === '' || $nama_sekolah === '' || $tingkat_pendidikan === '' || $npsn === '' || $status === '') {
        $error = 'Isi semua field yang bertanda * (wajib diisi)';
    } elseif (!is_numeric($latitude) || !is_numeric($Longtitude)) {
        $error = 'Latitude/Longtitude harus berupa angka';
    } elseif (!ctype_digit(strval($npsn))) {
        $error = 'NPSN harus berupa angka';
    } else {
        // Check if kecamatan exists, if not create it
        $kec_check = query("SELECT id_kecamatan FROM kecamatan WHERE nama_kecamatan = '$nama_kecamatan'");
        if (mysqli_num_rows($kec_check) > 0) {
            $kec_row = mysqli_fetch_assoc($kec_check);
            $id_kecamatan = $kec_row['id_kecamatan'];
        } else {
            // Create new kecamatan if it doesn't exist
            $insert_kec = "INSERT INTO kecamatan (nama_kecamatan) VALUES ('$nama_kecamatan')";
            if (mysqli_query($conn, $insert_kec)) {
                $id_kecamatan = mysqli_insert_id($conn);
            } else {
                $error = 'Error membuat kecamatan baru: ' . mysqli_error($conn);
                $id_kecamatan = null;
            }
        }
        
        if ($id_kecamatan) {
            // Handle kantor desa: create or update desa record if table exists
            $id_desa = null;
            if (table_exists('desa')) {
                $desa_cols = fetch_all("SHOW COLUMNS FROM desa");
                $desa_fields = array_column($desa_cols, 'Field');

                $desa_check = query("SELECT id_desa FROM desa WHERE nama_desa = '$nama_desa' AND id_kecamatan = $id_kecamatan");
                if ($desa_check && mysqli_num_rows($desa_check) > 0) {
                    $drow = mysqli_fetch_assoc($desa_check);
                    $id_desa = $drow['id_desa'];
                    // update desa data if provided
                    $update_parts = [];
                    if (in_array('kepala_desa', $desa_fields) && $kepala_desa !== '') $update_parts[] = "kepala_desa = '$kepala_desa'";
                    if (in_array('alamat', $desa_fields) && $alamat_kantor !== '') $update_parts[] = "alamat = '$alamat_kantor'";
                    if (in_array('latitude', $desa_fields) && $desa_latitude !== '') $update_parts[] = "latitude = " . (float)$desa_latitude;
                    if (in_array('Longtitude', $desa_fields) && $desa_Longtitude !== '') $update_parts[] = "Longtitude = " . (float)$desa_Longtitude;
                    if ($update_parts) {
                        query("UPDATE desa SET " . implode(', ', $update_parts) . " WHERE id_desa = $id_desa");
                    }
                } else {
                    $cols = ['id_kecamatan', 'nama_desa'];
                    $vals = [$id_kecamatan, "'$nama_desa'"];
                    if (in_array('kepala_desa', $desa_fields) && $kepala_desa !== '') { $cols[]='kepala_desa'; $vals[] = "'$kepala_desa'"; }
                    if (in_array('alamat', $desa_fields) && $alamat_kantor !== '') { $cols[]='alamat'; $vals[] = "'$alamat_kantor'"; }
                    if (in_array('latitude', $desa_fields) && $desa_latitude !== '') { $cols[]='latitude'; $vals[] = (float)$desa_latitude; }
                    if (in_array('Longtitude', $desa_fields) && $desa_Longtitude !== '') { $cols[]='Longtitude'; $vals[] = (float)$desa_Longtitude; }
                    $sql_ins = "INSERT INTO desa (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
                    if (query($sql_ins)) { $id_desa = mysqli_insert_id($conn); }
                }
            }

            // Prepare values
            $latitude_f = (float)$latitude;
            $Longtitude_f = (float)$Longtitude;
            $npsn_i = (int)$npsn;

            // Build sekolah insert dynamically (include id_desa if sekolah has that column)
            $sk_cols = ['id_kecamatan', 'nama_desa', 'alamat', 'latitude', 'Longtitude', 'nama_sekolah', 'tingkat_pendidikan', 'npsn', 'status'];
            $sk_vals = [$id_kecamatan, "'$nama_desa'", "'$alamat'", $latitude_f, $Longtitude_f, "'$nama_sekolah'", "'$tingkat_pendidikan'", $npsn_i, "'$status'"];
            $sk_table_cols = array_column(fetch_all("SHOW COLUMNS FROM sekolah"), 'Field');
            if (in_array('id_desa', $sk_table_cols) && isset($id_desa) && $id_desa) {
                // insert id_desa after id_kecamatan
                array_splice($sk_cols, 1, 0, 'id_desa');
                array_splice($sk_vals, 1, 0, $id_desa);
            }
            $insert_query = "INSERT INTO sekolah (" . implode(',', $sk_cols) . ") VALUES (" . implode(',', $sk_vals) . ")";

            if (mysqli_query($conn, $insert_query)) {
                // redirect to hasil page for sekolah
                header('Location: hasil_sekolah.php?msg=added'); exit;
            } else {
                $error = 'Error: ' . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Data - Sistem Rekomendasi</title>
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

        /* dropdown arrow */
        .dropdown-arrow {
            margin-left: auto;
            display: inline-flex;
        }

        .dropdown-arrow img {
            width: 14px;
            transition: transform 0.3s ease;
        }

        /* rotate arrow ketika aktif */
        .dropdown-toggle.active .dropdown-arrow img {
            transform: rotate(90deg);
        }

        .dropdown-arrow {
            display: inline-block;
            transition: transform 0.3s ease;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 6px;
            width: 100%;
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
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

        /* Form Container */
        .form-wrapper {
            display: flex;
            justify-content: center;
            animation: slideInUp 0.8s ease;
            margin-bottom: 60px;
        }

        .form-container {
            background: white;
            border-radius: 16px;
            padding: 50px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            width: 100%;
            border-top: 5px solid #ff9f43;
            position: relative;
            overflow: hidden;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 159, 67, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(50%, -50%);
            pointer-events: none;
        }

        .form-content {
            position: relative;
            z-index: 1;
        }

        .form-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a3a5c;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-subtitle {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 35px;
        }

        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .form-grid.full {
            grid-template-columns: 1fr;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            color: #1a3a5c;
            font-weight: 600;
            font-size: 14px;
        }

        .form-label .required {
            color: #e74c3c;
            margin-left: 4px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #bebbbb;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
        }

        .form-control:focus {
            outline: none;
            border-color: #1a3a5c;
            background-color: white;
            box-shadow: 0 0 0 4px rgba(26, 58, 92, 0.08);
        }

        .form-control::placeholder {
            color: #95a5a6;
        }

        select.form-control {
            cursor: pointer;
        }

        /* Alert Messages */
        .alert {
            padding: 16px 18px;
            border-radius: 8px;
            margin-bottom: 28px;
            font-size: 14px;
            border-left: 4px solid;
            animation: slideInDown 0.4s ease;
        }

        .alert-error {
            background-color: #fadbd8;
            color: #c0392b;
            border-left-color: #e74c3c;
        }

        .alert-success {
            background-color: #d5f4e6;
            color: #27ae60;
            border-left-color: #2ecc71;
        }

        /* Button Group */
        .button-group {
            display: flex;
            gap: 20px;
            margin-top: 40px;
        }

        .btn {
            padding: 14px 40px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
        }

        .btn-reset {
            background-color: white;
            color: #1a3a5c;
            border: 2px solid #1a3a5c;
        }

        .btn-reset:hover {
            background-color: #f5f5f5;
            transform: translateY(-2px);
        }

        .btn-submit {
            background: linear-gradient(135deg, #1a3a5c 0%, #0f2643 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(26, 58, 92, 0.3);
        }

        .btn-submit:hover {
            box-shadow: 0 6px 20px rgba(26, 58, 92, 0.4);
            transform: translateY(-2px);
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

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
            }

            .sidebar-menu a {
                padding: 15px 10px;
                font-size: 12px;
            }

            .main-content {
                margin-left: 80px;
                padding: 30px 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-container {
                padding: 40px;
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
                padding: 70px 20px 20px;
            }

            .admin-status-top {
                display: none;
            }

            .form-container {
                padding: 30px 20px;
            }

            .page-header {
                padding-left: 64px;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .form-title {
                font-size: 20px;
            }

            .form-grid {
                gap: 15px;
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
                gap: 10px;
            }

            .btn {
                padding: 12px 30px;
                font-size: 14px;
                width: 100%;
            }

            .form-group {
                margin-bottom: 15px;
            }

            input, select, textarea {
                padding: 10px;
                font-size: 14px;
            }
        }

        @media (max-width: 600px) {
            .form-container {
                padding: 20px 15px;
            }

            .page-header h1 {
                font-size: 20px;
                margin-bottom: 10px;
            }

            .page-header p {
                font-size: 12px;
            }

            .form-title {
                font-size: 18px;
                margin-bottom: 15px;
            }

            .form-group {
                margin-bottom: 12px;
            }

            label {
                font-size: 12px;
                margin-bottom: 4px;
            }

            input, select, textarea {
                padding: 8px 10px;
                font-size: 13px;
            }

            .btn {
                padding: 10px 16px;
                font-size: 12px;
                width: 100%;
            }

            .button-group {
                flex-direction: column;
                gap: 8px;
            }

            .form-grid label {
                font-size: 11px;
            }

            .error-message, .success-message {
                padding: 12px;
                font-size: 12px;
                margin-bottom: 15px;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 100%;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .form-container {
                padding: 15px 12px;
            }

            .page-header h1 {
                font-size: 18px;
            }

            .page-header p {
                font-size: 11px;
            }

            .form-title {
                font-size: 16px;
                margin-bottom: 12px;
            }

            .form-group {
                margin-bottom: 10px;
            }

            label {
                font-size: 11px;
            }

            input, select, textarea {
                padding: 6px 8px;
                font-size: 12px;
                width: 100%;
            }

            .btn {
                padding: 8px 12px;
                font-size: 11px;
                width: 100%;
                min-height: 36px;
            }

            .button-group {
                gap: 6px;
            }
        }

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

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
            }

            .mobile-user-icon {
                display: flex !important;
                visibility: visible;
            }

            .sidebar {
                transition: all 0.3s ease;
            }

            .sidebar.hidden {
                display: none !important;
            }

            .container {
                flex-direction: column;
            }

            .main-content {
                margin-left: 0 !important;
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
        <aside class="sidebar" id="sidebar">
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
                            <a href="input_data.php" class="active">
                                <span class="menu-icon">
                                    <img src="assets/icons/input.png" alt="Input Data">
                                </span>
                                <span class="menu-text">Input Data</span>
                            </a>
                        </li>
                        <li>
                            <a href="hasil_input.php">
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
                <h1>Input Data Baru</h1>
                <p>Tambahkan informasi sekolah baru ke dalam sistem</p>
            </div>

            <div class="form-wrapper">
                <div class="form-container">
                    <div class="form-content">
                        <div class="form-title">
                            <span>📋</span>
                            Form Input Data Sekolah
                        </div>
                        <div class="form-subtitle">Isi semua form di bawah ini!</div>

                        <?php if ($error): ?>
                            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" id="inputForm">
                            <div class="form-grid">
    
                                <div class="form-group">
                                    <label class="form-label">
                                        Tingkat Pendidikan 
                                    </label>
                                    <input type="text" class="form-control" name="tingkat_pendidikan" placeholder="Masukkan tingkat pendidikan (SD/SMP/SMA)" value="<?php echo htmlspecialchars($_POST['tingkat_pendidikan'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        Nama Sekolah 
                                    </label>
                                    <input type="text" class="form-control" name="nama_sekolah" placeholder="Masukkan nama sekolah" value="<?php echo htmlspecialchars($_POST['nama_sekolah'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">
                                        NPSN 
                                    </label>
                                    <input type="text" class="form-control" name="npsn" placeholder="Masukkan NPSN" value="<?php echo htmlspecialchars($_POST['npsn'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        Status 
                                    </label>
                                    <input type="text" class="form-control" name="status" placeholder="Masukkan status sekolah" value="<?php echo htmlspecialchars($_POST['status'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-grid full">
                                <div class="form-group">
                                    <label class="form-label">
                                        Alamat 
                                    </label>
                                    <input type="text" class="form-control" name="alamat" placeholder="Masukkan alamat lengkap" value="<?php echo htmlspecialchars($_POST['alamat'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">
                                        Latitude 
                                    </label>
                                    <input type="number" class="form-control" name="latitude" placeholder="Masukkan latitude" step="0.000001" value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        Longtitude 
                                    </label>
                                    <input type="number" class="form-control" name="Longtitude" placeholder="Masukkan Longtitude" step="0.000001" value="<?php echo htmlspecialchars($_POST['Longtitude'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="button-group">
                                <button type="reset" class="btn btn-reset">RESET</button>
                                <button type="submit" class="btn btn-submit">SUBMIT</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="form-wrapper">
                                <div class="form-container">
                    <div class="form-content">
                        <div class="form-title">
                            <span>📋</span>
                            Form Input Data Kecamatan
                        </div>
                        <div class="form-subtitle">Isi semua form di bawah ini!</div>

                        <?php if ($error): ?>
                            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" id="inputForm">
                            <div class="form-grid">
    
                                <div class="form-group">
                                    <label class="form-label">
                                        Nama Kecamatan 
                                    </label>
                                    <input type="text" class="form-control" name="nama_kecamatan" placeholder="Masukkan nama kecamatan" value="<?php echo htmlspecialchars($_POST['nama_kecamatan'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        Nama Desa 
                                    </label>
                                    <input type="text" class="form-control" name="nama_desa" placeholder="Masukkan nama desa" value="<?php echo htmlspecialchars($_POST['nama_desa'] ?? ''); ?>" required>
                                </div>
                            </div>


                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">
                                        Latitude 
                                    </label>
                                    <input type="number" class="form-control" name="latitude" placeholder="Masukkan latitude" step="0.000001" value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        Longtitude 
                                    </label>
                                    <input type="number" class="form-control" name="Longtitude" placeholder="Masukkan Longtitude" step="0.000001" value="<?php echo htmlspecialchars($_POST['Longtitude'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="button-group">
                                <button type="reset" class="btn btn-reset">RESET</button>
                                <button type="submit" class="btn btn-submit">SUBMIT</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </main>
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

        // Mobile Menu Toggle
        function toggleMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('mobileMenuToggle');
            
            sidebar.classList.toggle('hidden');
            toggle.classList.toggle('active');
        }

        // Close menu when a link is clicked on mobile (but not dropdowns)
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            link.addEventListener('click', function() {
                // Don't close sidebar for dropdown toggles
                if (this.classList.contains('dropdown-toggle')) {
                    return;
                }
                
                if (window.innerWidth <= 768) {
                    const sidebar = document.getElementById('sidebar');
                    sidebar.classList.add('hidden');
                    document.getElementById('mobileMenuToggle').classList.remove('active');
                }
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('mobileMenuToggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.add('hidden');
                    toggle.classList.remove('active');
                }
            }
        });

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

        // Close modal when clicking outside
        const notifModal = document.getElementById('notificationModal');
        if (notifModal) {
            notifModal.addEventListener('click', function(event) {
                if (event.target === notifModal) {
                    cancelLogout();
                }
            });
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
    </script>
</body>
</html>