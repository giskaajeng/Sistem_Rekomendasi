<?php
require_once 'config.php';

// Get ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: hasil_input.php');
    exit;
}

// Fetch existing data
$row = fetch_row("SELECT s.*, k.nama_kecamatan FROM sekolah s JOIN kecamatan k ON s.id_kecamatan = k.id_kecamatan WHERE s.id_sekolah = $id");
if (!$row) {
    header('Location: hasil_input.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kecamatan = isset($_POST['id_kecamatan']) ? (int)$_POST['id_kecamatan'] : 0;
    $nama_desa = escape(trim($_POST['nama_desa'] ?? ''));
    $alamat = escape(trim($_POST['alamat'] ?? ''));
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $nama_sekolah = escape(trim($_POST['nama_sekolah'] ?? ''));
    $tingkat_pendidikan = escape(trim($_POST['tingkat_pendidikan'] ?? ''));

    if (!$id_kecamatan) $errors[] = 'Pilih Kecamatan';
    if ($nama_sekolah === '') $errors[] = 'Nama sekolah wajib diisi';
    if ($nama_desa === '') $errors[] = 'Nama desa wajib diisi';
    if ($alamat === '') $errors[] = 'Alamat wajib diisi';
    if ($latitude === '' || !is_numeric($latitude)) $errors[] = 'Latitude harus berupa angka';
    if ($longitude === '' || !is_numeric($longitude)) $errors[] = 'Longitude harus berupa angka';

    if (empty($errors)) {
        // Prepare values
        $latitude = (float)$latitude;
        $longitude = (float)$longitude;

        $sql = "UPDATE sekolah SET 
            id_kecamatan = $id_kecamatan,
            nama_desa = '$nama_desa',
            alamat = '$alamat',
            latitude = $latitude,
            longitude = $longitude,
            nama_sekolah = '$nama_sekolah',
            tingkat_pendidikan = '$tingkat_pendidikan'
            WHERE id_sekolah = $id";

        query($sql);
        header('Location: hasil_input.php?msg=updated');
        exit;
    }
}

// Get kecamatan list
$kecamatans = fetch_all('SELECT * FROM kecamatan');

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sekolah - Sistem Rekomendasi</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f8; padding: 30px; }
        .card { max-width: 760px; margin: 0 auto; background: #fff; padding: 24px; border-radius: 10px; box-shadow: 0 8px 30px rgba(0,0,0,0.06); }
        label { display:block; margin-top:12px; font-weight:600; }
        input[type="text"], select, textarea { width:100%; padding:10px; border:1px solid #dcdcdc; border-radius:6px; margin-top:6px; }
        .actions { margin-top:18px; display:flex; gap:10px; }
        .btn { padding:10px 14px; border-radius:8px; border:none; cursor:pointer; }
        .btn-primary { background:#1088ff; color:#fff; }
        .btn-secondary { background:#eee; }
        .errors { background:#ffe6e6; color:#7a0b0b; padding:10px; border-radius:6px; margin-bottom:10px; }
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
            width: 18px;
            height: 18px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Edit Data Sekolah</h2>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="edit_sekolah.php?id=<?php echo $id; ?>">
            <label for="id_kecamatan">Kecamatan</label>
            <select name="id_kecamatan" id="id_kecamatan">
                <option value="">-- Pilih Kecamatan --</option>
                <?php foreach ($kecamatans as $k): ?>
                    <option value="<?php echo $k['id_kecamatan']; ?>" <?php echo ($k['id_kecamatan'] == $row['id_kecamatan']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($k['nama_kecamatan']); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="nama_desa">Nama Desa</label>
            <input type="text" name="nama_desa" id="nama_desa" value="<?php echo htmlspecialchars($row['nama_desa']); ?>">

            <label for="alamat">Alamat</label>
            <textarea name="alamat" id="alamat" rows="3"><?php echo htmlspecialchars($row['alamat']); ?></textarea>

            <label for="latitude">Latitude</label>
            <input type="text" name="latitude" id="latitude" value="<?php echo htmlspecialchars($row['latitude']); ?>">

            <label for="longitude">Longitude</label>
            <input type="text" name="longitude" id="longitude" value="<?php echo htmlspecialchars($row['longitude']); ?>">

            <label for="nama_sekolah">Nama Sekolah</label>
            <input type="text" name="nama_sekolah" id="nama_sekolah" value="<?php echo htmlspecialchars($row['nama_sekolah']); ?>">

            <label for="tingkat_pendidikan">Tingkat Pendidikan</label>
            <input type="text" name="tingkat_pendidikan" id="tingkat_pendidikan" value="<?php echo htmlspecialchars($row['tingkat_pendidikan']); ?>">

            <div class="actions">
                <button type="submit" class="btn btn-primary btn-icon-text">
                    <img src="../assets/icons/simpan.png" alt="Simpan" class="icon">
                    <span>Simpan Perubahan</span>
                </button>

                <a href="hasil_input.php" class="btn btn-secondary">
                    Batal
                </a>
            </div>
        </form>
    </div>
</body>
</html>
