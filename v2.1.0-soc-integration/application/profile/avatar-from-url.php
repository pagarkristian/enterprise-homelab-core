<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die("Unauthorized. Please log in.");
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = $_POST['avatar_url'];

    // FIXED (Tahap 6 - Hardening, VULN-04 SSRF): validate the URL before
    // fetching anything.
    // 1. Only allow http/https schemes (blocks file://, gopher://, etc.)
    // 2. Resolve the hostname and reject private/reserved/loopback IP
    //    ranges (blocks SSRF to internal network resources and localhost)
    $parsed = parse_url($url);
    $scheme = $parsed['scheme'] ?? '';
    $host = $parsed['host'] ?? '';
    $blocked = false;

    if (!in_array($scheme, ['http', 'https']) || empty($host)) {
        $message = "Invalid URL: only http/https URLs are allowed.";
        $blocked = true;
    } else {
        $ip = gethostbyname($host);
        $is_safe_ip = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($is_safe_ip === false) {
            $message = "URL tidak diizinkan: alamat internal/privat tidak boleh diakses.";
            $blocked = true;
        }
    }

    // Only attempt the actual fetch if validation passed — this keeps the
    // validation-failure message intact instead of letting a later
    // "could not fetch" message overwrite it.
    if (!$blocked) {
        $image_data = @file_get_contents($url);

        if ($image_data !== false) {
            $save_path = "../uploads/avatar_" . $_SESSION['user_id'] . ".dat";
            file_put_contents($save_path, $image_data);
            $message = "Avatar import attempted. Response length: " . strlen($image_data) . " bytes. "
                      . "First 200 bytes shown below:<br><pre>" . htmlspecialchars(substr($image_data, 0, 200)) . "</pre>";
        } else {
            $message = "Could not fetch the given URL.";
        }
    }
}
?>
<h2>Import Avatar from URL</h2>
<form method="POST">
    Image URL: <input type="text" name="avatar_url" size="50" placeholder="https://example.com/photo.jpg">
    <input type="submit" value="Import">
</form>
<?php if ($message): ?>
    <div><?= $message ?></div>
<?php endif; ?>
