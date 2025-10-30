<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportSettingsModel extends Model
{
    protected $table            = 'report_settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'setting_key',
        'setting_value',
    ];

    protected $useTimestamps = false;

    /**
     * Get all settings as an associative array.
     *
     * @return array
     */
    public function getSettings(): array
    {
        $settings = [];
        foreach ($this->findAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Save settings from an associative array.
     *
     * @param array $settings
     * @return void
     */
    public function saveSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $entry = $this->where('setting_key', $key)->first();
            $data = [
                'setting_key'   => $key,
                'setting_value' => is_array($value) ? implode(',', $value) : $value,
            ];

            if ($entry) {
                $this->update($entry['id'], $data);
            } else {
                $this->insert($data);
            }
        }
    }
}
