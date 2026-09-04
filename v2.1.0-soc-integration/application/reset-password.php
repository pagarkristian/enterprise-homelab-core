<?php
require_once 'db.php';

$step = isset($_GET['token']) ? 'reset' : 'request';
$message = "";

// STEP 1: Request reset — generates a cryptographically secure random token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'request') {
    $username = $_POST['username'];

    // FIXED (Tahap 6 - Hardening, VULN-02): use a cryptographically secure
    // random token instead of a predictable timestamp-based one.
    $token = bin2hex(random_bytes(16));
    $expiry = time() + 900; // valid for 15 minutes

    $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE username = ?");
    $stmt->bind_param("sis", $token, $expiry, $username);
    $stmt->execute();

    // FIXED (Tahap 6 - Hardening, VULN-06): the token is no longer shown to
    // the requester. In a real deployment this would be emailed to the
    // address on file for the account. Here we simulate that by writing it
    // to the server log instead of returning it in the HTTP response —
    // demonstrating that the requester and the account owner are no longer
    // assumed to be the same person.
    if ($stmt->affected_rows > 0) {
        error_log("[PASSWORD RESET] Token for user '$username': $token (would be emailed in production)");
    }

    // Always show the identical generic message regardless of whether the
    // account exists, to avoid leaking which usernames are valid
    // (username enumeration).
    $message = "If an account with that username exists, a password reset link has been sent to the registered email address.";
}

// STEP 2: Actually reset the password using the token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'reset') {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $now = time();

    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > ?");
    $stmt->bind_param("si", $token, $now);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
        $update->bind_param("si", $new_password, $row['id']);
        $update->execute();
        $message = "Password successfully reset. You may now log in.";
    } else {
        $message = "Invalid or expired token.";
    }
}
?>
<h2>Reset Password</h2>
<?php if ($message): ?>
    <p><?= $message ?></p>
<?php endif; ?>

<?php if ($step === 'request'): ?>
    <form method="POST">
        Username: <input type="text" name="username">
        <input type="submit" value="Request Reset">
    </form>
<?php else: ?>
    <form method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token']) ?>">
        New Password: <input type="password" name="new_password">
        <input type="submit" value="Reset Password">
    </form>
<?php endif; ?>
