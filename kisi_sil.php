<?php

include 'config.php';

// Giriş yapılmamışsa login'e yönlendir
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: login.php");
    exit();
}

$aktif_kullanici_id = (int) $_SESSION['kullanici_id'];
$aktif_rol = $_SESSION['rol'];
$mesaj = "";

if (isset($_GET['id']) && is_numeric($_GET['id'])) {

    $kisi_id = (int) $_GET['id'];

    // Kişinin silinmeye yetkili olup olmadığını kontrol et
    if ($aktif_rol === 'Admin') {

        $stmt = $conn->prepare(
            "SELECT kisi_id FROM kisiler WHERE kisi_id = ?"
        );
        $stmt->bind_param("i", $kisi_id);

    } else {

        $stmt = $conn->prepare(
            "SELECT kisi_id FROM kisiler 
             WHERE kisi_id = ? AND kullanici_id = ?"
        );
        $stmt->bind_param("ii", $kisi_id, $aktif_kullanici_id);
    }

    $stmt->execute();
    $sonuc = $stmt->get_result();

    if ($sonuc->num_rows === 0) {

        $mesaj = "Hata: Bu kişiyi silme yetkiniz yok veya kayıt bulunamadı.";

    } else {

        // İletişim bilgilerini sil
        $stmt_iletisim = $conn->prepare(
            "DELETE FROM iletisim_bilgileri WHERE kisi_id = ?"
        );
        $stmt_iletisim->bind_param("i", $kisi_id);
        $stmt_iletisim->execute();
        $stmt_iletisim->close();

        // Kişiyi sil
        $stmt_kisi = $conn->prepare(
            "DELETE FROM kisiler WHERE kisi_id = ?"
        );
        $stmt_kisi->bind_param("i", $kisi_id);

        if ($stmt_kisi->execute()) {
            $mesaj = "Kişi başarıyla silindi.";
        } else {
            $mesaj = "Hata: Kişi silinirken bir veritabanı hatası oluştu.";
        }

        $stmt_kisi->close();
    }

    $stmt->close();

} else {

    $mesaj = "Hata: Geçersiz kişi ID'si.";
}

$conn->close();

header("Location: kisiler_liste.php?durum=" . urlencode($mesaj));
exit();

?>