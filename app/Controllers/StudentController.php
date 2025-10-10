<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\CityModel;
use App\Models\DistrictModel;
use App\Models\StudentEvaluationModel;
use App\Models\RamReportAnalysisModel;


class StudentController extends BaseController
{
// app/Controllers/StudentController.php

public function index()
{
    $model = new StudentModel();
    $districtModel = new DistrictModel();
    $fixedLessonModel = new \App\Models\FixedLessonModel();

    // GET üzerinden gelen tüm filtreleme parametrelerini alıyoruz.
    $filterDistrict = $this->request->getGet('district_id');
    $filterMesafe   = $this->request->getGet('mesafe');
    $filterSabitDurum = $this->request->getGet('sabit_durum');
    $filterSabitGun   = $this->request->getGet('sabit_gun');
    $filterProgram    = $this->request->getGet('egitim_programi'); // YENİ FİLTRE

    $query = $model;

    // İlçe ve Mesafe filtreleri
    if ($filterDistrict && is_numeric($filterDistrict)) {
        $query->where('district_id', $filterDistrict);
    }
    if ($filterMesafe && in_array($filterMesafe, ['Civar', 'Yakın', 'Uzak'])) {
        $query->where('mesafe', $filterMesafe);
    }

    // Sabit Ders Filtresi
    if ($filterSabitDurum === 'eklenen') {
        $fixedQuery = $fixedLessonModel->distinct()->select('student_id');
        if ($filterSabitGun && is_numeric($filterSabitGun)) {
            $fixedQuery->where('day_of_week', $filterSabitGun);
        }
        $fixedStudentIds = $fixedQuery->findColumn('student_id');
        if (empty($fixedStudentIds)) {
            $query->where('id', 0);
        } else {
            $query->whereIn('id', $fixedStudentIds);
        }
    } elseif ($filterSabitDurum === 'eklenmeyen') {
        $fixedStudentIds = $fixedLessonModel->distinct()->findColumn('student_id');
        if (!empty($fixedStudentIds)) {
            $query->whereNotIn('id', $fixedStudentIds);
        }
    }
    
    // --- YENİ EĞİTİM PROGRAMI FİLTRESİ ---
    if ($filterProgram && !empty($filterProgram)) {
        // egitim_programi alanı 'program1,program2' gibi olduğu için LIKE ile arama yapıyoruz.
        $query->like('egitim_programi', $filterProgram);
    }
    // --- YENİ FİLTRE SONU ---

    $students = $query->orderBy('adi', 'ASC')->findAll();

    $districts = $districtModel
        ->distinct()
        ->select('districts.id, districts.name')
        ->join('students', 'students.district_id = districts.id')
        ->orderBy('districts.name', 'ASC')
        ->findAll();

    // View'de dropdown'ı doldurmak için eğitim programlarının listesi
    $egitimProgramlari = [
        'Bedensel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı',
        'Dil ve Konuşma Bozukluğu Olan Bireyler İçin Destek Eğitim Programı',
        'Zihinsel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı',
        'Öğrenme Güçlüğü Olan Bireyler İçin Destek Eğitim Programı',
        'Otizm Spektrum Bozukluğu Olan Bireyler İçin Destek Eğitim Programı'
    ];

    $data = [
        'title'               => 'Öğrenci Yönetimi',
        'students'            => $students,
        'districts'           => $districts,
        'egitim_programlari'  => $egitimProgramlari, // Listeyi view'e gönder
        'selected_district'   => $filterDistrict,
        'selected_mesafe'     => $filterMesafe,
        'selected_sabit_durum'=> $filterSabitDurum,
        'selected_sabit_gun'  => $filterSabitGun,
        'selected_program'    => $filterProgram,   // Seçili programı view'e gönder
    ];

    return view('students/index', array_merge($this->data, $data));
}

    public function new()
    {
        $cityModel = new CityModel();
        $data = [
            'title'   => 'Yeni Öğrenci Ekle',
            'student' => array_fill_keys((new StudentModel())->allowedFields, null),
            'cities'  => $cityModel->orderBy('name', 'ASC')->findAll(),
        ];
        return view('students/new', array_merge($this->data, $data));
    }

public function create()
{
    $model = new StudentModel();
    $data = $this->request->getPost();

    // Fotoğraf yükleme işlemi
        $croppedImageData = $this->request->getPost('cropped_image_data');
        if (!empty($croppedImageData)) {
            list(, $croppedImageData) = explode(',', $croppedImageData);
            $decodedImage = base64_decode($croppedImageData);
            $imageName = uniqid('student_') . '.jpg';
            $uploadPath = FCPATH . 'uploads/student_photos/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            file_put_contents($uploadPath . $imageName, $decodedImage);
            $data['profile_image'] = 'uploads/student_photos/' . $imageName;
        }

    if (isset($data['egitim_programi']) && is_array($data['egitim_programi'])) {
        $data['egitim_programi'] = implode(',', $data['egitim_programi']);
    }
    
    $reportFile = $this->request->getFile('ram_raporu');
    if ($reportFile && $reportFile->isValid() && !$reportFile->hasMoved()) {
        $newName = $reportFile->getRandomName();
        $reportFile->move(WRITEPATH . 'uploads/ram_reports', $newName);
        $data['ram_raporu'] = $newName;
        // RAM raporu analizi
        $filePath = WRITEPATH . 'uploads/ram_reports/' . $newName;
        $this->analyzeAndSaveRamReport($id, $filePath);
    }

    // Tek insert işlemi ve sonucunu kontrol et
    $insertResult = $model->insert($data);
    
    if ($insertResult === false) {
        return redirect()->back()->withInput()->with('errors', $model->errors());
    }

    // Başarılı insert durumu
    $yeniOgrenciId = $model->getInsertID();
    $yeniOgrenciData = $model->find($yeniOgrenciId);
    \CodeIgniter\Events\Events::trigger('student.created', $yeniOgrenciData, auth()->user());
    
    return redirect()->to(site_url('students'))->with('success', 'Öğrenci başarıyla eklendi.');
}

   public function show($id = null)
{
    $model = new StudentModel();
    $evaluationModel = new StudentEvaluationModel(); // YENİ: Değerlendirme modelini çağırıyoruz
    $user = auth()->user();

    // --- GÜVENLİK KONTROLÜ (Mevcut yapın korunuyor) ---
    if ($user->inGroup('ogretmen') && !$user->inGroup('admin', 'yonetici', 'mudur', 'sekreter')) {
        if (!$model->isStudentOfTeacher($id, $user->id)) {
            return redirect()->to(site_url('my-students'))->with('error', 'Bu öğrencinin detaylarını görme yetkiniz yok.');
        }
    }
    // --- GÜVENLİK KONTROLÜ SONU ---

    

    $student = $model
        ->select('students.*, cities.name as city_name, districts.name as district_name')
        ->join('cities', 'cities.id = students.city_id', 'left')
        ->join('districts', 'districts.id = students.district_id', 'left')
        ->find($id);

    if (empty($student)) {
        throw new \CodeIgniter\Exceptions\PageNotFoundException('Öğrenci bulunamadı: ' . $id);
    }
    
    $student['egitim_programi'] = !empty($student['egitim_programi']) ? explode(',', $student['egitim_programi']) : [];
   // --- DEĞİŞEN KISIM BAŞLANGICI ---

    $evaluations = $evaluationModel->getEvaluationsForStudent((int)$id);
    $canAddEvaluation = $user->inGroup('admin', 'yonetici', 'mudur', 'sekreter', 'ogretmen');

    // YENİ: Filtre için benzersiz öğretmenleri alalım
    $evaluators = [];
    if (!empty($evaluations)) {
        $seenTeachers = [];
        foreach ($evaluations as $eval) {
            if (!in_array($eval['teacher_id'], $seenTeachers) && $eval['teacher_id'] !== null) {
                $evaluators[] = [
                    'id' => $eval['teacher_id'],
                    'name' => $eval['teacher_snapshot_name'],
                ];
                $seenTeachers[] = $eval['teacher_id'];
            }
        }
    }

    // ✅ RAM ANALİZ DURUMU KONTROLÜ
    $db = \Config\Database::connect();
    $isAnalyzed = $db->table('ram_report_analysis')
                     ->where('student_id', $id)
                     ->countAllResults() > 0;

    $data = [
        'title'            => 'Öğrenci Profili',
        'student'          => $student,
        'evaluations'      => $evaluationModel->getEvaluationsForStudent((int)$id),
        'canAddEvaluation' => $user->inGroup('admin', 'yonetici', 'mudur', 'sekreter', 'ogretmen'),
        'evaluators'       => $evaluationModel->getUniqueEvaluators((int)$id),
        'isAnalyzed'       => $isAnalyzed, // ✅ YENİ
    ];
    
    return view('students/show', array_merge($this->data, $data));
}
    public function edit($id = null)
    {
        $model = new StudentModel();
        $user = auth()->user();

        // --- GÜVENLİK KONTROLÜ ---
        if ($user->inGroup('ogretmen') && !$user->inGroup('admin', 'yonetici', 'mudur', 'sekreter')) {
            if (!$model->isStudentOfTeacher($id, $user->id)) {
                return redirect()->to(site_url('students'))->with('error', 'Bu öğrenciyi düzenleme yetkiniz yok.');
            }
        }
        // --- GÜVENLİK KONTROLÜ SONU ---

        $cityModel = new CityModel();
        $student = $model->find($id);

        if (empty($student)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Öğrenci bulunamadı: ' . $id);
        }

        $student['egitim_programi'] = !empty($student['egitim_programi']) ? explode(',', $student['egitim_programi']) : [];

        $data = [
            'title'   => 'Öğrenciyi Düzenle',
            'student' => $student,
            'cities'  => $cityModel->orderBy('name', 'ASC')->findAll(),
        ];

        return view('students/edit', array_merge($this->data, $data));
    }
    
public function update($id = null)
{
    $model = new StudentModel();
    $user = auth()->user();

    

    // --- GÜVENLİK KONTROLÜ ---
    if ($user->inGroup('ogretmen') && !$user->inGroup('admin', 'yonetici', 'mudur', 'sekreter')) {
        if (!$model->isStudentOfTeacher($id, $user->id)) {
            return redirect()->to(site_url('my-students'))->with('error', 'Bu öğrenciyi güncelleme yetkiniz yok.');
        }
    }
    
    $data = $this->request->getPost();

        // --- FOTOĞRAF GÜNCELLEME KISMI ---
    $croppedImageData = $this->request->getPost('cropped_image_data');
    if (!empty($croppedImageData)) {
        // Mevcut bir fotoğraf varsa ve varsayılan değilse sunucudan sil
        if (!empty($student['profile_image']) && $student['profile_image'] !== 'assets/images/user.jpg' && file_exists(FCPATH . $student['profile_image'])) {
             @unlink(FCPATH . $student['profile_image']);
        }

        // Yeni fotoğrafı işle ve kaydet
        list(, $croppedImageData) = explode(',', $croppedImageData);
        $decodedImage = base64_decode($croppedImageData);
        // İsimlendirme çakışmasını önlemek için öğrenci ID ve uniqid kullanalım
        $imageName = 'student_' . $id . '_' . uniqid() . '.jpg';
        $uploadPath = FCPATH . 'uploads/student_photos/';

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        
        file_put_contents($uploadPath . $imageName, $decodedImage);
        $data['profile_image'] = 'uploads/student_photos/' . $imageName;
    }
    // --- FOTOĞRAF GÜNCELLEME SONU ---

    // TCKN validation kuralını dinamik olarak ayarla
    $model->setValidationRule('tckn', 'required|exact_length[11]|is_unique[students.tckn,id,' . $id . ']');

    if (isset($data['egitim_programi']) && is_array($data['egitim_programi'])) {
        $data['egitim_programi'] = implode(',', $data['egitim_programi']);
    }

    // Dosya yükleme işlemleri...
    $reportFile = $this->request->getFile('ram_raporu');
    if ($reportFile && $reportFile->isValid() && !$reportFile->hasMoved()) {
        $student = $model->find($id);
        if (!empty($student['ram_raporu']) && file_exists(WRITEPATH . 'uploads/ram_reports/' . $student['ram_raporu'])) {
            @unlink(WRITEPATH . 'uploads/ram_reports/' . $student['ram_raporu']);
        }
        $newName = $reportFile->getRandomName();
        $reportFile->move(WRITEPATH . 'uploads/ram_reports', $newName);
        $data['ram_raporu'] = $newName;
    }

    if ($model->update($id, $data)) {
        $guncelOgrenciData = $model->find($id);
        \CodeIgniter\Events\Events::trigger('student.updated', $guncelOgrenciData, auth()->user());
        
        return redirect()->to(site_url('students/' . $id))->with('success', 'Öğrenci başarıyla güncellendi.');
    } else {
        return redirect()->back()->withInput()->with('errors', $model->errors());
    }
}
    public function delete($id = null)
    {
        $model = new StudentModel();
        $user = auth()->user();

        // --- GÜVENLİK KONTROLÜ ---
        if ($user->inGroup('ogretmen') && !$user->inGroup('admin', 'yonetici', 'mudur', 'sekreter')) {
            if (!$model->isStudentOfTeacher($id, $user->id)) {
                return redirect()->to(site_url('my-students'))->with('error', 'Bu öğrenciyi silme yetkiniz yok.');
            }
        }
        // --- GÜVENLİK KONTROLÜ SONU ---

        $silinecekOgrenci = $model->find($id);
        if (!$silinecekOgrenci) {
            return redirect()->back()->with('error', 'Silinecek öğrenci bulunamadı.');
        }
        
        // RAM analiz verilerini sil (öğrenci silinmeden ÖNCE)
        $analysisModel = new RamReportAnalysisModel();
        $analysisModel->where('student_id', $id)->delete();
        
        if ($model->delete($id)) {
            \CodeIgniter\Events\Events::trigger('student.deleted', $silinecekOgrenci, auth()->user());
            return redirect()->to(site_url('students'))->with('success', 'Öğrenci başarıyla silindi.');
        }
        return redirect()->back()->with('error', 'Öğrenci silinirken bir hata oluştu.');
    }

    public function viewRamReport($studentId)
    {
        // Not: Öğretmenlerin sadece kendi öğrencilerinin raporunu görmesi için
        // buraya da bir güvenlik kontrolü eklenebilir. Şimdilik temel yetki kontrolü yeterli.
        $studentModel = new StudentModel();
        $student = $studentModel->find($studentId);
        if (empty($student) || empty($student['ram_raporu'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('İstenen rapor bulunamadı.');
        }
        $filePath = WRITEPATH . 'uploads/ram_reports/' . $student['ram_raporu'];
        if (!file_exists($filePath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Rapor dosyası sunucuda mevcut değil.');
        }
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $student['ram_raporu'] . '"')
            ->setBody(file_get_contents($filePath));
    }
    
    public function getDistricts($cityId)
    {
        if ($this->request->isAJAX()) {
            $districtModel = new DistrictModel();
            $districts = $districtModel->where('city_id', $cityId)->orderBy('name', 'ASC')->findAll();
            return $this->response->setJSON($districts);
        }
        return redirect()->to('/');
    }

    public function myStudents()
    {
        $model = new StudentModel();
        $teacherId = auth()->id();

        $data = [
            'title'    => 'Öğrencilerim',
            'students' => $model->getStudentsForTeacher($teacherId),
        ];

        return view('students/my_students', array_merge($this->data, $data));
    }

        private function analyzeAndSaveRamReport($studentId, $filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }
        
        $analysisModel = new RamReportAnalysisModel();
        
        // PDF içeriğini text olarak çıkar (OgretmenAIController'daki readPdfContent metodunu kullan)
        $content = $this->extractPdfText($filePath);
        
        if (empty(trim($content))) {
            return false;
        }
        
        // RAM bilgilerini parse et
        $data = [
            'student_id' => $studentId,
            'ram_text_content' => $content,
            'total_memory' => $this->extractTotalMemory($content),
            'available_memory' => $this->extractAvailableMemory($content),
            'memory_info' => $this->extractMemoryInfo($content),
            'analyzed_at' => date('Y-m-d H:i:s')
        ];
        
        // Varsa güncelle, yoksa oluştur
        $existing = $analysisModel->where('student_id', $studentId)->first();
        
        if ($existing) {
            $analysisModel->update($existing['id'], $data);
        } else {
            $analysisModel->insert($data);
        }
        
        return true;
    }

    // Linux için düzenlenmiş hali
    private function extractPdfText($filePath)
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            
            if (strlen(trim($text)) > 50) {
                return $text;
            }
        } catch (\Exception $e) {
            log_message('info', 'Smalot başarısız, OCR deneniyor: ' . basename($filePath));
        }
        
        try {
            $imagePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid() . '.png';
            
            // İşletim sistemine göre yol
            $convertCmd = (DIRECTORY_SEPARATOR === '\\') 
                ? 'C:\\laragon\\bin\\imagemagick\\convert.exe' 
                : 'convert'; // Linux
            
            exec("\"$convertCmd\" -density 300 " . escapeshellarg($filePath) . "[0] " . escapeshellarg($imagePath) . " 2>&1", $output, $code);
            
            if (file_exists($imagePath)) {
                $ocr = new \thiagoalessio\TesseractOCR\TesseractOCR($imagePath);
                $text = $ocr->lang('tur')->run();
                @unlink($imagePath);
                
                if (strlen(trim($text)) > 10) {
                    return $text;
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'OCR hatası: ' . $e->getMessage());
        }
        
        return '';
    }

    private function extractTotalMemory($content)
    {
        // Windows komut çıktısından bellek bilgisi çıkarma
        if (preg_match('/TotalPhysicalMemory\s*:\s*(\d+)/i', $content, $matches)) {
            $bytes = $matches[1];
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }
        return null;
    }

    private function extractAvailableMemory($content)
    {
        if (preg_match('/AvailablePhysicalMemory\s*:\s*(\d+)/i', $content, $matches)) {
            $bytes = $matches[1];
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }
        return null;
    }

    private function extractMemoryInfo($content)
    {
        $info = [];
        
        // Üretici
        if (preg_match('/Manufacturer\s*:\s*(.+)/i', $content, $matches)) {
            $info['manufacturer'] = trim($matches[1]);
        }
        
        // Part Number
        if (preg_match('/PartNumber\s*:\s*(.+)/i', $content, $matches)) {
            $info['part_number'] = trim($matches[1]);
        }
        
        // Hız
        if (preg_match('/Speed\s*:\s*(\d+)/i', $content, $matches)) {
            $info['speed'] = $matches[1] . ' MHz';
        }
        
        return !empty($info) ? json_encode($info, JSON_UNESCAPED_UNICODE) : null;
    }

    public function analyzeSingleRam($id = null)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $studentModel = new StudentModel();
        $student = $studentModel->select('id, ram_raporu')->find($id);
        
        if (!$student) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Öğrenci bulunamadı!'
            ]);
        }

        if (empty($student['ram_raporu'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Bu öğrencinin RAM raporu yok!'
            ]);
        }

        $filePath = WRITEPATH . 'uploads/ram_reports/' . $student['ram_raporu'];
        
        if (!file_exists($filePath)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'RAM rapor dosyası bulunamadı!'
            ]);
        }

        // Mevcut analiz metodunu kullan
        if ($this->analyzeAndSaveRamReport($student['id'], $filePath)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'RAM raporu başarıyla analiz edildi!'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Rapor analiz edilirken bir hata oluştu!'
            ]);
        }
    }
}