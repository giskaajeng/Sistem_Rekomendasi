<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portofolio KP - BPS Bangkalan 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #f97316;
            --secondary-light: #fb923c;
            --accent: #06b6d4;
            --background: #f8fafc;
            --foreground: #1e293b;
            --card: #ffffff;
            --muted: #64748b;
            --border: #e2e8f0;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 90px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background);
            color: var(--foreground);
            line-height: 1.6;
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            padding: 0.75rem 0;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--foreground);
        }

        .nav-logo {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
        }

        .nav-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary);
        }

        .nav-subtitle {
            font-size: 0.75rem;
            color: var(--muted);
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 0.5rem;
        }

        .nav-link {
            text-decoration: none;
            color: var(--foreground);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: var(--primary);
            color: white;
        }

        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
        }

        .hamburger {
            display: block;
            width: 24px;
            height: 2px;
            background: var(--foreground);
            position: relative;
        }

        .hamburger::before,
        .hamburger::after {
            content: '';
            position: absolute;
            width: 24px;
            height: 2px;
            background: var(--foreground);
            left: 0;
        }

        .hamburger::before { top: -7px; }
        .hamburger::after { top: 7px; }

        /* Hero Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-15px);
            }
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 10px 40px rgba(37, 99, 235, 0.25);
            }
            50% {
                box-shadow: 0 15px 50px rgba(37, 99, 235, 0.4);
            }
        }

        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes rotateGlow {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 50%, #ecfeff 100%);
            background-size: 200% 200%;
            animation: gradientShift 8s ease infinite;
            padding: 6rem 1.5rem 4rem;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.12) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.12) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite reverse;
        }

        /* Floating particles */
        .hero-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 10px;
            height: 10px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 50%;
            opacity: 0.3;
            animation: float 4s ease-in-out infinite;
        }

        .particle:nth-child(1) { top: 20%; left: 10%; animation-delay: 0s; width: 8px; height: 8px; }
        .particle:nth-child(2) { top: 60%; left: 20%; animation-delay: 1s; width: 12px; height: 12px; }
        .particle:nth-child(3) { top: 30%; right: 15%; animation-delay: 2s; width: 6px; height: 6px; }
        .particle:nth-child(4) { top: 70%; right: 25%; animation-delay: 0.5s; width: 10px; height: 10px; }
        .particle:nth-child(5) { top: 45%; left: 5%; animation-delay: 1.5s; width: 14px; height: 14px; }
        .particle:nth-child(6) { top: 80%; left: 40%; animation-delay: 2.5s; width: 8px; height: 8px; }

        .hero-content {
            max-width: 900px;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .hero-logo-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 2rem;
        }

        .hero-logo-wrapper::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            background: linear-gradient(135deg, var(--primary), var(--accent), var(--secondary));
            border-radius: 50%;
            opacity: 0.3;
            animation: rotateGlow 4s linear infinite;
            z-index: -1;
        }

        .hero-logo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 10px 40px rgba(37, 99, 235, 0.25);
            animation: scaleIn 0.8s ease-out, pulse 3s ease-in-out infinite;
        }

        .hero-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            background-size: 200% 200%;
            animation: fadeInDown 0.8s ease-out 0.2s both, gradientShift 4s ease infinite;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 2rem;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .hero h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--foreground);
            margin-bottom: 1rem;
            line-height: 1.2;
            animation: fadeInUp 0.8s ease-out 0.3s both;
        }

        .hero h1 span {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientShift 4s ease infinite;
        }

        .hero p {
            font-size: 1.1rem;
            color: var(--muted);
            max-width: 600px;
            margin: 0 auto 2rem;
            animation: fadeInUp 0.8s ease-out 0.5s both;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease-out 0.7s both;
        }

        .hero-buttons .btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hero-buttons .btn:hover {
            transform: translateY(-3px) scale(1.02);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }

        .btn-outline {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        /* Section Styles */
        .section {
            padding: 5rem 1.5rem;
        }

        .section-alt {
            background: white;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .section-badge {
            display: inline-block;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.1), rgba(6, 182, 212, 0.1));
            color: var(--primary);
            padding: 0.4rem 1rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--foreground);
            margin-bottom: 0.75rem;
        }

        .section-subtitle {
            color: var(--muted);
            font-size: 1rem;
            max-width: 700px;
            margin: 0 auto;
        }

        /* Reduce gap between section title and its content */
        .section .projects-grid,
        .section .impressions-grid,
        .section .team-grid {
            margin-top: 0.6rem;
        }

        /* Team Section */
        .team-grid {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: stretch;
            gap: 2.5rem;
            flex-wrap: wrap;
        }

        .team-card {
            background: var(--card);
            border-radius: 1.5rem;
            padding: 2.5rem 2.25rem 2rem 2.25rem;
            text-align: center;
            box-shadow: 0 8px 32px rgba(37, 99, 235, 0.10), 0 1.5px 8px rgba(0,0,0,0.04);
            border: 1.5px solid var(--primary);
            transition: all 0.3s cubic-bezier(.4,2,.6,1);
            position: relative;
            overflow: visible;
            flex: 0 0 320px;
            max-width: 400px;
        }

        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(37, 99, 235, 0.1);
            border-color: var(--primary);
        }

        .team-avatar {
            width: 170px;
            height: 170px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary);
            margin-bottom: 1.75rem;
            box-shadow: 0 4px 24px rgba(37,99,235,0.15);
        }

        .team-name {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--foreground);
            margin-bottom: 0.25rem;
        }

        .team-nim {
            font-size: 0.85rem;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .team-role {
            font-size: 0.85rem;
            color: var(--muted);
        }

        /* Projects Section */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .project-card {
            background: var(--card);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(37, 99, 235, 0.15);
            border-color: var(--primary);
        }

        .project-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: linear-gradient(135deg, #eff6ff, #ecfeff);
        }

        .project-content {
            padding: 1.5rem;
        }

        .project-category {
            display: inline-block;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.1), rgba(6, 182, 212, 0.1));
            color: var(--primary);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .project-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--foreground);
            margin-bottom: 0.5rem;
        }

        .project-desc {
            font-size: 0.9rem;
            color: var(--muted);
            margin-bottom: 1rem;
        }

        /* Project action buttons */
        .project-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            justify-content: flex-start;
            align-items: center;
        }

        .project-actions .btn {
            padding: 0.5rem 0.9rem;
            font-size: 0.9rem;
            border-radius: 0.6rem;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.08);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .project-actions .btn-outline {
            background: white;
            color: var(--primary);
            border: 1.5px solid rgba(37,99,235,0.12);
        }

        .project-actions .btn-outline:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(37, 99, 235, 0.12);
        }

        .project-actions .btn-primary {
            padding: 0.5rem 1rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
        }

        .project-actions .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(37, 99, 235, 0.18);
        }

        .project-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            font-weight: 600;
            font-size: 0.9rem;
            transition: transform 0.2s ease;
        }

        .project-link svg {
            transition: transform 0.3s ease;
        }

        .project-card:hover .project-link svg {
            transform: translateX(4px);
        }

        /* Impressions Section */
        .impressions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .impression-card {
            background: var(--card);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            border-left: 4px solid var(--primary);
        }

        .impression-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .impression-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .impression-name {
            font-weight: 600;
            color: var(--foreground);
        }

        .impression-role {
            font-size: 0.8rem;
            color: var(--muted);
        }

        .impression-text {
            color: var(--muted);
            font-size: 0.95rem;
            font-style: italic;
            line-height: 1.7;
        }

        .impression-text::before {
            content: '"';
            font-size: 1.5rem;
            color: var(--primary);
            font-weight: bold;
        }

        /* Footer - Combined with BPS Info */
        .footer {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 4rem 1.5rem 2rem;
        }

        .footer-main {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2.5rem;
            margin-bottom: 2.5rem;
        }

        .footer-bps-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }

        .footer-logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.3);
            background: white;
        }

        .footer-title {
            font-weight: 700;
            font-size: 1.2rem;
        }

        .footer-subtitle {
            font-size: 0.85rem;
            opacity: 0.85;
        }

        .footer-description {
            font-size: 0.9rem;
            opacity: 0.9;
            line-height: 1.7;
            margin-bottom: 0.5rem;
        }

        .footer-contact {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .footer-contact-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .footer-contact-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .footer-contact-item svg {
            flex-shrink: 0;
            margin-top: 2px;
            opacity: 0.9;
        }

        .footer-links-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .footer-links-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            opacity: 0.85;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-links a:hover {
            opacity: 1;
            transform: translateX(5px);
        }

        .footer-links a::before {
            content: '→';
            font-size: 0.8rem;
        }

        .footer-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .footer-stat-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            padding: 1rem;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .footer-stat-card:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-3px);
        }

        .footer-stat-number {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }

        .footer-stat-label {
            font-size: 0.75rem;
            opacity: 0.85;
        }

        .footer-map {
            border-radius: 0.75rem;
            overflow: hidden;
            height: 180px;
            border: 2px solid rgba(255, 255, 255, 0.15);
        }

        .footer-map iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .footer-bottom-text {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .footer-social {
            display: flex;
            gap: 1rem;
        }

        .footer-social a {
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-social a:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 1rem;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            }

            .nav-menu.active {
                display: flex;
            }

            .mobile-toggle {
                display: block;
            }

            .hero h1 {
                font-size: 1.75rem;
            }

            .hero-logo {
                width: 90px;
                height: 90px;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 280px;
                justify-content: center;
            }

            .footer-main {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-brand {
                justify-content: center;
            }

            .footer-contact-item {
                justify-content: center;
            }

            .footer-links {
                align-items: center;
            }

            .footer-links a {
                justify-content: center;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="#beranda" class="nav-brand">
                <img src="portofolio/bps.jpg" alt="Logo BPS" class="nav-logo">
                <div>
                    <div class="nav-title">BPS Bangkalan</div>
                    <div class="nav-subtitle">Portofolio Kerja Praktik</div>
                </div>
            </a>
            <ul class="nav-menu" id="navMenu">
                <li><a href="#beranda" class="nav-link">Beranda</a></li>
                <li><a href="#anggota" class="nav-link">Anggota</a></li>
                <li><a href="#project" class="nav-link">Project</a></li>
                <li><a href="#kesan" class="nav-link">Kesan</a></li>
                <li><a href="#info-bps" class="nav-link">Kontak</a></li>
            </ul>
            <button class="mobile-toggle" onclick="toggleMenu()">
                <span class="hamburger"></span>
            </button>
        </div>
    </nav>

    <!-- Hero / Beranda -->
    <section class="hero" id="beranda">
        <!-- Floating particles -->
        <div class="hero-particles">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-logo-wrapper">
                <img src="portofolio/bps.jpg" alt="Logo BPS" class="hero-logo">
            </div>
            <h1>Portofolio Mahasiswa KP<br><span>BPS Kabupaten Bangkalan</span></h1>
            <p>Dokumentasi kegiatan dan hasil kerja praktek mahasiswa di Badan Pusat Statistik Kabupaten Bangkalan periode 2026.</p>
            <div class="hero-buttons">
                <a href="#anggota" class="btn btn-outline">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Selengkapnya
                </a>
            </div>
        </div>
    </section>

    <!-- Anggota Kelompok -->
    <section class="section section-alt" id="anggota">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Anggota Kelompok</h2>
                <p class="section-subtitle">Mahasiswa peserta kerja praktek di BPS Kabupaten Bangkalan periode 2026</p>
            </div>
            <div class="team-grid">
                <?php
                $anggota = [
                    [
                        'nama' => 'Nurul Hasanah',
                        'nim' => 'Universitas Trunojoyo Madura',
                        'role' => 'Sistem Informasi',
                        'foto' => 'portofolio/ana.jpg'
                    ],
                    [
                        'nama' => 'Giska Ajeng Savitri',
                        'nim' => 'Universitas Trunojoyo Madura',
                        'role' => 'Sistem Informasi',
                        'foto' => 'portofolio/giska.jpg'
                    ]
                ];

                foreach ($anggota as $member) {
                    echo '<div class="team-card">';
                    echo '<img src="' . $member['foto'] . '" alt="' . $member['nama'] . '" class="team-avatar">';
                    echo '<h3 class="team-name">' . $member['nama'] . '</h3>';
                    echo '<p class="team-nim">' . $member['nim'] . '</p>';
                    echo '<p class="team-role">' . $member['role'] . '</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Project / Tugas -->
    <section class="section" id="project">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Tugas atau Project yang Telah Dikerjakan</h2>
                <p class="section-subtitle">Kumpulan tugas dan project yang telah diselesaikan selama kegiatan kerja praktek</p>
            </div>
            <div class="projects-grid">
                <?php
                $projects = [
                    [
                        'judul' => 'Klasifikasi Data Nama Usaha',
                        'kategori' => 'Data Klasifikasi',
                        'deskripsi' => 'Standarisasi nama usaha dan pemetaan potensi usaha berdasarkan wilayah untuk mengetahui sentra dan jumlah usaha.',
                        'link' => 'https://colab.research.google.com/drive/1HoMDctmfzbPle1KDD6YT7rpjd0coCkPx?usp=sharing',
                        'link_proses' => 'https://colab.research.google.com/drive/1HoMDctmfzbPle1KDD6YT7rpjd0coCkPx?usp=sharing',
                        'link_hasil' => 'https://docs.google.com/spreadsheets/d/1LZbgGZt16C7DfeOHBHvJlc-AJXZO2HpS/edit?gid=712684456#gid=712684456',
                        'gambar' => 'portofolio/data1.jpg'
                        ],
                    [
                        'judul' => 'Entry Data Penerima Bantuan IKM',
                        'kategori' => 'Data Entry',
                        'deskripsi' => 'Melakukan entry, pengecekan, dan verifikasi data penerima bantuan IKM menggunakan Microsoft Excel.',
                        'link' => '#project-entry-penerima-bantuan-ikm',
                        'link_proses' => 'https://docs.google.com/spreadsheets/d/1ahQfsZ0RaJtxeJteJhbsojeeIYw3INnO/edit?gid=280793660#gid=280793660',
                        'link_hasil' => 'https://docs.google.com/spreadsheets/d/1Fzm1T1Zcb4RwS-nazfg3BQx-mBy63uhwfkBe9z6buj8/edit?usp=sharing',
                        'gambar' => 'portofolio/data2.png'
                    ],
                        [
                        'judul' => 'Rekomendasi Pendidikan Terdekat',
                        'kategori' => 'Sistem Rekomendasi',
                        'deskripsi' => 'Sistem rekomendasi pendidikan terdekat di tiap wilayah Bangkalan berdasarkan data lokasi sekolah dan kantor kepala desa.',
                        'link' => '#project-rekomendasi-pendidikan',
                        'link_proses' => 'https://docs.google.com/spreadsheets/d/1W0pItbqvkHeruUjnA0yl20RI-SmhEq_O/edit?gid=1734783778#gid=1734783778',
                        'link_hasil' => 'login.php',
                        'gambar' => 'portofolio/data3.jpg'
                    ],
                ];

                foreach (array_slice($projects, 0, 3) as $project) {
                    echo '<div class="project-card">';
                    echo '<img src="' . $project['gambar'] . '" alt="' . $project['judul'] . '" class="project-image">';
                    echo '<div class="project-content">';
                    echo '<span class="project-category">' . $project['kategori'] . '</span>';
                    echo '<h3 class="project-title">' . $project['judul'] . '</h3>';
                    echo '<p class="project-desc">' . $project['deskripsi'] . '</p>';
                    echo '<div class="project-actions">';
                    echo '<a href="' . $project['link_proses'] . '" class="btn btn-outline" aria-label="Lihat Proses" target="_blank" rel="noopener noreferrer">Lihat Proses</a>';

                    // Special case: For the "Rekomendasi Pendidikan Terdekat" project,
                    // always open the `login.php` page in a new tab (same folder, different file).
                    if ($project['judul'] === 'Rekomendasi Pendidikan Terdekat') {
                        $target = 'login.php';
                        $targetAttr = ' target="_blank" rel="noopener noreferrer"';
                    } else {
                        // If this project uses login.php as result link, and session indicates a logged-in user,
                        // point directly to the results page (`rekomendasi_user.php`). Otherwise keep original link.
                        $target = $project['link_hasil'];
                        $targetAttr = ' target="_blank" rel="noopener noreferrer"';
                        if ($target === 'login.php' && isset($_SESSION['username'])) {
                            $target = 'rekomendasi_user.php';
                        }
                    }
                    echo '<a href="' . $target . '" class="btn btn-primary" aria-label="Lihat Hasil"' . $targetAttr . '>Lihat Hasil</a>';

                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Kesan dan Ilmu -->
    <section class="section section-alt" id="kesan">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Kesan Kerja Praktik</h2>
                <p class="section-subtitle">Kesan Yang Dirasakan Selama Menjalani Kerja Praktik</p>
            </div>
            <div class="impressions-grid">
                <?php
                $kesan = [
                    [
                        'nama' => 'Nurul Hasanah',
                        'role' => 'Sistem Informasi',
                        'foto' => 'portofolio/ana.jpg',
                        'kesan' => 'Selama KP di BPS, saya memperoleh pengalaman berharga serta pemahaman yang lebih mendalam tentang pentingnya data dan pengolahan data statistik.'
                    ],
                    [
                        'nama' => 'Giska Ajeng Savitri',
                        'role' => 'Sistem Informasi',
                        'foto' => 'portofolio/giska.jpg',
                        'kesan' => 'Kegiatan KP di BPS memberikan wawasan baru tentang peran data statistik dalam mendukung perencanaan dan pengambilan keputusan yang tepat.'
                    ]
                ];

                foreach (array_slice($kesan, 0, 2) as $item) {
                    echo '<div class="impression-card">';
                    echo '<div class="impression-header">';
                    echo '<img src="' . $item['foto'] . '" alt="' . $item['nama'] . '" class="impression-avatar">';
                    echo '<div>';
                    echo '<div class="impression-name">' . $item['nama'] . '</div>';
                    echo '<div class="impression-role">' . $item['role'] . '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '<p class="impression-text">' . $item['kesan'] . '</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Footer with BPS Info -->
    <footer class="footer" id="info-bps">
        <div class="footer-main">
            <!-- BPS Info Section -->
            <div class="footer-bps-info">
                <div class="footer-brand">
                    <img src="portofolio/bps.jpg" alt="Logo BPS" class="footer-logo">
                    <div>
                        <div class="footer-title">BPS Kabupaten Bangkalan</div>
                        <div class="footer-subtitle">Badan Pusat Statistik</div>
                    </div>
                </div>
                <p class="footer-description">
                    Badan Pusat Statistik Kabupaten Bangkalan merupakan instansi pemerintah yang bertugas mengumpulkan, mengolah, dan menyajikan data statistik untuk mendukung perencanaan dan pembangunan daerah.
                </p>
            </div>

            <!-- Contact Info -->
            <div class="footer-contact">
                <div class="footer-contact-title">Kontak Kami</div>
                <div class="footer-contact-item">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Jl. Halim Perdana Kusuma No.5, Area Sawah, Mlajah, Kec. Bangkalan, Kabupaten Bangkalan, Jawa Timur 69116</span>
                </div>
                <div class="footer-contact-item">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span>(031) 3095622</span>
                </div>
                <div class="footer-contact-item">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span>bps3526@bps.go.id</span>
                </div>
                <div class="footer-contact-item">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                    </svg>
                    <span>https://bangkalankab.bps.go.id/id</span>
                </div>
            </div>

            <!-- Stats & Map -->
            <div>
                <div class="footer-map" style="margin-top: 1rem;">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3958.4714999999997!2d112.7353!3d-7.0461!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd801a3b7d6a4a7%3A0x64b7b09f83f5f5f5!2sBPS%20Kabupaten%20Bangkalan!5e0!3m2!1sid!2sid!4v1690000000000!5m2!1sid!2sid" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('navMenu');
            menu.classList.toggle('active');
        }

        // Close menu when clicking on a link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                document.getElementById('navMenu').classList.remove('active');
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.1)';
            } else {
                navbar.style.boxShadow = '0 1px 3px rgba(0, 0, 0, 0.1)';
            }
        });
    </script>

</body>
</html>