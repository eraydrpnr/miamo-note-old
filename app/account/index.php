<?php
require '/home/mymiamo/miamonote.com/config.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];

    // Şifre uzunluğunu kontrol et
    if (strlen($new_password) < 8) {
        $error_message = "The new password must be at least 8 characters long.";
    } else {
        // Mevcut şifreyi veritabanından çek
        $stmt = $conn->prepare("SELECT password, email FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($current_password, $email);
        $stmt->fetch();
        $stmt->close();

        // Eski şifreyi doğrula
        if (password_verify($old_password, $current_password)) {
            // Şifreyi güncelle
            $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_new_password, $user_id);

            if ($stmt->execute()) {
                $success_message = "Your password has been updated successfully.";

                // E-posta bildirimi gönder
                $to = $email;
                $subject = "Password Change Notification";
                $message = "Dear user,\n\nYour password has been changed successfully. If you did not make this change, please contact support immediately.\n\nBest regards,\nMyMiamo Team";
                $headers = "From: no-reply@mymiamo.net";
                mail($to, $subject, $message, $headers);
            } else {
                $error_message = "An error occurred while updating your password. Please try again.";
            }
            $stmt->close();
        } else {
            $error_message = "The old password you entered is incorrect.";
        }
    }
}

// Kullanıcı bilgilerini çek
$stmt = $conn->prepare("SELECT id, username, email, last_activity, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($id, $username, $email, $last_activity, $created_at);
$stmt->fetch();
$stmt->close();




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Account Information</title>
    <link rel="stylesheet" href="../assets/css/app.css">
   <link rel="shortcut icon" href="../assets/image/logo.png" type="image/x-icon">

    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6746070536404496"
     crossorigin="anonymous"></script>
   
    

     <meta name="robots" content="noindex">
</head>
<body>
    <?php require '../menu.html'; ?>
  
    <div class="container">
    <div class="account-container">
    <div class="account-card">
        <div class="footer">
            <img src="https://miamonote.com/assets/image/logo.png" draggable="false" alt="Logo">
        </div>
        <h1>Account Information</h1>
        
        <p><i class="bi bi-person-circle"></i> Username: <span><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></span></p>
        <p><i class="bi bi-envelope"></i> E-mail: <span><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></span></p>
       

        <h1><i class="bi bi-key-fill"></i> Change Password</h1>
        <form method="POST" autocomplete="off">
            <label for="old_password">Old Password</label>
            <input type="password" name="old_password" id="old_password" required><br>
            <label for="new_password">New Password</label>
            <input type="password" name="new_password" id="new_password" required><br>
            <input type="submit" name="update_password" class="button" value="Save Account">
            <a href="./account-remove">I want to delete my account permanently</a>
        </form>
        <?php if (!empty($error_message)): ?>
            <p class="notification error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <p class="notification success"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>
    </div>
</div>

    </div>
    <?php require '../footer.html'; ?>
</body>
</html>
