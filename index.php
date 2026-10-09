<?php
include 'config.php'; 

// Güvenlik Kontrolü ve Aktif Kullanıcı Bilgileri
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: login.php");
    exit();
}

$aktif_kullanici_id = (int)$_SESSION['kullanici_id'];
$aktif_rol = $_SESSION['rol'];

// FİLTRELEME KOŞULU: Admin değilse, sadece kendi ID'sini filtrele
$sql_kosulu = "";
if ($aktif_rol !== 'Admin') {
    // Organizasyonlar tablosunda (o) kullanici_id filtresini ekle
    $sql_kosulu = " AND o.kullanici_id = $aktif_kullanici_id"; 
}

$sql_org = "SELECT o.* FROM organizasyonlar o
            WHERE o.tarih >= CURDATE()" 
            . $sql_kosulu .
            " ORDER BY o.tarih ASC";
            
$result_org = $conn->query($sql_org);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizasyon CRM - Ana Kontrol Paneli</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f9; }
        .header { background-color: #3f51b5; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; }
        .nav a { color: white; text-decoration: none; margin-left: 15px; padding: 8px 12px; border: 1px solid white; border-radius: 4px; }
        .nav a:hover { background-color: #5c6bc0; }
        .card-container { display: flex; flex-wrap: wrap; gap: 20px; }
        .card { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: calc(33% - 20px); }
        .card h3 { color: #3f51b5; margin-top: 0; }
        .status-plan { color: orange; font-weight: bold; }
        .status-signed { color: green; font-weight: bold; }
        .status-done { color: gray; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Organizasyon CRM Kontrol Paneli</h1>
        <div class="nav">
           <a href="kisi_ekle.php">➕ Yeni Kişi Kaydı</a>
           <a href="organizasyon_ekle.php">🗓️ Yeni Organizasyon</a>
           <a href="kisiler_liste.php">👥 Tüm Kişiler Listesi</a>
           <a href="logout.php" style="background-color: #f44336; border-color: #f44336;">Çıkış Yap</a>
   </div>
    </div>

    <h2>Yaklaşan Organizasyonlar</h2>
    
    <div class="card-container">
        <?php
        if ($result_org->num_rows > 0) {
            while($row = $result_org->fetch_assoc()) {
                $durum_class = 'status-' . strtolower(str_replace(' ', '', $row['durum']));
                echo '<div class="card">';
                echo "<h3>" . htmlspecialchars($row['ad']) . "</h3>";
                echo "<p><strong>Tarih:</strong> " . date('d.m.Y', strtotime($row['tarih'])) . "</p>";
                echo "<p><strong>Salon:</strong> " . htmlspecialchars($row['salon_adi']) . "</p>";
                echo "<p><strong>Durum:</strong> <span class='" . htmlspecialchars($durum_class) . "'>" . htmlspecialchars($row['durum']) . "</span></p>";
                echo '<p><a href="organizasyon_detay.php?id=' . $row['org_id'] . '">Detayları Gör</a></p>';
                echo '</div>';
            }
        } else {
            echo "<p>Yaklaşan organizasyon bulunmamaktadır.</p>";
        }
        ?>
    </div>
    
</body>
</html>
<?php $conn->close(); ?>