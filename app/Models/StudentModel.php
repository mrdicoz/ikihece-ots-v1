<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table            = 'students';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
   protected $allowedFields    = [
        'okul_no', 'tc_kimlik_no', 'adi', 'soyadi', 'cinsiyet', 'dogum_tarihi',
        'kayit_tarihi', 'sinifi', 'subesi', 'gelis_donemi', 'ayrilis_tarihi',
        'ayrilis_nedeni', 'servis_durumu', 'servis_plakasi', 'veli_anne_tc',
        'veli_anne_adi_soyadi', 'veli_anne_telefon', 'veli_anne_eposta',
        'veli_anne_is_adresi', 'veli_anne_gorevi', 'veli_anne_mezuniyet',
        'veli_anne_sag_durumu', 'veli_baba_tc', 'veli_baba_adi_soyadi',
        'veli_baba_telefon', 'veli_baba_eposta', 'veli_baba_is_adresi',
        'veli_baba_gorevi', 'veli_baba_mezuniyet', 'veli_baba_sag_durumu',
        'acil_durum_aranacak_kisi_1_adi', 'acil_durum_aranacak_kisi_1_yakinlik',
        'acil_durum_aranacak_kisi_1_telefon', 'acil_durum_aranacak_kisi_2_adi',
        'acil_durum_aranacak_kisi_2_yakinlik', 'acil_durum_aranacak_kisi_2_telefon',
        'kan_grubu', 'gecirilen_hastaliklar', 'alerjiler', 'ameliyatlar',
        'ilaclar', 'diyet_durumu', 'engel_durumu', 'boy', 'kilo', 'goz_sorunu',
        'isitsel_sorun', 'kardes_var_mi', 'kardes_okulumuzda_mi', 'kardes_adi_1',
        'kardes_dogum_tarihi_1', 'kardes_okulu_1', 'kardes_adi_2',
        'kardes_dogum_tarihi_2', 'kardes_okulu_2', 'adres_il', 'adres_ilce',
        'adres_mahalle', 'adres_detay', 'sozlesme_no', 'sozlesme_tarihi',
        'sozlesme_tutari', 'odeme_sekli', 'google_konum', 'profile_image',
        'ram_raporu', 'ram_baslangic_tarihi', 'ram_bitis_tarihi'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}