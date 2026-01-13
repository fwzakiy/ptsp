<?php
// api.php (Versi Final Lengkap: Database, Antrian, Log, Setting, & Jadwal Salat)

// ----------------------------------------------------------------------
// 1. KONFIGURASI DATABASE
// ----------------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_USER', 'zakiymyi_ptsp_amal');
define('DB_PASS', '_EvcEO!DlFm70+$^');
define('DB_NAME', 'zakiymyi_ptsp_amal');
// ----------------------------------------------------------------------
define('TIMESTAMP_FILE', 'antrian_reset.touch');
// ----------------------------------------------------------------------


// 2. HEADER
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// 3. KONEKSI DATABASE
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset('utf8');

// 4. FUNGSI AUTO RESET HARIAN
// (Hanya me-reset counter harian, tidak menghapus log)
function cekDanResetHarian($conn) {
    $today = date('Y-m-d');
    if (!file_exists(TIMESTAMP_FILE)) { touch(TIMESTAMP_FILE); }
    $lastResetDate = date('Y-m-d', filemtime(TIMESTAMP_FILE));
    if ($today > $lastResetDate) {
        $conn->query("UPDATE antrian_layanan SET total_antrian = 0, nomor_sekarang = 0, loket_terakhir = 1");
        touch(TIMESTAMP_FILE);
        error_log("Antrian PTSP otomatis direset pada tanggal: " . $today);
    }
}

// 5. ROUTING / LOGIKA UTAMA
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

cekDanResetHarian($conn);

switch ($action) {
    // --- AKSI: STATUS (Untuk Display & Admin) ---
    case 'status':
        $result = $conn->query("SELECT * FROM antrian_layanan");
        $state = [];
        while ($row = $result->fetch_assoc()) {
            $state[$row['kode']] = $row;
            $state[$row['kode']]['total_antrian'] = (int)$row['total_antrian'];
            $state[$row['kode']]['nomor_sekarang'] = (int)$row['nomor_sekarang'];
            $state[$row['kode']]['loket_terakhir'] = (int)$row['loket_terakhir'];
        }
        echo json_encode($state);
        break;

    // --- AKSI: TAMBAH (Ambil Tiket) ---
    case 'tambah':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             http_response_code(405);
             echo json_encode(['success' => false, 'message' => 'Hanya metode POST yang diizinkan']);
             break;
        }
        $kode = $input['kode'] ?? '';
        if (empty($kode) || !in_array($kode, ['A', 'B', 'C', 'D'])) { 
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Kode layanan tidak valid']);
            break;
        }
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE antrian_layanan SET total_antrian = total_antrian + 1 WHERE kode = '$kode'");
            $result = $conn->query("SELECT total_antrian FROM antrian_layanan WHERE kode = '$kode'");
            $newTotal = (int)$result->fetch_assoc()['total_antrian'];
            
            // Catat ke Log
            $stmt = $conn->prepare("INSERT INTO antrian_log (kode_layanan, nomor_antrian) VALUES (?, ?)");
            $stmt->bind_param('si', $kode, $newTotal);
            $stmt->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'nomor_baru' => $kode . '-' . $newTotal]);
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error transaksi database']);
        }
        break;

    // --- AKSI: PANGGIL (Admin Loket) ---
    case 'panggil':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             http_response_code(405);
             echo json_encode(['success' => false, 'message' => 'Hanya metode POST yang diizinkan']);
             break;
        }
        $kode = $input['kode'] ?? '';
        $loket = (int)($input['loket'] ?? 1);

        $conn->begin_transaction();
        try {
            $result = $conn->query("SELECT * FROM antrian_layanan WHERE kode = '$kode' FOR UPDATE");
            $state = $result->fetch_assoc();
            if ((int)$state['nomor_sekarang'] >= (int)$state['total_antrian']) {
                $conn->rollback();
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Antrian ' . $kode . ' sudah habis']);
                break;
            }
            $newNomor = (int)$state['nomor_sekarang'] + 1;
            
            // Update State
            $stmt = $conn->prepare("UPDATE antrian_layanan SET nomor_sekarang = ?, loket_terakhir = ? WHERE kode = ?");
            $stmt->bind_param('iis', $newNomor, $loket, $kode);
            $stmt->execute();
            $conn->commit();
            
            // Update Log (Waktu Dilayani)
            $stmt_log = $conn->prepare(
                "UPDATE antrian_log SET waktu_dilayani = CURRENT_TIMESTAMP 
                 WHERE kode_layanan = ? AND nomor_antrian = ? AND DATE(waktu_ambil) = CURDATE() AND waktu_dilayani IS NULL"
            );
            $stmt_log->bind_param('si', $kode, $newNomor);
            $stmt_log->execute();
            
            echo json_encode([
                'success' => true,
                'nomor_dipanggil' => $kode . '-' . $newNomor,
                'loket_terakhir' => $loket,
                'kode_layanan' => $kode
            ]);
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error transaksi database']);
        }
        break;

    // --- AKSI: RESET HARIAN (Tombol Kuning) ---
    case 'reset':
        // 1. Reset counter
        $conn->query("UPDATE antrian_layanan SET total_antrian = 0, nomor_sekarang = 0, loket_terakhir = 1");
        
        // 2. Kosongkan 'waktu_dilayani' (Log tetap ada, status jadi belum dilayani)
        $conn->query("UPDATE antrian_log SET waktu_dilayani = NULL"); 
        
        // 3. Update timestamp
        touch(TIMESTAMP_FILE); 
        
        echo json_encode(['success' => true, 'message' => 'Antrian harian direset ke 0 dan "Waktu Dilayani" telah dikosongkan.']);
        break;

    // --- AKSI: HAPUS SEMUA DATA (Tombol Merah) ---
    case 'hapus_semua':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        
        // 1. Hapus TOTAL semua log
        $conn->query("TRUNCATE TABLE antrian_log"); 
        
        // 2. Reset counter juga
        $conn->query("UPDATE antrian_layanan SET total_antrian = 0, nomor_sekarang = 0, loket_terakhir = 1");
        touch(TIMESTAMP_FILE); 
        
        echo json_encode(['success' => true, 'message' => 'SEMUA DATA LOG antrian telah berhasil dihapus permanen.']);
        break;

    // --- AKSI: LAPORAN (Filter Harian/Bulanan/Tahunan) ---
    case 'laporan':
        $filterDay = $_GET['day'] ?? null;
        $filterMonth = $_GET['month'] ?? null;
        $filterYear = $_GET['year'] ?? null;

        $query = "SELECT * FROM antrian_log";
        $params = [];
        $types = "";

        if ($filterDay) {
            $query .= " WHERE DATE(waktu_ambil) = ?";
            $params[] = $filterDay;
            $types = "s";
        } elseif ($filterMonth) {
            $year = substr($filterMonth, 0, 4);
            $month = substr($filterMonth, 5, 2);
            $query .= " WHERE YEAR(waktu_ambil) = ? AND MONTH(waktu_ambil) = ?";
            $params[] = $year; $params[] = $month;
            $types = "ss";
        } elseif ($filterYear) {
            $query .= " WHERE YEAR(waktu_ambil) = ?";
            $params[] = $filterYear;
            $types = "s";
        } else {
            $query .= " WHERE DATE(waktu_ambil) = CURDATE()";
        }
        $query .= " ORDER BY waktu_ambil DESC";

        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $bind_params = [];
            $bind_params[] = & $types; 
            for ($i = 0; $i < count($params); $i++) {
                $bind_params[] = & $params[$i]; 
            }
            call_user_func_array(array($stmt, 'bind_param'), $bind_params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        echo json_encode($logs);
        break;
        
    // --- AKSI: HAPUS SATU LOG ---
    case 'hapus':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $id = (int)($input['id'] ?? 0);
        if ($id === 0) { http_response_code(400); exit; }

        $stmt = $conn->prepare("DELETE FROM antrian_log WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Data log telah dihapus']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus data']);
        }
        break;

    // --- AKSI: GET SETTING (Teks Berjalan) ---
    case 'get_setting':
        $key = $_GET['key'] ?? '';
        $stmt = $conn->prepare("SELECT key_value FROM settings WHERE key_name = ?");
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        echo json_encode(['key_name' => $key, 'key_value' => $row ? $row['key_value'] : '']); 
        break;
        
    // --- AKSI: UPDATE SETTING (Teks Berjalan) ---
    case 'update_setting':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $key = $input['key'] ?? '';
        $value = $input['value'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = ?");
        $stmt->bind_param('sss', $key, $value, $value);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Pengaturan berhasil disimpan']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan']);
        }
        break;

    // --- AKSI: JADWAL SALAT (Proxy API Kemenag) ---
    case 'jadwal_salat':
        // ID Kota Depok = 0507
        $id_kota = "0507"; 
        $tanggal = date("Y/m/d");
        $api_url = "https://api.myquran.com/v2/sholat/jadwal/$id_kota/$tanggal";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'KemenagDepokQueue/1.0');
        $output = curl_exec($ch);
        curl_close($ch);
        
        echo $output;
        break;

    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Aksi tidak ditemukan']);
        break;
}

// 6. TUTUP KONEKSI
if ($action !== 'jadwal_salat') {
    $conn->close();
}
?>