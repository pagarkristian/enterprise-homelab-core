<?php
session_start();
require_once 'db.php';

if ($_POST) {
    $user = $_POST['user'];
    $pass = $_POST['pass'];

    // Hardened query (kept from v1.1.0)
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $user, $pass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // NOTE: session now stores logged-in user id — required for v2.0 API auth checks
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<p style='color: #ff4d4d; text-align: center;'>Login Failed!</p>";
    }
}
?>
<form method="POST">
    <h2>PT TechSecure Admin Login</h2>
    User: <input type="text" name="user"><br>
    Pass: <input type="password" name="pass"><br>
    <input type="submit" value="Login">
</form>
<p><a href="reset-password.php">Forgot password?</a></p>
