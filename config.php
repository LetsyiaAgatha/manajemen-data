<?php
/**
 * Konfigurasi Database - Referral System (SQLite Version)
 * RSUD Maju Jaya
 */

// Lokasi file database SQLite
$db_file = __DIR__ . '/dms_hospital.sqlite';

try {
    // Koneksi menggunakan PDO SQLite
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    /**
     * Wrapper sederhana untuk menjaga kompatibilitas dengan kode lama yang menggunakan mysqli
     * Ini memungkinkan kita menggunakan $conn->query() seperti sebelumnya.
     */
    class SQLiteCompatibilityLayer {
        private $pdo;
        public $insert_id;

        public function __construct($pdo) {
            $this->pdo = $pdo;
        }

        public function query($sql) {
            try {
                $stmt = $this->pdo->query($sql);
                // Update insert_id jika ini adalah operasi INSERT
                if (stripos($sql, 'INSERT') === 0) {
                    $this->insert_id = $this->pdo->lastInsertId();
                }
                return new SQLiteResultWrapper($stmt);
            } catch (PDOException $e) {
                // Simulasi error mysqli
                die("Database Error: " . $e->getMessage() . " (SQL: $sql)");
            }
        }

        public function real_escape_string($string) {
            // SQLite PDO handle quoting differently, tapi untuk compatibility kita strip saja
            return str_replace("'", "''", $string);
        }
    }

    class SQLiteResultWrapper {
        private $stmt;
        public $num_rows = 0;
        private $data = [];
        private $index = 0;

        public function __construct($stmt) {
            $this->stmt = $stmt;
            if ($stmt) {
                // SQLite tidak punya num_rows bawaan di PDO, kita hitung manual untuk SELECT
                $all = $this->stmt->fetchAll();
                $this->num_rows = count($all);
                // Reset statement untuk fetch
                $this->data = $all;
                $this->index = 0;
            }
        }

        public function fetch_assoc() {
            if (isset($this->data[$this->index])) {
                return $this->data[$this->index++];
            }
            return null;
        }

        public function fetch_row() {
            $row = $this->fetch_assoc();
            return $row ? array_values($row) : null;
        }

        public function data_seek($n) {
            $this->index = $n;
        }
    }

    $conn = new SQLiteCompatibilityLayer($pdo);

} catch (PDOException $e) {
    die("Koneksi SQLite Gagal: " . $e->getMessage());
}

// Fonnte API Settings
define('FONNTE_TOKEN', 'wX252AUYDchAWcfq8pR3');

/**
 * Fungsi Global untuk kirim WhatsApp via Fonnte
 */
function sendWhatsApp($target, $message) {
    $curl = curl_init();

    // Pastikan target adalah string dan tidak null
    $target = (string)$target;
    $target = preg_replace('/[^0-9]/', '', $target);
    if (substr($target, 0, 1) === '0') {
        $target = '62' . substr($target, 1);
    }

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.fonnte.com/send',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => array(
        'target' => $target,
        'message' => $message,
        'countryCode' => '62', // Default Indonesia
      ),
      CURLOPT_HTTPHEADER => array(
        'Authorization: ' . FONNTE_TOKEN
      ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
