<?php
include 'config.php';

// Güvenlik Kontrolü ve Aktif Kullanıcı Bilgileri
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: login.php");
    exit();
}

$aktif_kullanici_id = (int)$_SESSION['kullanici_id'];
$aktif_rol = $_SESSION['rol']; 

// FİLTRELEME MANTIĞI: Admin değilse (Çalışan ise) filtreleme koşulu ekle
$sql_kosulu = "";
if ($aktif_rol !== 'Admin') {
    // Kişiler tablosunda (k) kullanici_id filtresini ekle
    $sql_kosulu = " WHERE k.kullanici_id = $aktif_kullanici_id"; 
}

// TEK VE DOĞRU SQL SORGUSU: Filtreleme koşulunu ekleriz.
$sql = "SELECT 
            k.kisi_id, 
            k.ad, 
            k.soyad, 
            r.rol_adi,  
            k.sirket_adi, 
            k.unvan 
        FROM kisiler k
        JOIN roller r ON k.rol_id = r.rol_id"
        . $sql_kosulu . // <--- DİNAMİK FİLTRE BURADA UYGULANIR
        " ORDER BY r.rol_adi ASC, k.soyad ASC";
        
$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Tüm Kişiler Listesi</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f9; }
        .container { background-color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 900px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #3f51b5; color: white; }
        .action-link { margin-right: 10px; text-decoration: none; color: #3f51b5; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h2>👥 Tüm Kayıtlı Kişiler</h2>
        <a href="index.php">← Ana Sayfa</a> | <a href="kisi_ekle.php">➕ Yeni Kişi Ekle</a>

        <table>
            <thead>
                <tr>
                    <th>Ad Soyad</th>
                    <th>Rol</th>
                    <th>Şirket Adı</th>
                    <th>Unvan</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['ad']) . " " . htmlspecialchars($row['soyad']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['rol_adi']) . "</td>"; 
                        echo "<td>" . htmlspecialchars($row['sirket_adi']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['unvan']) . "</td>";
                        echo "<td>";
                        echo "<a href='kisi_duzenle.php?id={$row['kisi_id']}' class='action-link'>Düzenle</a>";
                        echo "<a href='kisi_sil.php?id={$row['kisi_id']}' class='action-link' onclick='return confirm(\"Bu kişiyi silmek istediğinizden emin misiniz?\")'>Sil</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>Henüz kayıtlı bir kişi bulunmamaktadır.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>