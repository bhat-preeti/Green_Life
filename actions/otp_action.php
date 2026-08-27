<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $otp_entered = trim($_POST['otp']);
    $user_id = $_SESSION['otp_user_id'];

    if (empty($user_id)) {
        header("Location: ../index.php");
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM otp_codes 
                            WHERE user_id = ? 
                            AND CAST(otp AS CHAR) = CAST(? AS CHAR)
                            AND is_used = 0
                            ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id, $otp_entered]);
    $record = $stmt->fetch();

    if (!$record) {
        $_SESSION['otp_error'] = "Invalid OTP. Please try again.";
        header("Location: ../otp_verify.php");
        exit();
    }

    $expires_at = strtotime($record['expires_at']);
    $current_time = time();

    if ($current_time > $expires_at + 330) { // +330 = 5.5hrs for IST offset
        $_SESSION['otp_error'] = "OTP has expired. Please login again.";
        header("Location: ../index.php");
        exit();
    }

    $stmt = $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE id = ?");
    $stmt->execute([$record['id']]);

  
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

   
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    unset($_SESSION['otp_user_id']);

    header("Location: ../pages/home.php");
    exit();
}
?>