<?php
require_once 'config.php';

// Count statistics
$kb = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE UPPER(tingkat_pendidikan) = 'KB'");
$pkbm = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE UPPER(tingkat_pendidikan) = 'PKBM'");
$sps = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE UPPER(tingkat_pendidikan) = 'SPS'");
$tk = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE UPPER(tingkat_pendidikan) = 'TK'");
$tpa = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE UPPER(tingkat_pendidikan) = 'TPA'");
$sd = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE UPPER(tingkat_pendidikan) = 'SD'");
$smp = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE UPPER(tingkat_pendidikan) = 'SMP'");
$mts = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE UPPER(tingkat_pendidikan) = 'MTS'");
$sma = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE UPPER(tingkat_pendidikan) = 'SMA'");
$smk = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE UPPER(tingkat_pendidikan) = 'SMK'");
$ma = count_rows("SELECT COUNT(*) as total FROM sekolah WHERE UPPER(tingkat_pendidikan) = 'MA'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Rekomendasi</title>
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

        /* .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        } */

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
            font-size: 16px;
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
            padding: 40px;
            padding-top: 20px;
        }

        .page-header {
            margin-bottom: 30px;
            margin-top: 0;
            padding-top: 20px;
            padding-bottom: 20px;
        }

        .page-header h1 {
            font-size: 36px;
            color: #1a3a5c;
            margin-bottom: 8px;
            margin-top: 0;
        }

        .page-header p {
            color: #707879;
            font-size: 16px;
            margin: 0;
        }

        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
            margin-top: 0;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px 20px;
            text-align: center;
            border-top: 4px solid #ff9f43;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.6s ease backwards;
            min-height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin: 0;
        }

        .card:nth-child(1) { animation-delay: 0.05s; }
        .card:nth-child(2) { animation-delay: 0.1s; }
        .card:nth-child(3) { animation-delay: 0.15s; }
        .card:nth-child(4) { animation-delay: 0.2s; }
        .card:nth-child(5) { animation-delay: 0.25s; }
        .card:nth-child(6) { animation-delay: 0.3s; }
        .card:nth-child(7) { animation-delay: 0.35s; }
        .card:nth-child(8) { animation-delay: 0.4s; }
        .card:nth-child(9) { animation-delay: 0.45s; }
        .card:nth-child(10) { animation-delay: 0.5s; }
        .card:nth-child(11) { animation-delay: 0.55s; }

        .card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 12px 32px rgba(255, 159, 67, 0.2);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
        }

        .card-icon {
            font-size: 48px;
            margin-bottom: 12px;
            margin-top: 0;
        }

        .card-number {
            font-size: 38px;
            font-weight: bold;
            color: #1a3a5c;
            margin-bottom: 8px;
            margin-top: 0;
            line-height: 1.2;
        }

        .card-label {
            color: #707879;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        /* Informasi Sistem Section */
        .info-section {
            margin-top: 40px;
            margin-bottom: 40px;
        }

        .info-section h2 {
            font-size: 24px;
            color: #1a3a5c;
            margin-bottom: 20px;
            margin-top: 0;
            font-weight: 600;
        }

        .info-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .info-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            border: 1px solid #e8e8e8;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.5s ease backwards;
            margin: 0;
            text-decoration: none;
            color: inherit;
        }

        .info-card:nth-child(1) { animation-delay: 0.7s; }
        .info-card:nth-child(2) { animation-delay: 0.8s; }
        .info-card:nth-child(3) { animation-delay: 0.9s; }

        .info-card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: 0 16px 32px rgba(0,0,0,0.15);
        }

        .info-card-icon {
            font-size: 48px;
            margin-bottom: 20px;
            margin-top: 0;
        }

        .info-card-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a3a5c;
            margin-bottom: 12px;
            margin-top: 0;
        }

        .info-card-desc {
            color: #7f8c8d;
            font-size: 13px;
            line-height: 1.6;
            margin: 0;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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

        .page-header {
            animation: slideInDown 0.6s ease;
        }

        .cards-grid {
            animation: fadeInUp 0.8s ease;
        }

        .info-section {
            animation: fadeInUp 1s ease;
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
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 10px 16px;
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            animation: slideInDown 0.5s ease;
            white-space: nowrap;
            flex-shrink: 0;
            min-height: 40px;
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

        /* Make desktop admin badge fixed so it doesn't move on scroll
           and adjust vertical position to align with the Dashboard heading */
        .admin-status-top.desktop-admin {
            position: fixed;
            top: 40px; /* tweak this value if needed to align perfectly */
            right: 40px;
            z-index: 10006;
            padding: 10px 14px;
            transform: translateY(2px);
            /* make sure it renders above other elements */
            pointer-events: auto;
        }

        /* If hidden-on-scroll is accidentally applied, keep desktop badge visible */
        .admin-status-top.desktop-admin.hidden-on-scroll {
            transform: none !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }

        /* Hide desktop badge on small screens (mobile uses .mobile-user-icon) */
        @media (max-width: 768px) {
            .admin-status-top.desktop-admin { display: none; }
        }


        /* Responsive */
        @media (max-width: 1200px) {
            .cards-grid {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            }
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
                position: fixed;
            }

            .sidebar-menu a {
                padding: 15px 10px;
                font-size: 12px;
            }

            .sidebar-menu .submenu li a {
                padding-left: 30px;
                font-size: 12px;
            }

            .logo {
                width: 70px;
                height: 70px;
                font-size: 30px;
            }

            .main-content {
                margin-left: 80px;
                padding: 30px 20px;
                padding-top: 20px;
            }

            .page-header {
                margin-bottom: 25px;
                margin-top: 0;
            }

            .cards-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 15px;
                margin-bottom: 30px;
            }

            .card {
                padding: 20px 15px;
                min-height: 150px;
                margin: 0;
            }

            .card-number {
                font-size: 32px;
            }

            .card-label {
                font-size: 14px;
            }

            .info-section {
                margin-top: 30px;
                margin-bottom: 30px;
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
                padding-top: 70px;
            }

            .page-header {
                margin-bottom: 25px;
                margin-top: 0;
            }

            .cards-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
                margin-bottom: 30px;
            }

            .card {
                padding: 18px 12px;
                min-height: 160px;
                margin: 0;
            }

            .card-icon {
                font-size: 36px;
                margin-bottom: 8px;
            }

            .card-number {
                font-size: 28px;
            }

            .card-label {
                font-size: 12px;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .page-header p {
                font-size: 14px;
            }

            .admin-status-top {
                display: none;
            }

            .info-cards-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }

            .info-section {
                margin-top: 30px;
                margin-bottom: 30px;
            }

            .info-section h2 {
                margin-bottom: 18px;
            }
        }

        @media (max-width: 600px) {
            .cards-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin-bottom: 25px;
            }

            .card {
                padding: 16px 10px;
                min-height: 140px;
                border-top: 3px solid #ff9f43;
                margin: 0;
            }

            .card-icon {
                font-size: 32px;
                margin-bottom: 6px;
            }

            .card-number {
                font-size: 24px;
                margin-bottom: 4px;
            }

            .card-label {
                font-size: 11px;
            }

            .main-content {
                padding: 15px;
                padding-top: 70px;
            }

            .page-header {
                margin-bottom: 20px;
                margin-top: 0;
            }

            .page-header h1 {
                font-size: 20px;
                margin-bottom: 5px;
            }

            .page-header p {
                font-size: 12px;
            }

            .admin-status-top {
                padding: 6px 10px;
                font-size: 11px;
            }

            .info-cards-grid {
                grid-template-columns: 1fr;
            }

            .info-card {
                padding: 20px 15px;
            }

            .info-card-title {
                font-size: 16px;
            }

            .info-card-desc {
                font-size: 12px;
            }

            .info-section {
                margin-top: 25px;
                margin-bottom: 25px;
            }

            .info-section h2 {
                font-size: 18px;
                margin-bottom: 15px;
            }
        }

        @media (max-width: 480px) {
            .cards-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .card {
                padding: 12px 8px;
                min-height: 120px;
                border-top: 2px solid #ff9f43;
            }

            .card-icon {
                font-size: 28px;
                margin-bottom: 4px;
            }

            .card-number {
                font-size: 20px;
            }

            .card-label {
                font-size: 10px;
            }

            .main-content {
                padding: 12px;
            }

            .page-header h1 {
                font-size: 18px;
                margin-bottom: 5px;
            }

            .page-header p {
                font-size: 11px;
            }

            .admin-status-top {
                display: flex;
            }

            .info-section h2 {
                font-size: 18px;
                margin-bottom: 15px;
            }
        }

        @media (max-width: 360px) {
            .cards-grid {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .card {
                padding: 12px;
                min-height: 100px;
            }

            .card-number {
                font-size: 18px;
            }

            .card-label {
                font-size: 9px;
            }
        }

        /* css untuk icon */
        .card-icon img {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }

        .info-card-icon img {
            width: 60px;
            height: 60px;
            margin-top: 10px;
        }

        .menu-icon {
            display: inline-flex;
            align-items: center;
            margin-right: 12px;
        }

        .menu-icon img {
            width: 25px;
            height: 25px;
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

        /* dropdown arrow */
        .dropdown-arrow {
            margin-left: auto;
            display: inline-flex;
        }

        .dropdown-arrow img {
            width: 14px;
            transition: transform 0.3s ease;
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
            pointer-events: none;
        }

        .mobile-menu-toggle.hidden-on-scroll,
        .mobile-user-icon.hidden-on-scroll,
        .admin-status-top.hidden-on-scroll {
            transform: translateY(-80px);
            opacity: 0;
            pointer-events: none;
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
                padding: 70px 16px 30px;
                width: 100%;
            }

            .page-header {
                display: block;
                margin-bottom: 30px;
                padding-bottom: 15px;
                border-bottom: none;
            }

            .page-header h1 {
                font-size: 24px;
                margin-bottom: 5px;
            }

            .page-header p {
                font-size: 13px;
                margin-bottom: 0;
            }

            .admin-status-top {
                display: flex;
            }

            .cards-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
                margin-bottom: 35px;
            }

            .card {
                padding: 18px 14px;
                min-height: 155px;
                border-radius: 10px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }

            .card-icon {
                font-size: 36px;
                margin-bottom: 10px;
            }

            .card-number {
                font-size: 26px;
                margin-bottom: 5px;
            }

            .card-label {
                font-size: 12px;
                letter-spacing: 0px;
            }

            .info-section {
                margin-top: 35px;
                margin-bottom: 30px;
            }

            .info-section h2 {
                font-size: 20px;
                margin-bottom: 18px;
                border-bottom: none;
                padding-bottom: 0;
            }

            .info-cards-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .info-card {
                padding: 22px;
                border-radius: 10px;
            }

            .info-card-title {
                font-size: 16px;
            }

            .info-card-desc {
                font-size: 13px;
            }
        }

        .dropdown-toggle.active .dropdown-arrow img {
            transform: rotate(90deg);
        }

        /* submenu */
        .submenu {
            display: none;
            padding-left: 32px;
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

        .info-card {
            text-decoration: none;
            color: inherit;
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

        /* Table spacing & padding improvements */
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px; /* vertical gap between rows */
        }

        .table thead th {
            padding: 10px 12px;
            text-align: left;
            font-weight: 700;
            color: #2c3e50;
            background: transparent;
        }

        .table tbody td {
            background: #ffffff;
            padding: 12px 16px;
            vertical-align: middle;
            border: none;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            border-radius: 8px;
        }

        .table tbody tr {
            /* keep transparent since cells have background */
        }

        @media (max-width: 768px) {
            .table thead { display: none; }
            .table tbody td { display: block; width: 100%; box-sizing: border-box; }
            .table tbody tr { display: block; margin-bottom: 10px; }
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

    <!-- Mobile Menu Toggle Button -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleMobileMenu()">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- Desktop Admin Badge (fixed, placed at top-level so it doesn't follow scrolling containers) -->
    <div class="admin-status-top desktop-admin" id="desktopAdminBadge" onclick="showLogoutNotification()">
        <div class="online-dot"></div>
        <span>Admin</span>
    </div>

    <!-- Mobile User Icon -->
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
                    <a href="dashboard.php" class="active">
                        <span class="menu-icon">
                            <img src="assets/icons/dashboard.png" alt="Dashboard">
                        </span>
                        Dashboard
                    </a>
                </li>
                <li>


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
                <h1>Dashboard</h1>
                <p>Jumlah Informasi Sekolah di Bangkalan</p>
            </div>

            <!-- Desktop Admin Badge removed from here (rendered at top-level body) -->

            <div class="cards-grid">
                <div class="card">
                    <div class="card-icon">
                        <img src="assets/icons/kb.png" alt="KB">
                    </div>
                    <div class="card-number"><?php echo $kb; ?></div>
                    <div class="card-label">KB</div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <img src="assets/icons/pkbm.png" alt="PKBM">
                    </div>
                    <div class="card-number"><?php echo $pkbm; ?></div>
                    <div class="card-label">PKBM</div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <img src="assets/icons/sps.png" alt="SPS">
                    </div>
                    <div class="card-number"><?php echo $sps; ?></div>
                    <div class="card-label">SPS</div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <img src="assets/icons/tk.png" alt="TK">
                    </div>
                    <div class="card-number"><?php echo $tk; ?></div>
                    <div class="card-label">TK</div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <img src="assets/icons/tpa.png" alt="TPA">
                    </div>
                    <div class="card-number"><?php echo $tpa; ?></div>
                    <div class="card-label">TPA</div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <img src="assets/icons/sd.png" alt="SD">
                    </div>
                    <div class="card-number"><?php echo $sd; ?></div>
                    <div class="card-label">SD</div>
                </div>

                <div class="card">
                    <div class="card-icon">
                        <img src="assets/icons/smp.png" alt="SMP">
                    </div>
                    <div class="card-number"><?php echo $smp; ?></div>
                    <div class="card-label">SMP</div>
                </div>

                <div class="card">
                    <div class="card-icon">
                        <img src="assets/icons/mts.png" alt="MTS">
                    </div>
                    <div class="card-number"><?php echo $mts; ?></div>
                    <div class="card-label">MTS</div>
                </div>

                <div class="card">
                    <div class="card-icon">
                        <img src="assets/icons/sma1.png" alt="SMA">
                    </div>
                    <div class="card-number"><?php echo $sma; ?></div>
                    <div class="card-label">SMA</div>
                </div>                

                <div class="card">
                    <div class="card-icon">
                        <img src="assets/icons/smk.png" alt="SMK">
                    </div>
                    <div class="card-number"><?php echo $smk; ?></div>
                    <div class="card-label">SMK</div>
                </div>

                <div class="card">
                    <div class="card-icon">
                        <img src="assets/icons/ma.png" alt="MA">
                    </div>
                    <div class="card-number"><?php echo $ma; ?></div>
                    <div class="card-label">MA</div>
                </div>
            </div>

            <!-- Informasi Sistem Section -->
            <div class="info-section">
                <h2>Informasi Sistem</h2>
                <div class="info-cards-grid">
                    <a href="hasil_input.php" class="info-card">
                        <div class="info-card-icon">
                            <img src="assets/icons/data.png" alt="Data Management">
                        </div>

                        <div class="info-card-title">Data Management</div>

                        <div class="info-card-desc">
                            Kelola data sekolah dengan mudah melalui sistem informasi yang terintegrasi
                        </div>
                    </a>

                    <a href="peta.php" class="info-card">
                        <div class="info-card-icon">
                            <img src="assets/icons/peta.png" alt="Peta Lokasi">
                        </div>
                        <div class="info-card-title">Peta Lokasi</div>
                        <div class="info-card-desc">
                            Visualisasi lokasi sekolah berdasarkan geografis dan zona pendidikan
                        </div>
                    </a>

                    <div class="info-card">
                        <div class="info-card-icon">
                            <img src="assets/icons/analisis.png" alt="Analisis Data">
                        </div>
                        <div class="info-card-title">Analisis Data</div>
                        <div class="info-card-desc">
                            Dapatkan insights dan analisis mendalam tentang distribusi sekolah
                        </div>
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

        // Close sidebar when clicking on menu items (but only on small screens)
        const menuLinks = document.querySelectorAll('.sidebar-menu a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function(event) {
                // Don't close sidebar for dropdown toggles
                if (this.classList.contains('dropdown-toggle')) {
                    return;
                }

                // Only auto-hide sidebar on small screens (mobile)
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

        // Scroll detection: hide/show only the mobile menu toggle (keep Admin visible)
        let lastScrollTop = 0;
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');

        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            
            if (currentScroll > lastScrollTop && currentScroll > 60) {
                // Scrolling DOWN - hide mobile menu toggle only
                if (mobileMenuToggle) mobileMenuToggle.classList.add('hidden-on-scroll');
            } else if (currentScroll < lastScrollTop) {
                // Scrolling UP - show mobile menu toggle only
                if (mobileMenuToggle) mobileMenuToggle.classList.remove('hidden-on-scroll');
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
</body>
</html>
