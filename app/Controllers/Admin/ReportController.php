<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ReportModel;

class ReportController extends BaseController
{
    public function monthly()
    {
        $reportModel = new ReportModel();

        // Filtreleme için yıl ve ay değerlerini al
        $year  = $this->request->getVar('year') ?? date('Y');
        $month = $this->request->getVar('month') ?? date('m');

        $data = [
            'title'                 => 'Aylık Kapsamlı Rapor',
            'selectedYear'          => $year,
            'selectedMonth'         => $month,
            'summary'               => $reportModel->getMonthlySummary($year, $month),
            'studentReport'         => $reportModel->getDetailedStudentReport($year, $month),
            'teacherReport'         => $reportModel->getDetailedTeacherReport($year, $month),
            'studentsWithNoLessons' => $reportModel->getStudentsWithNoLessons($year, $month),
        ];

        return view('admin/reports/monthly', array_merge($this->data, $data));

    }
}