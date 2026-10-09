<?php
include 'config.php';

// Güvenlik Kontrolü: Giriş yapılmamışsa login'e yönlendir
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: login.php");
    exit();
}

$aktif_kullanici_id = (int)$_SESSION['kullanici_id'];
$aktif_rol = $_SESSION['rol']; // Aktif kullanıcının rol bilgisini alıyoruz

$org_id = 0;
$organizasyon = null;
$musteri_ad_soyad = 'Bilinmiyor';

if (isset($_GET['id'])) {
    $org_id = (int)$_GET['id'];
    
    // FİLTRELEME KOŞULU OLUŞTURMA
    $sql_kosulu = "";
    // Admin değilse (Çalışan ise), sorguya kullanici_id filtresini ekle
    if ($aktif_rol !== 'Admin') {
        $sql_kosulu = " AND o.kullanici_id = $aktif_kullanici_id"; 
    }

    $sql = "SELECT 
                o.*, 
                k.ad AS musteri_ad, 
                k.soyad AS musteri_soyad
            FROM organizasyonlar o
            JOIN kisiler k ON o.musteri_id = k.kisi_id
            WHERE o.org_id = $org_id" 
            . $sql_kosulu;            
            
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $organizasyon = $result->fetch_assoc();
        $musteri_ad_soyad = $organizasyon['musteri_ad'] . ' ' . $organizasyon['musteri_soyad'];
    } else {
        
        die("Bu organizasyon kaydı bulunamadı veya bu kayda erişim izniniz yok.");
    }
} else {
    header("Location: index.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($organizasyon['ad']); ?> Detayları</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f9; }
        .container { background-color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        h2 { color: #3f51b5; border-bottom: 2px solid #3f51b5; padding-bottom: 10px; }
        a.back-link { 
            text-decoration: underline; 
            color: #673AB7; 
            font-weight: normal; 
            display: block; 
            margin-bottom: 15px; 
            margin-top: -5px;
        }
        a.back-link:hover { text-decoration: none; }
        .detail-group { margin-bottom: 15px; padding: 10px; border-left: 5px solid #ccc; }
        .detail-group strong { display: inline-block; width: 150px; color: #3f51b5; }
        .detail-group span { font-weight: normal; }
        .actions a { text-decoration: none; padding: 8px 15px; margin-right: 10px; border-radius: 4px; }
        .actions .edit { background-color: orange; color: white; }
        .actions .delete { background-color: red; color: white; }
        .actions { margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px; }
    </style>
</head>
<body>
    
    <div class="container">
      <h2>🗓️ <?php echo htmlspecialchars($organizasyon['ad']); ?> Detayları</h2>
        
        <a href="index.php" class="back-link">← Ana Sayfaya Dön</a>
        
        <div class="detail-group">
            <strong>Müşteri:</strong>
            <span><?php echo htmlspecialchars($musteri_ad_soyad); ?></span>
        </div>
        <div class="detail-group">
            <strong>Tarih:</strong>
            <span><?php echo date('d.m.Y', strtotime($organizasyon['tarih'])); ?></span>
        </div>
        <div class="detail-group">
            <strong>Durum:</strong>
            <span><?php echo htmlspecialchars($organizasyon['durum']); ?></span>
        </div>
        <div class="detail-group">
            <strong>Salon Adı:</strong>
            <span><?php echo htmlspecialchars($organizasyon['salon_adi']); ?></span>
        </div>
        <div class="detail-group">
            <strong>Tahmini Bütçe:</strong>
            <span><?php echo number_format($organizasyon['tahmini_butce'], 2, ',', '.') . ' TL'; ?></span>
        </div>
        <div class="detail-group">
            <strong>Notlar:</strong>
            <span><?php echo nl2br(htmlspecialchars($organizasyon['notlar'])); ?></span>
        </div>

        <div class="actions">
            <a href="organizasyon_duzenle.php?id=<?php echo $org_id; ?>" class="edit">✏️ Organizasyonu Düzenle</a>
            <a href="organizasyon_sil.php?id=<?php echo $org_id; ?>" class="delete" onclick="return confirm('Bu organizasyonu silmek istediğinizden emin misiniz?')">🗑️ Organizasyonu Sil</a>
        </div>
    </div>
</body>
</html>