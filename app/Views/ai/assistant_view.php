<?= $this->extend('layouts/chat') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>

<header class="bg-white border-bottom shadow-sm py-3 px-4 d-flex align-items-center justify-content-between flex-shrink-0">
    <div class="d-flex align-items-center gap-3">
        <div class="">
            <i class="bi bi-robot text-success fs-2"></i>
        </div>
        <div>
            <h1 class="fs-5 fw-semibold text-dark mb-0">İkihece AI Asistanı</h1>
            <p class="small text-muted mb-0">Yalnızca referans amaçlıdır.</p>
        </div>
    </div>
</header>

<main id="chatContainer" class="flex-grow-1 overflow-y-auto custom-scrollbar" style="min-height: calc(100vh - var(--footer-height) - var(--navbar-height));">
    <div id="chat-content" class="container-md d-flex flex-column gap-3 py-3">
        <?php if (empty($chatHistory)): ?>
            <div class="h-100 d-flex flex-column align-items-center justify-content-center text-center mx-auto py-5" style="max-width: 28rem;">
                <div class="">
                    <i class="bi bi-robot text-success fs-2"></i>
                </div>
                <h2 class="h2 fw-bold text-body-emphasis mb-2">Merhaba, ben İkihece AI</h2>
                <p class="text-secondary">Size nasıl yardımcı olabilirim?</p>
            </div>
        <?php else: ?>
            <?php foreach ($chatHistory as $turn): ?>
                <div class="d-flex justify-content-end">
                    <div style="max-width: 85%;">
                        <div class="bg-success text-white rounded-4 rounded-bottom-end-0 px-4 py-3 shadow-sm">
                            <p class="mb-0" style="white-space: pre-wrap;"><?= esc($turn['user']) ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-start">
                    <div style="max-width: 85%;">
                        <div class="bg-white border rounded-4 rounded-bottom-start-0 px-4 py-3 shadow-sm">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="">
                                    <i class="bi bi-robot text-success"></i>
                                </div>
                                <span class="fw-semibold text-success small">İkihece AI</span>
                            </div>
                            <div class="markdown-content text-dark"><?= $turn['ai'] ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<footer id="chat-footer" class="bg-white border-top" style="height: var(--footer-height);">
    <form id="messageForm" class="container-md h-100 d-flex align-items-center py-2" action="<?= route_to('ai.process') ?>" method="post">
        <?= csrf_field() ?>
        <div class="d-flex align-items-center w-100">
            <textarea name="message" id="messageInput" class="form-control flex-grow-1 bg-light border-0 me-2" style="resize: none;" placeholder="Mesajınızı buraya yazın..." rows="1" required autofocus></textarea>
            
            <button type="submit" id="sendButton" class="btn btn-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                <i class="bi bi-send-fill"></i>
            </button>
        </div>
    </form>
</footer>

<?= $this->endSection() ?>