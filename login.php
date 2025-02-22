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

$registration_success = false;
if (isset($_SESSION['registration_success'])) {
    $registration_success = true;
    unset($_SESSION['registration_success']);
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username_or_email = trim($_POST['username']);
    $password = trim($_POST['password']);
    $remember_me = isset($_POST['remember_me']);

    if (empty($username_or_email) || empty($password)) {
        $errors[] = "Username or email and password cannot be empty.";
    } else {
        // Kullanıcı doğrulama
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE BINARY username = ? OR BINARY email = ?");
        $stmt->bind_param("ss", $username_or_email, $username_or_email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            $errors[] = "The user could not be found.";
        } else {
            $stmt->bind_result($user_id, $db_username, $hashed_password);
            $stmt->fetch();

            // Şifre doğrulama
            if (password_verify($password, $hashed_password)) {
                // Giriş başarılı
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $db_username;

                if ($remember_me) {
                    setcookie("user_id", $user_id, time() + (86400 * 30), "/"); // 30 gün boyunca çerez ayarla
                    setcookie("username", $db_username, time() + (86400 * 30), "/"); // 30 gün boyunca çerez ayarla
                }

                header("Location: ./app");
                exit();
            } else {
                $errors[] = "Your information or password is incorrect. Please try again.";
            }
        }
        $stmt->close();
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
    <title>Miamo Note ~ Login</title>
    <link rel="stylesheet" href="./assets/css/login-register.css">
    <link rel="icon" href="./assets/image/logo.png" type="image/x-icon">

     <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6746070536404496"
     crossorigin="anonymous"></script>


     <meta name="description" content="Log in to access your securely stored personal notes.">
     <meta name="keywords" content="Note, Not Uygulaması, Çevrimiçi not al">
     <meta name="author" content="mymiamo.net">
     
     <!-- Open Graph Meta Tags (Facebook, LinkedIn, vb.) -->
     <meta property="og:title" content="Miamo Note | Login">
     <meta property="og:description" content="Log in to access your securely stored personal notes.">
     <meta property="og:image" content="https://miamonote.com/assets/image/logo.png">
     <meta property="og:url" content="https://miamonote.com">
     <meta property="og:type" content="website">
     
     <!-- Twitter Card Meta Tags -->
     <meta name="twitter:card" content="summary_large_image">
     <meta name="twitter:title" content="Miamo Note | Login">
     <meta name="twitter:description" content="Log in to access your securely stored personal notes.">
     <meta name="twitter:image" content="https://miamonote.com/assets/image/logo.png">
     <meta name="twitter:site" content="@miamo_26">
     
     <link rel="canonical" href="https://miamonote.com/login">

     <!-- Favicon -->
     <link rel="icon" href="./assets/image/logo.png" type="image/x-icon">
</head>
<body>
   <div class="form-container">
       <div class="form">
        <a class="goback" href="javascript:history.back()"><i class="bi bi-chevron-left"></i></a>
           <div class="logo">
            <img src="./assets/image/logo.png"  alt="">
           </div>
          

           <form method="POST" action="">
               <label for="username">Username or E-mail</label>
               <input type="text" id="username" name="username" required> 
               
               <label for="password">Password</label>
               <div class="password-container">
                   <input type="password" id="password" name="password" placeholder="••••••••" required> 
                   <span class="toggle-password" onclick="togglePasswordVisibility('password', this)">
                       <i class="bi bi-eye"></i>
                   </span>
               </div>
               
               <label for="remember_me">
                   <input type="checkbox"  id="remember_me" name="remember_me"> <span style="font-weight: 900;">Remember Me</span>
               </label>
                
               <div class="user">
                <a href="./register">Sign Up</a> 
                <a href="./user/reset-password">Forgot Password</a>
               </div>
               <button type="submit" name="login">Login</button>
               
               <?php if ($registration_success): ?>
                   <p class="success">Registration is successful! You can log in now.</p>
               <?php endif; ?>
               
               <?php if (!empty($errors)): ?>
               <ul class="eroor">
                   <?php foreach ($errors as $error): ?>
                       <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                       
                   <?php endforeach; ?>
               </ul>
               <?php endif; ?> 
           </form>
           
       </div>
   </div>
   <script>
    function togglePasswordVisibility(inputId, iconElement) {
        const passwordField = document.getElementById(inputId);
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);

        // Toggle eye icon (show/hide)
        const eyeIcon = iconElement.querySelector('i');
        eyeIcon.classList.toggle('bi-eye');
        eyeIcon.classList.toggle('bi-eye-slash');
    }

    document.addEventListener("DOMContentLoaded", function() {
        const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
        
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    this.style.borderColor = 'green';
                } else {
                    this.style.borderColor = '#ff0000';
                }
            });
        });
    });
</script>
</body>
</html>