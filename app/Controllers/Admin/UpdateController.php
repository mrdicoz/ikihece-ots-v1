<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class UpdateController extends BaseController
{
    public function index()
    {
        $data = [
            'title'          => 'Sistem Güncelleme',
            'currentVersion' => config('Ots')->version,
        ];

        return view('admin/update/index', $data);
    }

    /**
     * GitHub'daki repo ile yerel kopyayı karşılaştırır ve
     * bir güncelleme olup olmadığını kontrol eder.
     */
    public function check()
    {
        // Önce uzak sunucudaki değişiklikleri yerel git deposuna indiriyoruz (ancak uygulamıyoruz).
        $fetchProcess = new Process(['command' => 'git fetch', 'cwd' => ROOTPATH]);
        $fetchProcess->run();

        if (!$fetchProcess->isOK()) {
            return $this->response->setJSON(['error' => 'Git fetch başarısız oldu: ' . $fetchProcess->getErrorOutput()])->setStatusCode(500);
        }
        
        // Yerel ve uzak dallar arasındaki durumu kontrol ediyoruz.
        $statusProcess = new Process(['command' => 'git status -uno', 'cwd' => ROOTPATH]);
        $statusProcess->run();

        if (!$statusProcess->isOK()) {
            return $this->response->setJSON(['error' => 'Git status başarısız oldu: ' . $statusProcess->getErrorOutput()])->setStatusCode(500);
        }

        $output = $statusProcess->getOutput();
        $updateAvailable = str_contains($output, 'Your branch is behind');

        $latestVersion = config('Ots')->version;
        if ($updateAvailable) {
            // Güncelleme varsa, GitHub'daki güncel Ots.php dosyasının içeriğini alıp versiyon numarasını okuyoruz.
            $catFileProcess = new Process(['command' => 'git show origin/main:app/Config/Ots.php', 'cwd' => ROOTPATH]);
            $catFileProcess->run();
            if ($catFileProcess->isOK()) {
                preg_match("/public string \$version = '(.*?)';/", $catFileProcess->getOutput(), $matches);
                if (isset($matches[1])) {
                    $latestVersion = $matches[1];
                }
            }
        }
        
        return $this->response->setJSON([
            'update_available' => $updateAvailable,
            'latest_version'   => $latestVersion,
        ]);
    }

    /**
     * Güncelleme işlemini başlatan ve adımları yürüten metod.
     */
    public function runUpdate()
    {
        // Bu metodun çıktısını anlık olarak tarayıcıya göndermek için ayarlar yapıyoruz.
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');

        // Komutları ve açıklamalarını bir diziye alıyoruz.
        $commands = [
            'git pull origin main' => 'Değişiklikler çekiliyor...',
            'composer install --no-dev --optimize-autoloader' => 'Composer bağımlılıkları güncelleniyor...',
            'php spark migrate' => 'Veritabanı göçleri çalıştırılıyor...',
            'php spark cache:clear' => 'Sistem önbelleği temizleniyor...',
        ];
        
        // Her bir komutu sırayla çalıştır ve çıktısını anlık olarak gönder.
        foreach ($commands as $command => $description) {
            echo "data: ### {$description} ###\n\n";
            ob_flush();
            flush();
            
            $process = new Process(['command' => $command, 'cwd' => ROOTPATH]);
            $process->run(function ($type, $buffer) {
                // Çıktıyı satır satır ayırıp gönderiyoruz.
                foreach (explode("\n", trim($buffer)) as $line) {
                    echo "data: " . trim($line) . "\n\n";
                    ob_flush();
                    flush();
                    usleep(10000); // 10ms bekleme
                }
            });

            if (!$process->isOK()) {
                echo "data: ERROR: Komut çalıştırılırken hata oluştu!\n\n";
                echo "data: " . trim($process->getErrorOutput()) . "\n\n";
            }
        }

        echo "data: --- GÜNCELLEME TAMAMLANDI ---\n\n";
        ob_flush();
        flush();
    }
}
