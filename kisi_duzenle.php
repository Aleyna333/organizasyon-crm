<?php
include 'config.php';

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: login.php");
    exit();
}

$aktif_kullanici_id = (int)$_SESSION['kullanici_id'];
$aktif_rol = $_SESSION['rol'];

$hata_mesaji = '';
$basari_mesaji = '';
$kisi = null;
$kisi_id = 0;

$sql_roller = "SELECT rol_id, rol_adi FROM roller ORDER BY rol_adi ASC";
$result_roller = $conn->query($sql_roller);
$roller = [];
if ($result_roller->num_rows > 0) {
    while($row = $result_roller->fetch_assoc()) {
        $roller[] = $row;
    }
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $kisi_id = (int)$_GET['id'];
} elseif (isset($_POST['kisi_id']) && is_numeric($_POST['kisi_id'])) {
    $kisi_id = (int)$_POST['kisi_id'];
}

// KİŞİ VERİSİNİ ÇEKME (GET İŞLEMİ)
if ($kisi_id > 0) {
    $sql_kisi = "SELECT k.*, r.rol_adi AS mevcut_rol_adi, k.rol_id AS mevcut_rol_id
                 FROM kisiler k
                 JOIN roller r ON k.rol_id = r.rol_id
                 WHERE k.kisi_id = ?";
                 
    if ($aktif_rol !== 'Admin') {
        $sql_kisi .= " AND k.kullanici_id = ?";
    }
    
    $stmt_select = $conn->prepare($sql_kisi);

    if ($aktif_rol !== 'Admin') {
        $stmt_select->bind_param("ii", $kisi_id, $aktif_kullanici_id);
    } else {
        $stmt_select->bind_param("i", $kisi_id);
    }
    
    $stmt_select->execute();
    $result_kisi = $stmt_select->get_result();
    
    if ($result_kisi && $result_kisi->num_rows > 0) {
        $kisi = $result_kisi->fetch_assoc();
        
        $sql_iletisim = "SELECT tur, deger FROM iletisim_bilgileri WHERE kisi_id = ?";
        $stmt_iletisim = $conn->prepare($sql_iletisim);
        $stmt_iletisim->bind_param("i", $kisi_id);
        $stmt_iletisim->execute();
        $result_iletisim = $stmt_iletisim->get_result();
        
        $kisi['telefon'] = '';
        $kisi['eposta'] = '';
        
        if ($result_iletisim) {
            while ($iletisim = $result_iletisim->fetch_assoc()) {
                if ($iletisim['tur'] == 'Telefon') {
                    $kisi['telefon'] = $iletisim['deger'];
                } elseif ($iletisim['tur'] == 'Eposta') {
                    $kisi['eposta'] = $iletisim['deger'];
                }
            }
        }
        $stmt_iletisim->close();
    } else {
        $hata_mesaji = "Kişi bulunamadı veya bu kaydı düzenlemeye yetkiniz yok.";
    }
    $stmt_select->close();
}

// FORM KAYDETME (UPDATE) Kısmı
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['kisi_id'])) {
    $kisi_id = (int)$_POST['kisi_id'];
    $ad = $_POST['ad'];
    $soyad = $_POST['soyad'];
    $rol_id = (int)$_POST['rol_id']; // HATA GİDERME: Formdan gelen rol_id'yi integer'a dönüştürdük.
    $sirket_adi = $_POST['sirket_adi'];
    $unvan = $_POST['unvan'];
    $notlar = $_POST['notlar'];
    $telefon = $_POST['telefon'];
    $eposta = $_POST['eposta'];

    $rol_valid = false;
    foreach ($roller as $rol) {
        if ($rol['rol_id'] == $rol_id) {
            $rol_valid = true;
            break;
        }
    }

       if (!$kisi || (int)$kisi['kisi_id'] !== $kisi_id) {
        $hata_mesaji = "Bu kaydı düzenleme yetkiniz yok.";
    } elseif ($rol_id > 0 && $rol_valid) {

        $sql_kisi_update = "UPDATE kisiler SET 
                                 ad = ?, 
                                 soyad = ?, 
                                 rol_id = ?, 
                                 sirket_adi = ?, 
                                 unvan = ?, 
                                 notlar = ?
                            WHERE kisi_id = ?";

        if ($aktif_rol !== 'Admin') {
            $sql_kisi_update .= " AND kullanici_id = ?";
        }

        $stmt_update = $conn->prepare($sql_kisi_update);

        if ($aktif_rol !== 'Admin') {
            $stmt_update->bind_param("ssisssii", $ad, $soyad, $rol_id, $sirket_adi, $unvan, $notlar, $kisi_id, $aktif_kullanici_id);
        } else {
            $stmt_update->bind_param("ssisssi", $ad, $soyad, $rol_id, $sirket_adi, $unvan, $notlar, $kisi_id);
        }

        if ($stmt_update->execute()) {

            // İletişim Bilgilerini Silme
            $sql_delete_iletisim = "DELETE FROM iletisim_bilgileri WHERE kisi_id = ?";
            $stmt_delete = $conn->prepare($sql_delete_iletisim);
            $stmt_delete->bind_param("i", $kisi_id);
            $stmt_delete->execute();
            $stmt_delete->close();

            $sql_insert_iletisim = "INSERT INTO iletisim_bilgileri (kisi_id, tur, deger) VALUES (?, ?, ?)";

            if (!empty($telefon)) {
                $tur = 'Telefon';
                $stmt_insert_tel = $conn->prepare($sql_insert_iletisim);
                $stmt_insert_tel->bind_param("iss", $kisi_id, $tur, $telefon);
                $stmt_insert_tel->execute();
                $stmt_insert_tel->close();
            }
            if (!empty($eposta)) {
                $tur = 'Eposta';
                $stmt_insert_mail = $conn->prepare($sql_insert_iletisim);
                $stmt_insert_mail->bind_param("iss", $kisi_id, $tur, $eposta);
                $stmt_insert_mail->execute();
                $stmt_insert_mail->close();
            }

            header("Location: kisi_duzenle.php?id=$kisi_id&success=true");
            exit();

        } else {
            $hata_mesaji = "Kişi güncellenirken hata oluştu: " . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        $hata_mesaji = "Lütfen geçerli bir Rol seçimi yapın.";
    }
}

if (isset($_GET['success']) && $_GET['success'] == 'true') {
    $basari_mesaji = "Kişi bilgileri başarıyla güncellendi!";
}

if (!$kisi && $kisi_id > 0 && $aktif_rol !== 'Admin') {
    $hata_mesaji = "Kişi bulunamadı veya bu kaydı düzenlemeye yetkiniz yok.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kişi Düzenle: <?php echo $kisi ? htmlspecialchars($kisi['ad'] . ' ' . $kisi['soyad']) : ''; ?></title>
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
        <h2>✏️ Kişi Düzenle</h2>
        <a href="kisiler_liste.php">← Tüm Kişiler Listesi</a>
        
        <?php if ($hata_mesaji) { echo "<p style='color:red;'>$hata_mesaji</p>"; } ?>
        <?php if ($basari_mesaji) { echo "<p style='color:green;'>$basari_mesaji</p>"; } ?>

        <?php if ($kisi): ?>
            <form method="POST" action="kisi_duzenle.php">
                <input type="hidden" name="kisi_id" value="<?php echo $kisi['kisi_id']; ?>">
                <h3>Temel Bilgiler</h3>
                <div>
                    <label for="ad">Ad:</label>
                    <input type="text" id="ad" name="ad" value="<?php echo htmlspecialchars($kisi['ad']); ?>" required>
                </div>
                <div>
                    <label for="soyad">Soyad:</label>
                    <input type="text" id="soyad" name="soyad" value="<?php echo htmlspecialchars($kisi['soyad']); ?>" required>
                </div>
                <div>
                    <label for="rol_id">Rol:</label>
                    <select id="rol_id" name="rol_id" required>
                        <option value="">Lütfen Seçiniz</option>
                        <?php 
                        foreach ($roller as $rol) {
                            $selected = ($rol['rol_id'] == $kisi['rol_id']) ? 'selected' : '';
                            echo "<option value=\"" . (int)$rol['rol_id'] . "\" $selected>" . htmlspecialchars($rol['rol_adi']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <h3>Kurum Bilgileri</h3>
                <div>
                    <label for="sirket_adi">Şirket Adı:</label>
                    <input type="text" id="sirket_adi" name="sirket_adi" value="<?php echo htmlspecialchars($kisi['sirket_adi']); ?>">
                </div>
                <div>
                    <label for="unvan">Pozisyon/Unvan:</label>
                    <input type="text" id="unvan" name="unvan" value="<?php echo htmlspecialchars($kisi['unvan']); ?>">
                </div>

                <h3>İletişim Bilgileri</h3>
                <div>
                    <label for="telefon">Telefon:</label>
                    <input type="text" id="telefon" name="telefon" value="<?php echo htmlspecialchars($kisi['telefon']); ?>">
                </div>
                <div>
                    <label for="eposta">E-posta:</label>
                    <input type="email" id="eposta" name="eposta" value="<?php echo htmlspecialchars($kisi['eposta']); ?>">
                </div>
                
                <h3>Ek Notlar</h3>
                <div>
                    <label for="notlar">Notlar:</label>
                    <textarea id="notlar" name="notlar" rows="3"><?php echo htmlspecialchars($kisi['notlar']); ?></textarea>
                </div>
                
                <button type="submit">Değişiklikleri Kaydet</button>
            </form>
        <?php else: ?>
            <p>Düzenlenecek kişi bilgisi yüklenemedi.</p>
        <?php endif; ?>
    </div>
</body>
</html>