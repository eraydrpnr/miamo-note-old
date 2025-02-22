<?php
require '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

function encrypt($data, $key) {
    $cipher = "AES-256-CBC";
    $ivLength = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivLength);
    $encrypted = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

function decrypt($data, $key) {
    $cipher = "AES-256-CBC";
    $data = base64_decode($data);
    $ivLength = openssl_cipher_iv_length($cipher);
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    return openssl_decrypt($encrypted, $cipher, $key, OPENSSL_RAW_DATA, $iv);
}

$errors = [];
$title = '';
$content = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $title = trim($_POST["title"]);
  $content = trim($_POST["content"]);
  $log_time = $_POST['log'];  // Tarih ve saat bilgisini formdan al

  // Başlık ve içerik uzunluklarını kontrol et
  if (strlen($title) > 255 ) {
      $errors[] = "Başlık 255 karakterden ve içerik 9000 karakterden uzun olamaz.";
  } else {
      // Şifrelenmiş başlıkları kontrol et
      $stmt = $conn->prepare("SELECT title FROM notes WHERE user_id = ?");
      $stmt->bind_param("i", $_SESSION['user_id']);
      $stmt->execute();
      $stmt->bind_result($encrypted_title);
      
      $is_title_exists = false;
      $encryption_key = 'key';
      while ($stmt->fetch()) {
          $existing_title = decrypt($encrypted_title, $encryption_key);
          if ($existing_title === $title) {
              $is_title_exists = true;
              break;
          }
      }
      $stmt->close();

      if ($is_title_exists) {
          $errors[] = "Bu başlıkla bir not zaten var.";
      } else {
          $note_id = bin2hex(random_bytes(4));
          $encrypted_title = encrypt($title, $encryption_key);
          $encrypted_content = encrypt($content, $encryption_key);

          // Veritabanına log_time bilgisiyle birlikte ekleme
          $stmt = $conn->prepare("INSERT INTO notes (note_id, user_id, title, content, log_time) VALUES (?, ?, ?, ?, ?)");
          $stmt->bind_param("sisss", $note_id, $_SESSION['user_id'], $encrypted_title, $encrypted_content, $log_time);
          $stmt->execute();
          $stmt->close();

          header("Location: ../app/");
          exit();
      }
  }
}



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create a New Note ~ My Miamo Store</title>
    <link rel="stylesheet" href="./assets/css/app.css">
    <link rel="shortcut icon" href="./assets/image/logo.png" type="image/x-icon">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6746070536404496" crossorigin="anonymous"></script>
    <!-- Seo -->
    <meta name="description" content="As Miamo Note, we store your notes securely. Thanks to encryption, only you can access it and it is not shared with anyone. Your privacy is our top priority. Start taking your personal notes now.">
    <meta name="keywords" content="Note, Not Uygulaması, Çevrimiçi not al">
    <meta name="author" content="mymiamo.net">
    <!-- Open Graph Meta Tags (Facebook, LinkedIn, vb.) -->
    <meta property="og:title" content="Miamo Note | Create a New Note ">
    <meta property="og:description" content="As Miamo Note, we store your notes securely. Thanks to encryption, only you can access it and it is not shared with anyone. Your privacy is our top priority. Start taking your personal notes now.">
    <meta property="og:image" content="https://miamonote.com/assets/image/logo.png">
    <meta property="og:url" content="https://miamonote.com">
    <meta property="og:type" content="website">
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Miamo Note | Create a New Note ">
    <meta name="twitter:description" content="As Miamo Note, we store your notes securely. Thanks to encryption, only you can access it and it is not shared with anyone. Your privacy is our top priority. Start taking your personal notes now.">
    <meta name="twitter:image" content="https://miamonote.com/assets/image/logo.png">
    <meta name="twitter:site" content="@miamo_26">
    <!-- Favicon -->
    <link rel="icon" href="../assets/image/logo.png" type="image/x-icon">

    <link rel="canonical" href="https://miamonote.com/app/note-add">
</head>
<body>
    
<?php require 'menu.html'; ?>

<div class="container">
    <h1>Create a New Note</h1>
    <p>As Miamo Note, we store your notes securely. Thanks to encryption, only you can access it and it is not shared with anyone. Your privacy is our top priority. Start taking your personal notes now.</p>

    <div class="note-container">
        <div class="input-body">
            <form method="POST" action="">
                <label for="title">Note Title</label>
                <input type="text" placeholder="A great day to take notes" id="title" name="title" maxlength="255" value="<?php echo htmlspecialchars($title); ?>" required onkeyup="countCharacters()">
                
                <input type="hidden" name="log" id="datetime-input" readonly>
                <label for="content">Note</label>
                <textarea id="content" placeholder="Start taking Encrypted and Secure notes." name="content" maxlength="9000" onkeyup="countCharacters()" required><?php echo htmlspecialchars($content); ?></textarea>
                 
               
                <?php if (!empty($errors)): ?>
                    <ul style="list-style: none;">
                        <?php foreach ($errors as $error): ?>
                            <li><i class="bi bi-exclamation-diamond-fill"></i><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul><br>
                <?php endif; ?>
                <button type="submit" name="add_note">Save Note</button>
            </form>
        </div>
    </div>
</div>




<?php require './footer.html'; ?>

</body>
</html>
