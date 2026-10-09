<?php
// 1. Session ve Veritabanı Bağlantısı
include 'config.php';

// Güvenlik: Zaten giriş yapmış bir kullanıcıyı tekrar giriş sayfasına yönlendirme
if (isset($_SESSION['kullanici_id'])) {
    header("Location: index.php");
    exit();
}

$hata_mesaji = '';

// 2. Form Gönderildiğinde İşlem Yapma
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Veriler doğrudan POST'tan alınır, real_escape_string'e gerek kalmaz.
    $kullanici_adi = $_POST['kullanici_adi'];
    $sifre = $_POST['sifre'];

    //GÜVENLİK DÜZELTMESİ: Prepared Statements kullanımı (SQL Enjeksiyonunu önler)
    $sql = "SELECT kullanici_id, sifre, rol FROM kullanicilar WHERE kullanici_adi = ?";
    
    // Sorguyu Hazırla
    $stmt = $conn->prepare($sql);
    
    // Değişkeni Bağla ('s' -> string)
    $stmt->bind_param("s", $kullanici_adi); 
    
    // Sorguyu Çalıştır
    $stmt->execute();
    
    // Sonuçları Al
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Şifre doğrulama (password_verify modern ve güvenlidir)
        if (password_verify($sifre, $user['sifre'])) {
            
            // 3. Başarılı Giriş: Oturumu Başlatma
            session_regenerate_id(true);
            $_SESSION['kullanici_id'] = $user['kullanici_id'];
            $_SESSION['rol'] = $user['rol'];
            
            // Kullanıcıyı Ana Sayfa'ya yönlendir
            $stmt->close(); // Başarılıysa statement'ı kapat
            header("Location: index.php");
            exit();
        } else {
            $hata_mesaji = "Kullanıcı adı veya şifre hatalı.";
        }
    } else {
        $hata_mesaji = "Kullanıcı adı veya şifre hatalı.";
    }
    
    // Hatalıysa veya sonuç alınamazsa statement'ı kapat (eğer açıksa)
    if (isset($stmt)) {
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sisteme Giriş</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f4f4f9; }
        .login-box { background-color: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 350px; }
        h2 { color: #3f51b5; margin-bottom: 20px; text-align: center; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #3f51b5; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #303f9f; }
        .hata { color: red; text-align: center; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Çalışan Girişi</h2>
        
        <?php if ($hata_mesaji) { echo "<p class='hata'>$hata_mesaji</p>"; } ?>

        <form method="POST" action="login.php">
            <div>
                <input type="text" name="kullanici_adi" placeholder="Kullanıcı Adı" required>
            </div>
            <div>
                <input type="password" name="sifre" placeholder="Şifre" required>
            </div>
            <button type="submit">Giriş Yap</button>
        </form>
    </div>
</body>
</html>