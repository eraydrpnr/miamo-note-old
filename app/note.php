<?php
require '../config.php';
require './assets/module/erusev/parsedown/Parsedown.php'; // Parsedown kütüphanesi

session_start();

if (!isset($_SESSION['user_id']) && !isset($_COOKIE['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_COOKIE['user_id'];
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Kullanıcı';

// Toplam not sayısını al
$stmt = $conn->prepare("SELECT COUNT(*) FROM notes");
$stmt->execute();
$stmt->bind_result($total_notes);
$stmt->fetch();
$stmt->close();

// Giriş yapan kullanıcının not sayısını al
$stmt = $conn->prepare("SELECT COUNT(*) FROM notes WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_notes);
$stmt->fetch();
$stmt->close();

// Toplam kullanıcı sayısını al
$stmt = $conn->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$stmt->bind_result($total_users);
$stmt->fetch();
$stmt->close();

// Şifre çözme fonksiyonu
function decrypt($data, $key) {
    $cipher = "AES-256-CBC";
    $data = base64_decode($data);
    $ivLength = openssl_cipher_iv_length($cipher);
    $iv = substr($data, 0, $ivLength);
    $raw = substr($data, $ivLength);
    $decrypted = openssl_decrypt($raw, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    return $decrypted;
}

$encryption_key = 'key';
$notes = [];
$parsedown = new Parsedown(); // Parsedown örneği oluşturuluyor

// Notları al ve işleme
$stmt = $conn->prepare("SELECT note_id, title, content, log_time FROM notes WHERE user_id = ? ORDER BY log_time DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($note_id, $encrypted_title, $encrypted_content, $log_time);

while ($stmt->fetch()) {
    $title = decrypt($encrypted_title, $encryption_key);
    $content = decrypt($encrypted_content, $encryption_key);

    // Karakter sınırı ve "..." ekleme
    $preview_limit = 225;
    $preview = mb_substr($content, 0, $preview_limit);
    if (mb_strlen($content) > $preview_limit) {
        $preview .= '...';
    }

    // HTML özel karakterlerini escape edip Markdown olarak işliyoruz
    $safe_preview = htmlspecialchars($preview, ENT_QUOTES, 'UTF-8');
    $markdown_preview = $parsedown->text($safe_preview); // Markdown'u HTML'e çevir

    $formatted_date = $log_time ? $log_time : '<p class="error"> <i class="bi bi-bug"></i> </p>';
    $notes[] = [
        'note_id' => $note_id,
        'title' => $title,
        'preview' => $markdown_preview, // İşlenmiş markdown
        'log_time' => $formatted_date
    ];
}

// Arama
$search_term = isset($_GET['search']) ? strtolower('%' . $_GET['search'] . '%') : '';

if ($search_term) {
    // Arama işlemi yapılırsa
    $stmt = $conn->prepare("SELECT note_id, title, content, log_time FROM notes WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($note_id, $encrypted_title, $encrypted_content, $log_time);
    
    $notes = [];

    while ($stmt->fetch()) {
        $title = decrypt($encrypted_title, $encryption_key);
        $content = decrypt($encrypted_content, $encryption_key);
        
        // Arama terimi şifre çözülen verilere uygulanıyor
        if (strpos(strtolower($title), strtolower($_GET['search'])) !== false || 
            strpos(strtolower($content), strtolower($_GET['search'])) !== false) {
            
            $preview = mb_substr($content, 0, 225);
            if (mb_strlen($content) > 225) {
                $preview .= '...';
            }

            $safe_preview = htmlspecialchars($preview, ENT_QUOTES, 'UTF-8');
            $markdown_preview = $parsedown->text($safe_preview); // Markdown'u HTML'e çevir
            
            $formatted_date = $log_time ? $log_time : '<p class="error"> <i class="bi bi-bug"></i> </p>';
            $notes[] = [
                'note_id' => $note_id,
                'title' => $title,
                'preview' => $markdown_preview,
                'log_time' => $formatted_date
            ];
        }
    }
} else {
    // Arama yapılmıyorsa tüm notlar gösteriliyor
    $stmt = $conn->prepare("SELECT note_id, title, content, log_time FROM notes WHERE user_id = ? ORDER BY log_time DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($note_id, $encrypted_title, $encrypted_content, $log_time);

    $notes = [];

    while ($stmt->fetch()) {
        $title = decrypt($encrypted_title, $encryption_key);
        $content = decrypt($encrypted_content, $encryption_key);

        // Karakter sınırı ve "..." ekleme
        $preview = mb_substr($content, 0, 225);
        if (mb_strlen($content) > 225) {
            $preview .= '...';
        }

        $safe_preview = htmlspecialchars($preview, ENT_QUOTES, 'UTF-8');
        $markdown_preview = $parsedown->text($safe_preview); // Markdown'u HTML'e çevir
    
        $formatted_date = $log_time ? $log_time : '<p class="error"> <i class="bi bi-bug"></i> </p>';
        $notes[] = [
            'note_id' => $note_id,
            'title' => $title,
            'preview' => $markdown_preview,
            'log_time' => $formatted_date
        ];
    }
}

$stmt->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miamo Note</title>
     <link rel="stylesheet" href="./assets/css/app.css">
    <link rel="stylesheet" href="./assets/css/mobile.css">
    <link rel="shortcut icon" href="./assets/image/logo.png" type="image/x-icon">
    <link flex href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <!-- Seo -->
    <meta name="description" content="Your personal notes that we keep safely for you.">
    <meta name="keywords" content="Note, Not Uygulaması, Çevrimiçi not al">
    <meta name="author" content="mymiamo.net">
    <!-- Open Graph Meta Tags (Facebook, LinkedIn, vb.) -->
    <meta property="og:title" content="Miamo Note | Notes">
    <meta property="og:description" content="Your personal notes that we keep safely for you.">
    <meta property="og:image" content="https://miamonote.com/assets/image/logo.png">
    <meta property="og:url" content="https://miamonote.com">
    <meta property="og:type" content="website">
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Miamo Note | Notes">
    <meta name="twitter:description" content="Your personal notes that we keep safely for you.">
    <meta name="twitter:image" content="https://miamonote.com/assets/image/logo.png">
    <meta name="twitter:site" content="@miamo_26">

    <link rel="canonical" href="https://miamonote.com/app/note">
    <!-- Favicon -->
    <link rel="icon" href="../assets/image/logo.png" type="image/x-icon">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6746070536404496"
     crossorigin="anonymous"></script>
</head>
<body>

<?php require 'menu.html'; ?>

<div class="alert">
    <div class="alert-content">
        <h1 lang="en">Permanent Deletion</h1>
        <p lang="en">Your note will be Permanently deleted.<br> This process may not be irreversible!</p>
        <div class="buttons">
            <button class="accept" lang="en">Accept</button>
            <div class="border-alert"></div>
            <button class="cancel">Cancel</button>
        </div>
    </div>
</div>

<a href="./note-add" class="a-welcome">
        <div class="welcome">
            <h1 lang="en">Hey <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?> 👋, </h1>
            <p lang="en">Take notes securely, encrypted and freely now</p>
        </div>
       </a>

</div>
<div class="statusInfo-container">

    <div class="outer">
        <div class="dot"></div>
        <div class="card">
          <div class="ray"></div>
          <div class="text"><?php echo $total_notes; ?></div>
          <div>Notes to Date</div>
         
          <div class="line topl"></div>
          <div class="line leftl"></div>
          <div class="line bottoml"></div>
          <div class="line rightl"></div>
        </div>
      </div>

      <div class="outer">
        <div class="dot"></div>
        <div class="card">
          <div class="ray"></div>
          <div class="text"> <?php echo $user_notes; ?> </div>
          <div>Your Notes</div>
         
          <div class="line topl"></div>
          <div class="line leftl"></div>
          <div class="line bottoml"></div>
          <div class="line rightl"></div>
        </div>
      </div>

      <div class="outer">
        <div class="dot"></div>
        <div class="card">
          <div class="ray"></div>
          <div class="text">1<?php echo $total_users; ?></div>
          <div>Registered Members</div>
         
          <div class="line topl"></div>
          <div class="line leftl"></div>
          <div class="line bottoml"></div>
          <div class="line rightl"></div>
        </div>
      </div>
      

<!-- <div class="statusCard">
    <div class="statusIcon">
        <i class="bi bi-journal-bookmark-fill"></i>
    </div>
    <div class="statusInfo">
        <span>Notes to Date</span>
        <p><?php echo $total_notes; ?> </p>
    </div>
</div>

<div class="statusCard">
    <div class="statusIcon">
        <i class="bi bi-sticky-fill"></i>
    </div>
    <div class="statusInfo">
        <span>Your Notes</span>
        <p><?php echo $user_notes; ?> </p>
    </div>
</div>

<div class="statusCard">
    <div class="statusIcon">
        <i class="bi bi-person-heart"></i>
    </div>
    <div class="statusInfo">
        <span>Registered Members</span>
        <p><?php echo $total_users; ?> </p>
    </div>
</div> -->

</div>

<div class="container">
    <h1>Your Notes</h1>

    

    <form action="" method="get" class="search-form">
     <input autocomplete="off" type="text" name="search" placeholder="Search your notes" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
     <button type="submit" data-title="Search"><i class="bi bi-search"></i></button>
    </form>

    <div class="card-container">
    <?php if (empty($notes) && !$search_term): ?>
        <div class="noteCard">
            <div class="noteHeader">
                <h1>The note was not found</h1>
                <span><i class="bi bi-clock"></i> Timeless</span>
            </div>
            <div class="noteBody">
                <p class="preview">Create your private note now</p>
            </div>
            <div class="noteFooter">
                <div class="noteUser">
                    <i class="bi bi-person-circle"></i> Miamo Note
                </div>
                <div class="noteButton">
                    <a class="view" href="note-add"><i class="bi bi-plus-circle"></i> Create a New Note</a>
                    
                </div>
            </div>
        </div>
        <?php elseif(empty($notes) && $search_term): ?>
            <div class="noteCard">
            <div class="noteHeader">
                <h1>No notes found for "<?php echo htmlspecialchars($_GET['search']); ?>"</h1>
                <span><i class="bi bi-clock"></i> Timeless</span>
            </div>
            <div class="noteBody">
                <p class="preview">Try searching with different keywords or create your Decalogue</p>
            </div>
            <div class="noteFooter">
                <div class="noteUser">
                    <i class="bi bi-person-circle"></i> Miamo Note
                </div>
                <div class="noteButton">
                    <a class="view" href="note-add"><i class="bi bi-plus-circle"></i> Create a New Note</a>
                    <a class="view" href="note"><i class="bi bi-plus-circle"></i> Go Back </a>
                </div>
            </div>
        </div>
        <?php else: ?>
            <?php foreach ($notes as $note): ?>

             
            <div class="noteCard">
                <div class="noteHeader">
                    <h1><?php echo htmlspecialchars($note['title']); ?></h1>
                    <span ><i class="bi bi-clock" ></i>  <?php echo $note['log_time']; ?></span>
                </div>
                <div class="noteBody">
                    <p class="preview"><?php echo $note['preview']; // İşlenmiş markdown'u göster ?></p>
                </div>
                <div class="noteFooter">
                    <div class="noteUser" >
                    <i class="bi bi-lock-fill" data-title="Your Note was Encrypted Successfully"></i> <i data-title="Encrypted for you" class="bi bi-person-circle"></i> Encrypted for <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div class="noteButton">
                        <a class="del" data-title="Delete Permanently" onclick="confirmDeletion('<?php echo $note['note_id']; ?>'); return false;"><i class="bi bi-trash3"></i> Delete</a>
                        <a class="view" data-title="View Your Private Note" href="note-view?id=<?php echo $note['note_id']; ?>"><i class="bi bi-eye"></i> View</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
    </div>
</div>


<?php require './footer.html'; ?>

</body>
</html>
