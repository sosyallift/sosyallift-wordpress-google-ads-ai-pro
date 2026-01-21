# Sosyallift AI Pro - Enterprise SEO & Ads Intelligence Platform

## 📋 Tanım
Sosyallift AI Pro, Google Ads ve SEO verilerinizi birleştiren, AI destekli intent analizi yapan ve otomatik optimizasyon önerileri sunan kurumsal seviye bir WordPress eklentisidir.

## ✨ Özellikler
- **Google Ads Entegrasyonu**: Gerçek zamanlı performans verileri
- **SEO Analizi**: Organik trafik ve sıralama takibi
- **Intent Tespiti**: AI destekli kullanıcı niyeti analizi
- **Negatif Keyword Motoru**: Otomatik negatif keyword önerileri
- **Rekabet Analizi**: Rakip keyword ve strateji tespiti
- **AI Önerileri**: Akıllı optimizasyon önerileri
- **Detaylı Raporlama**: Özelleştirilebilir raporlar ve dashboard
- **Gerçek Zamanlı Bildirimler**: Performans değişiklikleri için alert'ler

## 🚀 Kurulum

### Gereksinimler
- PHP 7.4 veya üzeri
- WordPress 5.8 veya üzeri
- MySQL 5.7 veya üzeri
- cURL etkinleştirilmiş
- Minimum 128MB memory limit

### Kurulum Adımları
1. Eklentiyi zip olarak indirin
2. WordPress admin > Eklentiler > Yeni Eklenti > Yükle
3. Zip dosyasını yükleyin ve etkinleştirin
4. AI Intelligence menüsünden kurulum sihirbazını takip edin
5. Google Ads API bilgilerinizi girin
6. İlk analizi çalıştırın

## 🔧 Yapılandırma

### Google Ads API Bağlantısı
1. Google Cloud Console'da proje oluşturun
2. Google Ads API'yi etkinleştirin
3. OAuth 2.0 kimlik bilgileri oluşturun
4. Developer Token alın (Google Ads Manager)
5. Bilgileri eklenti ayarlarına girin

### SEO Takip
1. Google Search Console bağlantısı
2. Google Analytics entegrasyonu
3. Site içi SEO analizi

## 📊 Kullanım

### Dashboard
- Genel performans görünümü
- Keyword analizi
- Intent dağılımı
- AI önerileri
- Gerçek zamanlı alert'ler

### Raporlama
- Günlük/haftalık/aylık raporlar
- Özelleştirilebilir metrikler
- PDF/CSV/Excel export
- Otomatik email raporları

### API
- REST API endpoint'leri
- Webhook desteği
- Third-party entegrasyonlar

## 🔒 Güvenlik
- End-to-end şifreleme
- Role-based erişim kontrolü
- Audit logging
- GDPR uyumlu
- Regular security updates

## 🛠 Geliştiriciler İçin

### Hooks & Filters
```php
// Dashboard verilerini filtrele
add_filter('sl_ai_pro/dashboard/data', 'custom_dashboard_data');

// Yeni analiz modülü ekle
add_filter('sl_ai_pro/modules/register', 'register_custom_module');

// API endpoint'leri ekle
add_action('sl_ai_pro/api/register_routes', 'register_custom_routes');