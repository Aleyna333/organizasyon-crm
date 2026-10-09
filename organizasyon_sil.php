<?php

include 'config.php';

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: login.php");
    exit();
}

$aktif_kullanici_id = (int)$_SESSION['kullanici_id'];
$aktif_rol = $_SESSION['rol'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {

    $org_id = (int)$_GET['id'];

    if ($aktif_rol === 'Admin') {

        $stmt = $conn->prepare(
            "DELETE FROM organizasyonlar WHERE org_id = ?"
        );
        $stmt->bind_param("i", $org_id);

    } else {

        $stmt = $conn->prepare(
            "DELETE FROM organizasyonlar 
             WHERE org_id = ? AND kullanici_id = ?"
        );
        $stmt->bind_param("ii", $org_id, $aktif_kullanici_id);
    }

    if ($stmt->execute()) {
        header("Location: index.php?silme=basarili");
        exit();
    } else {
        die("Organizasyon silinirken bir hata oluştu.");
    }

} else {

    header("Location: index.php");
    exit();
}