<?php
require '../config.php';
session_start();

if (isset($_GET['id'])) {
    $note_id = $_GET['id'];
    $user_id = $_SESSION['user_id']; // Kullanıcının oturumdaki user_id'sini alın

    // Veritabanından notu silme işlemi
    $stmt = $conn->prepare("DELETE FROM notes WHERE note_id = ? AND user_id = ?");
    $stmt->bind_param("ss", $note_id, $user_id);
    
    if ($stmt->execute()) {
        // Başarılı silme işlemi sonrası yönlendirme
        header("Location: ./note"); // Tüm notların listelendiği bir sayfaya yönlendirin
        exit();
    } else {
        echo "Not silinirken bir hata oluştu.";
    }

    $stmt->close();
} else {
    echo "Not ID'si alınamadı.";
}
?>
