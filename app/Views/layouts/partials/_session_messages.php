<?php
// Session'da gösterilecek bir mesaj (hata, başarı vb.) olup olmadığını kontrol edelim.
$hasMessage = session()->has('errors') || session()->has('error') || session()->has('message');

// Mesaj türüne göre başlık ve renk belirleyelim.
if (session()->has('errors') || session()->has('error')) {
    $modalClass = 'bg-danger text-white';
    $modalTitle = session()->has('errors') ? 'Formda Hatalar Var!' : 'Bir Hata Oluştu!';
} else {
    $modalClass = 'bg-success text-white';
    $modalTitle = 'İşlem Başarılı!';
}
?>

<?php if ($hasMessage) : ?>
    <div class="modal fade" id="sessionMessageModal" tabindex="-1" aria-labelledby="sessionMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header <?= $modalClass ?>">
                    <h5 class="modal-title" id="sessionMessageModalLabel"><?= $modalTitle ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (session()->has('errors')) : ?>
                        <p>Lütfen aşağıdaki alanları kontrol edip tekrar deneyin:</p>
                        <ul class="mb-0" style="padding-left: 20px;">
                            <?php foreach (session('errors') as $error) : ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach ?>
                        </ul>
                    <?php elseif (session()->has('error')) : ?>
                        <p><?= session('error') ?></p>
                    <?php else : ?>
                        <p><?= session('message') ?></p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sayfanın tamamen yüklenmesini bekle
        document.addEventListener('DOMContentLoaded', function () {
            // Modal elementini ID'si ile bul ve bir örneğini oluştur
            var sessionModal = new bootstrap.Modal(document.getElementById('sessionMessageModal'));
            // Modalı göster
            sessionModal.show();
        });
    </script>
<?php endif; ?>