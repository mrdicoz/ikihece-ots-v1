<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ReportSettingsModel;
use App\Models\ServiceReportModel;

class ServiceReportController extends BaseController
{
    public function index()
    {
        $settingsModel = new ReportSettingsModel();
        $serviceReportModel = new ServiceReportModel();

        // Handle settings update
        if (strtolower($this->request->getMethod()) === 'post') {
            $rules = [
                'tracking_start_time' => 'required|valid_date[H:i]',
                'tracking_end_time'   => 'required|valid_date[H:i]',
                'fuel_price'          => 'required|regex_match[/^\d+([,.]\d+)?$/]',
                'tracking_active_days' => 'permit_empty',
            ];

            if ($this->validate($rules)) {
                $fuel_price = str_replace(',', '.', $this->request->getPost('fuel_price'));

                $settings = [
                    'tracking_start_time' => $this->request->getPost('tracking_start_time'),
                    'tracking_end_time'   => $this->request->getPost('tracking_end_time'),
                    'fuel_price'          => $fuel_price,
                    'tracking_active_days' => $this->request->getPost('tracking_active_days') ?? [],
                ];
                $settingsModel->saveSettings($settings);

                return redirect()->to('admin/service-reports')->with('message', 'Ayarlar başarıyla güncellendi.');
            } else {
                return redirect()->to('admin/service-reports')->withInput()->with('errors', $this->validator->getErrors());
            }
        }

        // Prepare data for the view
        $currentSettings = $settingsModel->getSettings();
        $data['settings'] = array_merge([
            'tracking_start_time' => '08:00',
            'tracking_end_time'   => '19:00',
            'fuel_price'          => '0.0',
            'tracking_active_days' => '1,2,3,4,5', // Default to Mon-Fri
        ], $currentSettings);
        
        $data['active_days'] = explode(',', $data['settings']['tracking_active_days']);

        // Handle filtering
        $selectedMonth = $this->request->getGet('month') ?? date('m');
        $selectedYear = $this->request->getGet('year') ?? date('Y');

        $data['selectedMonth'] = $selectedMonth;
        $data['selectedYear'] = $selectedYear;

        // Fetch drivers for the filter dropdown
        $db = \Config\Database::connect();
        $data['drivers'] = $db->table('users')
                                 ->select('users.id, users.username')
                                 ->join('auth_groups_users', 'auth_groups_users.user_id = users.id')
                                 ->where('auth_groups_users.group', 'servis')
                                 ->get()
                                 ->getResultArray();

        $selectedDriver = $this->request->getGet('driver_id');
        $data['selectedDriver'] = $selectedDriver;

        // Fetch and paginate reports
        $reportsQuery = $serviceReportModel
            ->select('service_reports.*, users.username')
            ->join('users', 'users.id = service_reports.user_id', 'left')
            ->where('MONTH(date)', $selectedMonth)
            ->where('YEAR(date)', $selectedYear);

        if (!empty($selectedDriver)) {
            $reportsQuery->where('service_reports.user_id', $selectedDriver);
        }

        $reports = $reportsQuery->orderBy('date', 'DESC')->paginate(31);

        $data['reports'] = $reports;
        $data['pager'] = $serviceReportModel->pager;

        // Prepare data for charts and summary cards
        $summaryQuery = $serviceReportModel
            ->select('SUM(total_km) as total_distance, SUM(total_idle_time_seconds) as total_idle_time')
            ->where('MONTH(date)', $selectedMonth)
            ->where('YEAR(date)', $selectedYear);

        if (!empty($selectedDriver)) {
            $summaryQuery->where('user_id', $selectedDriver);
        }
        $summary = $summaryQuery->first();

        $data['total_distance'] = $summary['total_distance'] ?? 0;
        $data['total_idle_time'] = $summary['total_idle_time'] ?? 0;
        $data['total_fuel_cost'] = $data['total_distance'] * (float)($currentSettings['fuel_price'] ?? 0);

        // Data for pie chart (distance per driver)
        $pieChartQuery = $serviceReportModel
            ->select('users.username, SUM(service_reports.total_km) as total_km')
            ->join('users', 'users.id = service_reports.user_id')
            ->where('MONTH(date)', $selectedMonth)
            ->where('YEAR(date)', $selectedYear)
            ->groupBy('users.username');
        
        if (!empty($selectedDriver)) {
            $pieChartQuery->where('service_reports.user_id', $selectedDriver);
        }

        $pieChartData = $pieChartQuery->findAll();

        $data['pie_chart_data'] = [
            'labels' => array_column($pieChartData, 'username'),
            'data'   => array_column($pieChartData, 'total_km'),
        ];

        return view('admin/service_report/index', array_merge($this->data, $data));
    }
}