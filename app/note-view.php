<?php
require '../config.php';
session_start();

// Check for user authentication
if (!isset($_SESSION['user_id']) && !isset($_COOKIE['user_id'])) {
    header("Location: ../login");
    exit();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_COOKIE['user_id'];

// Check if note_id is set and not empty
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Output HTML when note_id is not set
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="robots" content="noindex">
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>The Note Was Not Found ~ My Notes</title>
    <link rel="stylesheet" href="./assets/css/view.css">
    
 <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6746070536404496"
 crossorigin="anonymous"></script>
</head>
<body>
    <div class="topbar">
        <div class="content">
            <div class="logo">
                <a href="javascript:history.back()" class="goto">  <i class="bi bi-chevron-left"></i> </a>
               
            </div>
        
          </div>
        </div>
    <div class="container">
        <div class="title">
        <h1>The Note Was Not Found</h1>
    </div>
    
    <div class="body">
        Your Note has been Deleted and There is No Such Note
    </div>
    
    </div>
     
</body>
</html>
    <?php
    exit();
}

$note_id = $_GET['id'];

function decrypt($data, $key) {
    $cipher = "AES-256-CBC";
    $data = base64_decode($data);
    $ivLength = openssl_cipher_iv_length($cipher);
    $iv = substr($data, 0, $ivLength);
    $raw = substr($data, $ivLength);
    return openssl_decrypt($raw, $cipher, $key, OPENSSL_RAW_DATA, $iv);
}

$encryption_key = 'key';

$stmt = $conn->prepare("SELECT user_id, title, content FROM notes WHERE note_id = ?");
$stmt->bind_param("s", $note_id);
$stmt->execute();
$stmt->bind_result($note_user_id, $encrypted_title, $encrypted_content);
$stmt->fetch();
$stmt->close();

// Redirect if note is not found
if (!$note_user_id || !$encrypted_title || !$encrypted_content) {
    // Output HTML when the note is not found
    ?>
    <!DOCTYPE html>
<html lang="en">
<head>
<meta name="robots" content="noindex">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>The Note Was Not Found ~ My Notes</title>
    <link rel="stylesheet" href="./assets/css/view.css">

 <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6746070536404496"
 crossorigin="anonymous"></script>
</head>
<body>
    <div class="topbar">
        <div class="content">
            <div class="logo">
                <a href="javascript:history.back()" class="goto">  <i class="bi bi-chevron-left"></i> </a>
               
            </div>
        
          </div>
        </div>
   <div class="container">
    <div class="title">
        <h1>The Note Was Not Found</h1>
    </div>
    
    <div class="body">
        Your Note has been Deleted and There is No Such Note
    </div>
   </div>
    
     
</body>
</html>
    <?php
    exit();
}

// Check if the note belongs to the logged-in user
if ($user_id != $note_user_id) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta name="robots" content="noindex">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
        <title>This Note Doesn't Belong to You ~ My Notes</title>
        <link rel="stylesheet" href="./assets/css/view.css">
    
     <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6746070536404496"
     crossorigin="anonymous"></script>
</head>
    <body>
    <div class="topbar">
        <div class="content">
            <div class="logo">
                <a href="javascript:history.back()" class="goto">  <i class="bi bi-chevron-left"></i> </a>
               
            </div>
        
          </div>
        </div>
   <div class="container">
        <div class="title">
            <h1>This Note Doesn't Belong to You ~ My Notes</h1>
        </div>
        
        <div class="body">
            Accessing someone else's note without permission is not allowed.
        </div>
        
       

</div>
    </body>
    </html>
    <?php
exit();
}

require '../config.php';
require './assets/module/erusev/parsedown/Parsedown.php'; // Parsedown kütüphanesi
require './assets/module/autoload.php'; 
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

$note_id = $_GET['id']; // Notun ID'sini alıyoruz

// Notun içeriğini deşifre ediyoruz
$decrypted_title = decrypt($encrypted_title, $encryption_key);
$decrypted_content = decrypt($encrypted_content, $encryption_key);

// Satır sonlarını <br> ile değiştirme işlemini kaldırıyoruz
$newContent = $decrypted_content;

// Markdown'u HTML'e dönüştürmek için Parsedown kullanıyoruz
$parsedown = new Parsedown();
$parsedown->setBreaksEnabled(true); // Satır sonlarını otomatik <br> ile dönüştürme
$htmlContent = $parsedown->text($newContent);

// Form gönderimi işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_content = $_POST['content'];
    $log = $_POST['log'];

    $encrypted_content = encrypt($new_content, $encryption_key);

    $stmt = $conn->prepare("UPDATE notes SET content = ?, log_time = ? WHERE note_id = ? AND user_id = ?");
    $stmt->bind_param("ssss", $encrypted_content, $log, $note_id, $_SESSION['user_id']);

    if ($stmt->execute()) {
        // Değişiklikleri yansıtmak için sayfayı yeniden yüklüyoruz
        header("Location: note-view?id=" . urlencode($note_id));
        exit();
    } else {
        echo "Not güncellenirken hata oluştu.";
    }
    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="robots" content="noindex">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($decrypted_title); ?> ~ My Notes</title>
    <link rel="stylesheet" href="./assets/css/view.css">
    <link rel="shortcut icon" href="./assets/image/logo.png" type="image/x-icon">
    <script>
let scrollPosition = 0;

function confirmDeletion(noteId) {
    // Mevcut kaydırma pozisyonunu kaydet
    scrollPosition = window.pageYOffset;

    // Uyarıyı göster
    var alertElement = document.querySelector('.alert');
    if (alertElement) {
        alertElement.style.display = 'flex';
    } else {
        console.error('Uyarı elementi bulunamadı');
        return;
    }

    // Kaydırmayı devre dışı bırak ve gövdenin hareketini engelle
    document.body.style.position = 'fixed';
    document.body.style.top = `-${scrollPosition}px`;
    document.body.style.width = '100%';

    // Buton tıklamalarını işle
    var acceptButton = document.querySelector('.alert .accept');
    var cancelButton = document.querySelector('.alert .cancel');
    
    if (acceptButton) {
        acceptButton.onclick = function() {
            // Silme URL'sine yönlendir
            window.location.href = 'delete-note.php?id=' + encodeURIComponent(noteId);
        };
    } else {
        console.error('Kabul butonu bulunamadı');
    }
    
    if (cancelButton) {
        cancelButton.onclick = function() {
            // Uyarıyı gizle
            if (alertElement) {
                alertElement.style.display = 'none';
            }

            // Kaydırmayı geri yükle
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            window.scrollTo(0, scrollPosition);
        };
    } else {
        console.error('İptal butonu bulunamadı');
    }
}

// Sayfa yüklendiğinde uyarıyı gizle ve kaydırmayı etkinleştir
document.addEventListener('DOMContentLoaded', function() {
    var alertElement = document.querySelector('.alert');
    if (alertElement) {
        alertElement.style.display = 'none';
    }
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.width = '';
});



</script>
    <script>
    function toggleEdit() {
        var contentDiv = document.getElementById('note-content');
        var editForm = document.getElementById('edit-form');
        if (contentDiv.style.display === 'none') {
            contentDiv.style.display = 'block';
            editForm.style.display = 'none';
        } else {
            contentDiv.style.display = 'none';
            editForm.style.display = 'block';
        }
    }
    </script>

     <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6746070536404496"
     crossorigin="anonymous"></script>
</head>
<body>
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

    <div class="topbar">
        <div class="content">
            <div class="logo">
            <a href="javascript:void(0)" class="goto" onclick="customBackNavigation()">
    <i class="bi bi-chevron-left"></i>
</a>

               
            </div>
            <div class="notification">
                <a class="edit-btn" onclick="toggleEdit()"><i class="bi bi-pencil-square"></i></a>
                <a onclick="confirmDeletion('<?php echo htmlspecialchars($note_id, ENT_QUOTES, 'UTF-8'); ?>'); return false;" class="del-btn">
    <i class="bi bi-trash3"></i>
</a>



            </div>
          </div>
        </div>

        <div class="container">
        <div class="title">
            <h1><?php echo htmlspecialchars($decrypted_title); ?></h1>
        </div>

        <!-- Notun HTML olarak görüntülenmesi -->
        <div id="note-content" class="body ">
            <?php echo $htmlContent; ?>
        </div>

        <!-- Düzenleme formu -->
        <div id="edit-form" style="display: none;">
            <form action="note-view.php?id=<?php echo urlencode($note_id); ?>" method="POST">
                <textarea name="content" maxlength="9000" required rows="20" cols="80" required><?php echo htmlspecialchars($decrypted_content); ?></textarea>
                <input type="hidden" name="log" id="datetime-input" readonly>
                <br>
                <input type="submit" value="Save Changes">
                <button type="button" onclick="toggleEdit()">Cancel</button>
            </form>
        </div>
    </div>
   <script>
let scrollPosition = 0;

function confirmDeletion(noteId) {
    // Mevcut kaydırma pozisyonunu kaydet
    scrollPosition = window.pageYOffset;

    // Uyarıyı göster
    var alertElement = document.querySelector('.alert');
    if (alertElement) {
        alertElement.style.display = 'flex';
    } else {
        console.error('Uyarı elementi bulunamadı');
        return;
    }

    // Kaydırmayı devre dışı bırak ve gövdenin hareketini engelle
    document.body.style.position = 'fixed';
    document.body.style.top = `-${scrollPosition}px`;
    document.body.style.width = '100%';

    // Buton tıklamalarını işle
    var acceptButton = document.querySelector('.alert .accept');
    var cancelButton = document.querySelector('.alert .cancel');
    
    if (acceptButton) {
        acceptButton.onclick = function() {
            // Burada URL'yi PHP'den gelen note_id ile değiştirin
            window.location.href = 'delete-note?id=' + encodeURIComponent(noteId);
        };
    } else {
        console.error('Kabul butonu bulunamadı');
    }
    
    if (cancelButton) {
        cancelButton.onclick = function() {
            // Uyarıyı gizle
            if (alertElement) {
                alertElement.style.display = 'none';
            }

            // Kaydırmayı geri yükle
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            window.scrollTo(0, scrollPosition);
        };
    } else {
        console.error('İptal butonu bulunamadı');
    }
}




</script>

<script>
        // Tarih ve saati almak için Date nesnesi oluşturulur
        const now = new Date();

        // Tarihi almak için gerekli metodlar
        const day = String(now.getDate()).padStart(2, '0');
        const month = String(now.getMonth() + 1).padStart(2, '0'); // Aylar 0'dan başlar, bu yüzden +1 ekliyoruz
        const year = now.getFullYear();

        // Saat, dakika ve saniyeyi almak için gerekli metodlar
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');

        // Tarih ve saati düzgün bir şekilde formatlamak
        const formattedDate = `${day}.${month}.${year}`;
        const formattedTime = `${hours}:${minutes}`;

        // Tarih ve saat bilgisini birleştir
        const formattedDateTime = `${formattedDate} ~ ${formattedTime} <span class="edit"><i class="bi bi-pencil-fill"></i> [Edited]</span>`;

        // Elde edilen tarih ve saat bilgilerini input elemanına yazdırmak
        document.getElementById('datetime-input').value = formattedDateTime;
</script>

<script>
    function customBackNavigation() {
        // Önceki sayfa URL'sini kontrol et
        const previousPage = document.referrer; // Önceki sayfanın URL'sini alır
        const currentPage = window.location.href; // Mevcut sayfanın URL'sini alır

        // Önceki sayfa mevcut sayfaysa, belirli bir sayfaya yönlendir
        if (previousPage === currentPage || previousPage === '') {
            // Aynı sayfa ise, belirlenen sayfaya yönlendir
            window.location.href = "/app/note"; // Bu URL'yi değiştirebilirsiniz
        } else {
            // Farklı sayfa ise, geri dön
            history.back();
        }
    }
</script>
</body>
</html>
