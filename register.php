<?php
require 'config.php'; // Veritabanı bağlantısı
session_start();
// Eğer kullanıcı giriş yapmışsa not sayfasına yönlendir
if (isset($_SESSION['user_id']) || isset($_COOKIE['user_id'])) {
    if (isset($_COOKIE['user_id']) && !isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = $_COOKIE['user_id'];
        // Kullanıcı adını da çerezden çek
        $_SESSION['username'] = $_COOKIE['username'];
    }
    header("Location: ./app");
    exit();
}


$stage = isset($_POST['stage']) ? $_POST['stage'] : 1; // Geçerli aşama

$errors = [];
$success = false;

// İlk aşama: Kullanıcı bilgilerini al
if ($stage == 1 && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_step1'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // Kullanıcı adı, e-posta, şifre ve şifre doğrulama kontrolleri
    if (empty($username)) {
        $errors[] = "The user name cannot be empty.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid e-mail address.";
    }
    if (empty($password) || strlen($password) < 8) {
        $errors[] = "The password must be at least 8 characters long.";
    }
    if ($password !== $password_confirm) {
        $errors[] = "The passwords don't match.";
    }

    // Şifre güvenliği kontrolleri
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "The password must include at least one uppercase letter.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "The password must include at least one number.";
    }
    if (!preg_match('/[\W_]/', $password)) {
        $errors[] = "The password must include at least one special character.";
    }
    if (preg_match('/123|456|789/', $password)) {
        $errors[] = "The password cannot contain sequential numbers.";
    }

    // Kullanıcı adı ve e-posta kontrolü
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "This user name is already being used. Try another one.";
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "This email is already being used. If you are registered, you can log in.";
    }
    $stmt->close();

    if (empty($errors)) {
        // Geçici kullanıcı bilgilerini veritabanına ekleme
        $verification_code = bin2hex(random_bytes(4)); // 8 karakter uzunluğunda bir doğrulama kodu
        $expiration_time = date('Y-m-d H:i:s', strtotime('+1 minute')); // 1 dakika geçerli

        $stmt = $conn->prepare("INSERT INTO temp_users (username, email, password, verification_code, expiration_time) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, password_hash($password, PASSWORD_BCRYPT), $verification_code, $expiration_time);
        if ($stmt->execute()) {
            // Doğrulama kodunu e-posta ile gönderme
            $to = $email;
            $subject = "Your Verification Code";
            $message = "Your verification code is: $verification_code\nIt is valid for 1 minute.";
            $headers = "From: no-reply@mymiamo.net";

            mail($to, $subject, $message, $headers);

            $_SESSION['temp_user_id'] = $conn->insert_id; // Geçici kullanıcı ID'sini sakla
            $stage = 2; // İkinci aşamaya geç
        } else {
            $errors[] = "An error occurred during registration. Please try again.";
        }
        $stmt->close();
    }
}

// İkinci aşama: Doğrulama kodunu kontrol et
if ($stage == 2 && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_code'])) {
    $verification_code = $_POST['verification_code'];

    // Geçici kullanıcı bilgilerini al
    $stmt = $conn->prepare("SELECT username, email, password FROM temp_users WHERE id = ? AND verification_code = ? AND expiration_time > NOW()");
    $stmt->bind_param("is", $_SESSION['temp_user_id'], $verification_code);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows == 1) {
        $stmt->bind_result($username, $email, $password);
        $stmt->fetch();
        
        // Kalıcı kullanıcıyı veritabanına ekle
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password);
        if ($stmt->execute()) {
            // Geçici kullanıcıyı veritabanından sil
            $stmt = $conn->prepare("DELETE FROM temp_users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['temp_user_id']);
            $stmt->execute();
            
            // Başarılı kayıt durumunda e-posta gönderimi
            $to = $email;
            $subject = "Registration Successful";
            $message = "Your registration has been successfully completed. You can now log in.";
            $headers = "From: no-reply@mymiamo.net";
            mail($to, $subject, $message, $headers);

            $_SESSION['registration_success'] = true;
            header("Location: login");
            exit();
        } else {
            $errors[] = "An error occurred while completing your registration. Please try again.";
        }
        $stmt->close();
    } else {
        $errors[] = "Invalid or expired verification code.";
    }
}
?>


<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <title>My Miamo Note ~ Sign Up</title>
    <link rel="stylesheet" href="./assets/css/login-register.css">
    <link rel="icon" href="./assets/image/logo.png" type="image/x-icon">
     <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6746070536404496"
     crossorigin="anonymous"></script>

     <meta name="description" content="Discover a secure and free way to take notes. Your notes are protected with Bcrypt encryption, keeping them safe. Sign up today!">
     <meta name="keywords" content="Note, Not Uygulaması, Çevrimiçi not al">
     <meta name="author" content="mymiamo.net">
     
     <!-- Open Graph Meta Tags (Facebook, LinkedIn, vb.) -->
     <meta property="og:title" content="Miamo Note | Sign Up">
     <meta property="og:description" content="Discover a secure and free way to take notes. Your notes are protected with Bcrypt encryption, keeping them safe. Sign up today!">
     <meta property="og:image" content="https://miamonote.com/assets/image/logo.png">
     <meta property="og:url" content="https://miamonote.com">
     <meta property="og:type" content="website">
     
     <!-- Twitter Card Meta Tags -->
     <meta name="twitter:card" content="summary_large_image">
     <meta name="twitter:title" content="Miamo Note | Sign Up">
     <meta name="twitter:description" content="Discover a secure and free way to take notes. Your notes are protected with Bcrypt encryption, keeping them safe. Sign up today!">
     <meta name="twitter:image" content="https://miamonote.com/assets/image/logo.png">
     <meta name="twitter:site" content="@miamo_26">
     
     <link rel="canonical" href="https://miamonote.com/register">

     <!-- Favicon -->
     <link rel="icon" href="./assets/image/logo.png" type="image/x-icon">
</head>
<body>
    <div class="form-container">
        <div class="form">
            <?php if ($stage == 1): ?>
            <a class="goback" href="javascript:history.back()"><i class="bi bi-chevron-left"></i></a>
            <div class="logo">
                <img src="./assets/image/logo.png"  alt="">
               </div>

                <form method="POST" action="">
                    <input type="hidden" name="stage" value="1">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required><br><br>

                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" required><br><br>

                    <label for="password">Password:</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" required>
                        <span class="toggle-password" onclick="togglePasswordVisibility('password', this)">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div><br><br>

                    <label for="password_confirm">Verify The Password:</label>
                    <div class="password-container">
                        <input type="password" id="password_confirm" name="password_confirm" required>
                        <span class="toggle-password" onclick="togglePasswordVisibility('password_confirm', this)">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div><br><br>

                    <label for="Privacy-Polic">
                        <input type="checkbox" name="Privacy-Polic" required><a href="https://miamonote.com/user/privacy-policy" target="_blank">I have read and accept the Privacy Policy</a>
                    </label>
                    
                    <label for="data-usage">
                        <input type="checkbox" name="data-usage" required><a href="https://miamonote.com/user/user-agreement" target="_blank">I have read and accept the user agreement.</a>
                    </label>
                    <div class="user">
                        <a href="./login">Sign In</a> 
                        <a href="./user/reset-password">Forgot Password</a>
                       </div>
                    <button type="submit" name="register_step1">Continue</button>

                    <?php if (!empty($errors)): ?>
                    <ul class="eroor"">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?> </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    
                </form>
            <?php elseif ($stage == 2): ?>
            <div class="logo">
                <img src="./assets/image/logo.png"  alt="">
               </div>
                 
                <form method="POST" action="">
                    <input type="hidden" name="stage" value="2">
                    <label for="verification_code">Verification Code:</label>
                    <input type="text" id="verification_code" name="verification_code" required><br><br>
                    
                    <button type="submit" name="verify_code">Verify Code</button>

                    <?php if (!empty($errors)): ?>
                    <ul class="eroor"">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>


</body>
</html>
