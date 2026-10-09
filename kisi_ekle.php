<?php
include 'config.php';

// Güvenlik Kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: login.php");
    exit();
}

$aktif_kullanici_id = $_SESSION['kullanici_id']; // Kişi kaydını yapacak olan kullanıcının ID'si

$hata_mesaji = '';
$basari_mesaji = '';
$roller = [];

// Roller her zaman çekilir
$sql_roller = "SELECT rol_id, rol_adi FROM roller ORDER BY rol_adi ASC";
$result_roller = $conn->query($sql_roller);

if ($result_roller->num_rows > 0) {
    while($row = $result_roller->fetch_assoc()) {
        $roller[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Verileri al
    $ad = $_POST['ad'];
    $soyad = $_POST['soyad'];
    $rol_id = (int)$_POST['rol_id'];
    $sirket_adi = $_POST['sirket_adi'];
    $unvan = $_POST['unvan'];
    $notlar = $_POST['notlar'];
    $telefon = $_POST['telefon'];
    $eposta = $_POST['eposta'];

    // 1. KİŞİ EKLEME (kullanici_id ve diğer alanlar dahil edilmeli)
    // GÜVENLİK DÜZELTMESİ: Prepared Statement kullanımı
    $sql_kisi = "INSERT INTO kisiler (ad, soyad, rol_id, sirket_adi, unvan, notlar, kullanici_id) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql_kisi);
    
    // Değişkenleri Bağla (s=string, i=integer)
    $stmt->bind_param("ssisssi", $ad, $soyad, $rol_id, $sirket_adi, $unvan, $notlar, $aktif_kullanici_id);

    if ($stmt->execute()) {
        $kisi_id = $conn->insert_id; // Yeni eklenen kişinin ID'si
        
        // 2. İLETİŞİM BİLGİLERİNİ EKLEME (Bu sorgular da Prepared Statement olmalıdır)

        if (!empty($telefon)) {
            $stmt_tel = $conn->prepare("INSERT INTO iletisim_bilgileri (kisi_id, tur, deger) VALUES (?, 'Telefon', ?)");
            $stmt_tel->bind_param("is", $kisi_id, $telefon);
            $stmt_tel->execute();
            $stmt_tel->close();
        }
        
        if (!empty($eposta)) {
            $stmt_mail = $conn->prepare("INSERT INTO iletisim_bilgileri (kisi_id, tur, deger) VALUES (?, 'Eposta', ?)");
            $stmt_mail->bind_param("is", $kisi_id, $eposta);
            $stmt_mail->execute();
            $stmt_mail->close();
        }

        $basari_mesaji = "Kişi ve iletişim bilgileri başarıyla eklendi!";
        
        $stmt->close();
        
        // Başarılı kayıttan sonra sayfayı temizlemek için yönlendirme
        header("Location: kisi_ekle.php?success=true");
        exit();

    } else {
        $hata_mesaji = "Kişi eklenirken hata oluştu: " . $stmt->error;
        $stmt->close();
    }
}

// Yönlendirme sonrası başarı mesajını göster
if (isset($_GET['success']) && $_GET['success'] == 'true') {
    $basari_mesaji = "Kişi ve iletişim bilgileri başarıyla eklendi!";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Kişi Kaydı</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f9; }
        .container { background-color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        form div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #3f51b5; }
        input[type="text"], input[type="email"], select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 20px; background-color: #3f51b5; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #303f9f; }
    </style>
</head>
<body>
    <div class="container">
        <h2>➕ Yeni Kişi Kaydı</h2>
        <a href="index.php">← Ana Sayfaya Dön</a> | <a href="kisiler_liste.php">📋 Tüm Kişiler Listesi</a>
        
        <?php if ($hata_mesaji) { echo "<p style='color:red;'>$hata_mesaji</p>"; } ?>
        <?php if ($basari_mesaji) { echo "<p style='color:green;'>$basari_mesaji</p>"; } ?>

        <form method="POST" action="kisi_ekle.php">
            <h3>Temel Bilgiler</h3>
            <div><label for="ad">Ad:</label><input type="text" id="ad" name="ad" required></div>
            <div><label for="soyad">Soyad:</label><input type="text" id="soyad" name="soyad" required></div>
            
            <div>
                <label for="rol_id">Rol:</label>
                <select id="rol_id" name="rol_id" required>
                    <option value="">Lütfen Seçiniz</option>
                    <?php 
                    foreach ($roller as $rol) {
                       echo "<option value=\"" . (int)$rol['rol_id'] . "\">" . htmlspecialchars($rol['rol_adi']) . "</option>"; 
                    }
                    ?>
                </select>
            </div>
            
            <h3>Kurum Bilgileri</h3>
            <div><label for="sirket_adi">Şirket Adı:</label><input type="text" id="sirket_adi" name="sirket_adi"></div>
            <div><label for="unvan">Pozisyon/Unvan:</label><input type="text" id="unvan" name="unvan"></div>

            <h3>İletişim Bilgileri</h3>
            <div><label for="telefon">Telefon:</label><input type="text" id="telefon" name="telefon"></div>
            <div><label for="eposta">E-posta:</label><input type="email" id="eposta" name="eposta"></div>
            
            <h3>Ek Notlar</h3>
            <div><label for="notlar">Notlar:</label><textarea id="notlar" name="notlar" rows="3"></textarea></div>
            
            <button type="submit">Kişiyi Kaydet</button>
        </form>
    </div>
</body>
</html>