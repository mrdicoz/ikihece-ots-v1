<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\FixedLessonModel;
use App\Models\StudentModel;
use CodeIgniter\Shield\Models\UserModel;

class FixedScheduleController extends BaseController
{
    protected $fixedLessonModel;
    protected $userModel;
    protected $studentModel;

    public function __construct()
    {
        // Modelleri constructor içinde yüklüyoruz
        $this->fixedLessonModel = model(FixedLessonModel::class);
        $this->userModel = model(UserModel::class);
        $this->studentModel = model(StudentModel::class);
    }

    /**
     * Ana Sabit Ders Programı sayfasını yükler.
     * Bu sayfa, öğretmenler ve öğrenciler için gerekli verileri önceden yükler.
     */
    public function index()
    {
        // 'Öğretmen' grubundaki tüm aktif kullanıcıları profilleriyle birlikte alıyoruz.
        // --- DÜZELTME BURADA: asArray() metodu eklendi ---
        $teachers = $this->userModel
            ->select('users.id, p.first_name, p.last_name, p.profile_photo, p.branch')
            ->join('user_profiles p', 'p.user_id = users.id', 'left')
            ->join('auth_groups_users gu', 'gu.user_id = users.id')
            ->where('gu.group', 'ogretmen')
            ->where('users.active', 1)
            ->orderBy('p.first_name', 'ASC')
            ->asArray() // SONUÇLARI NESNE YERİNE DİZİ OLARAK ALMAK İÇİN BU EKLENDİ
            ->findAll();

        // TomSelect için tüm öğrencileri hazırlıyoruz.
        $allStudents = $this->studentModel
            ->select('students.id, students.adi, students.soyadi, c.name as city_name, d.name as district_name')
            ->join('cities c', 'c.id = students.city_id', 'left')
            ->join('districts d', 'd.id = students.district_id', 'left')
            ->orderBy('students.adi', 'ASC')
            ->findAll();

        $this->data['title'] = 'Sabit Ders Programı Yönetimi';
        
        // Verileri view'e göndermek için hazırlıyoruz.
        $this->data['teachers'] = $teachers;
        $this->data['students'] = $allStudents; // JS tarafında kullanılacak tam liste
        
        // --- DÜZELTME BURADA: Dizi erişimi artık doğru çalışacak ---
        $this->data['teachersForSelect'] = array_map(fn($t) => ['value' => $t['id'], 'text' => $t['first_name'] . ' ' . $t['last_name']], $teachers);

        return view('admin/fixed_schedule/index', $this->data);
    }

    /**
     * AJAX isteği ile belirli öğretmen ve günlere ait yapılandırılmış
     * ders programı verisini JSON olarak döndürür.
     */
    public function getScheduleData()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403, 'Forbidden');
        }

        $teacherIds = $this->request->getGet('teachers');
        $dayNumbers = $this->request->getGet('days');

        // Gelen parametreler boş veya dizi değilse, boş bir JSON döndür.
        if (empty($teacherIds) || empty($dayNumbers) || !is_array($teacherIds) || !is_array($dayNumbers)) {
            return $this->response->setJSON(['schedule' => []]);
        }
        
        // Model'den yapılandırılmış veriyi alıyoruz.
        $scheduleData = $this->fixedLessonModel->getStructuredSchedule($teacherIds, $dayNumbers);

        return $this->response->setJSON(['schedule' => $scheduleData]);
    }

    /**
     * AJAX isteği ile bir ders slotuna öğrenci ekler veya günceller.
     */
    public function saveSlot()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403, 'Forbidden');
        }

        $slotId = $this->request->getPost('slotId');
        $weekType = $this->request->getPost('weekType');
        $studentIds = $this->request->getPost('studentIds') ?? [];

        if (empty($slotId) || empty($weekType)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Eksik parametre.'])->setStatusCode(400);
        }

        [$teacherId, $dayOfWeek, $hour] = explode('-', $slotId);
        $startTime = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00:00';
        $endTime = str_pad((string)($hour + 1), 2, '0', STR_PAD_LEFT) . ':00:00';
        
        // Önce o slottaki mevcut tüm kayıtları siliyoruz (temiz bir başlangıç için).
        $this->fixedLessonModel
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', $startTime)
            ->where('week_type', $weekType)
            ->delete();

        // Eğer yeni öğrenci listesi boş değilse, toplu olarak ekliyoruz.
        if (!empty($studentIds)) {
            $dataToInsert = [];
            foreach ($studentIds as $studentId) {
                $dataToInsert[] = [
                    'teacher_id'  => $teacherId,
                    'student_id'  => $studentId,
                    'week_type'   => $weekType,
                    'day_of_week' => $dayOfWeek,
                    'start_time'  => $startTime,
                    'end_time'    => $endTime,
                ];
            }
            $this->fixedLessonModel->insertBatch($dataToInsert);
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Program başarıyla güncellendi.']);
    }
}