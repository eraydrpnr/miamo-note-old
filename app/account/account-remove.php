<?php
require '../../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';
$verification_code = '';
$verification_code_sent_at = null;
$code_verified = false;
$stage = 1; // Varsayılan olarak ilk aşama

// Şifre doğrulama ve doğrulama kodu işlemleri
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['check_password'])) {
        $old_password = $_POST['old_password'];

        // Mevcut şifreyi veritabanından çek
        $stmt = $conn->prepare("SELECT password, email FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($current_password, $email);
        $stmt->fetch();
        $stmt->close();

        // Eski şifreyi doğrula
        if (password_verify($old_password, $current_password)) {
            // Şifre doğrulandı, doğrulama kodu gönder
            $verification_code = rand(100000, 999999); // 6 haneli rastgele kod
            $verification_code_sent_at = time();
            $_SESSION['verification_code'] = $verification_code;
            $_SESSION['verification_code_sent_at'] = $verification_code_sent_at;
            
            // E-posta bildirimi gönder
            $to = $email;
            $subject = "Verification Code for Account Deletion";
            $message = "Dear user,\n\nYour verification code for account deletion is $verification_code. It will expire in 1 minute.\n\nBest regards,\nMyMiamo Team";
            $headers = "From: no-reply@mymiamo.net";
            mail($to, $subject, $message, $headers);

            $success_message = "A verification code has been sent to your email.";
            $stage = 2; // Geçiş yap
        } else {
            $error_message = "The old password you entered is incorrect.";
        }
    }

    if (isset($_POST['verify_code'])) {
        $entered_code = $_POST['verification_code'];
        $code_sent_at = isset($_SESSION['verification_code_sent_at']) ? $_SESSION['verification_code_sent_at'] : 0;

        if ($entered_code == $_SESSION['verification_code'] && (time() - $code_sent_at) <= 60) {
            // Kod doğru ve süresi dolmamış
            $code_verified = true;
            unset($_SESSION['verification_code']); // Kodun tekrar kullanılmasını engelle
            unset($_SESSION['verification_code_sent_at']);
            
            // Kullanıcı ve notları sil
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("DELETE FROM notes WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();

                $conn->commit();

                // Oturumu sonlandır
                session_destroy();

                // Kullanıcıyı giriş sayfasına yönlendir
                header("Location: ../index");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "An error occurred while deleting your account. Please try again.";
            }
        } else {
            $error_message = "The verification code is incorrect or has expired.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Account Deletion</title>
    <link rel="stylesheet" href="../assets/css/app.css">
   <link rel="shortcut icon" href="../assets/image/logo.png" type="image/x-icon">

    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6746070536404496"
     crossorigin="anonymous"></script>

     <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6746070536404496"
     crossorigin="anonymous"></script>
</head>
<body>
    <?php require '../menu.html'; ?>
  
    <div class="container">
        <div class="account-container">
            <div class="account-card">
                <div class="footer">
                    <img src="https://miamonote.com/assets/image/logo.png" alt="">
                </div>
                <h1>Account Deletion</h1>
                <p> There is something you need to know about the account deletion process!<br>
                    Account deletion is permanently deleted immediately after verifying your email address 
                    and cannot be restored. By this time, the notes you have written and your account will 
                    be deleted. Take these into consideration.</p>
                
                <?php if ($stage == 1): ?>
                    <form method="POST">
                        <label for="old_password">Password</label>
                        <input type="password" name="old_password" id="old_password" required><br>
                        <label for="data">
                            <input type="checkbox" name="data" required>I have read the above information, understood it, and I confirm that my account will be deleted permanently.
                        </label>
                        <input type="submit" name="check_password" class="button" value="Continue">
                    </form>

                    <?php if (!empty($success_message) && !isset($_SESSION['verification_code'])): ?>
                        <p class="success"><?php echo htmlspecialchars($success_message); ?></p>
                    <?php elseif (!empty($error_message)): ?>
                        <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
                    <?php endif; ?>
                <?php elseif ($stage == 2): ?>
                    <form method="POST">
                        <label for="verification_code">Verification Code</label>
                        <input type="text" name="verification_code" id="verification_code" required><br>
                        <input type="submit" name="verify_code" class="button" value="Verify Code">
                    </form>
                    
                    <?php if (!empty($success_message)): ?>
                        <p class="success"><?php echo htmlspecialchars($success_message); ?></p>
                    <?php elseif (!empty($error_message)): ?>
                        <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php require '../footer.html'; ?>
</body>
</html>
