<?php
require_once '../helpers/AuthHelper.php';
require_once '../models/Barang.php';
require_once '../models/Kategori.php';

class BarangController {

    public function __construct() {
        AuthHelper::requireRole(['Admin', 'Supervisor']);
    }

    public function index() {
        $barangModel = new Barang();
        $kategoriModel = new Kategori();

        $search = $_GET['search'] ?? '';
        $kategori_id = $_GET['kategori_id'] ?? '';
        
        // Pagination logic
        $limit = 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = $page > 0 ? $page : 1;
        $offset = ($page - 1) * $limit;

        $totalRows = $barangModel->getTotalRows($search, $kategori_id);
        $totalPages = ceil($totalRows / $limit);

        $items = $barangModel->getAllPaginated($limit, $offset, $search, $kategori_id);
        $categories = $kategoriModel->getAll();

        $title = "Master Data Barang - " . APP_NAME;
        require_once '../views/layouts/header.php';
        require_once '../views/layouts/sidebar.php';
        require_once '../views/barang/index.php';
        require_once '../views/layouts/footer.php';
    }

    public function create() {
        $kategoriModel = new Kategori();
        $categories = $kategoriModel->getAll();

        $title = "Tambah Barang - " . APP_NAME;
        require_once '../views/layouts/header.php';
        require_once '../views/layouts/sidebar.php';
        require_once '../views/barang/form.php';
        require_once '../views/layouts/footer.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'code' => trim($_POST['code']),
                'name' => trim($_POST['name']),
                'kategori_id' => $_POST['kategori_id'],
                'description' => trim($_POST['description']),
                'unit' => trim($_POST['unit']),
                'min_stock' => (int)$_POST['min_stock'],
                'status' => $_POST['status']
            ];

            $barangModel = new Barang();

            // Validation
            if (empty($data['code']) || empty($data['name']) || empty($data['kategori_id']) || empty($data['unit'])) {
                $_SESSION['error'] = "Mohon lengkapi field yang wajib diisi (*)!";
                header("Location: " . BASE_URL . "/barang/create");
                exit;
            }

            if (!$barangModel->isCodeUnique($data['code'])) {
                $_SESSION['error'] = "Kode Barang sudah digunakan. Gunakan kode yang unik.";
                header("Location: " . BASE_URL . "/barang/create");
                exit;
            }

            if ($barangModel->create($data)) {
                
                require_once '../models/LogAktivitas.php';
                $log = new LogAktivitas();
                $log->record('Tambah Barang', "Menambah barang baru: {$data['name']} ({$data['code']})", 'Barang', $data['code']);
                
                $_SESSION['success'] = "Barang berhasil ditambahkan.";
                header("Location: " . BASE_URL . "/barang/index");
            } else {
                $_SESSION['error'] = "Gagal menambahkan barang.";
                header("Location: " . BASE_URL . "/barang/create");
            }
            exit;
        }
    }

    public function edit($id = null) {
        if (!$id) {
            header("Location: " . BASE_URL . "/barang/index");
            exit;
        }

        $barangModel = new Barang();
        $barang = $barangModel->getById($id);

        if (!$barang) {
            header("Location: " . BASE_URL . "/barang/index");
            exit;
        }

        $kategoriModel = new Kategori();
        $categories = $kategoriModel->getAll();

        $title = "Edit Barang - " . APP_NAME;
        require_once '../views/layouts/header.php';
        require_once '../views/layouts/sidebar.php';
        require_once '../views/barang/form.php';
        require_once '../views/layouts/footer.php';
    }

    public function update($id = null) {
        if (!$id) {
            header("Location: " . BASE_URL . "/barang/index");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'code' => trim($_POST['code']),
                'name' => trim($_POST['name']),
                'kategori_id' => $_POST['kategori_id'],
                'description' => trim($_POST['description']),
                'unit' => trim($_POST['unit']),
                'min_stock' => (int)$_POST['min_stock'],
                'status' => $_POST['status']
            ];

            $barangModel = new Barang();

            if (empty($data['code']) || empty($data['name']) || empty($data['kategori_id']) || empty($data['unit'])) {
                $_SESSION['error'] = "Mohon lengkapi field yang wajib diisi (*)!";
                header("Location: " . BASE_URL . "/barang/edit/$id");
                exit;
            }

            if (!$barangModel->isCodeUnique($data['code'], $id)) {
                $_SESSION['error'] = "Kode Barang sudah digunakan oleh produk lain.";
                header("Location: " . BASE_URL . "/barang/edit/$id");
                exit;
            }

            if ($barangModel->update($id, $data)) {
                
                require_once '../models/LogAktivitas.php';
                $log = new LogAktivitas();
                $log->record('Edit Barang', "Memperbarui barang: {$data['name']} ({$data['code']})", 'Barang', $data['code']);
                
                $_SESSION['success'] = "Barang berhasil diperbarui.";
                header("Location: " . BASE_URL . "/barang/index");
            } else {
                $_SESSION['error'] = "Gagal memperbarui barang.";
                header("Location: " . BASE_URL . "/barang/edit/$id");
            }
            exit;
        }
    }

    public function show($id = null) {
        if (!$id) {
            header("Location: " . BASE_URL . "/barang/index");
            exit;
        }

        $barangModel = new Barang();
        $barang = $barangModel->getById($id);

        if (!$barang) {
            header("Location: " . BASE_URL . "/barang/index");
            exit;
        }

        $title = "Detail Barang - " . APP_NAME;
        require_once '../views/layouts/header.php';
        require_once '../views/layouts/sidebar.php';
        require_once '../views/barang/detail.php';
        require_once '../views/layouts/footer.php';
    }

    public function delete($id = null) {
        if ($id) {
            $barangModel = new Barang();
            
            // Fetch before delete to get the code for logging
            $barangData = $barangModel->getById($id);
            
            if ($barangModel->delete($id)) {
                
                if ($barangData) {
                    require_once '../models/LogAktivitas.php';
                    $log = new LogAktivitas();
                    $log->record('Hapus Barang', "Menghapus barang: {$barangData['name']} ({$barangData['code']})", 'Barang', $barangData['code']);
                }

                $_SESSION['success'] = "Barang berhasil dihapus.";
            } else {
                $_SESSION['error'] = "Gagal menghapus barang! Barang ini mungkin masih memiliki stok atau terlibat dalam transaksi.";
            }
        }
        header("Location: " . BASE_URL . "/barang/index");
        exit;
    }

    /**
     * Handle CSV file upload and bulk insertion for Barang
     */
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/barang/index");
            exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = "File CSV tidak valid atau gagal diunggah.";
            header("Location: " . BASE_URL . "/barang/index");
            exit;
        }

        $fileTmp = $_FILES['csv_file']['tmp_name'];
        $fileName = $_FILES['csv_file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($ext !== 'csv' && $ext !== 'txt') {
            $_SESSION['error'] = "Format file harus berekstensi .csv atau .txt";
            header("Location: " . BASE_URL . "/barang/index");
            exit;
        }

        $barangModel = new Barang();
        $result = $barangModel->importFromCsv($fileTmp);

        if ($result['success']) {
            require_once '../models/LogAktivitas.php';
            $log = new LogAktivitas();
            $log->record('Import Barang CSV', "Import CSV: {$result['inserted']} baru, {$result['updated']} diperbarui dari file $fileName", 'Barang', null);

            $msg = "Berhasil mengimpor file CSV: <strong>{$result['inserted']}</strong> barang baru ditambahkan, <strong>{$result['updated']}</strong> barang diperbarui.";
            if (!empty($result['errors'])) {
                $msg .= "<br><small class='text-warning'>" . implode('<br>', array_slice($result['errors'], 0, 5)) . "</small>";
            }
            $_SESSION['success'] = $msg;
        } else {
            $_SESSION['error'] = "Gagal memproses file CSV: " . ($result['message'] ?? 'Kesalahan format file.');
        }

        header("Location: " . BASE_URL . "/barang/index");
        exit;
    }

    /**
     * Download sample CSV template matching format (No;No Material;Nama Barang)
     */
    public function downloadTemplateCsv() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="template_import_barang.csv"');
        
        $output = fopen('php://output', 'w');
        // UTF-8 BOM
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, ['No', 'No Material', 'Nama Barang', 'Kategori', 'Satuan', 'Min Stock', 'Deskripsi'], ';');
        fputcsv($output, ['1', 'BRG-ELK-101', 'Keyboard Mechanical RGB Gaming', 'Elektronik', 'pcs', '10', 'Keyboard switches blue'], ';');
        fputcsv($output, ['2', 'BRG-PAK-202', 'Kemeja Katun Polos Navy L', 'Pakaian', 'pcs', '15', 'Kemeja katun bahan adem'], ';');
        fputcsv($output, ['3', '2022400073', 'IB SIIP RCN 20PCSx4G (D1-CHN) CAL', 'Bahan Makanan', 'box', '20', 'Kemasan karton isi 20'], ';');
        fclose($output);
        exit;
    }
}

