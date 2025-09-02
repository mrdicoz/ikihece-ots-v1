<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class UpdateController extends BaseController
{
    public function index()
    {
        $data = [
            'title'          => 'Sistem Güncelleme',
            'currentVersion' => config('Ots')->version,
        ];

        return view('admin/update/index', array_merge($this->data, $data));
    }

    public function check()
    {
        try {
            $updateManifestUrl = 'https://updates.mantaryazilim.tr/versions.json';
            $client = \Config\Services::curlrequest(['timeout' => 15]);
            $response = $client->get($updateManifestUrl, ['query' => ['v' => time()]]);
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Güncelleme manifest dosyasına ulaşılamadı. Yanıt Kodu: ' . $response->getStatusCode());
            }
            $release = json_decode($response->getBody());
            $latestVersion = $release->latest_version;
            $currentVersion = config('Ots')->version;
            $updateAvailable = version_compare($latestVersion, $currentVersion, '>');
            return $this->response->setJSON([
                'update_available' => $updateAvailable,
                'latest_version'   => $latestVersion,
                'download_url'     => $release->download_url ?? null,
                'release_notes'    => $release->release_notes ?? 'Sürüm notu bulunmuyor.'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['error' => 'Güncelleme kontrol hatası: ' . $e->getMessage()])->setStatusCode(500);
        }
    }

    public function runUpdate()
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        
        $downloadUrl = $this->request->getGet('url');
        if (empty($downloadUrl)) {
            $this->stream_message("HATA: İndirme URL'si belirtilmedi.", 'error');
            return;
        }

        $zipPath = WRITEPATH . 'updates/update.zip';
        $extractPath = WRITEPATH . 'updates/extracted/';
        
        try {
            if (!is_dir(WRITEPATH . 'updates')) { mkdir(WRITEPATH . 'updates', 0777, true); }
            if (is_dir($extractPath)) { $this->delete_directory($extractPath); }
            mkdir($extractPath, 0777, true);

            $this->stream_message("Güncelleme paketi indiriliyor...");
            $this->download_with_progress($downloadUrl, $zipPath);
            
            $this->stream_message("Paket dosyaları geçici klasöre çıkartılıyor...");
            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) { throw new \Exception("Zip dosyası açılamadı."); }
            $zip->extractTo($extractPath);
            $zip->close();
            $this->stream_message("Dosyalar başarıyla çıkartıldı.");

            $this->stream_message("Yeni dosyalar kopyalanıyor...");
            
            $files = array_diff(scandir($extractPath), ['.', '..']);
            $sourceDir = $extractPath;
            if (count($files) === 1 && is_dir($extractPath . DIRECTORY_SEPARATOR . reset($files))) {
                $sourceDir = $extractPath . DIRECTORY_SEPARATOR . reset($files);
            }
            
            $this->copy_directory($sourceDir, ROOTPATH);

            $this->stream_message("Dosya kopyalama tamamlandı.");
            
            $this->stream_message("Veritabanı ve önbellek işlemleri yapılıyor...");
            shell_exec('php ' . ROOTPATH . 'spark migrate -all');
            $this->stream_message("Veritabanı migrate edildi.");
            shell_exec('php ' . ROOTPATH . 'spark cache:clear');
            $this->stream_message("Önbellek temizlendi.");
            
            if (function_exists('opcache_reset')) {
                opcache_reset();
                $this->stream_message("PHP hafıza önbelleği (OPcache) temizlendi.");
            }
            
            $this->stream_message("Geçici dosyalar siliniyor...");
            @unlink($zipPath);
            $this->delete_directory($extractPath);
            $this->stream_message("Temizlik tamamlandı.");

            $this->stream_message("--- GÜNCELLEME BAŞARIYLA TAMAMLANDI ---", 'success');
        } catch (\Exception $e) {
            $this->stream_message("KRİTİK HATA: " . $e->getMessage(), 'error');
        }
    }

    private function download_with_progress($url, $destination)
    {
        $fileHandle = fopen($destination, 'w');
        $lastProgress = -1;
        $options = [CURLOPT_FILE => $fileHandle, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 300, CURLOPT_NOPROGRESS => false, CURLOPT_PROGRESSFUNCTION => function ($resource, $download_size, $downloaded_size) use (&$lastProgress) {
            if ($download_size > 0) {
                $progress = floor($downloaded_size / $download_size * 100);
                if ($progress > $lastProgress) {
                    $lastProgress = $progress;
                    if ($progress % 5 === 0) {
                        $this->stream_message("İndiriliyor: {$progress}%");
                    }
                }
            }
        }];
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            fclose($fileHandle);
            throw new \Exception('cURL indirme hatası: ' . $error);
        }
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fileHandle);
        if ($statusCode !== 200) {
            @unlink($destination);
            throw new \Exception("Paket indirilemedi. Sunucu HTTP yanıt kodu: {$statusCode}");
        }
    }

    private function stream_message(string $message, string $type = 'log')
    {
        echo "data: " . json_encode(['type' => $type, 'message' => $message]) . "\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    private function copy_directory($source, $destination)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $destPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0775, true);
                }
            } else {
                copy($item, $destPath);
            }
        }
    }

    private function delete_directory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return @unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }
}