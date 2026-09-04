<?php
session_start();

// FIXED (Level 7, Tahap 6): dashboard.php previously had NO authentication
// check at all — it could be accessed directly by URL without ever logging
// in. This is a Broken Access Control gap distinct from VULN-01 (which was
// in the API layer) but same root cause category: missing authorization.
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - TechSecure Indonesia</title>
    <style>
        /* CSS untuk mempercantik tampilan */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f4f7f6; 
            margin: 0;
        }
        .navbar { 
            background: #16213e; 
            color: white; 
            padding: 20px; 
            text-align: center; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .container { 
            padding: 50px; 
            text-align: center; 
        }
        .upload-card { 
            background: white; 
            padding: 40px; 
            border-radius: 12px; 
            display: inline-block; 
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            width: 400px;
        }
        h3 { color: #16213e; margin-bottom: 25px; }
        .file-input { margin-bottom: 20px; }
        .btn-upload { 
            padding: 12px 30px; 
            background: #e94560; 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-weight: bold;
            transition: 0.3s;
        }
        .btn-upload:hover { background: #950740; }
        .msg { 
            padding: 10px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            font-size: 14px;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="navbar">
    <h1>TechSecure Admin Panel v1.0</h1>
    <p style="margin:0; font-size:14px;">Login sebagai: <?= htmlspecialchars($_SESSION['username'] ?? '') ?></p>
</div>
<div class="container">
    <div class="upload-card">
        <h3>Update Laporan Bulanan</h3>
        <?php
        // LOGIKA PHP UNTUK PROSES UPLOAD
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_FILES['file'])) {
                $target_dir = "uploads/";
                $file_name  = basename($_FILES["file"]["name"]); // Mengambil nama file asli
                $target_file = $target_dir . $file_name;
                // --- HARDENING: WHITELIST VALIDATION ---
                $allowed_ext = array("jpg", "jpeg", "png", "pdf", "docx");
                $file_ext    = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                if (!in_array($file_ext, $allowed_ext)) {
                    // Jika ekstensi tidak ada dalam daftar whitelist
                    echo "<div class='msg error'><strong>Gagal!</strong> Format file tidak diizinkan.</div>";
                } 
                elseif (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                    // Jika upload berhasil
                    echo "<div class='msg success'>
                            <strong>Berhasil!</strong> File telah diunggah ke: <br>
                            <code>" . htmlspecialchars($target_file) . "</code>
                          </div>";
                } else {
                    // Jika terjadi error pada sistem file
                    echo "<div class='msg error'>Gagal mengunggah file. Pastikan izin folder uploads sudah benar.</div>";
                }
            }
        }
        ?>
           <form method="POST" enctype="multipart/form-data">
                <div class="file-input">
                    <input type="file" name="file" id="file" required>
                </div>
                <input type="submit" value="UPLOAD KE SERVER" class="btn-upload">
            </form>
            <p style="margin-top: 20px; font-size: 12px; color: #888;">
		Format yang diizinkan: .pdf, .docx, .jpg
            </p>
        </div>
    </div>

    <div class="container">
        <div class="upload-card">
            <h3>TechSecure v2.0 — Fitur Baru</h3>
            <p><a href="/api/v1/users/profile.php?id=<?= (int)($_SESSION['user_id'] ?? 0) ?>">Lihat Profil Saya</a></p>
            <p><a href="/profile/avatar-from-url.php">Import Avatar dari URL</a></p>
            <p><a href="/api/v1/reports/download.php?id=<?= (int)($_SESSION['user_id'] ?? 0) + 1 ?>">Download Laporan Saya</a></p>
        </div>
    </div>
</body>
</html>
