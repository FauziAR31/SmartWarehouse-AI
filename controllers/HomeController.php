<?php
require_once '../helpers/AuthHelper.php';
require_once '../models/Dashboard.php';

class HomeController {
    public function index() {
        AuthHelper::requireLogin();

        $dashboard = new Dashboard();

        $stokMinimum = $dashboard->getBarangStokMinimum();
        $barangHabis = $dashboard->getBarangHabis();

        $data = [
            'total_barang'          => $dashboard->getTotalBarang(),
            'total_stok'            => $dashboard->getTotalStok(),
            'barang_masuk_hari_ini' => $dashboard->getBarangMasukHariIni(),
            'barang_keluar_hari_ini'=> $dashboard->getBarangKeluarHariIni(),
            'total_lokasi'          => $dashboard->getTotalLokasi(),
            'stok_minimum'          => $stokMinimum,
            'barang_habis'          => $barangHabis,
            'recent_transactions'   => $dashboard->getRecentTransactions(),
            'chart_data'            => json_encode($dashboard->getChartData())
        ];

        // Send WhatsApp low-stock alert once per session (avoid flooding)
        if (empty($_SESSION['wa_low_stock_notified_today']) || $_SESSION['wa_low_stock_notified_today'] !== date('Y-m-d')) {
            try {
                require_once '../helpers/WhatsAppHelper.php';
                // Notify for each critical/habis item
                $combined = array_merge(
                    array_map(fn($b) => array_merge($b, ['_type' => 'minimum']), $stokMinimum),
                    array_map(fn($b) => array_merge($b, ['_type' => 'habis']), $barangHabis)
                );
                foreach ($combined as $item) {
                    $code = $item['code'] ?? $item['barang_code'] ?? '';
                    $name = $item['name'] ?? $item['barang_name'] ?? 'Material';
                    $qty  = (int)($item['total_qty'] ?? $item['qty'] ?? 0);
                    $min  = (int)($item['min_stock'] ?? 5);
                    WhatsAppHelper::notifyLowStock($name, $code, $qty, $min);
                }
                $_SESSION['wa_low_stock_notified_today'] = date('Y-m-d');
            } catch (Throwable $e) {
                // Keep resilient
            }
        }

        $title = "Dashboard - " . APP_NAME;
        require_once '../views/layouts/header.php';
        require_once '../views/layouts/sidebar.php';
        require_once '../views/home/index.php';
        require_once '../views/layouts/footer.php';
    }
}
