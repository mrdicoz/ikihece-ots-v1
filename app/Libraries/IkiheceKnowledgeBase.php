<?php

namespace App\Libraries;

class IkiheceKnowledgeBase
{
    /**
     * Sistem kullanım rehberi
     */
    public static function getSystemGuide(): string
    {
        return <<<EOT
═══════════════════════════════════════════════════════════════
📚 İKİHECE SİSTEM KULLANIM REHBERİ
═══════════════════════════════════════════════════════════════

🎓 ÖĞRENCİ YÖNETİMİ
─────────────────────────────────────────────────────────────
- Yeni öğrenci eklemek için:
  Sol menüden "Öğrenci Yönetimi" > "Yeni Öğrenci Ekle" butonuna tıklayın
  
- Öğrenci listesini görmek için:
  Ana menüden "Öğrenci Yönetimi" sayfasına gidin
  
- Öğrenci düzenlemek için:
  Öğrenci listesinde ilgili öğrencinin yanındaki "Düzenle" butonunu kullanın
  
- RAM raporu yüklemek için:
  Öğrenci düzenleme sayfasında "RAM Raporu" bölümünden PDF dosyası yükleyebilirsiniz
  
- Öğrenci fotoğrafı eklemek için:
  Öğrenci düzenleme sayfasında fotoğraf alanına tıklayın, fotoğrafı kesin ve kaydedin

📝 DERS PROGRAMI OLUŞTURMA
─────────────────────────────────────────────────────────────
- Ders eklemek için:
  "Ders Programı" > "Program Oluştur" menüsünden tarihi seçin
  Takvim görünümünde istediğiniz güne tıklayarak ders ekleyebilirsiniz
  
- Ders bilgileri:
  - Öğretmen seçin
  - Öğrenci(ler) seçin
  - Saat aralığını belirleyin
  - Ders tipini seçin (Bireysel/Grup, Normal/Telafi)
  
- Sabit program için:
  "Sabitler" menüsünden haftalık düzenli derslerinizi tanımlayın
  Sabit program tanımladığınızda, bu dersler otomatik olarak takviminize eklenir

📊 RAPOR VE İSTATİSTİKLER
─────────────────────────────────────────────────────────────
- Aylık raporlar için:
  "Sistem Yönetimi" > "Aylık Raporlar" sayfasını kullanın
  
- Öğrenci gelişim notları:
  Öğrenci detay sayfasındaki "Gelişim Günlüğü" sekmesinden erişebilirsiniz
  "Yeni Not Ekle" butonuyla öğrenci hakkında gözlemlerinizi kaydedin

📢 DUYURU OLUŞTURMA
─────────────────────────────────────────────────────────────
- Duyuru yayınlamak için:
  "Duyurular" menüsünden "Yeni Duyuru" butonuna tıklayın
  
- Duyuru içeriği:
  - Başlık girin
  - İçeriği yazın
  - Hedef kitleyi seçin (Herkese/Öğretmenler/Veliler)
  - "Yayınla" butonuyla duyurunuz sisteme girer

📁 VERİ YÜKLEME
─────────────────────────────────────────────────────────────
- Excel'den öğrenci yüklemek için:
  "Sistem Yönetimi" > "İçe Aktar" > "Öğrenciler" sayfasına gidin
  Excel dosyanızın örnek formata uygun olması önemlidir
  
- Ders hakları güncellemek için:
  "Sistem Yönetimi" > "İçe Aktar" > "Ders Hakları" menüsünü kullanın
  Toplu güncelleme için Excel formatını kullanabilirsiniz

🔐 KULLANICI YÖNETİMİ
─────────────────────────────────────────────────────────────
- Yeni kullanıcı eklemek için:
  "Sistem Yönetimi" > "Kullanıcılar" > "Yeni Kullanıcı" butonunu kullanın
  
- Rolleri doğru atamaya dikkat edin:
  - admin: Tam yetki
  - yonetici: Yönetim işlemleri
  - mudur: Üst düzey raporlama
  - sekreter: Program ve ders yönetimi
  - ogretmen: Kendi öğrencileri ve dersleri
  - veli: Sadece kendi çocuğunu görüntüleme

💡 İPUÇLARI
─────────────────────────────────────────────────────────────
- Sabit program kullanın: Düzenli gelen öğrenciler için sabit program tanımlayın
- Gelişim notları yazın: Öğrenci gelişimini düzenli takip edin
- RAM raporlarını yükleyin: Eğitim planlaması için kritik öneme sahip
- Ders haklarını takip edin: Azalan haklar için sistem size uyarı verir

EOT;
    }

    /**
     * Sık sorulan sorular
     */
    public static function getFAQ(): array
    {
        return [
            'ders nasıl eklenir' => 'Ders eklemek için "Ders Programı" menüsünden tarihi seçin, sonra takvimde istediğiniz saate tıklayarak ders bilgilerini girin. Öğretmen, öğrenci, saat ve ders tipini seçip kaydedin.',
            
            'ders ekle' => 'Ders eklemek için "Ders Programı" menüsünden tarihi seçin, sonra takvimde istediğiniz saate tıklayarak ders bilgilerini girin. Öğretmen, öğrenci, saat ve ders tipini seçip kaydedin.',
            
            'öğrenci nasıl eklenir' => 'Sol menüden "Öğrenci Yönetimi" sayfasına gidin ve sağ üstteki "Yeni Öğrenci Ekle" butonuna tıklayın. Formu doldurup kaydedin. TCKN, ad, soyad ve veli bilgileri zorunludur.',
            
            'öğrenci ekle' => 'Sol menüden "Öğrenci Yönetimi" sayfasına gidin ve sağ üstteki "Yeni Öğrenci Ekle" butonuna tıklayın. Formu doldurup kaydedin. TCKN, ad, soyad ve veli bilgileri zorunludur.',
            
            'ram raporu nasıl yüklenir' => 'Öğrenci detay sayfasında "Düzenle" butonuna tıklayın. Açılan formda "RAM Raporu" bölümünden PDF dosyanızı yükleyebilirsiniz. Dosya yüklendikten sonra kaydedin.',
            
            'ram yükle' => 'Öğrenci detay sayfasında "Düzenle" butonuna tıklayın. Açılan formda "RAM Raporu" bölümünden PDF dosyanızı yükleyebilirsiniz.',
            
            'toplu öğrenci nasıl yüklenir' => '"Sistem Yönetimi" > "İçe Aktar" > "Öğrenciler" sayfasından Excel dosyanızı yükleyebilirsiniz. Dosyanızın örnek formata uygun olması gerekir. Örnek dosyayı sayfadan indirebilirsiniz.',
            
            'toplu yükleme' => '"Sistem Yönetimi" > "İçe Aktar" > "Öğrenciler" sayfasından Excel dosyanızı yükleyebilirsiniz. Dosyanızın örnek formata uygun olması gerekir.',
            
            'ders hakkı nasıl güncellenir' => 'İki yol var: 1) Öğrenci düzenleme sayfasından manuel güncelleyebilirsiniz. 2) "Sistem Yönetimi" > "İçe Aktar" > "Ders Hakları" menüsünden toplu Excel yükleyebilirsiniz.',
            
            'ders hakkı güncelle' => 'İki yol var: 1) Öğrenci düzenleme sayfasından manuel güncelleyebilirsiniz. 2) "İçe Aktar" > "Ders Hakları" menüsünden toplu Excel yükleyebilirsiniz.',
            
            'duyuru nasıl yapılır' => '"Duyurular" menüsünden "Yeni Duyuru" butonuna tıklayın. Başlık ve içeriği yazın, hedef kitleyi seçin (Herkese/Öğretmenler/Veliler) ve yayınlayın.',
            
            'duyuru yap' => '"Duyurular" menüsünden "Yeni Duyuru" butonuna tıklayın. Başlık ve içeriği yazın, hedef kitleyi seçin ve yayınlayın.',
            
            'sabit program nedir' => 'Sabit program, her hafta aynı gün ve saatte düzenli olarak yapılan derslerdir. "Ders Programı" > "Sabitler" menüsünden tanımlarsanız, bu dersler otomatik olarak takviminize eklenir. Örneğin: Her Salı 14:00\'da Ayşe ile matematik dersi.',
            
            'sabit program' => 'Sabit program, her hafta aynı gün ve saatte düzenli olarak yapılan derslerdir. "Sabitler" menüsünden tanımlayabilirsiniz.',
            
            'gelişim notu nasıl yazılır' => 'Öğrenci detay sayfasındaki "Gelişim Günlüğü" sekmesine gidin. "Yeni Not Ekle" butonuyla öğrenci hakkında gözlemlerinizi kaydedin. Notlarınız tarihli olarak saklanır.',
            
            'gelişim notu' => 'Öğrenci detay sayfasındaki "Gelişim Günlüğü" sekmesine gidin. "Yeni Not Ekle" butonuyla gözlemlerinizi kaydedin.',
            
            'öğretmen nasıl atanır' => '"Sistem Yönetimi" > "Kullanıcılar" sayfasından yeni kullanıcı oluştururken "Rol" kısmından "Öğretmen" seçin. Kullanıcı bilgilerini doldurup kaydedin.',
            
            'öğretmen ekle' => '"Sistem Yönetimi" > "Kullanıcılar" sayfasından yeni kullanıcı oluşturun ve rol olarak "Öğretmen" seçin.',
            
            'rapor nasıl alınır' => '"Sistem Yönetimi" > "Aylık Raporlar" sayfasından ay seçerek detaylı raporları görüntüleyebilirsiniz. İstatistikler ve grafikler otomatik oluşturulur.',
            
            'rapor al' => '"Sistem Yönetimi" > "Aylık Raporlar" sayfasından ay seçerek detaylı raporları görüntüleyebilirsiniz.',
        ];
    }

    /**
     * Duyuru taslakları
     */
    public static function getAnnouncementTemplates(): array
    {
        return [
            'tatil' => [
                'title' => 'Resmi Tatil Bildirimi',
                'content' => "Sayın Velilerimiz,\n\n[TARİH] tarihinde resmi tatil nedeniyle kurumumuz kapalı olacaktır. Dersleriniz [YENİ TARİH] tarihine ertelenmiştir.\n\nBilgilerinize sunarız.\n\nİkihece Özel Eğitim Kurumu"
            ],
            'toplanti' => [
                'title' => 'Veli Toplantısı',
                'content' => "Sayın Velilerimiz,\n\n[TARİH] tarihinde saat [SAAT]'te veli toplantımız olacaktır. Çocuklarınızın eğitim süreçlerini değerlendirmek üzere sizleri bekliyoruz.\n\nKatılımlarınızı rica ederiz.\n\nİkihece Özel Eğitim Kurumu"
            ],
            'toplantı' => [
                'title' => 'Veli Toplantısı',
                'content' => "Sayın Velilerimiz,\n\n[TARİH] tarihinde saat [SAAT]'te veli toplantımız olacaktır. Çocuklarınızın eğitim süreçlerini değerlendirmek üzere sizleri bekliyoruz.\n\nKatılımlarınızı rica ederiz.\n\nİkihece Özel Eğitim Kurumu"
            ],
            'etkinlik' => [
                'title' => 'Özel Etkinlik Duyurusu',
                'content' => "Değerli Velilerimiz,\n\n[TARİH] tarihinde [ETKİNLİK ADI] etkinliğimiz düzenlenecektir. Çocuklarınızla birlikte katılmanızı bekliyoruz.\n\nDetaylar için lütfen iletişime geçiniz.\n\nİkihece Özel Eğitim Kurumu"
            ]
        ];
    }

    /**
     * Esprili yanıtlar için havadan sudan konuşma temaları
     */
    public static function getCasualResponses(): array
    {
        return [
            'merhaba' => [
                'Merhaba! Ben İkihece\'nin dijital asistanıyım. Size nasıl yardımcı olabilirim? ☕',
                'Selam! Umarım güzel bir gün geçiriyorsunuzdur. Ben buradayım, sorun!',
                'Hey! Bugün hangi konuda kafa yoracağız bakalım? 😊',
                'Merhaba! Yeni bir gün, yeni fırsatlar! Size nasıl yardımcı olabilirim?',
                'Selam! Asistanınız hazır ve nazır. Ne yapmak istersiniz?'
            ],
            'nasılsın' => [
                'Ben bir yapay zeka olarak her zaman iyiyim! 😄 Siz nasılsınız?',
                'Bugün formum yerinde! Sizin gününüz nasıl geçiyor?',
                'İyiyim, teşekkürler! Umarım siz de iyisinizdir. İşleriniz nasıl?',
                'Harikayım! Özellikle size yardımcı olabileceğim için mutluyum. Siz nasılsınız?',
                'Mükemmel! Sizin için burada olmak güzel. Nasıl gidiyor işler?'
            ],
            'teşekkür' => [
                'Rica ederim! Yardımcı olabildiysem ne mutlu bana 😊',
                'Her zaman! Ben bunun için buradayım.',
                'Estağfurullah! Başka bir konuda da yardımcı olabilirim.',
                'Ne demek! Size yardımcı olmak benim işim 💪',
                'Görevim! Başka sorunuz varsa çekinmeyin.'
            ],
            'şaka' => [
                'Öğretmen öğrenciye sormuş: "Hangi yazılımı kullanıyorsun?" Öğrenci: "İkihece!" demiş. "Peki memnun musun?" "Çok! Özellikle yapay zeka asistanı harika!" 😄',
                'RAM raporu okumak kolay mı? Hayır ama ben okuyorum! 📚',
                'Ders programı yapmak sanıldığı kadar zor değil, doğru asistan olursa! 🎯',
                'Neden öğretmenler kahve içer? Çünkü ders hakkı gibi enerjileri de tükeniyor! ☕😄',
                'Sabit program nedir bilir misiniz? Hayatın kendisi! Her hafta aynı gün, aynı saat... 📅'
            ],
            'yoruldum' => [
                'Anlıyorum... Bu işler yorucu olabiliyor. Bir kahve molası verebilirsiniz. Ben burada sizi bekleyeceğim! ☕',
                'O zaman size kolay bir işle başlayalım. Ne dersiniz?',
                'Normal! Özel eğitim ciddi bir meslek. Ama unutmayın, yaptığınız iş çok değerli! 💪',
                'Biraz mola verin kendinize. İnsan değilsiniz demeyin, benim bile ara sıra token limitim doluyor! 😄',
                'Yorgunluk olur. Ama baksanıza kaç öğrencinin hayatına dokunuyorsunuz! Bu muhteşem bir şey.'
            ],
            'günaydın' => [
                'Günaydın! Yeni bir gün, yeni fırsatlar! Bugün ne yapmak istersiniz?',
                'Günaydın! Umarım kahveniz güzeldir, çünkü bugün çok işimiz var! ☕',
                'Günaydın! Harika bir gün olacak, hissediyorum! 🌞'
            ],
            'iyi geceler' => [
                'İyi geceler! Yarın görüşürüz, ben burada bekliyor olacağım! 🌙',
                'İyi geceler! Güzel rüyalar, yarın yine işbaşındayım!',
                'İyi uykular! Dinlenin, yarın yine öğrencilerimiz için çalışacağız! 😴'
            ]
        ];
    }

    /**
     * Veritabanı şeması açıklamaları (insan diline çevrilmiş)
     */
    public static function getDatabaseExplanations(): array
    {
        return [
            'students' => 'Öğrencilerin tüm bilgilerini saklar: ad, soyad, TCKN, adres, veli bilgileri, ders hakları, RAM bilgileri',
            'lessons' => 'Yapılmış ve planlanmış derslerin kaydı: hangi öğretmen, hangi öğrenciyle, ne zaman, hangi tipte (bireysel/grup)',
            'lesson_history' => 'Geçmiş ders kayıtları: Silinmiş dersler dahil tüm ders geçmişi burada tutulur (history tablosu)',
            'fixed_lessons' => 'Haftalık sabit ders programı: Her pazartesi saat 10:00\'da Ali öğretmen ile matematik gibi düzenli dersler',
            'users' => 'Sistemdeki tüm kullanıcılar: adminler, öğretmenler, veliler, sekreterler',
            'user_profiles' => 'Kullanıcıların detay bilgileri: ad, soyad, telefon, adres, branş',
            'reports' => 'Aylık faaliyet raporları: Kaç ders yapıldı, hangi öğretmenler ne kadar çalıştı',
            'announcements' => 'Sistemdeki duyurular: Başlık, içerik, kime gösterileceği, ne zaman yayınlandığı',
            'logs' => 'Sistem işlem kayıtları: Kim, ne zaman, ne yaptı (güvenlik ve takip için)',
            'evaluations' => 'Öğrenci gelişim günlüğü: Öğretmenlerin öğrenciler hakkında yazdığı notlar ve gözlemler',
            'institution' => 'Kurum bilgileri: Kurum adı, adresi, telefon, vergi bilgileri gibi temel bilgiler',
        ];
    }
}