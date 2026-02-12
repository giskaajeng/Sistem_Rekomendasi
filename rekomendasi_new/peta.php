<?php
require_once 'config.php';

// Get all school data

$schools = fetch_all("SELECT s.*, k.nama_kecamatan FROM sekolah s 
          JOIN kecamatan k ON s.id_kecamatan = k.id_kecamatan 
          ORDER BY s.nama_sekolah");

// Ambil data kantor desa dari desa_new (hanya yang punya koordinat)
$kantor_desa = fetch_all("SELECT * FROM desa_new WHERE latitude IS NOT NULL AND latitude != '' AND (longitude IS NOT NULL OR longtitude IS NOT NULL) AND desa IS NOT NULL AND desa != ''");

// Normalisasi koordinat kantor desa
foreach ($kantor_desa as &$d) {
    if (!isset($d['longitude']) || $d['longitude'] === '' || $d['longitude'] === null) {
        if (isset($d['longtitude']) && $d['longtitude'] !== '' && $d['longtitude'] !== null) $d['longitude'] = $d['longtitude'];
    }
    if (!isset($d['latitude']) || !is_numeric($d['latitude'])) $d['latitude'] = null;
    if (!isset($d['longitude']) || !is_numeric($d['longitude'])) $d['longitude'] = null;
}
unset($d);

// Correct possible swapped coordinates for kantor desa as well
$minLat = -7.50; $maxLat = -6.80; $minLng = 112.45; $maxLng = 113.05;
foreach ($kantor_desa as &$dd) {
    if (isset($dd['latitude']) && isset($dd['longitude']) && is_numeric($dd['latitude']) && is_numeric($dd['longitude'])) {
        $latd = (float)$dd['latitude'];
        $lngd = (float)$dd['longitude'];
        if (($latd < $minLat || $latd > $maxLat) && ($lngd >= $minLat && $lngd <= $maxLat)) {
            $dd['latitude'] = $lngd;
            $dd['longitude'] = $latd;
        } elseif (($lngd < $minLng || $lngd > $maxLng) && ($latd >= $minLng && $latd <= $maxLng)) {
            $dd['latitude'] = $lngd;
            $dd['longitude'] = $latd;
        }
    }
    if (!isset($dd['latitude']) || !is_numeric($dd['latitude'])) $dd['latitude'] = null;
    if (!isset($dd['longitude']) || !is_numeric($dd['longitude'])) $dd['longitude'] = null;
}
unset($dd);

$kantor_desa_json = json_encode($kantor_desa, JSON_UNESCAPED_UNICODE);

// Count by type
$tk = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE tingkat_pendidikan = 'TK'");
$kb = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE tingkat_pendidikan = 'KB'");
$sd = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE tingkat_pendidikan = 'SD'");
$smp = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE tingkat_pendidikan = 'SMP'");
$sma = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE tingkat_pendidikan = 'SMA'");
$ma = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE tingkat_pendidikan = 'MA'");
$mts = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE tingkat_pendidikan = 'MTS'");
$pkbm = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE tingkat_pendidikan = 'PKBM'");
$tpa = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE tingkat_pendidikan = 'TPA'");
$sps = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE tingkat_pendidikan = 'SPS'");
$smk = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE tingkat_pendidikan = 'SMK'");

// Normalisasi field latitude/longitude untuk sekolah (dukungan berbagai nama kolom)
$schools_normalized = $schools;
foreach ($schools_normalized as &$s) {
    if (!isset($s['latitude']) || $s['latitude'] === '' || $s['latitude'] === null) {
        if (isset($s['lat']) && $s['lat'] !== '') $s['latitude'] = $s['lat'];
        elseif (isset($s['lintang']) && $s['lintang'] !== '') $s['latitude'] = $s['lintang'];
    }
    if (!isset($s['longitude']) || $s['longitude'] === '' || $s['longitude'] === null) {
        if (isset($s['longtitude']) && $s['longtitude'] !== '') $s['longitude'] = $s['longtitude'];
        elseif (isset($s['long']) && $s['long'] !== '') $s['longitude'] = $s['long'];
        elseif (isset($s['lng']) && $s['lng'] !== '') $s['longitude'] = $s['lng'];
        elseif (isset($s['bujur']) && $s['bujur'] !== '') $s['longitude'] = $s['bujur'];
    }
    $s['latitude'] = (isset($s['latitude']) && is_numeric($s['latitude'])) ? (float)$s['latitude'] : null;
    $s['longitude'] = (isset($s['longitude']) && is_numeric($s['longitude'])) ? (float)$s['longitude'] : null;
}
unset($s);

// Correct possible swapped latitude/longitude values where data may have been recorded in reverse
// Use Bangkalan approximate limits to detect swaps
$minLat = -7.50; $maxLat = -6.80; $minLng = 112.45; $maxLng = 113.05;
foreach ($schools_normalized as &$s) {
    // cleaning helper: normalize commas, strip non-numeric chars and cast
    $clean = function($v) {
        if ($v === null || $v === '') return null;
        $v = trim($v);
        // replace comma decimal separator
        $v = str_replace(',', '.', $v);
        // remove any characters except digits, dot and minus
        $v = preg_replace('/[^0-9\.\-]/', '', $v);
        if ($v === '' || $v === '.' || $v === '-') return null;
        if (!is_numeric($v)) return null;
        $f = (float)$v;
        if ($f < -180 || $f > 180) return null;
        return $f;
    };

    $latRaw = isset($s['latitude']) ? $s['latitude'] : null;
    $lngRaw = isset($s['longitude']) ? $s['longitude'] : null;
    $lat = $clean($latRaw);
    $lng = $clean($lngRaw);

    // If one value looks like a longitude (112..113) and the other looks like a latitude (-7..-6), swap
    if ($lat !== null && $lng !== null) {
        if (($lat < $minLat || $lat > $maxLat) && ($lng >= $minLng && $lng <= $maxLng)) {
            $tmp = $lat; $lat = $lng; $lng = $tmp;
        } elseif (($lng < $minLat || $lng > $maxLat) && ($lat >= $minLng && $lat <= $maxLng)) {
            $tmp = $lat; $lat = $lng; $lng = $tmp;
        }
    }

    $s['latitude'] = $lat;
    $s['longitude'] = $lng;
}
unset($s);
// Filter schools to only those within Bangkalan bounds for the map (prevents out-of-area markers)
$schools_for_map = array_values(array_filter($schools_normalized, function($s) use ($minLat, $maxLat, $minLng, $maxLng) {
    if (!isset($s['latitude']) || !isset($s['longitude'])) return false;
    if (!is_numeric($s['latitude']) || !is_numeric($s['longitude'])) return false;
    $lat = (float)$s['latitude'];
    $lng = (float)$s['longitude'];
    return ($lat >= $minLat && $lat <= $maxLat && $lng >= $minLng && $lng <= $maxLng);
}));

$schools_json = json_encode($schools_normalized, JSON_UNESCAPED_UNICODE);
// pastikan kantor desa juga di-encode dengan opsi unicode
$kantor_desa_json = json_encode($kantor_desa, JSON_UNESCAPED_UNICODE);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peta - Sistem Rekomendasi</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
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
            z-index: 100;
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
        }

        .sidebar-menu .submenu li a {
            padding-left: 40px;
            font-size: 13px;
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

        /* submenu */
        .submenu {
            display: none;
            padding-left: 32px;
        }

        .submenu.show {
            display: block;
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
            display: flex;
            flex-direction: column;
        }

        .page-header {
            padding: 20px 20px;
            padding-left: 64px;
            background: white;
            border-bottom: 1px solid #eee;
        }

        .page-header h1 {
            font-size: 24px;
            color: #1a3a5c;
            margin-bottom: 7px;
        }

        .map-wrapper {
            flex: 1;
            display: flex;
            gap: 20px;
            padding: 20px;
            position: relative;
            align-items: flex-start;
        }

        #map {
            flex: 1;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            background: white;
            min-height: 600px; /* pastikan Leaflet punya tinggi */
        }

        /* Toggle legend button (hamburger) */
        .toggle-legend-btn {
            position: absolute;
            top: 20px;
            right: 300px; /* when legend visible (approx) */
            z-index: 1200;
            width: 44px;
            height: 44px;
            border-radius: 8px;
            border: none;
            background: rgba(26,58,92,0.95);
            color: #fff;
            font-size: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 18px rgba(0,0,0,0.12);
            cursor: pointer;
        }
        .toggle-legend-btn:hover { transform: translateY(-2px); }
        .toggle-legend-btn.active { right: 12px; }

        /* Hidden legend state */
        .legend.hidden { display: none !important; }

        /* Legend */
        .legend {
            width: 280px;
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow-y: auto;
            max-height: 90vh;
        }
        
        .legend > div:first-of-type {
            max-height: none;
            overflow: visible;
        }

        .legend h3 {
            color: #1a3a5c;
            font-size: 16px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ff9f43;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .legend-item.sd .legend-color { background-color: #d9534f; }
        .legend-item.smp .legend-color { background-color: #5cb85c; }
        .legend-item.sma .legend-color { background-color: #5bc0de; }

        .legend-stats {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .stat-label {
            color: #7f8c8d;
        }

        .stat-value {
            font-weight: 600;
            color: #1a3a5c;
        }

        .school-list {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            max-height: 300px;
            overflow-y: auto;
        }

        .school-item {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 12px;
            color: #555;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .school-item:hover {
            background-color: #f9f9f9;
            padding-left: 5px;
        }

        .school-item strong {
            color: #1a3a5c;
        }

        /* Popup Action Link */
        .popup-action-link {
            display: inline-block;
            padding: 6px 10px;
            background-color: #5cb85c;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            transition: background-color 0.2s ease;
            cursor: pointer;
        }

        .popup-action-link:hover {
            background-color: #4cae4c;
        }

        /* Leaflet Popup */
        .leaflet-popup-content {
            font-family: 'Segoe UI', sans-serif !important;
            width: 250px !important;
        }

        .popup-school {
            font-weight: 600;
            color: #1a3a5c;
            margin-bottom: 8px;
        }

        .popup-info {
            font-size: 12px;
            color: #555;
            margin-bottom: 4px;
        }

        .popup-badge {
            display: inline-block;
            padding: 3px 8px;
            background-color: #f0f0f0;
            border-radius: 4px;
            font-size: 11px;
            color: #1a3a5c;
            font-weight: 600;
            margin-top: 6px;
        }

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
            }

            .map-wrapper {
                gap: 15px;
                padding: 15px;
            }

            .legend {
                width: 240px;
            }

            #map {
                min-height: 500px;
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
            }

            .admin-status-top {
                display: flex;
                top: 70px;
                right: 16px;
            }

            .map-wrapper {
                flex-direction: column;
                padding: 10px;
                gap: 10px;
            }

            .legend {
                width: 100%;
                max-height: 300px;
                order: 2;
            }

            #map {
                min-height: 400px;
                order: 1;
            }

            .page-header {
                padding: 20px;
                margin-top: 50px;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .page-header p {
                font-size: 13px;
            }

            .toggle-legend-btn {
                right: 12px;
                top: 80px;
            }

            .toggle-legend-btn.active {
                right: 12px;
            }
        }

        @media (max-width: 600px) {
            .sidebar-menu a {
                padding: 12px 15px;
                font-size: 13px;
            }

            .page-header {
                padding: 15px;
            }

            .page-header h1 {
                font-size: 20px;
                margin-bottom: 5px;
            }

            .map-wrapper {
                padding: 8px;
            }

            #map {
                min-height: 350px;
                border-radius: 8px;
            }

            .legend {
                padding: 15px;
                max-height: 250px;
            }

            .legend h3 {
                font-size: 14px;
                margin-bottom: 10px;
            }

            .legend-item {
                font-size: 12px;
                margin-bottom: 8px;
            }

            .school-list {
                max-height: 200px;
            }

            .toggle-legend-btn {
                width: 40px;
                height: 40px;
                font-size: 18px;
                top: 70px;
            }
        }

        @media (max-width: 480px) {
            .page-header {
                padding: 12px;
            }

            .page-header h1 {
                font-size: 18px;
            }

            .page-header p {
                font-size: 12px;
            }

            .main-content {
                margin-left: 0;
            }

            .map-wrapper {
                padding: 5px;
                flex-direction: column;
            }

            #map {
                min-height: 300px;
                border-radius: 6px;
            }

            .legend {
                padding: 12px;
                max-height: 220px;
                width: 100%;
            }

            .legend h3 {
                font-size: 13px;
                margin-bottom: 8px;
            }

            .legend-item {
                font-size: 11px;
                gap: 6px;
            }

            .school-list {
                max-height: 150px;
            }

            .stat-row {
                font-size: 12px;
            }

            .toggle-legend-btn {
                width: 36px;
                height: 36px;
                font-size: 16px;
                top: 60px;
                right: 8px;
            }
        }

        /* Mobile Menu Toggle */
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
                display: none;
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
            width: 18px;
            height: 18px;
        }

        /* Admin Status Top Right */
        .admin-status-top {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
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
                    <ul class="submenu" id="rekomendasi-menu" style="display: none;">
                        <li>
                            <a href="input_data.php">
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
                    <a href="peta.php" class="active">
                        <span class="menu-icon">
                            <img src="assets/icons/peta1.png" alt="Peta">
                        </span>
                        Peta
                    </a>
                </li>
            </ul>

            <!-- bacaan admin -->
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
                <h1>Peta Sebaran Sekolah</h1>
                <p>Visualisasi lokasi sekolah di Bangkalan</p>
            </div>

            <div class="map-wrapper">
                <div id="map"></div>
                    <button id="toggleLegendBtn" class="toggle-legend-btn" title="Tampilkan/Sembunyikan Info Peta">☰</button>
                
                <aside class="legend">
                    <h3>🗺️ Peta Bangkalan</h3>
                    
                    <div style="border: 1px solid #ddd; border-radius: 6px; padding: 10px; margin-bottom: 15px;">
                        <h4 style="color: #1a3a5c; font-size: 13px; margin-bottom: 8px; margin-top: 0;">Filter Sekolah:</h4>

                        <style>
                            .filter-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; width: 100%; max-height: none; }
                            .filter-card {
                                background: #fff;
                                border-radius: 12px;
                                padding: 12px;
                                box-shadow: 0 6px 18px rgba(23,63,96,0.06);
                                text-align: center;
                                cursor: pointer;
                                border: 2px solid transparent;
                                transition: transform .12s ease, border-color .12s ease;
                                position: relative;
                                overflow: visible;
                            }
                            .filter-card:hover { transform: translateY(-3px); }
                            .filter-card.selected { border-color: #ff8c00; }
                            .filter-card img { display: none; }
                            .legend-pin { width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; margin: 6px auto; }
                            .legend-pin svg { width: 34px; height: 34px; display: block; }
                            .filter-card .card-count { font-size: 20px; color: #0f2b44; font-weight: 700; margin-top: 6px; }
                            .filter-card .card-label { font-size: 12px; color: #516476; margin-top: 4px; }
                            .card-checkbox { position: absolute; top: 8px; right: 8px; width: 18px; height: 18px; cursor: pointer; z-index: 10; pointer-events: all; opacity: 1; visibility: visible; }
                        </style>

                        <div class="filter-cards">
                            <div class="filter-card" data-level="KB" title="KB">
                                <input type="checkbox" class="legend-checkbox card-checkbox" value="KB" checked>
                                <div class="legend-pin" data-level="KB" title="KB"></div>
                                <div class="card-count"><?php echo $kb; ?></div>
                                <div class="card-label">KB</div>
                            </div>
                            <div class="filter-card" data-level="PKBM" title="PKBM">
                                <input type="checkbox" class="legend-checkbox card-checkbox" value="PKBM" checked>
                                <div class="legend-pin" data-level="PKBM" title="PKBM"></div>
                                <div class="card-count"><?php echo $pkbm; ?></div>
                                <div class="card-label">PKBM</div>
                            </div>
                            <div class="filter-card" data-level="SPS" title="SPS">
                                <input type="checkbox" class="legend-checkbox card-checkbox" value="SPS" checked>
                                <div class="legend-pin" data-level="SPS" title="SPS"></div>
                                <div class="card-count"><?php echo $sps; ?></div>
                                <div class="card-label">SPS</div>
                            </div>
                            <div class="filter-card" data-level="TK" title="TK">
                                <input type="checkbox" class="legend-checkbox card-checkbox" value="TK" checked>
                                <div class="legend-pin" data-level="TK" title="TK"></div>
                                <div class="card-count"><?php echo $tk; ?></div>
                                <div class="card-label">TK</div>
                            </div>
                            <div class="filter-card" data-level="TPA" title="TPA">
                                <input type="checkbox" class="legend-checkbox card-checkbox" value="TPA" checked>
                                <div class="legend-pin" data-level="TPA" title="TPA"></div>
                                <div class="card-count"><?php echo $tpa; ?></div>
                                <div class="card-label">TPA</div>
                            </div>
                            <div class="filter-card" data-level="SD" title="SD">
                                <input type="checkbox" class="legend-checkbox card-checkbox" value="SD" checked>
                                <div class="legend-pin" data-level="SD" title="SD"></div>
                                <div class="card-count"><?php echo $sd; ?></div>
                                <div class="card-label">SD</div>
                            </div>
                            <div class="filter-card" data-level="SMP" title="SMP">
                                <input type="checkbox" class="legend-checkbox card-checkbox" value="SMP" checked>
                                <div class="legend-pin" data-level="SMP" title="SMP"></div>
                                <div class="card-count"><?php echo $smp; ?></div>
                                <div class="card-label">SMP</div>
                            </div>
                            <div class="filter-card" data-level="MTS" title="MTS">
                                <input type="checkbox" class="legend-checkbox card-checkbox" value="MTS" checked>
                                <div class="legend-pin" data-level="MTS" title="MTS"></div>
                                <div class="card-count"><?php echo $mts; ?></div>
                                <div class="card-label">MTS</div>
                            </div>
                            <div class="filter-card" data-level="SMA" title="SMA">
                                <input type="checkbox" class="legend-checkbox card-checkbox" value="SMA" checked>
                                <div class="legend-pin" data-level="SMA" title="SMA"></div>
                                <div class="card-count"><?php echo $sma; ?></div>
                                <div class="card-label">SMA</div>
                            </div>
                            <div class="filter-card" data-level="SMK" title="SMK">
                                <input type="checkbox" class="legend-checkbox card-checkbox" value="SMK" checked>
                                <div class="legend-pin" data-level="SMK" title="SMK"></div>
                                <div class="card-count"><?php echo $smk; ?></div>
                                <div class="card-label">SMK</div>
                            </div>
                            <div class="filter-card" data-level="MA" title="MA">
                                <input type="checkbox" class="legend-checkbox card-checkbox" value="MA" checked>
                                <div class="legend-pin" data-level="MA" title="MA"></div>
                                <div class="card-count"><?php echo $ma; ?></div>
                                <div class="card-label">MA</div>
                            </div>
                        </div>

                        <script>
                            // Wire up card clicks to toggle the checkbox and visual state
                            document.querySelectorAll('.filter-card').forEach(card => {
                                const checkbox = card.querySelector('.legend-checkbox');
                                if (checkbox && checkbox.checked) card.classList.add('selected');
                                card.addEventListener('click', (ev) => {
                                    // If the user clicked directly on the checkbox, let it handle the change
                                    if (ev.target && ev.target.closest && ev.target.closest('.legend-checkbox')) return;
                                    if (!checkbox) return;
                                    checkbox.checked = !checkbox.checked;
                                    checkbox.dispatchEvent(new Event('change'));
                                    card.classList.toggle('selected', checkbox.checked);
                                });
                                // Also respond to direct checkbox changes (e.g., keyboard toggle)
                                if (checkbox) {
                                    checkbox.addEventListener('change', () => {
                                        card.classList.toggle('selected', checkbox.checked);
                                    });
                                }
                            });
                        </script>
                    </div>

                    <div class="legend-stats">
                        <div class="stat-row">
                            <span class="stat-label">TK:</span>
                            <span class="stat-value"><?php echo $tk; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">KB:</span>
                            <span class="stat-value"><?php echo $kb; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">TPA:</span>
                            <span class="stat-value"><?php echo $tpa; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">SD:</span>
                            <span class="stat-value"><?php echo $sd; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">SMP:</span>
                            <span class="stat-value"><?php echo $smp; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">MTS:</span>
                            <span class="stat-value"><?php echo $mts; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">SMA:</span>
                            <span class="stat-value"><?php echo $sma; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">MA:</span>
                            <span class="stat-value"><?php echo $ma; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">PKBM:</span>
                            <span class="stat-value"><?php echo $pkbm; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">SPS:</span>
                            <span class="stat-value"><?php echo $sps; ?></span>
                        </div>
                        <div class="stat-row" style="border-top: 1px solid #eee; padding-top: 8px; margin-top: 8px;">
                            <span class="stat-label"><strong>Total Sekolah:</strong></span>
                            <span class="stat-value"><?php echo count($schools); ?></span>
                        </div>
                    </div>
                </aside>
            </div>
        </main>
    </div>

    <script>
        // Toggle dropdown menu
        function toggleDropdown(event) {
            event.preventDefault();
            const menu = document.getElementById('rekomendasi-menu');
            const arrow = event.target.closest('.dropdown-toggle').querySelector('.dropdown-arrow');
            
            if (menu.style.display === 'none') {
                menu.style.display = 'block';
                arrow.style.transform = 'rotate(90deg)';
                arrow.style.transition = 'transform 0.3s ease';
            } else {
                menu.style.display = 'none';
                arrow.style.transform = 'rotate(0deg)';
                arrow.style.transition = 'transform 0.3s ease';
            }
        }

        // Initialize map centered on Bangkalan
        const map = L.map('map', { zoomControl: true }).setView([-7.050, 112.750], 12);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Define Bangkalan bounds precisely - only show markers within these bounds
        const bangkalanBounds = L.latLngBounds([
            [-7.50, 112.45],   // southwest (expanded)
            [-6.80, 113.05]    // northeast (expanded)
        ]);

        // Function to check if a point is within Bangkalan bounds
        function isWithinBangkalan(lat, lng) {
            return lat >= -7.50 && lat <= -6.80 && lng >= 112.45 && lng <= 113.05;
        }

        // No hard bounds: allow viewing other regions as requested
        // (Bangkalan bounds are still available as `bangkalanBounds` if needed)


        // Schools data
        const schools = <?php echo $schools_json; ?>;
        // Quick client-side diagnostics: detect schools outside Bangkalan bounds
        (function debugOutside() {
            if (!Array.isArray(schools)) return;
            const outside = [];
            const tkOutside = [];
            schools.forEach(s => {
                const lat = parseFloat(s.latitude);
                const lng = parseFloat(s.longitude);
                const level = (s.tingkat_pendidikan || '').toUpperCase().trim();
                const valid = isFinite(lat) && isFinite(lng);
                if (!valid) return;
                if (!isWithinBangkalan(lat, lng)) {
                    outside.push({name: s.nama_sekolah, level, lat, lng});
                    if (level === 'TK') tkOutside.push({name: s.nama_sekolah, lat, lng});
                }
            });
            console.log('DEBUG: schools outside Bangkalan bounds count =', outside.length);
            if (outside.length > 0) console.table(outside.slice(0, 15));
            console.log('DEBUG: TK outside count =', tkOutside.length);
            if (tkOutside.length > 0) console.table(tkOutside.slice(0, 15));
        })();
        // Kantor Desa data
        const kantorDesa = <?php echo $kantor_desa_json; ?>;
        console.log('peta: schools count=', (schools && schools.length) || 0, 'kantorDesa count=', (kantorDesa && kantorDesa.length) || 0);
        if (schools && schools.length > 0) {
            console.log('First school:', schools[0]);
        }


        // Icon color mapping by education level - use single location pin icon with different colors per category
        const iconColorMap = {
            'TK': '#f39c12',
            'KB': '#ff66b2',
            'SD': '#d9534f',
            'SMP': '#5cb85c',
            'SMA': '#5bc0de',
            'SMK': '#f0ad4e',
            'MA': '#6f42c1',
            'MTS': '#6610f2',
            'PKBM': '#20c997',
            'TPA': '#ff7f50',
            'SPS': '#17a2b8'
        };
        // Color for kantor desa (still a distinct color)
        const kantorDesaColor = '#ffb300';

        // Helper: create a colored location pin icon (SVG data URI)
        function createPinIcon(color) {
            const svg = `
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none">
                    <path fill="${color}" stroke="#ffffff" stroke-width="1" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                    <circle cx="12" cy="9" r="3" fill="#fff" opacity="0.9"/>
                </svg>
            `;
            return L.icon({
                iconUrl: 'data:image/svg+xml;utf8,' + encodeURIComponent(svg),
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -35],
                className: 'custom-marker'
            });
        }

        // Track visible education levels and desa - for filtering
        const visibleLevels = new Set(['TK', 'KB', 'SD', 'SMP', 'SMA', 'SMK', 'MA', 'MTS', 'PKBM', 'TPA', 'SPS', 'DESA']);
        const markersByType = { TK: [], KB: [], SD: [], SMP: [], SMA: [], SMK: [], MA: [], MTS: [], PKBM: [], TPA: [], SPS: [], DESA: [] };


        // Add school markers
        const markers = {};
        let schoolMarkersAdded = 0;
        schools.forEach((school, index) => {
            const lat = parseFloat(school.latitude);
            const lng = parseFloat(school.longitude);
            if (!isFinite(lat) || !isFinite(lng)) {
                console.log('Skipped school ' + index + ': invalid coordinates - lat=', lat, 'lng=', lng);
                return;
            }
            // Don't skip out-of-area schools anymore; only skip invalid coordinates
            schoolMarkersAdded++;
            // Normalize tingkat_pendidikan to uppercase
            const normalizedLevel = (school.tingkat_pendidikan || '').toUpperCase().trim();
            const color = iconColorMap[normalizedLevel] || iconColorMap['SMA'] || '#5bc0de';
            console.log('School:', school.nama_sekolah, 'Level:', school.tingkat_pendidikan, 'Normalized:', normalizedLevel, 'Color:', color);
            let icon = createPinIcon(color);
            const marker = L.marker([lat, lng], { icon: icon, zIndexOffset: 1000 }).addTo(map);
            marker.tingkatPendidikan = school.tingkat_pendidikan;
            marker.normalizedLevel = normalizedLevel;
            const popupContent = `
                <div>
                    <div class="popup-school">${school.nama_sekolah}</div>
                    <div class="popup-info"><strong>Alamat:</strong> ${school.alamat}</div>
                    <div class="popup-info"><strong>Kecamatan:</strong> ${school.nama_kecamatan}</div>
                    <div class="popup-info"><strong>Desa:</strong> ${school.nama_desa}</div>
                    <div class="popup-info"><strong>Koordinat:</strong> ${parseFloat(school.latitude).toFixed(4)}, ${parseFloat(school.longitude).toFixed(4)}</div>
                    <span class="popup-badge">${school.tingkat_pendidikan}</span>
                    <div style="margin-top: 10px; display: flex; gap: 8px;">
                        <a href="hasil_input.php?search=${encodeURIComponent(school.nama_sekolah)}" style="
                            flex: 1;
                            padding: 6px 10px;
                            background-color: #5cb85c;
                            color: white;
                            text-decoration: none;
                            border-radius: 4px;
                            font-size: 12px;
                            text-align: center;
                            transition: background-color 0.2s ease;
                        </a>
                    </div>
                </div>
            `;
            marker.bindPopup(popupContent);
            markers['school_' + index] = { marker, lat, lng };
            // Track by type for filtering
            // Use normalized uppercase level as the key so filters match the cards
            const keyLevel = normalizedLevel || 'UNKNOWN';
            if (!markersByType[keyLevel]) {
                markersByType[keyLevel] = [];
            }
            markersByType[keyLevel].push(marker);
        });
        console.log('School markers added:', schoolMarkersAdded, '/ total schools:', schools.length);

        // Add kantor desa markers
        let desaMarkersAdded = 0;
        kantorDesa.forEach((desa, idx) => {
            const lat = parseFloat(desa.latitude);
            const lng = parseFloat(desa.longitude);
            if (!isFinite(lat) || !isFinite(lng)) {
                console.log('Skipped desa ' + idx + ': invalid coordinates - lat=', lat, 'lng=', lng);
                return;
            }
            // Do not skip desa outside Bangkalan; show all desa with valid coords
            desaMarkersAdded++;
            const icon = createPinIcon(kantorDesaColor);
            const popupContent = `
                <div>
                    <div class="popup-school"><b>Kantor Desa</b> ${desa.desa || desa.nama_desa}</div>
                    <div class="popup-info"><strong>Kecamatan:</strong> ${desa.kecamatan || desa.nama_kecamatan || ''}</div>
                    <div class="popup-info"><strong>Koordinat:</strong> ${lat.toFixed(4)}, ${lng.toFixed(4)}</div>
                </div>
            `;
            const marker = L.marker([lat, lng], { icon: icon, zIndexOffset: 900 }).addTo(map);
            marker.desaType = 'DESA';
            marker.bindPopup(popupContent);
            markers['desa_' + idx] = { marker, lat, lng };
            // Track desa markers
            markersByType['DESA'].push(marker);
        });
        console.log('Desa markers added:', desaMarkersAdded, '/ total desa:', kantorDesa.length);

        // Function to zoom to school
        window.zoomToSchool = function(lat, lng) {
            map.setView([lat, lng], 15);
        };

        // Fit map to all markers (schools + desa) if any
        (function fitToAllMarkers() {
            const coords = Object.values(markers).map(o => [o.lat, o.lng]);
            if (coords.length === 0) return;
            try {
                const bounds = L.latLngBounds(coords);
                const latSpan = Math.abs(bounds.getNorth() - bounds.getSouth());
                const lngSpan = Math.abs(bounds.getEast() - bounds.getWest());
                const maxSpan = Math.max(latSpan, lngSpan);
                // If markers spread too wide, avoid zooming out too far — keep default Bangkalan view
                const MAX_ALLOWED_SPAN = 1.0; // degrees, adjust if needed
                if (maxSpan > MAX_ALLOWED_SPAN) {
                    // center on Bangkalan with a reasonable zoom
                    map.setView([-7.050, 112.750], 12);
                    console.log('Markers spread wide (maxSpan=' + maxSpan.toFixed(3) + '), using default Bangkalan view.');
                } else {
                    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 14 });
                    console.log('fitBounds applied to markers');
                }
            } catch (e) {
                console.warn('fitBounds failed', e);
            }
        })();

        // Keep map centered on Bangkalan - don't auto-fit to markers
        console.log('Total markers in object:', Object.keys(markers).length);
        console.log('Map initialized and ready');
        
        // Force Leaflet to recalculate layout (fixes sometimes-stuck tile rendering)
        setTimeout(() => {
            try { map.invalidateSize(); console.log('map.invalidateSize() called'); } catch(e) { console.warn('invalidateSize error', e); }
        }, 250);

        // Add event listeners to legend checkboxes
        document.querySelectorAll('.legend-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const level = this.value;
                const isChecked = this.checked;
                
                // Update visibleLevels set
                if (isChecked) {
                    visibleLevels.add(level);
                } else {
                    visibleLevels.delete(level);
                }
                
                // Update marker visibility
                const markersToUpdate = markersByType[level] || [];
                markersToUpdate.forEach(marker => {
                    if (isChecked) {
                        marker.addTo(map);
                    } else {
                        map.removeLayer(marker);
                    }
                });
                
                console.log('Toggled ' + level + ': ' + (isChecked ? 'visible' : 'hidden'));
            });
        });

        // Render legend pins to match map pin colors
        function renderLegendPins() {
            document.querySelectorAll('.legend-pin').forEach(el => {
                const level = (el.dataset.level || '').toUpperCase();
                let color = '#5bc0de';
                if (level === 'DESA') color = kantorDesaColor;
                else if (iconColorMap && iconColorMap[level]) color = iconColorMap[level];
                const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24" fill="none"><path fill="${color}" stroke="#ffffff" stroke-width="1" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="3" fill="#fff" opacity="0.9"/></svg>`;
                el.innerHTML = svg;
            });
        }
        // initial render and on changes
        renderLegendPins();

        // Toggle legend visibility (hamburger button)
        const toggleLegendBtn = document.getElementById('toggleLegendBtn');
        const legendEl = document.querySelector('.legend');
        toggleLegendBtn.addEventListener('click', function() {
            const hidden = legendEl.classList.toggle('hidden');
            this.classList.toggle('active', hidden);
            // adjust map layout: when legend hidden, expand map by removing left margin on main-content
            setTimeout(() => {
                try { map.invalidateSize(); } catch(e) { console.warn('invalidateSize error', e); }
            }, 250);
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

        // Scroll detection: hide/show mobile buttons
        let lastScrollTop = 0;
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const mobileUserIcon = document.querySelector('.mobile-user-icon');

        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            
            // Always show when near top
            if (currentScroll < 60) {
                if (mobileUserIcon) mobileUserIcon.classList.remove('hidden-on-scroll');
                if (mobileMenuToggle) mobileMenuToggle.classList.remove('hidden-on-scroll');
            } else if (currentScroll < lastScrollTop) {
                // Scrolling UP - hide buttons
                if (mobileMenuToggle) mobileMenuToggle.classList.add('hidden-on-scroll');
                if (mobileUserIcon) mobileUserIcon.classList.add('hidden-on-scroll');
            } else if (currentScroll > lastScrollTop) {
                // Scrolling DOWN - show buttons
                if (mobileMenuToggle) mobileMenuToggle.classList.remove('hidden-on-scroll');
                if (mobileUserIcon) mobileUserIcon.classList.remove('hidden-on-scroll');
            }
            lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
        });
    </script>
</body>
</html>