<?php

namespace App\Controllers;

use App\Models\UserProfileModel;
use App\Models\LessonModel;
use App\Models\DegerlendirmeModel;
use CodeIgniter\I18n\Time;

class TeacherController extends BaseController
{
    public function index()
    {
        $userProfileModel = new UserProfileModel();
        $data['title'] = 'Öğretmenler';
        $data['teachers'] = $userProfileModel->getTeachers();

        return view('teachers/index', array_merge($this->data, $data));
    }

    public function show($id)
    {
        $userProfileModel = new UserProfileModel();
        $teacher = $userProfileModel->getTeacherDetails($id);

        if (!$teacher) {
            return redirect()->to(route_to('teachers.index'))->with('error', 'Öğretmen bulunamadı.');
        }

        $data['title'] = esc($teacher->first_name . ' ' . $teacher->last_name);
        $data['teacher'] = $teacher;

        $month = $this->request->getGet('month');
        $year  = $this->request->getGet('year');

        $data['selected_month'] = $month;
        $data['selected_year']  = $year;

        $leaveModel = new \App\Models\TeacherLeaveModel();
        // Modal için tüm izinler
        $data['leaves'] = $leaveModel->getLeavesByTeacherId($id);

        // Filtrelenmiş tablo için izinler
        $leaveQuery = $leaveModel->where('teacher_id', $id);
        if ($month && $year) {
            $dateFilterApplied = ($month && $year);
            $startDate = $dateFilterApplied ? Time::createFromDate($year, $month, 1)->toDateTimeString() : null;
            $endDate = $dateFilterApplied ? (new \DateTime("$year-$month-01"))->modify('last day of this month')->setTime(23, 59, 59)->format('Y-m-d H:i:s') : null;
            $leaveQuery->where('start_date >=', $startDate)->where('start_date <=', $endDate);
        }
        $data['filtered_leaves'] = $leaveQuery->orderBy('start_date', 'DESC')->findAll();


        // Öğretmen istatistiklerini al
        $data['stats'] = $this->_getTeacherStatistics($id, $month, $year);

        return view('teachers/show', array_merge($this->data, $data));
    }

    private function _getTeacherStatistics($teacherId, $month = null, $year = null)
    {
        $stats = [];
        $db = \Config\Database::connect();

        $turkishMonths = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
            7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
        ];

        // Zaman aralığı belirleme
        $dateFilterApplied = ($month && $year);
        $startDate = $dateFilterApplied ? Time::createFromDate($year, $month, 1)->toDateTimeString() : null;
        $endDate = $dateFilterApplied ? (new \DateTime("$year-$month-01"))->modify('last day of this month')->setTime(23, 59, 59)->format('Y-m-d H:i:s') : null;

        // 1. Toplam İzin Saati
        $leaveModel = new \App\Models\TeacherLeaveModel();
        $leaveQuery = $leaveModel->where('teacher_id', $teacherId);
        if ($dateFilterApplied) {
            $leaveQuery->where('start_date >=', $startDate)->where('start_date <=', $endDate);
        }
        $leaves = $leaveQuery->findAll();
        $totalLeaveHours = 0;
        foreach ($leaves as $leave) {
            $start = new \DateTime($leave->start_date);
            $end = new \DateTime($leave->end_date);
            if (in_array($leave->leave_type, ['unpaid_daily', 'paid_daily'])) {
                 $diff = $end->diff($start)->days;
                 $totalLeaveHours += ($diff + 1) * 8;
            } else {
                $diff = $end->diff($start);
                $totalLeaveHours += $diff->h;
            }
        }
        $stats['totalLeaveHours'] = $totalLeaveHours;

        // 2. Toplam Öğrenci Değerlendirmesi
        $degerlendirmeModel = new \App\Models\DegerlendirmeModel();
        $evalQuery = $degerlendirmeModel->where('teacher_id', $teacherId);
        if ($dateFilterApplied) {
            $evalQuery->where('evaluation_date >=', $startDate)->where('evaluation_date <=', $endDate);
        }
        $stats['totalEvaluations'] = $evalQuery->countAllResults();

        // 3. Aylara Göre Ders Sayısı (Filtresiz)
        $monthlyLessonData = $db->table('lessons')
                                ->select("DATE_FORMAT(lesson_date, '%Y-%m') as month_year, MONTH(lesson_date) as month_num, COUNT(lessons.id) as lesson_count")
                                ->where('lessons.teacher_id', $teacherId)
                                ->groupBy('month_year, month_num')
                                ->orderBy('month_year', 'ASC')
                                ->get()->getResultArray();

        $monthlyStudentLabels = [];
        $monthlyStudentCounts = [];
        foreach ($monthlyLessonData as $row) {
            $monthlyStudentLabels[] = $turkishMonths[(int)$row['month_num']];
            $monthlyStudentCounts[] = (int)$row['lesson_count'];
        }
        $stats['monthlyStudentChart'] = [
            'labels' => $monthlyStudentLabels,
            'data'   => $monthlyStudentCounts
        ];

        // 4. Aylara Göre Boş Dersler (Filtresiz)
        $monthlyEmptyLessons = [];
        $lessonDatesFromLessons = $db->table('lessons')->select('lesson_date as date')->where('teacher_id', $teacherId)->distinct()->get()->getResultArray();
        $evaluationDatesFromDegerlendirme = $db->table('degerlendirme')->select('evaluation_date as date')->where('teacher_id', $teacherId)->distinct()->get()->getResultArray();
        $allUniqueDates = array_merge($lessonDatesFromLessons, $evaluationDatesFromDegerlendirme);
        $uniqueDatesArray = [];
        foreach ($allUniqueDates as $row) {
            $uniqueDatesArray[] = $row['date'];
        }
        $uniqueDatesArray = array_unique($uniqueDatesArray);
        sort($uniqueDatesArray);
        $allLeaves = $leaveModel->where('teacher_id', $teacherId)->findAll();

        foreach ($uniqueDatesArray as $currentDate) {
            $dateObj = Time::parse($currentDate);
            $monthYearKey = $dateObj->format('Y-m');
            $monthNum = (int)$dateObj->format('m');

            $isDailyLeave = false;
            foreach ($allLeaves as $leave) {
                $leaveStart = Time::parse($leave->start_date);
                $leaveEnd = Time::parse($leave->end_date);
                if (($leave->leave_type === 'unpaid_daily' || $leave->leave_type === 'paid_daily') &&
                    ($dateObj->getTimestamp() >= $leaveStart->getTimestamp() && $dateObj->getTimestamp() <= $leaveEnd->getTimestamp())) {
                    $isDailyLeave = true;
                    break;
                }
            }
            if ($isDailyLeave) continue;

            $dailyLessons = $db->table('lessons')->select('start_time, end_time')->where('teacher_id', $teacherId)->where('lesson_date', $currentDate)->get()->getResultArray();
            $totalLessonDuration = 0;
            foreach ($dailyLessons as $lesson) {
                $start = Time::parse($lesson['start_time']);
                $end = Time::parse($lesson['end_time']);
                $totalLessonDuration += ($end->getTimestamp() - $start->getTimestamp()) / 3600;
            }

            $dailyEvaluations = $degerlendirmeModel->where('teacher_id', $teacherId)->where('evaluation_date', $currentDate)->get()->getResultArray();
            $totalEvaluationDuration = 0;
            foreach ($dailyEvaluations as $evaluation) {
                $start = Time::parse($evaluation['start_time']);
                $end = Time::parse($evaluation['end_time']);
                $totalEvaluationDuration += ($end->getTimestamp() - $start->getTimestamp()) / 3600;
            }

            $totalOccupiedDuration = $totalLessonDuration + $totalEvaluationDuration;
            $emptyHours = max(0, 8 - $totalOccupiedDuration);

            if (!isset($monthlyEmptyLessons[$monthYearKey])) {
                $monthlyEmptyLessons[$monthYearKey] = ['month_num' => $monthNum, 'empty_hours' => 0];
            }
            $monthlyEmptyLessons[$monthYearKey]['empty_hours'] += $emptyHours;
        }
        ksort($monthlyEmptyLessons);

        $monthlyEmptyLessonsLabels = [];
        $monthlyEmptyLessonsCounts = [];
        foreach ($monthlyEmptyLessons as $monthData) {
            $monthlyEmptyLessonsLabels[] = $turkishMonths[(int)$monthData['month_num']];
            $monthlyEmptyLessonsCounts[] = (int)$monthData['empty_hours'];
        }
        $stats['monthlyEmptyLessonsChart'] = [
            'labels' => $monthlyEmptyLessonsLabels,
            'data'   => $monthlyEmptyLessonsCounts
        ];

        // 5. En Çok Dersine Girilen Öğrenciler
        $topStudentsQuery = $db->table('lessons')
                            ->select('s.adi, s.soyadi, ls.student_id, COUNT(lessons.id) as lesson_count')
                            ->join('lesson_students ls', 'ls.lesson_id = lessons.id')
                            ->join('students s', 's.id = ls.student_id')
                            ->where('lessons.teacher_id', $teacherId);
        if ($dateFilterApplied) {
            $topStudentsQuery->where('lessons.lesson_date >=', $startDate)->where('lessons.lesson_date <=', $endDate);
        }
        $topStudentsData = $topStudentsQuery->groupBy('ls.student_id, s.adi, s.soyadi')
                                        ->orderBy('lesson_count', 'DESC')
                                        ->limit(5)
                                        ->get()->getResultArray();

        $stats['topStudents'] = array_map(function($student) {
            return [
                'student_name' => $student['adi'] . ' ' . $student['soyadi'],
                'lesson_count' => $student['lesson_count'],
                'student_id'   => $student['student_id']
            ];
        }, $topStudentsData);

        // 6. Son Değerlendirmeler
        $latestEvalsQuery = $db->table('student_evaluations se')
                                ->select('s.adi, s.soyadi, se.student_id, se.created_at as evaluation_date')
                                ->join('students s', 's.id = se.student_id')
                                ->where('se.teacher_id', $teacherId);
        if ($dateFilterApplied) {
            $latestEvalsQuery->where('se.created_at >=', $startDate)->where('se.created_at <=', $endDate);
        }
        $latestEvaluationsData = $latestEvalsQuery->orderBy('se.created_at', 'DESC')
                                                ->limit(5)
                                                ->get()->getResultArray();

        $stats['latestEvaluations'] = array_map(function($eval) {
            return [
                'student_name' => $eval['adi'] . ' ' . $eval['soyadi'],
                'evaluation_date' => $eval['evaluation_date'],
                'student_id'   => $eval['student_id']
            ];
        }, $latestEvaluationsData);

        return $stats;
    }

    public function addLeave($teacherId)
    {
        $leaveModel = new \App\Models\TeacherLeaveModel();
        $lessonModel = new \App\Models\LessonModel();

        $leaveType = $this->request->getPost('leave_type');
        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');

        $conflictingLessons = [];

        if (in_array($leaveType, ['unpaid_hourly', 'paid_hourly'])) {
            $startTime = date('H:i:s', strtotime($startDate));
            $endTime = date('H:i:s', strtotime($endDate));
            $conflictingLessons = $lessonModel->getConflictingLessons($teacherId, date('Y-m-d', strtotime($startDate)), date('Y-m-d', strtotime($endDate)), $startTime, $endTime);
        } else { // daily
            $conflictingLessons = $lessonModel->getConflictingLessons($teacherId, date('Y-m-d', strtotime($startDate)), date('Y-m-d', strtotime($endDate)));
        }

        if (!empty($conflictingLessons)) {
            $errorMessage = 'Öğretmenin bu tarihlerde dersleri bulunmaktadır. Lütfen önce bu dersleri iptal edin veya başka bir öğretmene atayın.<br>';
            $errorMessage .= 'Çakışan Dersler:<ul>';
            foreach ($conflictingLessons as $lesson) {
                $errorMessage .= '<li>'.date('d.m.Y', strtotime($lesson['lesson_date'])).' '.$lesson['start_time'].' - '.$lesson['end_time'].'</li>';
            }
            $errorMessage .= '</ul>';

            return redirect()->to(site_url('teachers/show/' . $teacherId))->with('error', $errorMessage);
        }

        $data = [
            'teacher_id' => $teacherId,
            'leave_type' => $leaveType,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'reason'     => $this->request->getPost('reason'),
        ];

        if ($leaveModel->save($data)) {
            return redirect()->to(site_url('teachers/show/' . $teacherId))->with('message', 'İzin başarıyla eklendi.');
        }

        return redirect()->to(site_url('teachers/show/' . $teacherId))->with('error', 'İzin eklenirken bir hata oluştu.')->with('errors', $leaveModel->errors());
    }

    public function deleteLeave($teacherId, $leaveId)
    {
        $leaveModel = new \App\Models\TeacherLeaveModel();

        if ($leaveModel->delete($leaveId)) {
            return redirect()->to(site_url('teachers/show/' . $teacherId))->with('message', 'İzin başarıyla silindi.');
        }

        return redirect()->to(site_url('teachers/show/' . $teacherId))->with('error', 'İzin silinirken bir hata oluştu.');
    }


}