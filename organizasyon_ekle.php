<?php
include 'config.php';

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: login.php");
    exit();
}

$aktif_kullanici_id = (int)$_SESSION['kullanici_id'];
$aktif_rol = $_SESSION['rol']; // Rol bilgisini alıyoruz

$bugunun_tarihi = date('Y-m-d');

$hata_mesaji = '';
$basari_mesaji = '';

// MÜŞTERİ LİSTESİ SORGUSU (GÜVENLİK FİLTRESİ EKLENMELİ)
$musteri_filtresi = "";
if ($aktif_rol !== 'Admin') {
    // Çalışan ise, sadece kendi eklediği müşterileri görebilmeli
    $musteri_filtresi = " AND k.kullanici_id = $aktif_kullanici_id"; 
}

$sql_musteriler = "SELECT 
                        k.kisi_id, 
                        k.ad, 
                        k.soyad 
                    FROM kisiler k
                    JOIN roller r ON k.rol_id = r.rol_id 
                    WHERE r.rol_adi = 'Müşteri'" 
                    . $musteri_filtresi . // GÜVENLİK FİLTRESİ
                    " ORDER BY k.soyad ASC";

$result_musteriler = $conn->query($sql_musteriler);

$musteriler = [];
if ($result_musteriler && $result_musteriler->num_rows > 0) {
    while($row = $result_musteriler->fetch_assoc()) {
        $musteriler[] = $row;
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Verileri doğrudan alıyoruz, Prepared Statements kullanacağız
    $ad = $_POST['ad'];
    $musteri_id = (int)$_POST['musteri_id'];
    if ($aktif_rol !== 'Admin') {
    $stmt_kontrol = $conn->prepare(
    "SELECT kisi_id FROM kisiler WHERE kisi_id = ? AND kullanici_id = ?"
    );

    $stmt_kontrol->bind_param(
        "ii",
        $musteri_id,
        $aktif_kullanici_id
    );

    $stmt_kontrol->execute();
    $sonuc_kontrol = $stmt_kontrol->get_result();

  if ($sonuc_kontrol->num_rows === 0) {
 die("Bu müşteriyi kullanmaya yetkiniz yok.");
}

    $stmt_kontrol->close();
}

    $tarih = $_POST['tarih'];
    $salon_adi = $_POST['salon_adi'];
    $tahmini_butce = $_POST['tahmini_butce'];
    $durum = $_POST['durum'];
    $notlar = $_POST['notlar'];
    
    //GÜVENLİK DÜZELTMESİ: Prepared Statement kullanımı ve kullanici_id ekleme
    // Tek bir doğru INSERT sorgusu kullanıldı.
    $sql = "INSERT INTO organizasyonlar (ad, musteri_id, tarih, salon_adi, tahmini_butce, durum, notlar, kullanici_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    
    // Değişkenleri Bağla (s: string, i: integer)
    $stmt->bind_param("sisssssi", $ad, $musteri_id, $tarih, $salon_adi, $tahmini_butce, $durum, $notlar, $aktif_kullanici_id);

    if ($stmt->execute()) {
        $basari_mesaji = "Organizasyon başarıyla eklendi!";
        $stmt->close();
        // Başarılı kayıttan sonra sayfayı temizlemek için yönlendirme
        header("Location: organizasyon_ekle.php?success=true");
        exit();
    } else {
        $hata_mesaji = "Organizasyon eklenirken hata oluştu: " . $stmt->error;
        $stmt->close();
    }
}

// Yönlendirme sonrası başarı mesajını göster
if (isset($_GET['success']) && $_GET['success'] == 'true') {
    $basari_mesaji = "Organizasyon başarıyla eklendi!";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Organizasyon Ekle</title>
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
        <h2>🗓️ Yeni Organizasyon Ekle</h2>
        <a href="index.php">← Ana Sayfaya Dön</a>
        
        <?php if ($hata_mesaji) { echo "<p style='color:red;'>$hata_mesaji</p>"; } ?>
        <?php if ($basari_mesaji) { echo "<p style='color:green;'>$basari_mesaji</p>"; } ?>

        <form method="POST" action="organizasyon_ekle.php">
            <div>
                <label for="ad">Organizasyon Adı:</label>
                <input type="text" id="ad" name="ad" required>
            </div>
            <div>
                <label for="musteri_id">Müşteri Seçimi: (sadece rolü müşteri olanlar seçilebilir)</label>
                <select id="musteri_id" name="musteri_id" required>
                    <option value="">Lütfen Bir Müşteri Seçiniz</option>
                    <?php 
                    if (!empty($musteriler)) {
                        foreach ($musteriler as $musteri) {
                            echo "<option value=\"" . (int)$musteri['kisi_id'] . "\">" . htmlspecialchars($musteri['ad'] . ' ' . $musteri['soyad']) . "</option>";
                        }
                    } else {
                        echo "<option value='' disabled>Kayıtlı Müşteri Bulunamadı</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="tarih">Tarih:</label>
                <input type="date" id="tarih" name="tarih" value="<?php echo $bugunun_tarihi; ?>" required>
            </div>
            <div>
                <label for="salon_adi">Salon Adı:</label>
                <input type="text" id="salon_adi" name="salon_adi">
            </div>
            <div>
                <label for="tahmini_butce">Tahmini Bütçe:</label>
                <input type="text" id="tahmini_butce" name="tahmini_butce">
            </div>
            <div>
                <label for="durum">Durum:</label>
                <select id="durum" name="durum" required>
                    <option value="Teklif Aşamasında">Teklif Aşamasında</option>
                    <option value="Planlanıyor">Planlanıyor</option>
                    <option value="Tamamlandı">Tamamlandı</option>
                    <option value="İptal Edildi">İptal Edildi</option>
                </select>
            </div>
            <div>
                <label for="notlar">Notlar:</label>
                <textarea id="notlar" name="notlar" rows="3"></textarea>
            </div>
            
            <button type="submit">Organizasyonu Kaydet</button>
        </form>
    </div>
</body>
</html>