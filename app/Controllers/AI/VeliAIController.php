<?php

namespace App\Controllers\AI;

class VeliAIController extends BaseAIController
{
    protected function getUserRole($user): string
    {
        return 'Veli';
    }

    protected function getSystemPrompt(string $role, object $user): string
    {
        // 1. Kullanıcı Profilinden TCKN al
        $userProfileModel = new \App\Models\UserProfileModel();
        $userProfile = $userProfileModel->where('user_id', $user->id)->first();
        $parentTckn = $userProfile->tc_kimlik_no ?? null;

        // 2. Kurum Bilgilerini Al
        $institutionModel = new \App\Models\InstitutionModel();
        $institution = $institutionModel->first();
        $institutionPhone = $institution->sabit_telefon ?? $institution->telefon ?? 'Belirtilmemiş';
        $institutionName = $institution->kurum_adi ?? 'Kurumumuz';

        // 3. Öğrenci Verilerini Topla
        $studentContext = "";
        
        if ($parentTckn) {
            $studentModel = new \App\Models\StudentModel();
            $children = $studentModel->getChildrenOfParent($parentTckn);

            if (!empty($children)) {
                $studentContext .= "VELİSİ OLDUĞUN ÖĞRENCİLER HAKKINDA BİLGİLER:\n";
                
                foreach ($children as $child) {
                    $studentContext .= "\n--- ÖĞRENCİ: {$child['adi']} {$child['soyadi']} ---\n";

                    // A. Devamsızlık Durumu (Bu ay)
                    $absenceModel = new \App\Models\StudentAbsenceModel();
                    $absences = $absenceModel->getAbsencesByMonthYear(date('m'), date('Y'), $child['id']);
                    $absenceCount = count($absences);
                    $studentContext .= "- Devamsızlık Durumu: Bu ay $absenceCount kez devamsızlık yapıldı.\n";

                    // B. Gelişim Süreci (Son Değerlendirmeler)
                    $evaluationModel = new \App\Models\StudentEvaluationModel();
                    $evaluations = $evaluationModel->getEvaluationsForStudent($child['id']);
                    $latestEvaluations = array_slice($evaluations, 0, 3); // Son 3 değerlendirme
                    if (!empty($latestEvaluations)) {
                        $studentContext .= "- Gelişim Süreci (HAM VERİ - BUNLARI YORUMLAYARAK AKTAR):\n";
                        foreach ($latestEvaluations as $eval) {
                            $date = date('d.m.Y', strtotime($eval['created_at']));
                            // Değerlendirme metnini daha uzun alalım ki AI yorumlayabilsin
                            $summary = mb_substr(strip_tags($eval['evaluation']), 0, 500); 
                            $studentContext .= "  * $date ({$eval['teacher_snapshot_name']}): $summary\n";
                        }
                    } else {
                        $studentContext .= "- Gelişim Süreci: Henüz girilmiş bir değerlendirme bulunmuyor.\n";
                    }

                    // C. Bu Ay Alınan Ders Saati
                    $lessonModel = new \App\Models\LessonModel();
                    $lessonsThisMonth = $lessonModel->getLessonsForStudentByMonth($child['id'], date('Y'), date('m'));
                    
                    $totalMinutes = 0;
                    $now = time();
                    foreach ($lessonsThisMonth as $lesson) {
                        $lessonTimestamp = strtotime($lesson['lesson_date'] . ' ' . $lesson['start_time']);
                        if ($lessonTimestamp <= $now) {
                            $start = strtotime($lesson['start_time']);
                            $end = strtotime($lesson['end_time'] ?? $lesson['start_time'] . ' +40 minutes'); 
                            $totalMinutes += ($end - $start) / 60;
                        }
                    }
                    $hours = floor($totalMinutes / 60);
                    $minutes = $totalMinutes % 60;
                    $studentContext .= "- Bu Ay Tamamlanan Ders: Toplam $hours saat $minutes dakika.\n";

                    // D. Öğretmenler
                    $teachers = $studentModel->getTeachersForStudent($child['id']);
                    $teacherNames = array_map(function($t) {
                        return $t['first_name'] . ' ' . $t['last_name'];
                    }, $teachers);
                    $studentContext .= "- Öğretmenleri: " . implode(', ', $teacherNames) . "\n";

                    // E. Ders Programı (Gelecek Dersler - Örnek)
                    // Basitçe bu ayki derslerden gün ve saat çıkarımı yapalım
                    $schedule = [];
                    foreach ($lessonsThisMonth as $lesson) {
                        // Sadece bugünden sonraki dersleri veya genel programı özetleyebiliriz.
                        // Burada bu ayki tüm dersleri listelemek yerine, hangi günlerde dersi olduğunu özetleyelim.
                        $dayName = date('l', strtotime($lesson['lesson_date'])); // İngilizce gün adı
                        $turkishDays = [
                            'Monday' => 'Pazartesi', 'Tuesday' => 'Salı', 'Wednesday' => 'Çarşamba', 
                            'Thursday' => 'Perşembe', 'Friday' => 'Cuma', 'Saturday' => 'Cumartesi', 'Sunday' => 'Pazar'
                        ];
                        $dayTr = $turkishDays[$dayName] ?? $dayName;
                        $time = date('H:i', strtotime($lesson['start_time']));
                        $key = "$dayTr $time";
                        if (!isset($schedule[$key])) {
                            $schedule[$key] = 0;
                        }
                        $schedule[$key]++;
                    }
                    // En çok tekrar eden gün/saatleri listele (Genel programı yansıtması için)
                    $studentContext .= "- Ders Günleri ve Saatleri (Bu ayki verilere göre): ";
                    $scheduleStr = [];
                    foreach ($schedule as $dt => $count) {
                        $scheduleStr[] = $dt;
                    }
                    $studentContext .= implode(', ', array_unique($scheduleStr)) . "\n";
                }
            } else {
                $studentContext = "Sistemde kayıtlı öğrenciniz bulunamadı veya T.C. Kimlik Numaranız eşleşmedi.";
            }
        } else {
            $studentContext = "Profilinizde T.C. Kimlik Numarası bulunmadığı için öğrenci bilgilerinize erişilemiyor.";
        }

        $userName = $userProfile->first_name ?? $user->username ?? 'Sayın Velimiz';

        return "Sen '$institutionName' adındaki kurumun yapay zeka asistanı 'Pusula'sın.
        Şu an velimiz '$userName' ile konuşuyorsun.

        GÖREVİN:
        Velinin sorularına aşağıdaki KURALLARA SIKI SIKIYA BAĞLI kalarak cevap vermek.

        KURALLAR:
        1. **KURUMA BAĞLAYICILIK:** Cevapların kurumu temsil eder. Yanlış veya emin olmadığın bilgi verme.
        2. **TEKNİK SORULAR:** Öğrencinin ders saati, devamsızlığı, öğretmenleri, ders programı gibi somut verilere dayalı sorulara NET ve DOĞRU cevap ver. (Aşağıdaki verileri kullan).
        3. **GELİŞİM NOTLARI (ÖNEMLİ):** Öğretmenlerin girdiği gelişim notlarını ASLA olduğu gibi kopyalayıp yapıştırma. Bu notları bir **PEDAGOG/PSİKOLOG** edasıyla, yapıcı, nazik, umut verici ve aileyi üzmeyecek bir dille YORUMLAYARAK özetle. Olumsuzlukları 'geliştirilmesi gereken alanlar' veya 'üzerine eğildiğimiz konular' olarak yumuşatarak ifade et.
        4. **YÖNLENDİRME:** Çok detaylı veya hassas konularda; genel, yapıcı bir bilgi verdikten sonra detaylar için ilgili öğretmene yönlendir.
        5. **İLETİŞİM:** Veli kurumla iletişime geçmek isterse veya detaylı görüşme gerekirse şu numarayı ver: $institutionPhone.
        6. **ÇOKLU ÖĞRENCİ:** Eğer velinin birden fazla öğrencisi varsa, genel sorularda TÜM öğrencileri hakkında özet bilgi ver.

        MEVCUT VERİLER (Sadece bu verileri kullan, uydurma):
        $studentContext

        ÜSLUBUN:
        - Saygılı, kurumsal, yapıcı ve yardımsever.
        - Bir eğitimci/psikolog hassasiyetiyle konuş.
        - 'Benim çocuğum' dendiğinde listedeki öğrencileri kastedildiğini anla.
        ";
    }
}
