<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\StudentModel;

class StudentController extends BaseController
{
    /**
     * Tüm öğrencileri listeler.
     */
    public function index()
    {
        $model = new StudentModel();
        
        // --- DEĞİŞİKLİK BURADA ---
        // Sayfalamayı kaldırıp TÜM öğrencileri alıyoruz.
        // DataTables geri kalan her şeyi (arama, sayfalama vb.) halledecek.
        $data = [
            'title'    => 'Öğrenci Yönetimi',
            'students' => $model->findAll(), 
            // 'pager' satırı kaldırıldı, artık ihtiyacımız yok.
        ];

        return view('students/index', $data);
    }

    /**
     * Yeni öğrenci ekleme formunu gösterir.
     */
    public function new()
    {
        $data = [
            'title' => 'Yeni Öğrenci Ekle',
            // Yeni öğrenci için boş bir 'student' dizisi gönderiyoruz ki form hata vermesin
            'student' => array_fill_keys((new StudentModel())->allowedFields, null)
        ];
        return view('students/new', $data);
    }

    /**
     * Yeni öğrenciyi veritabanına kaydeder.
     */
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

        // RAM Raporu Yükleme
        $reportFile = $this->request->getFile('ram_raporu');
        if ($reportFile && $reportFile->isValid() && !$reportFile->hasMoved()) {
            $newName = $reportFile->getRandomName();
            $reportFile->move(WRITEPATH . 'uploads/ram_reports', $newName);
            $data['ram_raporu'] = $newName;
        }


        if ($model->insert($data)) {
            return redirect()->to(site_url('students'))->with('success', 'Öğrenci başarıyla eklendi.');
        }

        return redirect()->back()->withInput()->with('errors', $model->errors());
    }

    /**
     * Belirli bir öğrencinin detaylarını gösterir.
     */
    public function show($id = null)
    {
        $model = new StudentModel();
        $data = [
            'title'   => 'Öğrenci Detayları',
            'student' => $model->find($id),
        ];

        if (empty($data['student'])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Öğrenci bulunamadı: ' . $id);
        }
        
        return view('students/show', $data);
    }

    /**
     * Öğrenci düzenleme formunu gösterir.
     */
    public function edit($id = null)
    {
        $model = new StudentModel();
        $data = [
            'title'   => 'Öğrenciyi Düzenle',
            'student' => $model->find($id),
        ];

        if (empty($data['student'])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Öğrenci bulunamadı: ' . $id);
        }

        return view('students/edit', $data);
    }

    /**
     * Öğrenci bilgilerini günceller.
     */
    public function update($id = null)
    {
        $model = new StudentModel();
        $student = $model->find($id);
        $data = $this->request->getPost();

        // Fotoğraf yükleme işlemi
        $croppedImageData = $this->request->getPost('cropped_image_data');
        if (!empty($croppedImageData)) {
            if (!empty($student['profile_image']) && file_exists(FCPATH . $student['profile_image'])) {
                 unlink(FCPATH . $student['profile_image']);
            }

            list(, $croppedImageData) = explode(',', $croppedImageData);
            $decodedImage = base64_decode($croppedImageData);
            $imageName = 'student_' . $id . '_' . uniqid() . '.jpg';
            $uploadPath = FCPATH . 'uploads/student_photos/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            file_put_contents($uploadPath . $imageName, $decodedImage);
            $data['profile_image'] = 'uploads/student_photos/' . $imageName;
        }

        // RAM Raporu Yükleme
        $reportFile = $this->request->getFile('ram_raporu');
        if ($reportFile && $reportFile->isValid() && !$reportFile->hasMoved()) {
            if (!empty($student['ram_raporu']) && file_exists(WRITEPATH . 'uploads/ram_reports/' . $student['ram_raporu'])) {
                unlink(WRITEPATH . 'uploads/ram_reports/' . $student['ram_raporu']);
            }
            $newName = $reportFile->getRandomName();
            $reportFile->move(WRITEPATH . 'uploads/ram_reports', $newName);
            $data['ram_raporu'] = $newName;
        }

        if ($model->update($id, $data)) {
            return redirect()->to(site_url('students'))->with('success', 'Öğrenci başarıyla güncellendi.');
        }

        return redirect()->back()->withInput()->with('errors', $model->errors());
    }

    /**
     * Öğrenciyi veritabanından (soft) siler.
     */
    public function delete($id = null)
    {
        $model = new StudentModel();
        if ($model->delete($id)) {
            return redirect()->to(site_url('students'))->with('success', 'Öğrenci başarıyla silindi.');
        }

        return redirect()->back()->with('error', 'Öğrenci silinirken bir hata oluştu.');
    }
        public function viewRamReport($studentId)
    {
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
}