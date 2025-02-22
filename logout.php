<?php
session_start();

// Oturumu sonlandır
session_unset();
session_destroy();

// Çerezleri temizle (varsayılı olarak kullanıcı kimlik çerezini temizler)
if (isset($_COOKIE['user_id'])) {
    setcookie('user_id', '', time() - 3600, '/'); // Çerezi 1 saat önce geçersiz kıl
}

// Ana sayfaya veya giriş sayfasına yönlendir
header("Location: ./index");
exit();
?>
