<?php
require_once 'config.php';

// Daftar username yang menjadi admin
// Ubah array ini sesuai kebutuhan Anda
$admin_users = array('admin', 'administrator');

$error = "";
$show_login_form = false;

// Cek apakah user memilih untuk login sebagai admin
if (isset($_GET['mode']) && $_GET['mode'] === 'admin') {
    $show_login_form = true;
}

// Jika user memilih mode user tanpa login
if (isset($_GET['mode']) && $_GET['mode'] === 'user') {
    $_SESSION['id_user'] = uniqid();
    $_SESSION['username'] = 'guest_user';
    $_SESSION['role'] = 'user';
    header("Location: dashboard_user.php");
    exit();
}

// Jika tabel user tidak ada atau kosong, kita tidak mengandalkan DB untuk login admin.
// Admin default didefinisikan di config.php (ADMIN_USERNAME, ADMIN_PASSWORD_HASH).

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil input dari form
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Jika cocok dengan admin yang didefinisikan di config, langsung izinkan
    if ($username === ADMIN_USERNAME) {
        // Bandingkan password plain text (bukan hash)
        if ($password === ADMIN_PASSWORD_HASH) {
            $_SESSION['id_user'] = uniqid();
            $_SESSION['username'] = ADMIN_USERNAME;
            $_SESSION['role'] = 'admin';
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Username atau Password salah!";
        }
    } else {
        // Cek apakah tabel user ada
        $hasUserTable = false;
        $res = query("SHOW TABLES LIKE 'user'");
        if ($res && mysqli_num_rows($res) > 0) $hasUserTable = true;

        if ($hasUserTable) {
            // Ambil user dari database jika ada
            $user = fetch_row("SELECT * FROM user WHERE username = '" . escape($username) . "' LIMIT 1");

            if ($user) {
                $stored = $user['pw'] ?? '';
                $password_ok = false;

                // Dukungan untuk password yang di-hash maupun plain (legacy)
                if (!empty($stored) && password_verify($password, $stored)) {
                    $password_ok = true;
                } elseif ($password === $stored) {
                    $password_ok = true;
                }

                if ($password_ok) {
                    $_SESSION['id_user'] = $user['id_user'] ?? uniqid();
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = in_array($user['username'], $admin_users) ? 'admin' : 'user';

                    // Redirect berdasarkan role
                    if ($_SESSION['role'] === 'admin') {
                        header("Location: dashboard.php");
                    } else {
                        header("Location: dashboard_user.php");
                    }
                    exit();
                } else {
                    $error = "Username atau Password salah!";
                }
            } else {
                // Jika user tidak ditemukan, tetap izinkan sebagai guest (user biasa)
                $_SESSION['id_user'] = uniqid();
                $_SESSION['username'] = $username ?: 'guest';
                $_SESSION['role'] = in_array($username, $admin_users) ? 'admin' : 'user';

                // Redirect berdasarkan role
                if ($_SESSION['role'] === 'admin') {
                    header("Location: dashboard.php");
                } else {
                    header("Location: dashboard_user.php");
                }
                exit();
            }
        } else {
            // Tidak ada tabel user: izinkan guest (kecuali admin harus pake credential di config)
            $_SESSION['id_user'] = uniqid();
            $_SESSION['username'] = $username ?: 'guest';
            $_SESSION['role'] = in_array($username, $admin_users) ? 'admin' : 'user';

            // Redirect berdasarkan role
            if ($_SESSION['role'] === 'admin') {
                // Jika username terdaftar sebagai admin_users tetapi tidak menggunakan ADMIN_USERNAME, tolak login
                $error = "Admin harus login dengan akun admin yang valid.";
            } else {
                header("Location: dashboard_user.php");
            }
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Rekomendasi Pendidikan Kabupaten Bangkalan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, rgb(255, 255, 255) 0%, rgb(255, 255, 255)  100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-wrapper {
            display: flex;
            width: 100%;
            max-width: 1000px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
.login-left {
            flex: 1;
            background: linear-gradient(135deg, rgb(221, 145, 59) 0%, rgb(221, 145, 59) 100%);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            position: relative;
        }
        
        .logo-container {
            margin-bottom: 40px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }
        
        .logo-container img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .info-text h2 {
            font-size: 28px;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .info-text p {
            font-size: 16px;
            line-height: 1.6;
            opacity: 0.95;
            text-align: center;
        }
        
        .decorative-icons {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .icon-badge {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        /* Button Selection Style */
        .selection-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 30px;
        }

        .selection-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .selection-header h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .selection-header p {
            color: #666;
            font-size: 16px;
        }

        .button-group {
            display: flex;
            gap: 20px;
            justify-content: center;
            width: 100%;
            max-width: 400px;
            flex-wrap: wrap;
        }

        .btn-selection {
            flex: 1;
            min-width: 150px;
            padding: 16px 32px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-admin {
            background: linear-gradient(180deg, #1a3a5c 0%, #0f2643 100%);
            color: white;
        }

        .btn-admin:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(26, 58, 92, 0.3);
        }

        .btn-user {
            background: linear-gradient(135deg, rgb(221, 145, 59) 0%, rgb(221, 145, 59) 100%);
            color: white;
        }

        .btn-user:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(87, 199, 65, 0.3);
        }

        .btn-selection:active {
            transform: translateY(-1px);
        }

        .form-container {
            width: 100%;
        }
        
.login-right {
            flex: 1;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 2;
        }
        
        /* Wave SVG divider - inside card only */
        .login-wrapper {
            position: relative;
        }
        
        .wave-divider {
            position: absolute;
            left: 50%;
            top: 0;
            width: 60px;
            height: 100%;
            transform: translateX(-50%);
            z-index: 10;
            overflow: hidden;
            pointer-events: none;
        }
        
        .wave-divider svg {
            height: 100%;
            width: 100%;
        }
        
        .login-header {
            margin-bottom: 40px;
        }
        
        .login-header h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background-color: #f8f9fa;
            color: #333;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        input[type="text"]::placeholder,
        input[type="password"]::placeholder {
            color: #999;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Password visibility toggle */
        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 44px;
        }

        .toggle-password {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            cursor: pointer;
            padding: 4px;
        }

        .toggle-password:focus {
            outline: none;
        }

        .toggle-password img {
            display: block;
            width: 22px;
            height: 22px;
            object-fit: contain;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(180deg, #1a3a5c 0%, #0f2643 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgb(30, 61, 197);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .error-message {
            background-color: #ff6b6b;
            color: white;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            animation: shake 0.3s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .info-message {
            background: linear-gradient(135deg, #57c741 100%);
            color: white;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 13px;
            font-weight: 500;
        }
        
        .info-message strong {
            display: block;
            margin-bottom: 5px;
        }
        
/* Responsive Design */
        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: column;
            }
            
            .login-left {
                padding: 40px 30px;
            }
            
            .wave-divider {
                display: none;
            }
            
            .login-right {
                padding: 40px 30px;
            }
            
            .logo-container {
                flex-direction: row;
                gap: 15px;
            }
            
            .logo-container img {
                width: 80px;
                height: 80px;
            }
            
            .info-text h2 {
                font-size: 22px;
            }
            
            .login-header h1 {
                font-size: 26px;
            }
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            width: 100%;
            padding-right: 45px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            color: #666;
            transition: color 0.3s ease;
        }

        .toggle-password:hover {
            color: #333;
        }

        .toggle-password svg {
            width: 22px;
            height: 22px;
            display: block;
        }

        .decorative-icons {
            display: flex;
            gap: 16px;
            justify-content: center;
        }

        .icon-badge {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #f2f8ff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-badge img {
            width: 26px;
            height: 26px;
            object-fit: contain;
        }

        /* css btn admin dan user */
        .button-group {
            display: flex;
            gap: 16px;
        }

        .btn-selection {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            text-decoration: none;
            font-weight: 600;
            border-radius: 8px;
            transition: 0.2s ease;
        }

        .btn-icon {
            width: 22px;
            height: 22px;
            object-fit: contain;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            width: 100%;
            padding-right: 45px; /* kasih ruang icon */
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }

        .toggle-password img {
            width: 22px;
            height: 22px;
        }

        /* Mobile Responsive Improvements */
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .login-wrapper {
                border-radius: 8px;
            }

            .login-left {
                padding: 30px 20px;
                min-height: auto;
            }

            .logo-container {
                margin-bottom: 20px;
                gap: 10px;
            }

            .logo-container img {
                width: 60px;
                height: 60px;
            }

            .info-text h2 {
                font-size: 18px;
                margin-bottom: 10px;
            }

            .info-text p {
                font-size: 13px;
                line-height: 1.4;
            }

            .decorative-icons {
                gap: 10px;
                margin-top: 20px;
            }

            .icon-badge {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }

            .login-right {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 22px;
            }

            .login-header p {
                font-size: 12px;
            }

            .form-group {
                margin-bottom: 18px;
            }

            input[type="text"],
            input[type="password"] {
                padding: 11px 14px;
                font-size: 16px;
            }

            .btn-login {
                padding: 12px;
                font-size: 15px;
            }

            .button-group {
                flex-direction: column;
                gap: 12px;
            }

            .btn-selection {
                width: 100%;
                justify-content: center;
                padding: 14px 16px;
                font-size: 14px;
                min-width: auto;
                flex: auto;
            }

            .selection-header h2 {
                font-size: 20px;
            }

            .selection-header p {
                font-size: 13px;
            }

            .error-message,
            .info-message {
                font-size: 12px;
                padding: 12px;
            }
        }

        @media (max-width: 600px) {
            .login-wrapper {
                max-width: 100%;
            }

            .login-left,
            .login-right {
                padding: 40px 25px;
            }

            .login-header h1 {
                font-size: 26px;
            }

            .button-group {
                max-width: 100%;
            }
        }

        @media (max-width: 768px) and (orientation: landscape) {
            .login-left {
                padding: 30px 20px;
            }

            .login-right {
                padding: 30px 20px;
            }

            .logo-container img {
                width: 60px;
                height: 60px;
            }

            .decorative-icons {
                gap: 10px;
                margin-top: 15px;
            }

            .info-text h2 {
                font-size: 16px;
            }

            .info-text p {
                font-size: 12px;
            }
        }

    </style>
</head>
<body>
<div class="login-wrapper">
        <!-- Wave Divider SVG - inside card -->
        <div class="wave-divider">
            <svg viewBox="0 0 60 100" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M30,0 Q60,5 30,10 Q0,15 30,20 Q60,25 30,30 Q0,35 30,40 Q60,45 30,50 Q0,55 30,60 Q60,65 30,70 Q0,75 30,80 Q60,85 30,90 Q0,95 30,100 L60,100 L60,0 Z" fill="white"/>
            </svg>
        </div>
        
        <!-- Left Section with Bangkalan Info -->
        <div class="login-left">
            <div class="logo-container">
                <img src="assets/icons/bkl.jpeg" alt="Logo Kabupaten Bangkalan">
                <img src="assets/icons/bps.jpg" alt="Logo BPS" style="background: white;">
            </div>
            <div class="info-text">
                <h2 style="text-align: center;">Kabupaten Bangkalan</h2>
                <p>Sistem Rekomendasi Informasi Persebaran Pendidikan Sekolah</p>
                <p style="margin-top: 20px; font-size: 14px;">Membantu mengidentifikasi distribusi institusi pendidikan di seluruh wilayah Kabupaten Bangkalan untuk pengembangan pendidikan yang merata dan berkualitas.</p>
            </div>
            <div class="decorative-icons">
                <div class="icon-badge">
                    <img src="assets/icons/sma.png" alt="Buku">
                </div>
                <div class="icon-badge">
                    <img src="assets/icons/kecamatan.png" alt="Wisuda">
                </div>
                <div class="icon-badge">
                    <img src="assets/icons/rekomendasi1.png" alt="Sekolah">
                </div>
            </div>
        </div>
        
        <div class="login-right">
            <?php if ($show_login_form): ?>
                <!-- Login Form for Admin -->
                <div class="form-container">
                    <div class="login-header">
                        <h1 style="text-align: center;">LOGIN ADMIN</h1>
                        <p style="text-align: center;">Silakan login dengan akun admin Anda</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" placeholder="Masukkan Username" required>
                        </div>    
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="password-wrapper">
                                <input type="password" id="password" name="password" placeholder="Masukkan Password" required>

                                <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="Toggle password">
                                    <img id="eyeIcon" src="assets/icons/pw1.png" alt="Password Tersembunyi">
                                </button>
                            </div>
                        </div>

                        <script>
                        function togglePassword() {
                            const password = document.getElementById('password');
                            const eyeIcon = document.getElementById('eyeIcon');

                            if (!password || !eyeIcon) return;

                            if (password.type === 'password') {
                                password.type = 'text';
                                eyeIcon.src = 'assets/icons/pw2.png'; // mata terbuka
                                eyeIcon.alt = 'Password Terlihat';
                            } else {
                                password.type = 'password';
                                eyeIcon.src = 'assets/icons/pw1.png'; // mata tertutup
                                eyeIcon.alt = 'Password Tersembunyi';
                            }
                        }
                        </script>

                        <!-- <div class="form-group">
                            <label for="password">Password</label>
                            <div class="password-wrapper">
                                <input type="password" id="password" name="password" placeholder="Masukkan Password" required>

                                <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="Toggle password">
                                    <svg id="eyeIcon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" style="stroke: #666;"></path>
                                        <circle cx="12" cy="12" r="3" style="stroke: #666;"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div> -->
                        
                        <button type="submit" class="btn-login">LOGIN</button>
                    </form>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="login.php" style="color: #667eea; text-decoration: none; font-weight: 600;">← Kembali ke Pilihan</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Selection Buttons Admin/User -->
                <div class="selection-container">
                    <div class="selection-header">
                        <h2>Pilih Mode Login</h2>
                        <p>Silakan pilih mode login Anda untuk melanjutkan.</p>
                    </div>
                <div class="button-group">
                    <a href="login.php?mode=admin" class="btn-selection btn-admin">
                        <img src="assets/icons/admin.png" alt="Admin Icon" class="btn-icon">
                        <span>ADMIN</span>
                    </a>

                    <a href="login.php?mode=user" class="btn-selection btn-user">
                        <img src="assets/icons/user.png" alt="User Icon" class="btn-icon">
                        <span>USER</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </body>
    </html>
