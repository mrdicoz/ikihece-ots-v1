<!DOCTYPE html>
<html lang="tr" data-bs-theme="auto">
<head>
    <?= $this->include('layouts/partials/_head') ?>
</head>
<body>
    <?= $this->include('layouts/partials/_session_messages') ?>
    <?= $this->include('layouts/partials/_navbar') ?>

    <main class="container py-4 mt-5">
        <?php
    // Her sayfada lisans servisini ve kalan günleri kontrol edelim.
    $licenseService = new \App\Libraries\LicenseService();
    $daysRemaining = $licenseService->getDaysRemaining();
?>

<div class="container pt-3"> 

    <?php
        if (auth()->loggedIn() && auth()->user()->inGroup('admin', 'yonetici')) {
            if (isset($daysRemaining) && $daysRemaining !== null && $daysRemaining <= 10) {
        ?>

            <div class="alert alert-danger" role="alert">
<strong>Uyarı!</strong> Lisansınızın sona ermesine sadece <strong><?= $daysRemaining ?> gün</strong> kaldı. Sorun yaşamamak için lütfen lisansınızı <a href="https://mantaryazilim.tr/ikihece-okul-takip-sistemi-v1/" class="alert-link">yenileyin</a>.
</div>
        <?php
            }
        }
        ?>

</div>
        <?= $this->renderSection('main') ?>
    </main>

    <?= $this->include('layouts/partials/_footer') ?>

    <?= $this->include('layouts/partials/_scripts') ?>
    <?= $this->renderSection('scripts') ?>

<div class="modal fade" id="roleSwitcherModal" tabindex="-1" aria-labelledby="roleSwitcherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roleSwitcherModalLabel"><i class="bi bi-people"></i> Görünümü Değiştir</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Lütfen devam etmek istediğiniz rolü seçin:</p>
                <div class="list-group">
                    <?php // Değişkenler zaten mevcut olduğu için buradaki PHP bloğunu sildik. ?>
                    <?php if(isset($userGroups)): // Sadece değişkenin varlığını kontrol etmek yeterli ?>
                        <?php foreach ($userGroups as $group): ?>
                            <a href="<?= route_to('user.switchRole', strtolower($group)) ?>" 
                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= (strtolower($group) === $activeRole) ? 'active' : '' ?>">
                                <?= esc(ucfirst($group)) ?>
                                <?php if (strtolower($group) === $activeRole): ?>
                                    <span class="badge bg-light text-success rounded-pill"><i class="bi bi-check-circle-fill"></i> Aktif</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


    <!-- AI Asistanı Tetikleyici Buton -->
    <button id="aiAssistantButton" class="btn btn-success rounded-pill shadow-lg d-flex align-items-center gap-2 position-fixed" 
            type="button" 
            data-bs-toggle="offcanvas" 
            data-bs-target="#aiAssistantOffcanvas" 
            aria-controls="aiAssistantOffcanvas"
            style="bottom: 30px; right: 30px; z-index: 1050; padding: 12px 20px; transition: all 0.3s ease;">
        <i class="bi bi-compass fs-5"></i>
        <span class="fw-semibold">Pusula'ya Sor</span>
    </button>

    <!-- AI Asistanı Offcanvas (Hostinger Tarzı) -->
    <div class="offcanvas offcanvas-end shadow-lg border-0" tabindex="-1" id="aiAssistantOffcanvas" aria-labelledby="aiAssistantOffcanvasLabel" style="width: 400px;">
      <div class="offcanvas-header bg-success text-white">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-compass fs-4"></i>
            <div>
                <h5 class="offcanvas-title mb-0" id="aiAssistantOffcanvasLabel">Pusula AI</h5>
                <small class="opacity-75">Geliştirme aşamasında bir yapayzeka</small>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Kapat"></button>
      </div>
      <div class="offcanvas-body p-0 d-flex flex-column bg-light">
        
        <!-- Sohbet Alanı -->
        <div id="aiChatContainer" class="flex-grow-1 overflow-y-auto p-3 custom-scrollbar" style="scroll-behavior: smooth;">
            <!-- Karşılama Mesajı -->
            <div class="d-flex justify-content-start mb-3">
                <div style="max-width: 85%;">
                    <div class="bg-white border rounded-4 rounded-bottom-start-0 px-3 py-2 shadow-sm">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-compass text-success"></i>
                            <span class="fw-bold text-success small">Pusula</span>
                        </div>
                        <p class="mb-0 small text-dark">Merhaba! Ben Pusula, asistanınız. 👋<br>Size nasıl yardımcı olabilirim?</p>
                    </div>
                </div>
            </div>
            <!-- Sohbet Geçmişi Buraya Yüklenecek -->
        </div>

        <!-- Mesaj Yazma Alanı -->
        <div class="p-3 bg-white border-top">
            <form id="aiMessageForm" class="d-flex gap-2 align-items-end">
                <textarea id="aiMessageInput" class="form-control bg-light border-0" rows="1" placeholder="Pusula'ya bir şeyler sor..." style="resize: none; min-height: 44px; max-height: 120px;"></textarea>
                <button type="submit" id="aiSendButton" class="btn btn-success rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; flex-shrink: 0;">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
            <div class="text-center mt-2">
                <small class="text-muted" style="font-size: 10px;">Pusula hata yapabilir. Önemli bilgileri kontrol edin.</small>
            </div>
        </div>

      </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- 1. Offcanvas ve Buton Yönetimi ---
    const aiAssistantButton = document.getElementById('aiAssistantButton');
    const aiOffcanvas = document.getElementById('aiAssistantOffcanvas');
    
    // Offcanvas açılmaya başladığında butonu gizle
    aiOffcanvas.addEventListener('show.bs.offcanvas', function () {
        aiAssistantButton.classList.remove('d-flex'); // Flex özelliğini kaldır
        aiAssistantButton.classList.add('d-none');    // Gizle
    });
    
    // Offcanvas tamamen kapandığında butonu geri getir
    aiOffcanvas.addEventListener('hidden.bs.offcanvas', function () {
        aiAssistantButton.classList.remove('d-none'); // Gizliliği kaldır
        aiAssistantButton.classList.add('d-flex');    // Flex özelliğini geri getir
    });
    
    // --- 2. Sohbet İşlevleri ---
    const chatContainer = document.getElementById('aiChatContainer');
    const messageForm = document.getElementById('aiMessageForm');
    const messageInput = document.getElementById('aiMessageInput');
    const sendButton = document.getElementById('aiSendButton');
    
    // Textarea otomatik yükseklik ayarı
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
        if(this.value === '') this.style.height = '44px';
    });

    // Enter tuşu ile gönderme (Shift+Enter satır atlar)
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault(); // Varsayılan alt satıra geçmeyi engelle
            
            // Formu manuel olarak tetikle
            // Not: dispatchEvent yerine requestSubmit kullanmak modern tarayıcılarda daha güvenlidir,
            // ancak eski kodunuzla uyumlu olması için dispatchEvent'i koruyoruz ve tetikliyoruz.
            var event = new Event('submit', {
                'bubbles': true,
                'cancelable': true
            });
            messageForm.dispatchEvent(event);
        }
    });

    // Sohbeti en alta kaydır
    function scrollToBottom() {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    // Mesaj Gönderme İşlemi
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Sayfa yenilenmesini engelle
        const message = messageInput.value.trim();
        if (!message) return;

        // A. Kullanıcı Mesajını Ekle
        const userBubble = `
            <div class="d-flex justify-content-end mb-3">
                <div style="max-width: 85%;">
                    <div class="bg-success text-white rounded-4 rounded-bottom-end-0 px-3 py-2 shadow-sm">
                        <p class="mb-0 small" style="white-space: pre-wrap;">${escapeHtml(message)}</p>
                    </div>
                </div>
            </div>`;
        chatContainer.insertAdjacentHTML('beforeend', userBubble);
        
        // Inputu temizle ve küçült
        messageInput.value = '';
        messageInput.style.height = '44px';
        scrollToBottom();

        // B. Yazıyor Animasyonu Ekle
        const loadingId = 'loading-' + Date.now();
        const loadingBubble = `
            <div id="${loadingId}" class="d-flex justify-content-start mb-3">
                <div style="max-width: 85%;">
                    <div class="bg-white border rounded-4 rounded-bottom-start-0 px-3 py-2 shadow-sm">
                        <div class="d-flex gap-1 align-items-center">
                            <div class="spinner-grow spinner-grow-sm text-success" role="status" style="width: 0.5rem; height: 0.5rem;"></div>
                            <div class="spinner-grow spinner-grow-sm text-success" role="status" style="width: 0.5rem; height: 0.5rem; animation-delay: 0.2s"></div>
                            <div class="spinner-grow spinner-grow-sm text-success" role="status" style="width: 0.5rem; height: 0.5rem; animation-delay: 0.4s"></div>
                        </div>
                    </div>
                </div>
            </div>`;
        chatContainer.insertAdjacentHTML('beforeend', loadingBubble);
        scrollToBottom();

        // C. AJAX İsteği
        const formData = new FormData();
        formData.append('message', message);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        fetch('<?= route_to("ai.process") ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            const loadingElement = document.getElementById(loadingId);
            if(loadingElement) loadingElement.remove();
            
            if (data.status === 'success') {
                const aiBubble = `
                    <div class="d-flex justify-content-start mb-3">
                        <div style="max-width: 85%;">
                            <div class="bg-white border rounded-4 rounded-bottom-start-0 px-3 py-2 shadow-sm">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="bi bi-compass text-success"></i>
                                    <span class="fw-bold text-success small">Pusula</span>
                                </div>
                                <div class="markdown-content small text-dark">${marked.parse(data.response)}</div>
                            </div>
                        </div>
                    </div>`;
                chatContainer.insertAdjacentHTML('beforeend', aiBubble);
            } else {
                // Hata Mesajı
                const errorBubble = `
                    <div class="d-flex justify-content-start mb-3">
                        <div class="alert alert-danger py-2 px-3 mb-0 small">
                            ${data.error || 'Bir hata oluştu.'}
                        </div>
                    </div>`;
                chatContainer.insertAdjacentHTML('beforeend', errorBubble);
            }
            scrollToBottom();
        })
        .catch(error => {
            console.error('Error:', error);
            const loadingElement = document.getElementById(loadingId);
            if(loadingElement) loadingElement.remove();
            
            chatContainer.insertAdjacentHTML('beforeend', '<div class="alert alert-danger py-2 px-3 mb-3 small">Bağlantı hatası oluştu.</div>');
            scrollToBottom();
        });
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #198754; border-radius: 3px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #157347; }
</style>

</body>
</html>
```