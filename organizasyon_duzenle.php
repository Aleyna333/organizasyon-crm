<?php
include 'config.php';

// Güvenlik Kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: login.php");
    exit();
}

$aktif_kullanici_id = (int)$_SESSION['kullanici_id'];
$aktif_rol = $_SESSION['rol']; // Aktif kullanıcının rol bilgisini alıyoruz

$hata_mesaji = '';
$basari_mesaji = '';
$organizasyon = null;
$org_id = 0;

// FİLTRELEME KOŞULU (Admin/Çalışan ayrımı için)
$sql_user_filter = "";
if ($aktif_rol !== 'Admin') {
    // Çalışan ise, sadece kendi ID'sine ait verilerle işlem yap
    $sql_user_filter = " AND k.kullanici_id = $aktif_kullanici_id"; 
}

// 1. Müşteri Listesi Sorgusu (Çalışan sadece kendi müşterilerini görmeli)
$sql_musteriler = "SELECT 
                        k.kisi_id, 
                        k.ad, 
                        k.soyad 
                    FROM kisiler k
                    JOIN roller r ON k.rol_id = r.rol_id 
                    WHERE r.rol_adi = 'Müşteri'" 
                    . $sql_user_filter . //  GÜVENLİK FİLTRESİ BURAYA EKLENDİ
                    " ORDER BY k.soyad ASC";

$result_musteriler = $conn->query($sql_musteriler);
$musteriler = [];
if ($result_musteriler && $result_musteriler->num_rows > 0) {
    while($row = $result_musteriler->fetch_assoc()) {
        $musteriler[] = $row;
    }
}

// 2. FORM GÖNDERİMİ (UPDATE İşlemi)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['org_id'])) {
    $org_id = (int)$_POST['org_id'];
    $ad = $_POST['ad'];
    $musteri_id = (int)$_POST['musteri_id'];
    $tarih = $_POST['tarih'];
    $salon_adi = $_POST['salon_adi'];
    $tahmini_butce = $_POST['tahmini_butce'];
    $durum = $_POST['durum'];
    $notlar = $_POST['notlar'];

    $musteri_gecerli = false;
foreach ($musteriler as $m) {
    if ((int)$m['kisi_id'] === $musteri_id) { $musteri_gecerli = true; break; }
}
if (!$musteri_gecerli) {
    die("Geçersiz müşteri seçimi.");
}

    
  $sql = "UPDATE organizasyonlar SET 
        ad = ?, 
        musteri_id = ?, 
        tarih = ?, 
        salon_adi = ?, 
        tahmini_butce = ?, 
        durum = ?, 
        notlar = ?
        WHERE org_id = ?";

if ($aktif_rol !== 'Admin') {
    $sql .= " AND kullanici_id = ?";
}

$stmt = $conn->prepare($sql);

if ($aktif_rol !== 'Admin') {
    $stmt->bind_param(
        "sisssssii",
        $ad,
        $musteri_id,
        $tarih,
        $salon_adi,
        $tahmini_butce,
        $durum,
        $notlar,
        $org_id,
        $aktif_kullanici_id
    );
} else {
    $stmt->bind_param(
        "sisssssi",
        $ad,
        $musteri_id,
        $tarih,
        $salon_adi,
        $tahmini_butce,
        $durum,
        $notlar,
        $org_id
    );
}

if ($stmt->execute()) {
    header("Location: organizasyon_duzenle.php?id=$org_id&success=true");
    exit();
} else {
    $hata_mesaji = "Organizasyon güncellenirken hata oluştu: " . $stmt->error;
} 
}

// 3. VERİ ÇEKME (GET İşlemi)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $org_id = (int)$_GET['id'];

    // Veri çekerken de filtre uyguluyoruz 
    $select_filter = "";
    if ($aktif_rol !== 'Admin') {
        $select_filter = " AND o.kullanici_id = $aktif_kullanici_id"; 
    }

    $sql = "SELECT 
                o.*, 
                k.ad AS musteri_ad, 
                k.soyad AS musteri_soyad
            FROM organizasyonlar o
            JOIN kisiler k ON o.musteri_id = k.kisi_id
            WHERE o.org_id = $org_id"
            . $select_filter; 
            
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $organizasyon = $result->fetch_assoc();
    } else {
        $hata_mesaji = "Organizasyon bulunamadı veya bu kaydı düzenlemeye yetkiniz yok.";
    }
} else {
    // Eğer GET ID yoksa ve POST işlemi başarısızsa hata mesajı göster
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        $hata_mesaji = "Geçersiz Organizasyon ID'si.";
    }
}

if (isset($_GET['success']) && $_GET['success'] == 'true') {
    $basari_mesaji = "Organizasyon bilgileri başarıyla güncellendi!";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Organizasyon Düzenle</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f9; }
        .container { background-color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        form div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #3f51b5; }
        input[type="text"], input[type="date"], select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 20px; background-color: #3f51b5; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #303f9f; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Organizasyon Düzenle</h2>
        <a href="index.php">← Ana Sayfa</a>
        
        <?php if ($hata_mesaji) { echo "<p style='color:red;'>$hata_mesaji</p>"; } ?>
        <?php if ($basari_mesaji) { echo "<p style='color:green;'>$basari_mesaji</p>"; } ?>

        <?php if ($organizasyon): ?>
            <form method="POST" action="organizasyon_duzenle.php"> <input type="hidden" name="org_id" value="<?php echo $organizasyon['org_id']; ?>">
                
                <div>
                    <label for="ad">Organizasyon Adı:</label>
                    <input type="text" id="ad" name="ad" value="<?php echo htmlspecialchars($organizasyon['ad']); ?>" required>
                </div>
                <div>
                    <label for="musteri_id">Müşteri Seçimi:</label>
                    <select id="musteri_id" name="musteri_id" required>
                        <option value="">Lütfen Bir Müşteri Seçiniz</option>
                        <?php 
                        if (!empty($musteriler)) {
                            foreach ($musteriler as $musteri) {
                                $selected = ($musteri['kisi_id'] == $organizasyon['musteri_id']) ? 'selected' : '';
                                echo "<option value=\"" . (int)$musteri['kisi_id'] . "\" $selected>" . htmlspecialchars($musteri['ad'] . ' ' . $musteri['soyad']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label for="tarih">Tarih:</label>
                    <input type="date" id="tarih" name="tarih" value="<?php echo htmlspecialchars($organizasyon['tarih']); ?>" required>
                </div>
                <div>
                    <label for="salon_adi">Salon Adı:</label>
                    <input type="text" id="salon_adi" name="salon_adi" value="<?php echo htmlspecialchars($organizasyon['salon_adi']); ?>">
                </div>
                <div>
                    <label for="tahmini_butce">Tahmini Bütçe:</label>
                    <input type="text" id="tahmini_butce" name="tahmini_butce" value="<?php echo htmlspecialchars($organizasyon['tahmini_butce']); ?>">
                </div>
                <div>
                    <label for="durum">Durum:</label>
                    <select id="durum" name="durum" required>
                        <?php 
                        $durumlar = ['Teklif Aşamasında', 'Planlanıyor', 'Tamamlandı', 'İptal Edildi'];
                        foreach ($durumlar as $d) {
                            $selected = ($d == $organizasyon['durum']) ? 'selected' : '';
                            echo "<option value=\"$d\" $selected>$d</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label for="notlar">Notlar:</label>
                    <textarea id="notlar" name="notlar" rows="3"><?php echo htmlspecialchars($organizasyon['notlar']); ?></textarea>
                </div>
                
                <button type="submit">Değişiklikleri Kaydet</button>
            </form>
        <?php else: ?>
            <p>Organizasyon bilgisi yüklenemedi.</p>
        <?php endif; ?>
    </div>
</body>
</html>