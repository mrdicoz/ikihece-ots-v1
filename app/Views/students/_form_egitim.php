<h5 class="mb-3">Eğitim Bilgileri</h5>
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">Örgün Eğitime Gidiyor mu?</label><select id="orgun_egitim" name="orgun_egitim" class="form-select">
        <option value="evet" <?= (old('orgun_egitim', $student['orgun_egitim'] ?? '') === 'evet') ? 'selected' : '' ?>>Evet</option>
        <option value="hayir" <?= (old('orgun_egitim', $student['orgun_egitim'] ?? '') === 'hayir') ? 'selected' : '' ?>>Hayır</option>
    </select></div>
    <div class="col-md-6"><label class="form-label">Eğitim Şekli</label><select id="egitim_sekli" name="egitim_sekli" class="form-select">
        <option value="tam gün" <?= (old('egitim_sekli', $student['egitim_sekli'] ?? '') === 'tam gün') ? 'selected' : '' ?>>Tam Gün</option>
        <option value="öğlenci" <?= (old('egitim_sekli', $student['egitim_sekli'] ?? '') === 'öğlenci') ? 'selected' : '' ?>>Öğlenci</option>
        <option value="sabahcı" <?= (old('egitim_sekli', $student['egitim_sekli'] ?? '') === 'sabahcı') ? 'selected' : '' ?>>Sabahçı</option>
    </select></div>
<div class="col-12">
    <label for="egitim_programi" class="form-label">Eğitim Program(lar)ı</label>
    
    <?php
        // Form gönderiminden sonra bir hata olursa, daha önce seçilmiş olan
        // eğitim programlarını bir dizi olarak alıyoruz. Eğer daha önce
        // bir seçim yapılmadıysa, boş bir dizi oluşturuyoruz.
        $selectedPrograms = old('egitim_programi', []);
    ?>

    <select id="egitim_programi" name="egitim_programi[]" multiple>
        <option value="Bedensel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı" 
            <?= in_array('Bedensel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı', $selectedPrograms) ? 'selected' : '' ?>>
            Bedensel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı
        </option>

        <option value="Dil ve Konuşma Bozukluğu Olan Bireyler İçin Destek Eğitim Programı" 
            <?= in_array('Dil ve Konuşma Bozukluğu Olan Bireyler İçin Destek Eğitim Programı', $selectedPrograms) ? 'selected' : '' ?>>
            Dil ve Konuşma Bozukluğu Olan Bireyler İçin Destek Eğitim Programı
        </option>

        <option value="Zihinsel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı" 
            <?= in_array('Zihinsel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı', $selectedPrograms) ? 'selected' : '' ?>>
            Zihinsel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı
        </option>

        <option value="Öğrenme Güçlüğü Olan Bireyler İçin Destek Eğitim Programı" 
            <?= in_array('Öğrenme Güçlüğü Olan Bireyler İçin Destek Eğitim Programı', $selectedPrograms) ? 'selected' : '' ?>>
            Öğrenme Güçlüğü Olan Bireyler İçin Destek Eğitim Programı
        </option>

        <option value="Otizm Spektrum Bozukluğu Olan Bireyler İçin Destek Eğitim Programı" 
            <?= in_array('Otizm Spektrum Bozukluğu Olan Bireyler İçin Destek Eğitim Programı', $selectedPrograms) ? 'selected' : '' ?>>
            Otizm Spektrum Bozukluğu Olan Bireyler İçin Destek Eğitim Programı
        </option>
    </select>
</div>
</div>