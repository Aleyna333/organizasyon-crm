<?php
// 1. Session ve Veritabanı Bağlantısı
include 'config.php';

// Güvenlik Kontrolü: Yalnızca Admin'in erişimine izin ver
// Eğer giriş yapılmamışsa veya rolü Admin değilse, Ana Sayfa'ya yönlendir.
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$hata_mesaji = '';
$basari_mesaji = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kullanici_adi = $_POST['kullanici_adi'];
    $email = $_POST['email'];
    $sifre = $_POST['sifre'];
    $rol = in_array($_POST['rol'], ['Calisan', 'Admin'], true) ? $_POST['rol'] : 'Calisan';
  
    // Şifreyi GÜVENLİ bir şekilde hash'liyoruz
    $hashed_sifre = password_hash($sifre, PASSWORD_DEFAULT);

    // SQL Sorgusu: Yeni kullanıcıyı veritabanına ekleme
    $stmt = $conn->prepare(
    "INSERT INTO kullanicilar (kullanici_adi, sifre, email, rol)
     VALUES (?, ?, ?, ?)"
);

$stmt->bind_param(
    "ssss",
    $kullanici_adi,
    $hashed_sifre,
    $email,
    $rol
);

if ($stmt->execute()) {
    $basari_mesaji = "Yeni kullanıcı (" . htmlspecialchars($kullanici_adi) . ") başarıyla eklendi.";
} else {
    if ($stmt->errno == 1062) { 
        $hata_mesaji = "Hata: Bu kullanıcı adı veya e-posta adresi zaten kullanımda.";
    } else {
        $hata_mesaji = "Kullanıcı eklenirken bir hata oluştu: " . $stmt->error;
    }
}
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Kullanıcı Ekle</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f9; }
        .container { background-color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 500px; margin: 0 auto; }
        h2 { color: #3f51b5; border-bottom: 2px solid #3f51b5; padding-bottom: 10px; }
        form div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #3f51b5; }
        input[type="text"], input[type="email"], input[type="password"], select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 20px; background-color: #3f51b5; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>➕ Yeni Kullanıcı Ekle</h2>
        <a href="index.php">← Ana Sayfa</a>

        <?php if ($hata_mesaji) { echo "<p style='color:red;'>$hata_mesaji</p>"; } ?>
        <?php if ($basari_mesaji) { echo "<p style='color:green;'>$basari_mesaji</p>"; } ?>
        
        <form method="POST" action="kullanici_ekle.php">
            <div>
                <label for="kullanici_adi">Kullanıcı Adı:</label>
                <input type="text" id="kullanici_adi" name="kullanici_adi" required>
            </div>
            <div>
                <label for="email">E-posta:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="sifre">Şifre:</label>
                <input type="password" id="sifre" name="sifre" required>
            </div>
            <div>
                <label for="rol">Rol:</label>
                <select id="rol" name="rol" required>
                    <option value="Calisan">Çalışan</option>
                    <option value="Admin">Admin</option>
                </select>
            </div>
            <button type="submit">Kullanıcıyı Kaydet</button>
        </form>
    </div>
</body>
</html>