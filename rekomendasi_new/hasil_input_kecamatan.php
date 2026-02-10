<?php
require_once 'config.php';

$search = escape($_GET['search'] ?? '');
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Build query
$query = "SELECT s.*, k.nama_kecamatan FROM sekolah s 
          JOIN kecamatan k ON s.id_kecamatan = k.id_kecamatan";

if ($search) {
    $query .= " WHERE k.nama_kecamatan LIKE '%$search%' 
               OR s.nama_desa LIKE '%$search%' 
               OR s.nama_sekolah LIKE '%$search%'";
}

// Count total - Fixed query
$count_sql = "SELECT COUNT(*) as total FROM sekolah s 
              JOIN kecamatan k ON s.id_kecamatan = k.id_kecamatan";
if ($search) {
    $count_sql .= " WHERE k.nama_kecamatan LIKE '%$search%' 
                   OR s.nama_desa LIKE '%$search%' 
                   OR s.nama_sekolah LIKE '%$search%'";
}
$count_result = query($count_sql);
$total_data = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_data / $per_page);

// Get data
$query .= " ORDER BY s.id_sekolah DESC LIMIT $offset, $per_page";
$result = query($query);

// Get all data for statistics
$all_schools = fetch_all("SELECT * FROM sekolah");
$total_sekolah = count($all_schools);
$kecamatan_unik = count(fetch_all("SELECT DISTINCT id_kecamatan FROM sekolah"));
$desa_unik = count(fetch_all("SELECT DISTINCT nama_desa FROM sekolah"));

// Handle delete dengan konfirmasi via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    query("DELETE FROM sekolah WHERE id_sekolah = $id");
    header('Location: hasil_input.php?msg=deleted');
    exit;
}

// Handle edit via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_edit_data') {
    $id = (int)$_POST['id'];
    $data = fetch_row("SELECT s.*, k.nama_kecamatan FROM sekolah s JOIN kecamatan k ON s.id_kecamatan = k.id_kecamatan WHERE s.id_sekolah = $id");
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Get kecamatan list
$kecamatans = fetch_all('SELECT * FROM kecamatan');

// Handle update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = (int)$_POST['id'];
    $id_kecamatan = (int)$_POST['id_kecamatan'];
    $nama_desa = escape(trim($_POST['nama_desa'] ?? ''));
    $alamat = escape(trim($_POST['alamat'] ?? ''));
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
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
    if ($longitude === '' || !is_numeric($longitude)) $errors[] = 'Longitude harus berupa angka';
    if ($npsn === '' || !ctype_digit(strval($npsn))) $errors[] = 'NPSN harus berupa angka';
    if ($status === '') $errors[] = 'Status wajib diisi';

    header('Content-Type: application/json');
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
    } else {
        $latitude = (float)$latitude;
        $longitude = (float)$longitude;
        $npsn = (int)$npsn;

        // Update tanpa kolom foto (DB schema diperbarui)
        $sql = "UPDATE sekolah SET 
            id_kecamatan = $id_kecamatan,
            nama_desa = '$nama_desa',
            alamat = '$alamat',
            latitude = $latitude,
            longitude = $longitude,
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
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a3a5c;
            margin-bottom: 8px;
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

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
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
            padding: 12px;
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

        .mobile-menu-toggle span {
            width: 24px;
            height: 3px;
            background: white;
            border-radius: 2px;
            transition: all 0.3s ease;
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
        }

        .mobile-menu-toggle.hidden-on-scroll,
        .admin-status-top.hidden-on-scroll {
            transform: translateY(-80px);
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
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
                                    <img src="assets/icons/file_sekolah.png" alt="Input Data Sekolah">
                                </span>
                                <span class="menu-text">Input Data Sekolah</span>
                            </a>
                        </li>

                        <li>
                            <a href="input_data_kecamatan.php">
                                <span class="menu-icon">
                                    <img src="assets/icons/input.png" alt="Input Data Kecamatan">
                                </span>
                                <span class="menu-text">Input Data Kecamatan</span>
                            </a>
                        </li>

                        <li>
                            <a href="hasil_input.php">
                                <span class="menu-icon">
                                    <img src="assets/icons/hasil1.png" alt="Hasil Input Data Sekolah">
                                </span>
                                <span class="menu-text">Hasil Input Data Sekolah</span>
                            </a>
                        </li>

                        <li>
                            <a href="hasil_input_kecamatan.php" class="active">
                                <span class="menu-icon">
                                    <img src="assets/icons/file_kecamatan.png" alt="Hasil Input Data Kecamatan">
                                </span>
                                <span class="menu-text">Hasil Input Data Kecamatan</span>
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

            <div class="data-section">
                <div class="section-title">
                    <span>📊</span>
                    Daftar Data Sekolah
                </div>
                <div class="section-desc">Kelola, cari, edit, dan hapus data sekolah yang sudah diinputkan</div>

                <!-- Search Bar -->
                <div class="search-container">
                    <form method="GET" style="width: 100%;">
                        <div class="search-bar">
                            <span class="search-icon">🔍</span>
                            <input type="text" name="search" placeholder="Cari berdasarkan nama kecamatan, desa, atau sekolah..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </form>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_sekolah; ?></div>
                        <div class="stat-label">Total Data</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $kecamatan_unik; ?></div>
                        <div class="stat-label">Jumlah Kecamatan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $desa_unik; ?></div>
                        <div class="stat-label">JumlahDesa</div>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-wrapper">
                    <?php if ($total_data > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Kecamatan</th>
                                    <th>Nama Desa</th>
                                    <th>Latitude</th>
                                    <th>Longitude</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = $offset + 1;
                                while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_kecamatan']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_desa']); ?></td>
                                        <td>
                                        <td>-</td>
                                        <td><?php echo htmlspecialchars($row['tingkat_pendidikan']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_sekolah']); ?></td> 
                                        <td><?php echo htmlspecialchars($row['npsn']); ?></td>
                                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                                        <td><?php echo htmlspecialchars($row['alamat']); ?></td>
                                        <td><?php echo is_numeric($row['latitude']) ? number_format($row['latitude'], 6) : ''; ?></td>
                                        <td><?php echo is_numeric($row['longitude']) ? number_format($row['longitude'], 6) : ''; ?></td>
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
                                    <a href="?page=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?>">« Pertama</a>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">‹ Sebelumnya</a>
                                <?php endif; ?>

                                <?php 
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <?php if ($i == $page): ?>
                                        <span class="active"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Selanjutnya ›</a>
                                    <a href="?page=<?php echo $total_pages; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Terakhir »</a>
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
                    <label for="edit_longitude">Longitude <span style="color: red;">*</span></label>
                    <input type="text" name="longitude" id="edit_longitude" placeholder="Contoh: 106.816666" required>
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
                    document.getElementById('edit_latitude').value = data.latitude;
                    document.getElementById('edit_longitude').value = data.longitude;
                    document.getElementById('edit_nama_sekolah').value = data.nama_sekolah;
                    document.getElementById('edit_tingkat_pendidikan').value = data.tingkat_pendidikan;
                    document.getElementById('edit_npsn').value = (data.npsn !== undefined ? data.npsn : '');
                    document.getElementById('edit_status').value = (data.status !== undefined ? data.status : '');
                    // set foto preview jika ada
                    const editFotoImg = document.getElementById('editFotoImg');
                    if (editFotoImg) {
                        if (data.foto && data.foto !== '') {
                            editFotoImg.src = 'uploads/' + data.foto;
                        } else {
                            editFotoImg.src = 'assets/icons/no-image.png';
                        }
                    }
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

// Preview foto di edit modal saat memilih file
const editFotoInput = document.getElementById('edit_foto');
if (editFotoInput) {
    editFotoInput.addEventListener('change', function() {
        const file = this.files[0];
        const img = document.getElementById('editFotoImg');
        if (file && img) {
            const reader = new FileReader();
            reader.onload = function(e) { img.src = e.target.result; };
            reader.readAsDataURL(file);
        }
    });
}
        });

        // Scroll detection: hide/show mobile buttons
        let lastScrollTop = 0;
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const adminStatusTop = document.querySelector('.admin-status-top');

        // Mobile Menu Toggle
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            sidebar.classList.toggle('hidden');
            toggle.classList.toggle('active');
        }

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
