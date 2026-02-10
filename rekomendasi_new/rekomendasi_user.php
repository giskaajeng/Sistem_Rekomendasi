
<?php
session_start();
require_once 'config.php';

// Haversine Formula Function untuk menghitung jarak antara dua titik koordinat
function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    // Radius bumi dalam kilometer
    $R = 6371;
    
    // Konversi derajat ke radian
    $lat1_rad = deg2rad($lat1);
    $lon1_rad = deg2rad($lon1);
    $lat2_rad = deg2rad($lat2);
    $lon2_rad = deg2rad($lon2);
    
    // Perbedaan koordinat
    $dlat = $lat2_rad - $lat1_rad;
    $dlon = $lon2_rad - $lon1_rad;
    
    // Formula Haversine
    $a = sin($dlat / 2) * sin($dlat / 2) + 
         cos($lat1_rad) * cos($lat2_rad) * 
         sin($dlon / 2) * sin($dlon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $R * $c;
    
    return round($distance, 2);
}

// Inisialisasi variabel
$error = '';
$success = '';
$results = [];
$filters = [
    'kecamatan' => $_GET['kecamatan'] ?? '',
    'desa' => $_GET['desa'] ?? '',
    'tingkat_pendidikan' => $_GET['tingkat_pendidikan'] ?? ''
];

// Cek apakah user sudah klik tombol "Cari Sekolah Terdekat"
$is_searching = !empty($filters['kecamatan']) && !empty($filters['desa']) && !empty($filters['tingkat_pendidikan']);

// Get unique kecamatan names dari desa_new
$kecamatans = fetch_all("SELECT DISTINCT kecamatan FROM desa_new WHERE COALESCE(TRIM(kecamatan),'') <> '' ORDER BY kecamatan");

$desas = [];
$tingkats = fetch_all("SELECT DISTINCT tingkat_pendidikan FROM sekolah WHERE COALESCE(TRIM(tingkat_pendidikan),'') <> '' ORDER BY tingkat_pendidikan");

// Get desas berdasarkan kecamatan yang dipilih dari desa_new
if (!empty($filters['kecamatan'])) {
    $desas = fetch_all("SELECT DISTINCT desa FROM desa_new 
                        WHERE kecamatan = '" . escape($filters['kecamatan']) . "' 
                        AND desa IS NOT NULL
                        ORDER BY desa");
}

// Inisialisasi hasil pencarian
$results = [];
$total_results = 0;
$desa_latitude = null;
$desa_longitude = null;

// HANYA jalankan query pencarian jika semua filter sudah dipilih dan user klik tombol cari
if ($is_searching) {
    // Step 1: Dapatkan koordinat dari desa_new berdasarkan desa yang dipilih
    $desa_coord = fetch_row("SELECT latitude, longitude FROM desa_new 
                             WHERE kecamatan = '" . escape($filters['kecamatan']) . "' 
                             AND desa = '" . escape($filters['desa']) . "' 
                             LIMIT 1");
    
    if ($desa_coord && !empty($desa_coord['latitude']) && !empty($desa_coord['longitude'])) {
        $desa_latitude = (float)$desa_coord['latitude'];
        $desa_longitude = (float)$desa_coord['longitude'];
    }
    
    // Step 2: Query sekolah berdasarkan tingkat pendidikan, kecamatan, dan desa
    $query = "SELECT s.* FROM sekolah s 
              WHERE s.tingkat_pendidikan = '" . escape($filters['tingkat_pendidikan']) . "' 
              AND s.nama_desa = '" . escape($filters['desa']) . "'";
    
    // Tambahkan kondisi kecamatan jika ada id_kecamatan di sekolah
    $query .= " AND (s.id_kecamatan IN (
                SELECT id_kecamatan FROM kecamatan 
                WHERE nama_kecamatan = '" . escape($filters['kecamatan']) . "'
              ) OR 1=1)";
    
    $all_results = fetch_all($query);
    
    // Step 3: Hitung jarak untuk setiap sekolah menggunakan Haversine
    $results_with_distance = [];
    foreach ($all_results as $school) {
        // Validasi: sekolah harus memiliki koordinat yang valid
        $school_latitude = !empty($school['latitude']) ? (float)$school['latitude'] : null;
        $school_longitude = !empty($school['longtitude']) ? (float)$school['longtitude'] : null;
        
        // Hitung jarak jika referensi desa dan sekolah punya koordinat valid
        if ($desa_latitude && $desa_longitude && $school_latitude && $school_longitude) {
            $distance = haversineDistance($desa_latitude, $desa_longitude, $school_latitude, $school_longitude);
            $school['distance'] = $distance;
            $results_with_distance[] = $school;
        }
    }
    
    // Step 4: Sort berdasarkan jarak (ascending) dan ambil TOP 5
    usort($results_with_distance, function($a, $b) {
        if ($a['distance'] === null) return 1;
        if ($b['distance'] === null) return -1;
        return $a['distance'] <=> $b['distance'];
    });
    
    // Ambil hanya 6 hasil terdekat
    $results = array_slice($results_with_distance, 0, 6);
    $total_results = count($results);
    
    // Set pesan jika tidak ada hasil
    if ($total_results === 0) {
        $error = 'Tidak ada sekolah ' . $filters['tingkat_pendidikan'] . ' ditemukan di ' . $filters['desa'] . '. Pastikan data sekolah tersedia dan memiliki koordinat yang lengkap.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekomendasi Sekolah Terdekat - Sistem Rekomendasi</title>
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
            z-index: 9998;
            transition: transform 0.3s ease;
            transform: translateX(0);
        }

        .sidebar.hidden {
            transform: translateX(-100%);
            pointer-events: none;
        }

        /* Sidebar Backdrop Overlay */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 9997;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-backdrop.show {
            display: block;
            opacity: 1;
        }

        .sidebar-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 17px;
            padding: 16px 0;
            margin-top: 18px;
        }

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

        .logo-circle img {
            width: 115%;
            height: 115%;
            object-fit: contain;
        }

        .sidebar-header h2 {
            font-size: 16px;
            line-height: 1.3;
            margin: 0;
            color: #ffffff;
            margin-bottom: 10px;
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

        .menu-icon {
            display: inline-flex;
            align-items: center;
            margin-right: 12px;
        }

        .menu-icon img {
            width: 20px;
            height: 20px;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 6px;
            width: 100%;
            padding: 16px;
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
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }

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

        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #2ecc71;
            border-radius: 50%;
            margin-right: 6px;
        }

        /* Main content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 40px;
        }

        .page-header {
            margin-bottom: 40px;
            animation: slideInDown 0.6s ease;
        }

        .page-header h1 {
            font-size: 36px;
            color: #1a3a5c;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .page-header p {
            color: #7f8c8d;
            font-size: 14px;
        }

        /* Filter Card */
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-top: 4px solid #ff9f43;
            animation: slideInUp 0.6s ease;
        }

        .filter-title {
            font-size: 18px;
            font-weight: 700;
            color: #1a3a5c;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-label {
            color: #1a3a5c;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-input {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: white;
        }

        .filter-input:focus {
            outline: none;
            border-color: #1a3a5c;
            box-shadow: 0 0 0 4px rgba(26, 58, 92, 0.08);
        }

        .filter-button {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .btn {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-search {
            background: linear-gradient(135deg, #1a3a5c 0%, #0f2643 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(26, 58, 92, 0.2);
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 58, 92, 0.3);
        }

        .btn-reset {
            background: white;
            color: #1a3a5c;
            border: 2px solid #1a3a5c;
        }

        .btn-reset:hover {
            background: #f5f5f5;
        }

        /* Results Section */
        .results-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            animation: slideInUp 0.8s ease;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .results-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a3a5c;
        }

        .results-count {
            background: #e8f4f8;
            color: #0066cc;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        /* School Cards Grid */
        .schools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .school-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            border: 2px solid #f0f0f0;
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease backwards;
            position: relative;
        }

        .school-card:hover {
            border-color: #ff9f43;
            box-shadow: 0 8px 24px rgba(255, 159, 67, 0.15);
            transform: translateY(-5px);
        }

        .rank-badge {
            position: absolute;
            top: -10px;
            left: 20px;
            background: linear-gradient(135deg, #ff9f43 0%, #ff6b6b 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 12px;
            box-shadow: 0 4px 12px rgba(255, 159, 67, 0.3);
        }

        .school-badge {
            display: inline-block;
            padding: 6px 12px;
            background: #e8f4f8;
            color: #0066cc;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 12px;
            margin-top: 10px;
        }

        .school-badge.sd { background: #fce8e6; color: #d32f2f; }
        .school-badge.smp { background: #e8f5e9; color: #388e3c; }
        .school-badge.sma { background: #e1f5fe; color: #0277bd; }

        .school-name {
            font-size: 16px;
            font-weight: 700;
            color: #1a3a5c;
            margin-bottom: 12px;
        }

        .school-info {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .school-info-label {
            font-weight: 600;
            color: #1a3a5c;
            min-width: 100px;
        }

        .distance-info {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            padding: 12px;
            border-radius: 8px;
            margin-top: 15px;
            text-align: center;
            border-left: 4px solid #4caf50;
        }

        .distance-label {
            font-size: 12px;
            color: #2e7d32;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .distance-value {
            font-size: 20px;
            font-weight: 700;
            color: #1b5e20;
            margin-top: 4px;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }

        .no-results-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .no-results-text {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .no-results-desc {
            font-size: 14px;
            color: #95a5a6;
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

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 1024px) {
            .filter-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .schools-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .schools-grid {
                grid-template-columns: 1fr;
            }

            .filter-section,
            .results-section {
                padding: 20px;
            }
        }

        .title-with-icon {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .title-with-icon img {
            width: 32px;
            height: 32px;
        }

        /* Logout Button */
        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none; 
            gap: 8px;
            width: 100%;
            padding: 10px 12px;
            background: linear-gradient(135deg, #ffffff 0%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 17px;
            color: #1a3a5c;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 12px;
            box-shadow: 0 4px 12px rgba(255, 107, 107, 0.25);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.35);
        }

        .logout-btn:active {
            transform: translateY(0px);
        }

        .logout-btn img {
            width: 18px;
            height: 18px;
        }

        /* Admin Status Top Right */
        .admin-status-top {
            position: absolute;
            top: 25px;
            right: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 10px 16px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            animation: slideInDown 0.5s ease;
            z-index: 50;
        }

        .admin-status-top span {
            color: white;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.3px;
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

        /* Creative datalist dropdown styles */
        .datalist-wrapper { position: relative; }
        .datalist-input { position: relative; z-index: 1; }
        .dropdown-list {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(250,250,255,0.96));
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(21, 35, 51, 0.18);
            border: 1px solid rgba(32,45,60,0.06);
            max-height: 240px;
            overflow-y: auto;
            padding: 6px;
            backdrop-filter: blur(6px);
            transition: opacity 180ms ease, transform 180ms ease;
            opacity: 0;
            transform: translateY(-6px);
            z-index: 2000;
            display: none;
        }
        .dropdown-list.show { display: block; opacity: 1; transform: translateY(0); }
        .dropdown-item {
            padding: 10px 12px;
            border-radius: 8px;
            margin: 4px 2px;
            cursor: pointer;
            color: #0b2a45;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dropdown-item:not(.active):hover { background: linear-gradient(90deg, rgba(255,249,240,0.7), rgba(255,255,255,0.6)); }
        .dropdown-item.active { background: linear-gradient(90deg, #ffefdb, #fff7f0); box-shadow: inset 0 1px 0 rgba(255,255,255,0.6); }
        .dropdown-item small { color: #6b7a86; font-weight: 500; }
        .dropdown-item mark { background: linear-gradient(90deg,#ffe8b8,#ffd79b); padding: 2px 6px; border-radius: 6px; color:#5a3b00; }
        /* scrollbar */
        .dropdown-list::-webkit-scrollbar { width: 9px; }
        .dropdown-list::-webkit-scrollbar-thumb { background: linear-gradient(180deg,#dfe7ef,#cfdbe8); border-radius: 6px; }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: #fefef9;
            margin: 9% auto;
            padding: 26px 26px 20px 26px;
            border: 1px solid #e6e6e6;
            border-radius: 12px;
            width: 90%;
            max-width: 680px;
            box-shadow: 0 8px 30px rgba(10, 25, 47, 0.12);
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px dashed #f0f0f0;
        }

        .modal-header h2 {
            margin: 0;
            color: #1a3a5c;
            font-size: 20px;
            font-weight: 700;
        }

        .modal-close {
            font-size: 26px;
            font-weight: 700;
            color: #6b7280;
            cursor: pointer;
            background: none;
            border: none;
            padding: 6px 8px;
            border-radius: 8px;
        }

        .modal-close:hover {
            color: #f0ad4e;
            background: rgba(240,173,78,0.06);
        }

        .modal-body {
            margin-bottom: 18px;
        }

        .school-detail-top {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: flex-start;
            margin-bottom: 14px;
        }

        .school-title {
            font-size: 18px;
            font-weight: 800;
            color: #132238;
            margin-bottom: 6px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 12px;
            color: #fff;
            text-transform: capitalize;
        }

        .status-badge.negeri { background: linear-gradient(90deg,#16a34a,#059669); }
        .status-badge.swasta { background: linear-gradient(90deg,#f97316,#fb923c); }
        .status-badge.unknown { background: linear-gradient(90deg,#6b7280,#9ca3af); }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 18px;
        }

        .detail-item { display:flex; flex-direction:column; gap:6px; }
        .detail-item label { font-size:12px; color:#495569; font-weight:700; }
        .detail-item .value { color:#17202a; font-weight:600; font-size:14px; }

        .modal-footer {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 20px;
        }

        .btn-modal {
            margin-top: 19px;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.18s ease;
        }

        .btn-maps {
            background-color: #1a73e8;
            color: white;
        }

        .btn-maps:hover { transform: translateY(-2px); }

        .btn-close-modal {
            background-color: #f3f4f6;
            color: #111827;
        }

        .btn-close-modal:hover { transform: translateY(-2px); }

        /* Responsive - Tablet & Below */
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
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 280px;
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                z-index: 9998;
                overflow-y: auto;
                border-right: 1px solid rgba(0,0,0,0.1);
                transform: translateX(-100%);
            }

            .sidebar.hidden {
                transform: translateX(-100%);
                pointer-events: none;
            }

            .sidebar:not(.hidden) {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 70px 16px 30px;
                width: 100%;
            }

            .page-header {
                display: block;
                margin-bottom: 30px;
                padding-bottom: 15px;
                border-bottom: none;
            }

            .filters-section {
                background: white;
                padding: 20px;
                border-radius: 10px;
                margin-bottom: 25px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            }

            .filter-row {
                display: grid;
                grid-template-columns: 1fr;
                gap: 15px;
                margin-bottom: 15px;
            }

            .form-group {
                display: flex;
                flex-direction: column;
            }

            .form-group label {
                font-size: 13px;
                margin-bottom: 5px;
            }

            .form-group select,
            .form-group input {
                padding: 12px;
                font-size: 14px;
            }

            .schools-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .school-card {
                padding: 16px;
                border-radius: 10px;
            }
        }

        @media (max-width: 600px) {
            .main-content {
                padding: 70px 14px 25px;
            }

            .page-header h1 {
                font-size: 22px;
                margin-bottom: 5px;
            }

            .page-header p {
                font-size: 13px;
            }

            .filters-section {
                padding: 16px;
                margin-bottom: 20px;
            }

            .filter-row {
                gap: 12px;
                margin-bottom: 12px;
            }

            .form-group select,
            .form-group input {
                padding: 10px;
                font-size: 13px;
            }

            .btn-filter {
                padding: 10px 20px;
                font-size: 13px;
                margin-top: 10px;
            }

            .schools-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .school-card {
                padding: 14px;
                border-radius: 9px;
            }

            .school-info h3 {
                font-size: 14px;
            }

            .school-info p {
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 65px 12px 20px;
            }

            .page-header h1 {
                font-size: 20px;
                margin-bottom: 4px;
            }

            .page-header p {
                font-size: 12px;
            }

            .filters-section {
                padding: 14px;
                margin-bottom: 18px;
            }

            .filter-row {
                gap: 10px;
                margin-bottom: 10px;
            }

            .form-group label {
                font-size: 12px;
                margin-bottom: 4px;
            }

            .form-group select,
            .form-group input {
                padding: 9px;
                font-size: 12px;
            }

            .btn-filter {
                padding: 9px 16px;
                font-size: 12px;
                margin-top: 8px;
            }

            .schools-grid {
                gap: 12px;
            }

            .school-card {
                padding: 12px;
                border-radius: 8px;
            }

            .school-info h3 {
                font-size: 13px;
            }

            .school-info p {
                font-size: 11px;
            }
        }

        @media (max-width: 360px) {
            .main-content {
                padding: 60px 10px 18px;
            }

            .page-header h1 {
                font-size: 18px;
                margin-bottom: 3px;
            }

            .page-header p {
                font-size: 11px;
            }

            .filters-section {
                padding: 12px;
                margin-bottom: 16px;
            }

            .filter-row {
                gap: 8px;
                margin-bottom: 8px;
            }

            .form-group label {
                font-size: 11px;
                margin-bottom: 3px;
            }

            .form-group select,
            .form-group input {
                padding: 8px;
                font-size: 11px;
            }

            .btn-filter {
                padding: 8px 14px;
                font-size: 11px;
                margin-top: 6px;
            }

            .schools-grid {
                gap: 10px;
            }

            .school-card {
                padding: 10px;
                border-radius: 8px;
            }

            .school-info h3 {
                font-size: 12px;
            }

            .school-info p {
                font-size: 10px;
            }
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 14px;
            left: 14px;
            z-index: 9999;
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

        .mobile-menu-toggle.hidden-on-scroll,
        .mobile-user-icon.hidden-on-scroll {
            transform: translateY(-80px);
            opacity: 0;
            pointer-events: none;
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
                display: flex !important;
            }

            .mobile-user-icon {
                display: flex !important;
                visibility: visible;
            }

            .sidebar {
                transition: all 0.3s ease;
                z-index: 9998;
            }

            .container {
                flex-direction: column;
            }

            .main-content {
                margin-left: 0 !important;
            }
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 6px rgba(46, 204, 113, 0.8);
            }
            50% {
                box-shadow: 0 0 12px rgba(46, 204, 113, 1);
            }
        }

    </style>
</head>
<body>
    <!-- Sidebar Backdrop Overlay -->
    <div class="sidebar-backdrop"></div>

    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="menuToggle">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- Mobile User Icon -->
    <div class="mobile-user-icon" id="mobileUserIcon" onclick="showLogoutNotification()">
        <div class="online-dot"></div>
        <span>USER</span>
    </div>

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
                    <a href="dashboard_user.php">
                        <span class="menu-icon">
                            <img src="assets/icons/dashboard.png" alt="Dashboard">
                        </span>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="rekomendasi_user.php" class="active">
                        <span class="menu-icon">
                            <img src="assets/icons/rekomendasi1.png" alt="Rekomendasi">
                        </span>
                        Rekomendasi
                    </a>
                </li>
                <li>
                    <a href="peta_user.php">
                        <span class="menu-icon">
                            <img src="assets/icons/peta1.png" alt="Peta">
                        </span>
                        Peta
                    </a>
                </li>
            </ul>

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
                <h1 class="title-with-icon">
                    <img src="assets/icons/petaRekom.png" alt="Rekomendasi">
                    <span>Rekomendasi Sekolah Terdekat</span>
                </h1>
                <p>Temukan sekolah yang paling dekat dengan lokasi Anda berdasarkan metode Haversine</p>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-title">
                    Filter Pencarian
                </div>

                <form method="GET" id="filterForm">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label class="filter-label">KECAMATAN</label>
                            <div class="datalist-wrapper">
                                <input name="kecamatan" id="kecamatanInput" class="filter-input datalist-input" onchange="updateDesa()" autocomplete="off" value="<?php echo htmlspecialchars($filters['kecamatan']); ?>" placeholder="Ketik untuk mencari...">
                                <datalist id="kecamatanList">
                                    <?php foreach ($kecamatans as $row): ?>
                                        <option value="<?php echo htmlspecialchars($row['kecamatan']); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <div id="kecamatanDropdown" class="dropdown-list" role="listbox" aria-hidden="true"></div>
                            </div>
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">DESA</label>
                            <div class="datalist-wrapper">
                                <input name="desa" id="desaInput" class="filter-input datalist-input" autocomplete="off" value="<?php echo htmlspecialchars($filters['desa']); ?>" placeholder="Ketik untuk mencari...">
                                <datalist id="desaList">
                                    <?php foreach ($desas as $row): ?>
                                        <option value="<?php echo htmlspecialchars($row['desa']); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <div id="desaDropdown" class="dropdown-list" role="listbox" aria-hidden="true"></div>
                            </div>
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">TINGKAT PENDIDIKAN</label>
                            <div class="datalist-wrapper">
                                <input name="tingkat_pendidikan" id="tingkatInput" class="filter-input datalist-input" autocomplete="off" value="<?php echo htmlspecialchars($filters['tingkat_pendidikan']); ?>" placeholder="Ketik untuk mencari...">
                                <datalist id="tingkatList">
                                    <?php foreach ($tingkats as $row): ?>
                                        <option value="<?php echo htmlspecialchars($row['tingkat_pendidikan']); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <div id="tingkatDropdown" class="dropdown-list" role="listbox" aria-hidden="true"></div>
                            </div>
                        </div>
                    </div>

                    <div class="filter-button">
                        <button type="button" class="btn btn-search" onclick="hideAllDropdowns(); setTimeout(function() { document.querySelector('#filterForm').submit(); }, 100);">🔎 Cari Sekolah Terdekat</button>
                        <button type="reset" class="btn btn-reset" onclick="window.location.href='rekomendasi_user.php'">↻ Reset Filter</button>
                        <?php if ($is_searching):
                            $mapUrl = 'peta_user.php?kecamatan=' . urlencode($filters['kecamatan']) . '&desa=' . urlencode($filters['desa']) . '&tingkat_pendidikan=' . urlencode($filters['tingkat_pendidikan']); ?>
                            <a href="<?php echo $mapUrl; ?>" class="btn btn-search" style="background: linear-gradient(135deg,#3b82f6 0%,#1e40af 100%); margin-left:8px; text-decoration:none; display:inline-flex; align-items:center;" target="_blank">🗺️ Lihat di Peta</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Results Section -->
            <div class="results-section">
                <div class="results-header">
                    <div class="results-title title-with-icon">
                        <img src="assets/icons/kecamatan.png" alt="Hasil Rekomendasi">
                        <span>Hasil Rekomendasi Sekolah</span>
                    </div>
                    <div class="results-count">
                        <?php echo $total_results; ?> Sekolah Ditemukan
                    </div>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="no-results">
                        <div class="no-results-icon">⚠️</div>
                        <div class="no-results-text">Peringatan</div>
                        <div class="no-results-desc"><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php elseif (empty($results)): ?>
                    <div class="no-results">
                        <div class="no-results-icon">🏫</div>
                        <div class="no-results-text">Tidak ada hasil</div>
                        <div class="no-results-desc">Silakan pilih Kecamatan, Desa, dan Tingkat Pendidikan kemudian klik "Cari Sekolah Terdekat"</div>
                    </div>
                <?php else: ?>
                    <div class="schools-grid">
                        <?php foreach ($results as $index => $school): ?>
                            <div class="school-card" onclick="openSchoolDetail(<?php echo htmlspecialchars(json_encode($school)); ?>, <?php echo htmlspecialchars(json_encode($filters['kecamatan'])); ?>)" style="cursor: pointer;">
                                <div class="rank-badge"><?php echo ($index + 1); ?></div>
                                
                                <span class="school-badge <?php echo strtolower(str_replace(['SD', 'SMP', 'SMA'], ['sd', 'smp', 'sma'], $school['tingkat_pendidikan'])); ?>">
                                    <?php echo htmlspecialchars($school['tingkat_pendidikan']); ?>
                                </span>

                                <div class="school-name">
                                    <?php echo htmlspecialchars($school['nama_sekolah']); ?>
                                </div>

                                <div class="school-info">
                                    <span class="school-info-label">Kecamatan:</span>
                                    <span><?php echo htmlspecialchars($filters['kecamatan']); ?></span>
                                </div>

                                <div class="school-info">
                                    <span class="school-info-label">Desa:</span>
                                    <span><?php echo htmlspecialchars($school['nama_desa']); ?></span>
                                </div>

                                <div class="school-info">
                                    <span class="school-info-label">Alamat:</span>
                                    <span><?php echo htmlspecialchars($school['alamat']); ?></span>
                                </div>

                                <div class="distance-info">
                                    <div class="distance-label">📍 Jarak Dari Kantor Desa</div>
                                    <div class="distance-value">
                                        <?php 
                                            if ($school['distance'] !== null) {
                                                echo $school['distance'] . ' km';
                                            } else {
                                                echo 'Data tidak lengkap';
                                            }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Hide all dropdowns
        function hideAllDropdowns() {
            hideDropdown('kecamatanDropdown');
            hideDropdown('desaDropdown');
            hideDropdown('tingkatDropdown');
        }

        // Indicate whether the user submitted a search (so we can auto-show dropdowns after reload)
        const searchPerformed = <?php echo $is_searching ? 'true' : 'false'; ?>;

        // ========= School Detail Modal Functions =========
        let currentSchoolData = null;

        function openSchoolDetail(school, kecamatan) {
            currentSchoolData = {
                ...school,
                kecamatan: kecamatan
            };

            const modalBody = document.getElementById('modalBody');
            const npsn = school.npsn || 'Tidak tersedia';
            // read possible status column as 'status' or 'status_sekolah'
            const statusSekolah = school.status || school.status_sekolah || 'Tidak tersedia';
            const alamat = school.alamat || 'Tidak tersedia';
            const namaSekolah = school.nama_sekolah || 'Tidak tersedia';
            const tingkatPendidikan = school.tingkat_pendidikan || 'Tidak tersedia';
            const namaDesa = school.nama_desa || 'Tidak tersedia';

            // determine badge class
            let statusClass = 'unknown';
            if (statusSekolah && typeof statusSekolah === 'string') {
                const s = statusSekolah.toLowerCase();
                if (s.includes('negeri')) statusClass = 'negeri';
                else if (s.includes('swasta')) statusClass = 'swasta';
            }

            const lat = school.latitude || school.lat || school.latitude || null;
            const lng = school.longtitude || school.longitude || school.lng || null;

            // normalize coordinates into currentSchoolData so openMaps() can reliably use them
            currentSchoolData.latitude = lat;
            currentSchoolData.longtitude = lng;

            modalBody.innerHTML = `
                <div class="school-detail-top">
                    <div>
                        <div class="school-title">${escapeHtml(namaSekolah)}</div>
                    </div>
                    <div style="text-align:right; min-width:120px;">
                        <div style="font-size:12px; color:#6b7280; font-weight:700;">Jarak</div>
                        <div style="font-size:20px; font-weight:800; color:#0f1724;">${school.distance ? (school.distance + ' km') : 'Tidak tersedia'}</div>
                    </div>
                </div>

                <div class="detail-grid">
                    <div class="detail-item"><label>Kecamatan</label><div class="value">${escapeHtml(kecamatan)}</div></div>
                    <div class="detail-item"><label>NPSN</label><div class="value">${escapeHtml(npsn)}</div></div>

                    <div class="detail-item"><label>Desa</label><div class="value">${escapeHtml(namaDesa)}</div></div>
                    <div class="detail-item"><label>Status</label><div class="value">${escapeHtml(statusSekolah)}</div></div>

                    <div class="detail-item"><label>Alamat</label><div class="value">${escapeHtml(alamat)}</div></div>
                    <div class="detail-item"><label>Koordinat</label><div class="value">${lat && lng ? escapeHtml(lat + ', ' + lng) : 'Tidak tersedia'}</div></div>
                </div>
            `;

            // show modal
            document.getElementById('schoolModal').style.display = 'block';
            // focus close for accessibility
            const closeBtn = document.querySelector('.modal-close');
            if (closeBtn) closeBtn.focus();
        }

        function closeSchoolDetail() {
            document.getElementById('schoolModal').style.display = 'none';
            currentSchoolData = null;
        }

        function openMaps() {
            if (!currentSchoolData || !currentSchoolData.latitude || !currentSchoolData.longtitude) {
                alert('Koordinat sekolah tidak tersedia');
                return;
            }

            const lat = currentSchoolData.latitude;
            const lng = currentSchoolData.longtitude;
            const schoolName = encodeURIComponent(currentSchoolData.nama_sekolah || 'Sekolah');
            
            // Buka Google Maps dengan marker pada lokasi sekolah
            const mapsUrl = `https://www.google.com/maps/search/${schoolName}/@${lat},${lng},18z`;
            window.open(mapsUrl, '_blank');
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('schoolModal');
            if (event.target == modal) {
                closeSchoolDetail();
            }
        }

        // ========= Custom Datalist Dropdown Logic =========
        function getOptionsFromDatalist(id) {
            const list = document.getElementById(id);
            if (!list) return [];
            return Array.from(list.options).map(o => o.value);
        }

        // renderDropdown now accepts an optional `show` flag. If show=false the list is rendered but not displayed.
        function renderDropdown(dropdownId, items, highlightIndex = -1, filter = '', show = true) {
            const el = document.getElementById(dropdownId);
            if (!el) return;
            el.innerHTML = '';
            const max = 100;
            const slice = items.slice(0, max);
            slice.forEach((value, idx) => {
                const item = document.createElement('div');
                item.className = 'dropdown-item' + (idx === highlightIndex ? ' active' : '');
                item.setAttribute('data-value', value);
                // highlight matching substring
                if (filter) {
                    const i = value.toLowerCase().indexOf(filter.toLowerCase());
                    if (i >= 0) {
                        const pre = value.substring(0,i);
                        const mid = value.substring(i, i + filter.length);
                        const post = value.substring(i + filter.length);
                        item.innerHTML = `<div><strong>${pre}</strong><mark>${mid}</mark><strong>${post}</strong></div>`;
                    } else {
                        item.textContent = value;
                    }
                } else {
                    item.textContent = value;
                }
                el.appendChild(item);
            });

            if (slice.length === 0) {
                const none = document.createElement('div');
                none.className = 'dropdown-item';
                none.textContent = 'Tidak ada hasil';
                el.appendChild(none);
            }

            if (show) {
                el.classList.add('show');
                el.setAttribute('aria-hidden', 'false');
            } else {
                el.classList.remove('show');
                el.setAttribute('aria-hidden', 'true');
            }
        }

        function hideDropdown(dropdownId) {
            const el = document.getElementById(dropdownId);
            if (!el) return;
            el.classList.remove('show');
            el.setAttribute('aria-hidden', 'true');
        }

        // refreshDropdownFromDatalist accepts optional show flag (default true)
        function refreshDropdownFromDatalist(datalistId, dropdownId, filter, show = true) {
            const opts = getOptionsFromDatalist(datalistId);
            const filtered = !filter ? opts : opts.filter(v => v.toLowerCase().includes(filter.toLowerCase()));
            renderDropdown(dropdownId, filtered, 0, filter, show);
        }

        function attachCustomDatalist(inputEl, datalistId, dropdownId, onSelect) {
            if (!inputEl) return;
            let highlight = 0;
            inputEl.addEventListener('input', function(e) {
                const v = inputEl.value || '';
                refreshDropdownFromDatalist(datalistId, dropdownId, v);
            });
            inputEl.addEventListener('focus', function(e) {
                const v = inputEl.value || '';
                refreshDropdownFromDatalist(datalistId, dropdownId, v);
            });
            inputEl.addEventListener('keydown', function(e) {
                const dropdown = document.getElementById(dropdownId);
                if (!dropdown || dropdown.getAttribute('aria-hidden') === 'true') return;
                const items = Array.from(dropdown.querySelectorAll('.dropdown-item'));
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    highlight = (highlight + 1) % items.length;
                    items.forEach((it,i) => it.classList.toggle('active', i === highlight));
                    items[highlight].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    highlight = (highlight - 1 + items.length) % items.length;
                    items.forEach((it,i) => it.classList.toggle('active', i === highlight));
                    items[highlight].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'Enter') {
                    if (items.length > 0) {
                        e.preventDefault();
                        const v = items[highlight].getAttribute('data-value');
                        inputEl.value = v;
                        hideDropdown(dropdownId);
                        if (onSelect) onSelect(v);
                    }
                } else if (e.key === 'Escape') {
                    hideDropdown(dropdownId);
                }
            });

            // pointerdown handler (fires before blur) to ensure selection works on click/tap
            document.getElementById(dropdownId).addEventListener('pointerdown', function(e) {
                const target = e.target.closest('.dropdown-item');
                if (!target) return;
                e.preventDefault();
                const v = target.getAttribute('data-value');
                if (v) {
                    inputEl.value = v;
                    hideDropdown(dropdownId);
                    if (onSelect) onSelect(v);
                }
            });

            // hide on blur (allow click to register) — improved to avoid hiding when interacting with dropdown
            inputEl.addEventListener('blur', function() {
                setTimeout(() => {
                    const dd = document.getElementById(dropdownId);
                    const active = document.activeElement;
                    // don't hide if focus moved into the dropdown
                    if (dd && dd.contains(active)) return;
                    hideDropdown(dropdownId);
                }, 200);
            });
        }

        // Update desa list and refresh dropdown
        // updateDesa(showDropdown = false)
        // - showDropdown: when true, open the desa dropdown automatically after loading data
        function updateDesa(showDropdown = false) {
            const kecInput = document.querySelector('[name="kecamatan"]');
            const kecamatan = kecInput ? kecInput.value : '';
            const desaList = document.getElementById('desaList');
            const desaInput = document.getElementById('desaInput');
            if (!kecamatan) {
                if (desaList) desaList.innerHTML = '';
                if (desaInput) desaInput.value = '';
                hideDropdown('desaDropdown');
                return;
            }

            fetch('get_desa_new.php?kecamatan=' + encodeURIComponent(kecamatan))
                .then(response => response.json())
                .then(data => {
                    if (desaList) {
                        desaList.innerHTML = '';
                        data.forEach(desa => {
                            const option = document.createElement('option');
                            option.value = desa.desa || desa.nama_desa || desa.nama_desa; // support both shapes
                            desaList.appendChild(option);
                        });
                    }
                    if (desaInput) {
                        const cur = desaInput.value || '';
                        const found = data && data.length ? data.some(d => (d.desa || d.nama_desa) === cur) : false;
                        if (!found) desaInput.value = '';

                        // refresh dropdown to reflect new values (do not auto-show unless explicitly requested)
                        refreshDropdownFromDatalist('desaList', 'desaDropdown', desaInput.value || '', showDropdown);

                        // If requested, show the dropdown automatically after loading data
                        if (showDropdown && data && data.length) {
                            // only show if input is empty or user didn't already manually select a desa
                            if (!desaInput.value) {
                                setTimeout(() => {
                                    const dd = document.getElementById('desaDropdown');
                                    if (dd) dd.classList.add('show');
                                    // ensure highlighted state
                                    refreshDropdownFromDatalist('desaList', 'desaDropdown', desaInput.value || '');
                                }, 60);
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching desa:', error);
                });
        }

        // Initialize custom datalists on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            attachCustomDatalist(document.getElementById('kecamatanInput'), 'kecamatanList', 'kecamatanDropdown', function(val) { updateDesa(); });
            attachCustomDatalist(document.getElementById('desaInput'), 'desaList', 'desaDropdown');
            attachCustomDatalist(document.getElementById('tingkatInput'), 'tingkatList', 'tingkatDropdown');

            // if kecamatan preselected, load desa but do NOT auto-show dropdown (do not show after search/refresh)
            const kecInput = document.querySelector('[name="kecamatan"]');
            const kecamatan = kecInput ? kecInput.value : '';
            if (kecamatan) updateDesa(false);

            // Close all dropdowns when clicking outside the form
            document.addEventListener('click', function(e) {
                const filterSection = document.querySelector('.filter-section');
                if (filterSection && !filterSection.contains(e.target)) {
                    hideAllDropdowns();
                }
            });
        });
    </script>

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
        function showLogoutNotification() {
            const modal = document.getElementById('notificationModal');
            modal.classList.add('show');
        }

        function cancelLogout() {
            const modal = document.getElementById('notificationModal');
            modal.classList.remove('show');
        }

        function confirmLogout() {
            // Redirect ke halaman logout
            window.location.href = 'logout.php';
        }

        // Close modal when clicking outside of it
        const notifModal = document.getElementById('notificationModal');
        if (notifModal) {
            notifModal.addEventListener('click', function(event) {
                if (event.target === notifModal) {
                    cancelLogout();
                }
            });
        }
    </script>

    <!-- Modal Detail Sekolah -->
    <div id="schoolModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detail Sekolah</h2>
                <!-- <button class="modal-close" onclick="closeSchoolDetail()" aria-label="Tutup">&times;</button> -->
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Konten akan diisi secara dinamis -->
            </div>
            <div class="modal-footer">
                <button class="btn-modal btn-maps" onclick="openMaps()">Buka di Maps</button>
                <button class="btn-modal btn-close-modal" onclick="closeSchoolDetail()">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        // Toggle Mobile Menu
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const backdrop = document.querySelector('.sidebar-backdrop');
            const toggle = document.querySelector('.mobile-menu-toggle');

            if (sidebar.classList.contains('hidden')) {
                sidebar.classList.remove('hidden');
                backdrop.classList.add('show');
                toggle.classList.add('active');
            } else {
                sidebar.classList.add('hidden');
                backdrop.classList.remove('show');
                toggle.classList.remove('active');
            }
        }

        // Close sidebar when clicking on backdrop
        const backdrop = document.querySelector('.sidebar-backdrop');
        if (backdrop) {
            backdrop.addEventListener('click', function() {
                const sidebar = document.querySelector('.sidebar');
                sidebar.classList.add('hidden');
                this.classList.remove('show');
                document.querySelector('.mobile-menu-toggle').classList.remove('active');
            });
        }

        // Close sidebar when clicking on menu items (only on small screens)
        const menuLinks = document.querySelectorAll('.sidebar-menu a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function(event) {
                // Don't close sidebar for dropdown toggles
                if (this.classList.contains('dropdown-toggle')) return;

                if (window.innerWidth <= 768) {
                    const sidebar = document.querySelector('.sidebar');
                    const backdrop = document.querySelector('.sidebar-backdrop');
                    const toggle = document.querySelector('.mobile-menu-toggle');

                    if (sidebar) sidebar.classList.add('hidden');
                    if (backdrop) backdrop.classList.remove('show');
                    if (toggle) toggle.classList.remove('active');
                }
            });
        });

        // Menu Toggle Button Click
        const menuToggle = document.getElementById('menuToggle');
        if (menuToggle) {
            menuToggle.addEventListener('click', toggleMobileMenu);
        }

        // Scroll detection: hide/show mobile buttons
        let lastScrollTop = 0;
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const mobileUserIcon = document.querySelector('.mobile-user-icon');

        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            
            if (currentScroll > lastScrollTop && currentScroll > 60) {
                // Scrolling DOWN - hide buttons
                if (mobileMenuToggle) mobileMenuToggle.classList.add('hidden-on-scroll');
                if (mobileUserIcon) mobileUserIcon.classList.add('hidden-on-scroll');
            } else if (currentScroll < lastScrollTop) {
                // Scrolling UP - show buttons
                if (mobileMenuToggle) mobileMenuToggle.classList.remove('hidden-on-scroll');
                if (mobileUserIcon) mobileUserIcon.classList.remove('hidden-on-scroll');
            }
            lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
        });

        // User Logout Button Click
        const userLogout = document.getElementById('userLogout');
        if (userLogout) {
            userLogout.addEventListener('click', function() {
                window.location.href = 'logout.php';
            });
        }
    </script>

</body>
</html>