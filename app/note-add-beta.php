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
    <link rel="stylesheet" href="./assets/css/mobile.css">
    <link rel="shortcut icon" href="./assets/image/logo.png" type="image/x-icon">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6746070536404496" crossorigin="anonymous"></script>
    <meta name="description" content="As Miamo Note, we store your notes securely. Thanks to encryption, only you can access it and it is not shared with anyone. Your privacy is our top priority. Start taking your personal notes now.">
    <meta name="keywords" content="Note, Not Uygulaması, Çevrimiçi not al">
    <meta name="author" content="mymiamo.net">
    <meta property="og:title" content="Miamo Note | Create a New Note ">
    <meta property="og:description" content="As Miamo Note, we store your notes securely. Thanks to encryption, only you can access it and it is not shared with anyone. Your privacy is our top priority. Start taking your personal notes now.">
    <meta property="og:image" content="https://miamonote.com/assets/image/logo.png">
    <meta property="og:url" content="https://miamonote.com">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Miamo Note | Create a New Note ">
    <meta name="twitter:description" content="As Miamo Note, we store your notes securely. Thanks to encryption, only you can access it and it is not shared with anyone. Your privacy is our top priority. Start taking your personal notes now.">
    <meta name="twitter:image" content="https://miamonote.com/assets/image/logo.png">
    <meta name="twitter:site" content="@miamo_26">
    <link rel="icon" href="../assets/image/logo.png" type="image/x-icon">
    <link rel="canonical" href="https://miamonote.com/app/note-add">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/theme/dracula.min.css">
</head>
<body>
    
<?php require 'menu.html'; ?>

<div class="container">
    <h1>Create a New Note</h1>
    <p>As Miamo Note, we store your notes securely. Thanks to encryption, only you can access it and it is not shared with anyone. Your privacy is our top priority. Start taking your personal notes now.</p>
    <p> <a href="#help" id="formattingBtn" class="help" data-title="Formatting Support"><i class="bi bi-question-circle"></i> Help <span style="color:#da0026; font-style:bolder;">BETA</span></a> </p>
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

<div class="container">
  <h2>The Formatting Wizard</h2>
  <div id="help">
    <button type="button" class="collapsible"><h2>Headings</h2></button>
    <div class="content">
      <p>There are 6 heading levels. Usage:<br>
      #Heading : <h1>This is how the heading will appear</h1><br>
      ##Heading : <h2>This is how the heading will appear</h2><br>
      ###Heading : <h3>This is how the heading will appear</h3><br>
      ####Heading : <h4>This is how the heading will appear</h4><br>
      #####Heading : <h5>This is how the heading will appear</h5><br>
      ######Heading : <h6>This is how the heading will appear</h6><br>
      </p>
    </div>
    
    <button type="button" class="collapsible"><h2>Emphasis</h2></button>
    <div class="content">
      <p>There are 3 types of emphasis available. Usage:<br>
      **Bold Text** or __bold text__ : <strong>bold text</strong><br>
      *Italic Text* or _italic text_ : <em>italic text</em><br>
      **_Bold and Italic_** : <strong><em>bold and italic</em></strong>
      </p>
    </div>
    
    <button type="button" class="collapsible"><h2>Lists and Sub-Lists (Bullets)</h2></button>
    <div class="content">
      <p>Usage for Lists and Sub-Lists (Bullets):<br>
      - Item 1<br>
      - Item 2<br>
      &nbsp;- Sub-Item<br>
      &nbsp;* Sub-Item<br>
      To create sub-items, you need to leave space and then write the bullet type.
      </p>
    </div>
    
    <button type="button" class="collapsible"><h2>Links</h2></button>
    <div class="content">
      <p>Usage of Links:<br>
      Directly pasting a link will make it clickable, but here are customization options:<br>
      [Link](https://miamonote.com) : Link (clickable)<br>
      [Titled Link](https://miamonote.com "Miamo Note") : Titled Link (clickable)
      </p>
    </div>
    
    <button type="button" class="collapsible"><h2>Inserting Images</h2></button>
    <div class="content">
      <p>You currently cannot upload images to your notes, but you can use images hosted elsewhere.<br>
      ![Alt Text](https://miamonote.com/assets/image/logo.png)
      </p>
    </div>
    
    <button type="button" class="collapsible"><h2>Code</h2></button>
    <div class="content">
      <p>Usage for code:<br>
      Inline Code : `Code`<br>
      Code Block:<br>
      ```<br>
      Code<br>
      ```<br>
      </p>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/markdown/markdown.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/theme/dracula.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var textarea = document.getElementById("content");
    if (textarea) {
        var editor = CodeMirror.fromTextArea(textarea, {
            mode: "markdown",
            theme: "dracula",
            lineNumbers: true,
            lineWrapping: true
        });

        editor.on("change", function() {
            textarea.value = editor.getValue();
        });
    } else {
        console.error("Textarea with id 'content' not found.");
    }
});


</script>
</body>
</html>
