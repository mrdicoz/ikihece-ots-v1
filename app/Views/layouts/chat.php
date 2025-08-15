<!DOCTYPE html>
<html lang="tr" class="h-100">
<head>
    <?= $this->include('layouts/partials/_head') ?>
    <title><?= esc($title ?? 'İkihece AI Asistanı') ?></title>
    
    <style>
        :root {
            --footer-height: 80px;
            --navbar-height: 60px;
        }
        
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        
        #app-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: relative;
            padding-top: var(--navbar-height);
        }
        
        #chatContainer {
            flex: 1;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            padding-bottom: var(--footer-height);
            will-change: transform;
            backface-visibility: hidden;
        }
        
        #chat-content {
            min-height: calc(100% - var(--footer-height));
            padding: 1rem;
        }
        
        #chat-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: var(--footer-height);
            background: white;
            border-top: 1px solid #dee2e6;
            padding: 0.75rem;
            z-index: 1000;
        }

        @keyframes pulse { 
            0%, 100% { opacity: 1; } 
            50% { opacity: 0.5; } 
        }
        
        .animate-pulse { 
            animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite; 
        }
        
        .custom-scrollbar::-webkit-scrollbar { 
            width: 6px; 
        }
        
        .custom-scrollbar::-webkit-scrollbar-track { 
            background: #f1f1f1; 
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb { 
            background: #c1c1c1; 
            border-radius: 10px; 
        }
        
        .markdown-content p:last-child { 
            margin-bottom: 0; 
        }
        
        .markdown-content ul { 
            list-style-type: disc; 
            padding-left: 1.5rem; 
        }
        
        .markdown-content ol { 
            list-style-type: decimal; 
            padding-left: 1.5rem; 
        }
        
        .typing-indicator {
            display: inline-flex;
            gap: 0.5rem;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #6c757d;
            animation: typingAnimation 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(1) { animation-delay: 0s; }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typingAnimation {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.6; }
            30% { transform: translateY(-5px); opacity: 1; }
        }

        @media (max-width: 768px) {
            :root {
                --footer-height: 70px;
            }
            
            #chat-footer {
                padding: 0.5rem;
            }
        }
    </style>
    <?= $this->renderSection('pageStyles') ?>
</head>

<body class="d-flex flex-column h-100 bg-light">
    <?= $this->include('layouts/partials/_navbar') ?>

    <div id="app-container">
        <?= $this->renderSection('main') ?>
    </div>

    <?= $this->include('layouts/partials/_scripts') ?>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <?= $this->renderSection('pageScripts') ?>
    
    <script>
    $(document).ready(function() {
        // HTML escape fonksiyonu
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        const chatContainer = $('#chatContainer');
        const chatContent = $('#chat-content');
        const messageForm = $('#messageForm');
        const sendButton = $('#sendButton');
        const messageInput = $('#messageInput');
        const csrfName = '<?= csrf_token() ?>';
        const csrfHash = '<?= csrf_hash() ?>';

        // Scroll fonksiyonları
        function initializeScroll() {
            const container = chatContainer[0];
            container.scrollTop = container.scrollHeight;
        }

        function scrollToBottom() {
            const container = chatContainer[0];
            container.scrollTo({
                top: container.scrollHeight,
                behavior: 'smooth'
            });
        }

        // Markdown ayarları
        marked.setOptions({
            breaks: true,
            gfm: true
        });

        // Mevcut içeriği işle
        $('.markdown-content').each(function() {
            const rawText = $(this).html();
            $(this).html(marked.parse(rawText));
        });

        // Sayfa yüklendiğinde
        initializeScroll();

        // Textarea ayarları
        messageInput.on('input', function() {
            this.style.height = 'auto';
            const maxHeight = 150;
            this.style.height = Math.min(this.scrollHeight, maxHeight) + 'px';
            
            // Footer yüksekliğini güncelle
            const newFooterHeight = Math.min(this.scrollHeight + 30, 180);
            document.documentElement.style.setProperty('--footer-height', `${newFooterHeight}px`);
        });

        // Enter ile gönder
        messageInput.on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                messageForm.submit();
            }
        });
        
        // Form gönderimi
        messageForm.on('submit', function(e) {
            e.preventDefault();
            const userMessage = messageInput.val().trim();
            if (userMessage === '') return;

            // Kullanıcı mesajını ekle
            const userBubble = `
                <div class="d-flex justify-content-end mb-3">
                    <div style="max-width: 85%;">
                        <div class="bg-success text-white rounded-4 rounded-bottom-end-0 px-4 py-3 shadow-sm">
                            <p class="mb-0" style="white-space: pre-wrap;">${escapeHtml(userMessage)}</p>
                        </div>
                    </div>
                </div>`;
            chatContent.append(userBubble);
            messageInput.val('').css('height', 'auto').focus();
            scrollToBottom();

            // Yazıyor animasyonu
            const typingBubble = `
                <div class="d-flex justify-content-start mb-3" id="typing-bubble">
                    <div style="max-width: 85%;">
                        <div class="bg-white border rounded-4 rounded-bottom-start-0 px-4 py-3 shadow-sm">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="">
                                    <i class="bi bi-robot text-success"></i>
                                </div>
                                <span class="fw-semibold text-success small">İkihece AI</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="typing-indicator">
                                    <div class="typing-dot"></div>
                                    <div class="typing-dot"></div>
                                    <div class="typing-dot"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            chatContent.append(typingBubble);
            scrollToBottom();
            sendButton.prop('disabled', true);

            // AJAX isteği
            let formData = { [csrfName]: csrfHash, message: userMessage };

            $.ajax({
                url: '<?= route_to("ai.process") ?>',
                type: 'POST',
                data: formData,
                success: function(data) {
                    $('#typing-bubble').remove();
                    
                    const aiBubble = `
                        <div class="d-flex justify-content-start mb-3">
                            <div style="max-width: 85%;">
                                <div class="bg-white border rounded-4 rounded-bottom-start-0 px-4 py-3 shadow-sm">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="">
                                            <i class="bi bi-robot text-success"></i>
                                        </div>
                                        <span class="fw-semibold text-success small">İkihece AI</span>
                                    </div>
                                    <div class="markdown-content text-dark" id="streaming-response"></div>
                                </div>
                            </div>
                        </div>`;
                    chatContent.append(aiBubble);
                    scrollToBottom();
                    
                    // Akıcı yazma efekti
                    const responseDiv = $('#streaming-response');
                    const fullText = data.response;
                    let i = 0;
                    
                    const interval = setInterval(() => {
                        if (i < fullText.length) {
                            responseDiv.html(marked.parse(fullText.substring(0, i + 1)));
                            scrollToBottom();
                            i++;
                        } else {
                            clearInterval(interval);
                            responseDiv.removeAttr('id').addClass('markdown-content');
                        }
                    }, 20);
                },
                error: function() {
                    $('#typing-bubble').remove();
                    const errorBubble = `
                        <div class="d-flex justify-content-start mb-3">
                            <div style="max-width: 85%;">
                                <div class="alert alert-danger mb-0">Bir hata oluştu, lütfen tekrar deneyin.</div>
                            </div>
                        </div>`;
                    chatContent.append(errorBubble);
                    scrollToBottom();
                },
                complete: function() {
                    sendButton.prop('disabled', false);
                }
            });
        });
    });
    </script>
</body>
</html>