<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\CityModel;
use App\Models\DistrictModel;

class StudentController extends BaseController
{
    public function index()
    {
        $model = new StudentModel();
        $data = [
            'title'    => 'Öğrenci Yönetimi',
            'students' => $model->findAll(),
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

        if (isset($data['egitim_programi']) && is_array($data['egitim_programi'])) {
            $data['egitim_programi'] = implode(',', $data['egitim_programi']);
        }
        
        $reportFile = $this->request->getFile('ram_raporu');
        if ($reportFile && $reportFile->isValid() && !$reportFile->hasMoved()) {
            $newName = $reportFile->getRandomName();
            $reportFile->move(WRITEPATH . 'uploads/ram_reports', $newName);
            $data['ram_raporu'] = $newName;
        }

        if ($model->insert($data)) {
            $yeniOgrenciId = $model->getInsertID();
            $yeniOgrenciData = $model->find($yeniOgrenciId);
            \CodeIgniter\Events\Events::trigger('student.created', $yeniOgrenciData, auth()->user());
            return redirect()->to(site_url('my-students'))->with('success', 'Öğrenci başarıyla eklendi.');
        }

        return redirect()->back()->withInput()->with('errors', $model->errors());
    }

    public function show($id = null)
    {
        $model = new StudentModel();
        $user = auth()->user();

        // --- GÜVENLİK KONTROLÜ ---
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

        $data = [
            'title'   => 'Öğrenci Profili',
            'student' => $student,
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
        // --- GÜVENLİK KONTROLÜ SONU ---
        
        $data = $this->request->getPost();

        if (isset($data['egitim_programi']) && is_array($data['egitim_programi'])) {
            $data['egitim_programi'] = implode(',', $data['egitim_programi']);
        }

        $reportFile = $this->request->getFile('ram_raporu');
        if ($reportFile && $reportFile->isValid() && !$reportFile->hasMoved()) {
            $student = $model->find($id);
            if (!empty($student['ram_raporu']) && file_exists(WRITEPATH . 'uploads/ram_reports/' . $student['ram_raporu'])) {
                unlink(WRITEPATH . 'uploads/ram_reports/' . $student['ram_raporu']);
            }
            $newName = $reportFile->getRandomName();
            $reportFile->move(WRITEPATH . 'uploads/ram_reports', $newName);
            $data['ram_raporu'] = $newName;
        }

        if ($model->update($id, $data)) {
            $guncelOgrenciData = $model->find($id);
            \CodeIgniter\Events\Events::trigger('student.updated', $guncelOgrenciData, auth()->user());
            return redirect()->to(site_url('students/' . $id))->with('success', 'Öğrenci başarıyla güncellendi.');
        }

        return redirect()->back()->withInput()->with('errors', $model->errors());
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
        if ($model->delete($id)) {
            \CodeIgniter\Events\Events::trigger('student.deleted', $silinecekOgrenci, auth()->user());
            return redirect()->to(site_url('my-students'))->with('success', 'Öğrenci başarıyla silindi.');
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
}