<?php
/**
 * FILE TEST HAVERSINE
 * 
 * Gunakan file ini untuk testing formula Haversine dengan data contoh
 * Sebelum menggunakan di production, pastikan semua test case berhasil
 */

// Haversine Function untuk testing
function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371; // Radius bumi dalam km
    
    $lat1_rad = deg2rad($lat1);
    $lon1_rad = deg2rad($lon1);
    $lat2_rad = deg2rad($lat2);
    $lon2_rad = deg2rad($lon2);
    
    $dlat = $lat2_rad - $lat1_rad;
    $dlon = $lon2_rad - $lon1_rad;
    
    $a = sin($dlat / 2) * sin($dlat / 2) + 
         cos($lat1_rad) * cos($lat2_rad) * 
         sin($dlon / 2) * sin($dlon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $R * $c;
    
    return round($distance, 2);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Haversine Formula</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            line-height: 1.6;
        }
        
        .test-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .test-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }
        
        .test-case {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
            border-radius: 6px;
        }
        
        .test-case h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .coords {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .coord-box {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }
        
        .coord-box label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .coord-value {
            color: #667eea;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            padding: 8px;
            background: #f0f4ff;
            border-radius: 4px;
        }
        
        .result {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #4caf50;
        }
        
        .result-label {
            color: #2e7d32;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .result-value {
            color: #1b5e20;
            font-size: 24px;
            font-weight: 700;
            font-family: 'Courier New', monospace;
        }
        
        .formula {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.8;
            overflow-x: auto;
        }
        
        .note {
            background: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            border-radius: 6px;
            margin: 20px 0;
        }
        
        .note strong {
            color: #856404;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🧪 Testing Haversine Formula</h1>
            <p>
                Halaman ini digunakan untuk testing dan validasi formula Haversine sebelum digunakan 
                dalam production. Semua test case berhasil berarti implementation sudah benar.
            </p>
        </div>
        
        <!-- Test 1: Contoh Data Nyata -->
        <div class="test-section">
            <h2>Test 1: Contoh Data Nyata (Indonesia)</h2>
            
            <?php
            // Data contoh: Koordinat Lombok
            $test1_ref_lat = -8.2234;
            $test1_ref_lon = 116.5432;
            $test1_school_lat = -8.2500;
            $test1_school_lon = 116.5500;
            $test1_distance = haversineDistance($test1_ref_lat, $test1_ref_lon, $test1_school_lat, $test1_school_lon);
            ?>
            
            <div class="test-case">
                <h3>Scenario: Jarak Kantor Kepala Desa ke Sekolah Terdekat</h3>
                
                <div class="coords">
                    <div class="coord-box">
                        <label>📍 Referensi (Kantor Kepala Desa):</label>
                        <div class="coord-value">
                            Lat: <?php echo $test1_ref_lat; ?><br>
                            Lon: <?php echo $test1_ref_lon; ?>
                        </div>
                        <small style="color: #666; margin-top: 8px; display: block;">
                            Lokasi: Lombok, Indonesia
                        </small>
                    </div>
                    
                    <div class="coord-box">
                        <label>🏫 Target (Sekolah):</label>
                        <div class="coord-value">
                            Lat: <?php echo $test1_school_lat; ?><br>
                            Lon: <?php echo $test1_school_lon; ?>
                        </div>
                        <small style="color: #666; margin-top: 8px; display: block;">
                            Lokasi: Lombok, Indonesia
                        </small>
                    </div>
                </div>
                
                <div class="result">
                    <div class="result-label">📏 Hasil Perhitungan Jarak:</div>
                    <div class="result-value"><?php echo $test1_distance; ?> km</div>
                </div>
            </div>
        </div>
        
        <!-- Test 2: Jarak Pendek -->
        <div class="test-section">
            <h2>Test 2: Jarak Pendek (< 1 km)</h2>
            
            <?php
            // Dua titik yang sangat dekat
            $test2_ref_lat = -8.2234;
            $test2_ref_lon = 116.5432;
            $test2_school_lat = -8.2234;
            $test2_school_lon = 116.5438; // Selisih kecil
            $test2_distance = haversineDistance($test2_ref_lat, $test2_ref_lon, $test2_school_lat, $test2_school_lon);
            ?>
            
            <div class="test-case">
                <h3>Scenario: Sekolah di Lokasi yang Sama/Sangat Dekat</h3>
                
                <div class="coords">
                    <div class="coord-box">
                        <label>📍 Referensi:</label>
                        <div class="coord-value">
                            Lat: <?php echo $test2_ref_lat; ?><br>
                            Lon: <?php echo $test2_ref_lon; ?>
                        </div>
                    </div>
                    
                    <div class="coord-box">
                        <label>🏫 Target:</label>
                        <div class="coord-value">
                            Lat: <?php echo $test2_school_lat; ?><br>
                            Lon: <?php echo $test2_school_lon; ?>
                        </div>
                    </div>
                </div>
                
                <div class="result">
                    <div class="result-label">📏 Hasil Perhitungan Jarak:</div>
                    <div class="result-value"><?php echo $test2_distance; ?> km</div>
                </div>
                
                <div class="note">
                    <strong>✓ Expected:</strong> Nilai harus sangat kecil (< 1 km) karena lokasi sangat dekat
                </div>
            </div>
        </div>
        
        <!-- Test 3: Jarak Jauh -->
        <div class="test-section">
            <h2>Test 3: Jarak Jauh (> 10 km)</h2>
            
            <?php
            // Dua titik yang jauh
            $test3_ref_lat = -8.2234;
            $test3_ref_lon = 116.5432;
            $test3_school_lat = -8.5000;
            $test3_school_lon = 116.8000;
            $test3_distance = haversineDistance($test3_ref_lat, $test3_ref_lon, $test3_school_lat, $test3_school_lon);
            ?>
            
            <div class="test-case">
                <h3>Scenario: Sekolah di Lokasi yang Jauh</h3>
                
                <div class="coords">
                    <div class="coord-box">
                        <label>📍 Referensi:</label>
                        <div class="coord-value">
                            Lat: <?php echo $test3_ref_lat; ?><br>
                            Lon: <?php echo $test3_ref_lon; ?>
                        </div>
                    </div>
                    
                    <div class="coord-box">
                        <label>🏫 Target:</label>
                        <div class="coord-value">
                            Lat: <?php echo $test3_school_lat; ?><br>
                            Lon: <?php echo $test3_school_lon; ?>
                        </div>
                    </div>
                </div>
                
                <div class="result">
                    <div class="result-label">📏 Hasil Perhitungan Jarak:</div>
                    <div class="result-value"><?php echo $test3_distance; ?> km</div>
                </div>
                
                <div class="note">
                    <strong>✓ Expected:</strong> Nilai harus besar (> 10 km) karena lokasi berjauhan
                </div>
            </div>
        </div>
        
        <!-- Test 4: Multiple Schools -->
        <div class="test-section">
            <h2>Test 4: Multiple Schools Sorting</h2>
            <p style="margin-bottom: 20px; color: #666;">
                Test ini menunjukkan bagaimana sistem mengurutkan sekolah berdasarkan jarak terdekat
            </p>
            
            <?php
            // Multiple schools
            $ref_lat = -8.2234;
            $ref_lon = 116.5432;
            
            $schools = [
                ['nama' => 'SD Negeri 1', 'lat' => -8.2300, 'lon' => 116.5450],
                ['nama' => 'SMP Negeri 1', 'lat' => -8.2400, 'lon' => 116.5500],
                ['nama' => 'SMA Negeri 1', 'lat' => -8.2250, 'lon' => 116.5400],
                ['nama' => 'SD Negeri 2', 'lat' => -8.2500, 'lon' => 116.5600],
                ['nama' => 'SMP Negeri 2', 'lat' => -8.2100, 'lon' => 116.5300],
            ];
            
            // Calculate distances
            foreach ($schools as &$school) {
                $school['distance'] = haversineDistance($ref_lat, $ref_lon, $school['lat'], $school['lon']);
            }
            
            // Sort by distance
            usort($schools, function($a, $b) {
                return $a['distance'] <=> $b['distance'];
            });
            ?>
            
            <div class="test-case">
                <h3>Input Data: 5 Sekolah</h3>
                
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Sekolah</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Jarak (km)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schools as $index => $school): ?>
                        <tr>
                            <td><strong><?php echo ($index + 1); ?></strong></td>
                            <td><?php echo $school['nama']; ?></td>
                            <td><?php echo $school['lat']; ?></td>
                            <td><?php echo $school['lon']; ?></td>
                            <td>
                                <strong><?php echo $school['distance']; ?> km</strong>
                            </td>
                            <td>
                                <?php if ($index === 0): ?>
                                    <span class="badge badge-success">✓ Terdekat</span>
                                <?php else: ?>
                                    <span class="badge badge-info"><?php echo ($index); ?> km lebih jauh</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="note" style="margin-top: 20px;">
                    <strong>✓ Expected Result:</strong><br>
                    <span style="display: block; margin-top: 8px;">
                        1. Tabel menunjukkan urutan dari jarak terdekat ke terjauh<br>
                        2. Sekolah dengan jarak terkecil ada di urutan pertama<br>
                        3. Semua nilai jarak positif dan masuk akal
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Formula Reference -->
        <div class="test-section">
            <h2>📐 Referensi Formula Haversine</h2>
            
            <div class="formula">
Δlat = lat₂ - lat₁<br>
Δlon = lon₂ - lon₁<br><br>

a = sin²(Δlat/2) + cos(lat₁) × cos(lat₂) × sin²(Δlon/2)<br><br>

c = 2 × atan2(√a, √(1-a))<br><br>

d = R × c<br><br>

Dimana:<br>
- R = 6371 km (radius bumi)<br>
- d = jarak dalam kilometer<br>
- lat, lon dalam radian
            </div>
        </div>
        
        <!-- Checklist -->
        <div class="test-section">
            <h2>✅ Checklist Implementasi</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h3 style="margin-bottom: 15px; color: #333;">Database</h3>
                    <ul style="list-style: none; line-height: 2;">
                        <li>☐ Tabel kecamatan memiliki kolom latitude & longitude</li>
                        <li>☐ Tabel sekolah memiliki kolom latitude & longitude</li>
                        <li>☐ Koordinat kantor kepala desa sudah diisi</li>
                        <li>☐ Koordinat sekolah sudah diisi</li>
                        <li>☐ Index pada latitude & longitude sudah dibuat</li>
                    </ul>
                </div>
                
                <div>
                    <h3 style="margin-bottom: 15px; color: #333;">Code</h3>
                    <ul style="list-style: none; line-height: 2;">
                        <li>☐ Function haversineDistance sudah ada</li>
                        <li>☐ File rekomendasi_user.php sudah upload</li>
                        <li>☐ File get_desa.php sudah upload</li>
                        <li>☐ config.php sudah konfigurasi</li>
                        <li>☐ Semua test case berhasil</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="test-section">
            <h3 style="color: #667eea; margin-bottom: 15px;">ℹ️ Informasi Penting</h3>
            <ul style="list-style: none; line-height: 2; color: #666;">
                <li><strong>Format Koordinat:</strong> Decimal Degrees (contoh: -8.2234, 116.5432)</li>
                <li><strong>Negative Latitude:</strong> Belahan bumi selatan (Indonesia)</li>
                <li><strong>Positive Longitude:</strong> Belahan bumi timur (Indonesia)</li>
                <li><strong>Accuracy:</strong> Akurat untuk jarak hingga ribuan kilometer</li>
                <li><strong>Performance:</strong> O(n) complexity, cocok untuk dataset besar</li>
            </ul>
        </div>
    </div>
</body>
</html>
