<?php
// Veritabanı bilgileri
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "database name";

// Veritabanı bağlantısı oluştur
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Oturumu başlat
session_start();

// Giriş yapmış kullanıcı bilgilerini al
$logged_in_username = null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} elseif (isset($_COOKIE['user_id'])) {
    // Çerezlerde kullanıcı ID'si varsa, oturum değişkenlerini güncelle
    $user_id = $_COOKIE['user_id'];
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $_COOKIE['username'];
} else {
    $user_id = null;
}

if ($user_id !== null) {
    $query = "SELECT username FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($username);
    $stmt->fetch();
    $logged_in_username = $username;
    $stmt->close();
}

// $logged_in_username için varsayılan değer
if ($logged_in_username === null) {
    $logged_in_username = 'Guest';
}

// Çerez ayarlarını kontrol et
if (!isset($_COOKIE['cookieAccepted'])) {
    // Çerez kabul edilmemişse banner'ı göster
    $showBanner = true;
} else {
    // Çerez kabul edilmişse banner'ı gösterme
    $showBanner = false;
}

// Çerez kabul edildiğinde PHP'de ayarla
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acceptCookie'])) {
    setcookie('cookieAccepted', 'true', time() + (30 * 24 * 60 * 60), '/'); // 30 gün geçerli olacak
    header('Location: ' . $_SERVER['PHP_SELF']); // Sayfayı yenile
    exit; // Yönlendirmeden sonra scripti durdur
}
?>
