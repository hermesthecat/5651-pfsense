# pfSense TİB Projesi - Bug Raporu ve Çözüm Önerileri

## Kritik Güvenlik Açıkları

### 1. **KRİTİK: Command Injection Açığı**

**Dosya:** `pfsense_turkish/exec_raw.php`
**Satır:** 40
**Açıklama:** GET parametresi doğrudan `passthru()` fonksiyonuna geçiriliyor.

```php
passthru($_GET['cmd']);
```

**Risk Seviyesi:** 🔴 KRİTİK
**Etki:** Saldırgan sistemde herhangi bir komut çalıştırabilir
**Çözüm:**

- Input validation ekle
- Whitelist tabanlı komut kontrolü
- Parametreleri sanitize et

```php
$allowed_commands = ['ls', 'ps', 'netstat'];
$cmd = trim($_GET['cmd']);
if (!in_array($cmd, $allowed_commands)) {
    die("Geçersiz komut");
}
passthru(escapeshellcmd($cmd));
```

### 2. **KRİTİK: PHP Code Injection**

**Dosya:** `pfsense_turkish/exec.php`
**Satır:** 206
**Açıklama:** POST verisi doğrudan `eval()` fonksiyonuna geçiriliyor.

```php
echo eval($_POST['txtPHPCommand']);
```

**Risk Seviyesi:** 🔴 KRİTİK
**Etki:** Saldırgan arbitrary PHP kodu çalıştırabilir
**Çözüm:**

- `eval()` kullanımını tamamen kaldır
- Güvenli alternatifler kullan
- Input validation ekle

### 3. **YÜKSEK: Hardcoded Credentials**

**Dosyalar:**

- `pfsense_tib_rc_2.0.1_0.4/dhcplistcronftp.sh`
- `zaman_damgasi/Scripts/dhcplistcronftp.sh`
- `zaman_damgasi/logzamandamgasi.sh`

**Açıklama:** Şifreler ve kullanıcı bilgileri kodda sabit olarak tanımlanmış.

```bash
HOST='purenet.domain'
USER='muzik'
PASSWD='vardar'
SERVER='10.0.0.10'
password=nevport
```

**Risk Seviyesi:** 🟠 YÜKSEK
**Etki:** Kimlik bilgileri açığa çıkabilir
**Çözüm:**

- Şifreleri environment variables'da sakla
- Configuration dosyası kullan
- Şifreleri encrypt et

### 4. **YÜKSEK: Path Traversal Açığı**

**Dosya:** `pfsense_turkish/exec.php`
**Satır:** 11-20
**Açıklama:** Dosya yolu doğrudan kullanıcı inputundan alınıyor.

```php
if (($_POST['submit'] == "Download") && file_exists($_POST['dlPath'])) {
    $fd = fopen($_POST['dlPath'], "rb");
```

**Risk Seviyesi:** 🟠 YÜKSEK
**Etki:** Sistem dosyalarına erişim
**Çözüm:**

- Dosya yolunu validate et
- Allowed directories listesi oluştur
- `realpath()` kullanarak path normalization yap

### 5. **ORTA: XSS Açıkları**

**Dosyalar:** Birden fazla PHP dosyası
**Açıklama:** POST/GET verileri htmlspecialchars() olmadan echo ediliyor.

```php
<input name="dnsquery" type="checkbox"<?php if($_POST['dnsquery']) echo " CHECKED"; ?>>
```

**Risk Seviyesi:** 🟡 ORTA
**Etki:** Cross-site scripting saldırıları
**Çözüm:**

```php
<input name="dnsquery" type="checkbox"<?php if(htmlspecialchars($_POST['dnsquery'])) echo " CHECKED"; ?>>
```

### 6. **ORTA: Güvensiz Dosya Upload**

**Dosya:** `pfsense_turkish/exec.php`
**Satır:** 21-23
**Açıklama:** Upload edilen dosyalar kontrol edilmiyor.

```php
move_uploaded_file($_FILES['ulfile']['tmp_name'], "/tmp/" . $_FILES['ulfile']['name']);
```

**Risk Seviyesi:** 🟡 ORTA
**Etki:** Zararlı dosya upload edilebilir
**Çözüm:**

- Dosya tipini kontrol et
- Dosya boyutunu sınırla
- Dosya adını sanitize et

## Kod Kalitesi Sorunları

### 7. **TODO/FIXME İşaretleri**

**Açıklama:** Kodda tamamlanmamış işler var:

- `pfsense_turkish/diag_backup.php:255`: "XXX - this feature may hose your config"
- `pfsense_turkish/status_interfaces.php:46`: "FIXME: when we support multi-pppoe"
- `pfsense_turkish/pkg_edit.php:150`: "XXX: this really should be passed from the form"

**Çözüm:** Bu işaretli alanları gözden geçir ve tamamla.

### 8. **Güvensiz Cron Script'leri**

**Dosyalar:**

- `dhcplistcronftp.sh`
- `dhcplistcronsmb.sh`
- `dhcplistcronusb.sh`

**Sorunlar:**

- Error handling yok
- Logging yetersiz
- Temporary dosyalar güvenli değil

**Çözüm:**

```bash
# Error handling ekle
set -e
trap 'echo "Script failed at line $LINENO"' ERR

# Güvenli temp directory
TEMP_DIR=$(mktemp -d)
trap 'rm -rf "$TEMP_DIR"' EXIT
```

### 9. **Zaman Damgası Script Sorunları**

**Dosya:** `zaman_damgasi/logzamandamgasi.sh`
**Sorunlar:**

- Hardcoded password: `password=nevport`
- Error handling yetersiz
- Path'ler sabit kodlanmış

**Çözüm:**

- Configuration dosyası kullan
- Proper error handling ekle
- Logging mekanizması ekle

## Güvenlik Önerileri

### Genel Güvenlik

1. **Input Validation:** Tüm user input'ları validate et
2. **Output Encoding:** XSS'e karşı tüm output'ları encode et
3. **Error Handling:** Detaylı error mesajlarını kullanıcıya gösterme
4. **Logging:** Güvenlik olaylarını logla
5. **Authentication:** Strong authentication mekanizması ekle

### Kod Güvenliği

1. **Dangerous Functions:** `eval()`, `exec()`, `passthru()` kullanımını minimize et
2. **File Operations:** Dosya işlemlerinde path validation yap
3. **Database:** Prepared statements kullan
4. **Encryption:** Sensitive data'yı encrypt et

### Deployment Güvenliği

1. **Permissions:** Dosya izinlerini minimize et
2. **Configuration:** Production'da debug mode'u kapat
3. **Updates:** Düzenli güvenlik güncellemeleri yap
4. **Monitoring:** Güvenlik monitoring ekle

## Acil Eylem Planı

### Hemen Yapılması Gerekenler (24 saat)

1. `exec_raw.php` dosyasını devre dışı bırak veya kaldır
2. `exec.php` dosyasındaki `eval()` fonksiyonunu kaldır
3. Hardcoded şifreleri environment variables'a taşı

### Kısa Vadede (1 hafta)

1. Tüm XSS açıklarını düzelt
2. File upload güvenliğini sağla
3. Input validation ekle

### Orta Vadede (1 ay)

1. Comprehensive security audit yap
2. Automated security testing ekle
3. Security documentation hazırla

## Test Önerileri

### Güvenlik Testleri

1. **Penetration Testing:** Profesyonel pentest yaptır
2. **Code Review:** Security-focused code review
3. **Automated Scanning:** SAST/DAST araçları kullan

### Test Senaryoları

1. Command injection testleri
2. XSS payload testleri  
3. File upload bypass testleri
4. Authentication bypass testleri

---

**Not:** Bu rapor mevcut kod analizi temel alınarak hazırlanmıştır. Gerçek production ortamında daha detaylı güvenlik analizi yapılması önerilir.
