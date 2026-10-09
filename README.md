# Organizasyon CRM

Etkinlik ve organizasyon firmaları için kişi (adres defteri) ve organizasyon takip uygulaması. PHP ve MySQL ile geliştirildi, arayüz Türkçedir.

## Özellikler
- **Kişi yönetimi:** Firmanın muhatap olduğu kişiler (müşteri, tedarikçi vb.) rol, şirket, unvan ve iletişim bilgileriyle kaydedilir; listelenir, düzenlenir, silinir.
- **Organizasyon takibi:** Her organizasyon bir müşteriye bağlanır. Tarih, salon, tahmini bütçe ve durum (Teklif Aşamasında, Planlanıyor, Tamamlandı, İptal Edildi) takip edilir. Ana sayfada yaklaşan organizasyonlar kart olarak görünür.
- **Rol bazlı erişim:** Admin tüm kayıtları görür ve yeni kullanıcı ekler. Çalışan sadece kendi eklediği kayıtlara erişir.

## Güvenlik
- Tüm SQL sorguları prepared statement ile çalışır (SQL injection koruması).
- Şifreler `password_hash` ile hash'lenerek saklanır, giriş `password_verify` ile doğrulanır.
- Ekrana basılan kullanıcı girdileri `htmlspecialchars` ile escape edilir (XSS koruması).
- Her sayfada session kontrolü yapılır. Düzenleme ve silme işlemlerinde kaydın o kullanıcıya ait olup olmadığı kontrol edilir. `kullanici_ekle.php` sayfası sadece Admin'e açıktır.
- Veritabanı bilgileri `config.php` içindedir ve `.gitignore` ile repoya dahil edilmez.

## Kurulum
1. XAMPP'te Apache ve MySQL'i başlatın, projeyi `htdocs` klasörüne koyun.
2. phpMyAdmin'de **Import** sekmesinden `adres_defteri.sql` dosyasını yükleyin (veritabanı otomatik oluşur).
3. `config.example.php` dosyasını `config.php` adıyla kopyalayın, gerekirse veritabanı bilgilerini düzenleyin.
4. Tarayıcıda `http://localhost/<klasör-adı>/login.php` adresini açın.

Demo giriş: kullanıcı adı `admin`, şifre `password`

## Bilinen sınırlamalar
- Silme işlemleri GET isteğiyle yapılıyor ve CSRF token kontrolü yok. Gerçek kullanımda POST ve CSRF token eklenmeli.
- `roller` tablosu şu an sadece organizasyon formundaki müşteri filtresinde kullanılıyor, diğer roller kategori amaçlı.
- Tedarikçiler henüz organizasyonlara bağlanamıyor.

## AI Kullanımı
Kod yazımı, hata ayıklama ve güvenlik incelemesi sırasında AI asistanından yardım aldım. Veri modelini ve özellik kararlarını ben belirledim; kodu inceleyip uygulamayı test ettim.