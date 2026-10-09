-- Organizasyon CRM - veritabanı yapısı ve örnek veri
-- UYARI: Bu dosya aynı isimli tabloları silip yeniden oluşturur. Sadece temiz kurulumda çalıştırın.

CREATE DATABASE IF NOT EXISTS adresdefteridb_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE adresdefteridb_v2;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS iletisim_bilgileri, organizasyonlar, kisiler, roller, kullanicilar;
SET FOREIGN_KEY_CHECKS = 1;

-- Sisteme giriş yapan kullanıcılar (Admin / Çalışan)
CREATE TABLE kullanicilar (
    kullanici_id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_adi VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    sifre VARCHAR(255) NOT NULL,
    rol ENUM('Calisan', 'Admin') NOT NULL DEFAULT 'Calisan'
) ENGINE=InnoDB;

-- Adres defterindeki kişi kategorileri (Müşteri, Tedarikçi, ...)
CREATE TABLE roller (
    rol_id INT AUTO_INCREMENT PRIMARY KEY,
    rol_adi VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE kisiler (
    kisi_id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(50) NOT NULL,
    soyad VARCHAR(50) NOT NULL,
    rol_id INT NOT NULL,
    sirket_adi VARCHAR(100) NULL,
    unvan VARCHAR(50) NULL,
    notlar TEXT NULL,
    kullanici_id INT NOT NULL,
    FOREIGN KEY (rol_id) REFERENCES roller(rol_id) ON DELETE CASCADE,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(kullanici_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE iletisim_bilgileri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kisi_id INT NOT NULL,
    tur ENUM('Telefon', 'Eposta', 'Adres', 'Diğer') NOT NULL,
    deger VARCHAR(255) NOT NULL,
    FOREIGN KEY (kisi_id) REFERENCES kisiler(kisi_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE organizasyonlar (
    org_id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(100) NOT NULL,
    musteri_id INT NOT NULL,
    tarih DATE NOT NULL,
    salon_adi VARCHAR(100) NULL,
    tahmini_butce DECIMAL(10,2) NULL DEFAULT 0.00,
    durum ENUM('Teklif Aşamasında', 'Planlanıyor', 'Tamamlandı', 'İptal Edildi') NOT NULL DEFAULT 'Teklif Aşamasında',
    notlar TEXT NULL,
    kullanici_id INT NOT NULL,
    FOREIGN KEY (musteri_id) REFERENCES kisiler(kisi_id) ON DELETE CASCADE,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(kullanici_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ÖRNEK VERİ (tamamen uydurmadır)
-- Demo giriş: admin / password
INSERT INTO kullanicilar (kullanici_adi, email, sifre, rol) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin');

INSERT INTO roller (rol_adi) VALUES ('Müşteri'), ('Tedarikçi'), ('Salon Sahibi');

INSERT INTO kisiler (ad, soyad, rol_id, sirket_adi, unvan, notlar, kullanici_id) VALUES
('Ayşe', 'Demir', 1, NULL, NULL, 'Düğün için görüşüldü', 1),
('Mehmet', 'Kaya', 1, 'Kaya Ltd.', 'Genel Müdür', 'Şirket yıl sonu yemeği', 1),
('Selim', 'Arslan', 2, 'Arslan Catering', 'Sahip', 'Yemek tedarikçisi', 1);

INSERT INTO iletisim_bilgileri (kisi_id, tur, deger) VALUES
(1, 'Telefon', '0500 000 00 01'), (1, 'Eposta', 'ayse@example.com'),
(2, 'Telefon', '0500 000 00 02'),
(3, 'Telefon', '0500 000 00 03');

INSERT INTO organizasyonlar (ad, musteri_id, tarih, salon_adi, tahmini_butce, durum, notlar, kullanici_id) VALUES
('Demir Düğünü', 1, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Örnek Salon', 150000.00, 'Planlanıyor', 'Örnek kayıt', 1),
('Kaya Ltd. Yıl Sonu Yemeği', 2, DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'Örnek Otel', 80000.00, 'Teklif Aşamasında', 'Örnek kayıt', 1);