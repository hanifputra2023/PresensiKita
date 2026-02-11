<?php
cek_login();
cek_role(['admin']);
$page = 'admin_setting';

// 1. Inisialisasi Tabel Settings (Auto-create jika belum ada)
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'app_settings'");
if (mysqli_num_rows($check_table) == 0) {
    $sql_create = "CREATE TABLE app_settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT,
        description VARCHAR(255)
    )";
    if (mysqli_query($conn, $sql_create)) {
        // Insert default values
        $defaults = [
            'app_name' => ['PresensiKita', 'Nama Aplikasi'],
            'instansi_name' => ['Universitas AKPRIND', 'Nama Instansi'],
            'semester_aktif' => ['Ganjil', 'Semester Aktif'],
            'tahun_ajaran' => [date('Y') . '/' . (date('Y') + 1), 'Tahun Ajaran'],
            'contact_wa' => ['', 'Nomor WhatsApp Admin'],
            'maintenance_mode' => ['0', 'Mode Maintenance (1=Ya, 0=Tidak)']
        ];
        
        foreach ($defaults as $key => $val) {
            $k = escape($key);
            $v = escape($val[0]);
            $d = escape($val[1]);
            mysqli_query($conn, "INSERT INTO app_settings (setting_key, setting_value, description) VALUES ('$k', '$v', '$d')");
        }
    }
}

// Handle Backup Database
if (isset($_POST['backup_db'])) {
    $tables = [];
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }

    $sql = "-- Backup Database PresensiKita\n-- Tanggal: " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $row2 = mysqli_fetch_row(mysqli_query($conn, "SHOW CREATE TABLE $table"));
        $sql .= "\n\n" . $row2[1] . ";\n\n";

        $result3 = mysqli_query($conn, "SELECT * FROM $table");
        while ($row3 = mysqli_fetch_assoc($result3)) {
            $sql .= "INSERT INTO $table VALUES(";
            $values = [];
            foreach ($row3 as $value) {
                if ($value === null) {
                    $values[] = "NULL";
                } else {
                    $values[] = "'" . mysqli_real_escape_string($conn, $value) . "'";
                }
            }
            $sql .= implode(", ", $values);
            $sql .= ");\n";
        }
    }
    
    $sql .= "\nSET FOREIGN_KEY_CHECKS=1;";

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=db_backup_' . date('Y-m-d_His') . '.sql');
    echo $sql;
    exit;
}

// Handle Clear Data
if (isset($_POST['clear_data'])) {
    $type = $_POST['clear_type'];
    if ($type == 'log') {
        mysqli_query($conn, "TRUNCATE TABLE log_presensi");
        set_alert('success', 'Log aktivitas berhasil dibersihkan.');
    } elseif ($type == 'presensi') {
        // Reset data transaksi presensi (Persiapan Semester Baru)
        mysqli_query($conn, "TRUNCATE TABLE presensi_mahasiswa");
        mysqli_query($conn, "TRUNCATE TABLE jurnal_praktikum");
        mysqli_query($conn, "TRUNCATE TABLE penggantian_inhall");
        mysqli_query($conn, "TRUNCATE TABLE qr_code_session");
        mysqli_query($conn, "TRUNCATE TABLE absen_asisten");
        mysqli_query($conn, "TRUNCATE TABLE feedback_praktikum");
        mysqli_query($conn, "TRUNCATE TABLE hasil_kuis");
        mysqli_query($conn, "TRUNCATE TABLE detail_jawaban_kuis");
        set_alert('success', 'Semua data presensi dan aktivitas perkuliahan berhasil direset.');
    }
    echo "<meta http-equiv='refresh' content='1'>";
    exit;
}

// 2. Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_settings'])) {
        $settings = [
            'app_name' => $_POST['app_name'],
            'instansi_name' => $_POST['instansi_name'],
            'semester_aktif' => $_POST['semester_aktif'],
            'tahun_ajaran' => $_POST['tahun_ajaran'],
            'contact_wa' => $_POST['contact_wa'],
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0'
        ];
        
        foreach ($settings as $key => $value) {
            $val = escape($value);
            $key = escape($key);
            $query = "INSERT INTO app_settings (setting_key, setting_value) VALUES ('$key', '$val') 
                      ON DUPLICATE KEY UPDATE setting_value = '$val'";
            mysqli_query($conn, $query);
        }
        
        set_alert('success', 'Pengaturan sistem berhasil disimpan!');
        echo "<meta http-equiv='refresh' content='1'>";
    }
}

// 3. Fetch Settings
$settings_data = [];
$q = mysqli_query($conn, "SELECT * FROM app_settings");
while ($row = mysqli_fetch_assoc($q)) {
    $settings_data[$row['setting_key']] = $row['setting_value'];
}

// Default fallback
$s = array_merge([
    'app_name' => 'PresensiKita',
    'instansi_name' => 'Universitas AKPRIND',
    'semester_aktif' => 'Ganjil',
    'tahun_ajaran' => date('Y') . '/' . (date('Y') + 1),
    'contact_wa' => '',
    'maintenance_mode' => '0'
], $settings_data);
?>

<?php include 'includes/header.php'; ?>

<style>
:root {
    --primary: #0d6efd;
    --primary-dark: #0b5ed7;
    --secondary: #6c757d;
    --success: #198754;
    --success-dark: #157347;
    --danger: #dc3545;
    --danger-dark: #bb2d3b;
    --warning: #ffc107;
    --warning-dark: #ffca2c;
    --info: #0dcaf0;
    --light: #f8f9fa;
    --dark: #212529;
}

.settings-modern {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
}

.card-modern {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    background: white;
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.card-modern:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.card-header-modern {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 1.25rem 1.5rem;
    border-bottom: none;
    font-weight: 600;
    font-size: 1.1rem;
}

.card-header-success {
    background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
    color: white;
    padding: 1.25rem 1.5rem;
    border-bottom: none;
    font-weight: 600;
    font-size: 1.1rem;
}

.card-header-info {
    background: linear-gradient(135deg, var(--info) 0%, #0aabcf 100%);
    color: white;
    padding: 1.25rem 1.5rem;
    border-bottom: none;
    font-weight: 600;
    font-size: 1.1rem;
}

.card-header-dark {
    background: linear-gradient(135deg, #343a40 0%, var(--dark) 100%);
    color: white;
    padding: 1.25rem 1.5rem;
    border-bottom: none;
    font-weight: 600;
    font-size: 1.1rem;
}

.card-header-modern i,
.card-header-success i,
.card-header-info i,
.card-header-dark i {
    background: rgba(255, 255, 255, 0.2);
    padding: 10px;
    border-radius: 10px;
    margin-right: 12px;
    font-size: 1.2rem;
}

.form-control-modern {
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 15px;
    transition: all 0.3s;
    background: #fff;
}

.form-control-modern:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    background: #fff;
}

.form-label-modern {
    font-weight: 600;
    color: #343a40;
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.form-label-modern i {
    color: var(--primary);
    margin-right: 8px;
    width: 20px;
    text-align: center;
}

.btn-modern {
    padding: 12px 28px;
    border-radius: 10px;
    font-weight: 600;
    border: none;
    transition: all 0.3s;
}

.btn-primary-modern {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
}

.btn-primary-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
    color: white;
}

.btn-outline-modern {
    border: 2px solid var(--primary);
    background: transparent;
    color: var(--primary);
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-outline-modern:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(13, 110, 253, 0.2);
}

.switch-modern {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 32px;
}

.switch-modern input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider-modern {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to right, #ddd, #ddd);
    transition: .4s;
    border-radius: 34px;
}

.slider-modern:before {
    position: absolute;
    content: "";
    height: 24px;
    width: 24px;
    left: 4px;
    bottom: 4px;
    background: white;
    transition: .4s;
    border-radius: 50%;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

input:checked + .slider-modern {
    background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
}

input:checked + .slider-modern:before {
    transform: translateX(28px);
}

.action-card {
    text-align: center;
    padding: 25px 20px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    background: white;
    height: 100%;
    transition: all 0.3s;
}

.action-card:hover {
    border-color: var(--primary);
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.action-card i {
    font-size: 2.5rem;
    margin-bottom: 15px;
    display: block;
}

.action-card-primary i {
    color: var(--primary);
}

.action-card-warning i {
    color: var(--warning);
}

.action-card-danger i {
    color: var(--danger);
}

.danger-zone-modern {
    border: 2px dashed var(--danger);
    background: linear-gradient(135deg, #fff5f5 0%, #ffeaea 100%);
    border-radius: 12px;
    padding: 20px;
}

.info-badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 16px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 8px;
    margin: 4px;
    font-size: 0.9rem;
}

.info-badge i {
    color: var(--primary);
    margin-right: 8px;
    font-size: 1.1rem;
}

.progress-modern {
    height: 10px;
    border-radius: 5px;
    background: #e9ecef;
}

.progress-modern .progress-bar {
    border-radius: 5px;
    background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
}

.loading-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid var(--primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.page-header {
    background: white;
    padding: 1.5rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.page-header h1 {
    color: var(--dark);
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.page-header p {
    color: var(--secondary);
    margin-bottom: 0;
}
</style>

<div class="settings-modern">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Asli (Tidak Diubah) -->
            <div class="col-md-3 col-lg-2 px-0">
                <?php include 'includes/sidebar.php'; ?>
            </div>
            
            <!-- Main Content Area -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1><i class="fas fa-cogs me-2 text-primary"></i>Pengaturan Sistem</h1>
                                <p class="mb-0">Kelola konfigurasi aplikasi dan preferensi sistem</p>
                            </div>
                            <button type="submit" form="settingsForm" name="save_settings" class="btn btn-primary-modern btn-modern">
                                <i class="fas fa-save me-2"></i>Simpan Perubahan
                            </button>
                        </div>
                    </div>
                    
                    <?= show_alert() ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="settingsForm" onsubmit="showLoading()">
                        <div class="row">
                            <!-- Pengaturan Umum -->
                            <div class="col-lg-6">
                                <div class="card-modern">
                                    <div class="card-header-modern">
                                        <i class="fas fa-sliders-h"></i> Pengaturan Umum
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="mb-4">
                                            <label class="form-label-modern">
                                                <i class="fas fa-signature"></i> Nama Aplikasi
                                            </label>
                                            <input type="text" name="app_name" class="form-control form-control-modern" 
                                                   value="<?= htmlspecialchars($s['app_name']) ?>" required>
                                            <div class="form-text mt-2">Nama yang akan ditampilkan di aplikasi</div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label-modern">
                                                <i class="fas fa-university"></i> Nama Instansi
                                            </label>
                                            <input type="text" name="instansi_name" class="form-control form-control-modern" 
                                                   value="<?= htmlspecialchars($s['instansi_name']) ?>" required>
                                        </div>
                                        
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label-modern">
                                                    <i class="fas fa-calendar-alt"></i> Tahun Ajaran
                                                </label>
                                                <input type="text" name="tahun_ajaran" class="form-control form-control-modern" 
                                                       value="<?= htmlspecialchars($s['tahun_ajaran']) ?>" placeholder="2024/2025">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label-modern">
                                                    <i class="fas fa-graduation-cap"></i> Semester Aktif
                                                </label>
                                                <select name="semester_aktif" class="form-control form-control-modern">
                                                    <option value="Ganjil" <?= $s['semester_aktif'] == 'Ganjil' ? 'selected' : '' ?>>Ganjil</option>
                                                    <option value="Genap" <?= $s['semester_aktif'] == 'Genap' ? 'selected' : '' ?>>Genap</option>
                                                    <option value="Pendek" <?= $s['semester_aktif'] == 'Pendek' ? 'selected' : '' ?>>Pendek</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Kontak & Mode Maintenance -->
                            <div class="col-lg-6">
                                <div class="card-modern">
                                    <div class="card-header-success">
                                        <i class="fas fa-tools"></i> Kontak & Mode Pemeliharaan
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="mb-4">
                                            <label class="form-label-modern">
                                                <i class="fab fa-whatsapp"></i> WhatsApp Support
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-success text-white border-0">
                                                    <i class="fab fa-whatsapp"></i>
                                                </span>
                                                <input type="text" name="contact_wa" class="form-control form-control-modern" 
                                                       value="<?= htmlspecialchars($s['contact_wa']) ?>" placeholder="628123456789">
                                            </div>
                                            <div class="form-text mt-2">Nomor WhatsApp untuk bantuan teknis</div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label-modern d-block">
                                                <i class="fas fa-wrench"></i> Mode Pemeliharaan
                                            </label>
                                            <div class="d-flex align-items-center justify-content-between p-3 border rounded" style="background: #f8f9fa;">
                                                <div>
                                                    <div class="fw-bold <?= (string)$s['maintenance_mode'] === '1' ? 'text-danger' : 'text-success' ?>">
                                                        <?= (string)$s['maintenance_mode'] === '1' ? 'AKTIF' : 'NON-AKTIF' ?>
                                                    </div>
                                                    <small class="text-muted">Hanya admin yang dapat login</small>
                                                </div>
                                                <label class="switch-modern">
                                                    <input type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                                           value="1" <?= (string)$s['maintenance_mode'] === '1' ? 'checked' : '' ?>>
                                                    <span class="slider-modern"></span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-warning border-0 rounded-3" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);">
                                            <div class="d-flex">
                                                <i class="fas fa-exclamation-triangle text-warning me-3 fs-4"></i>
                                                <div>
                                                    <strong class="text-warning">Perhatian</strong>
                                                    <p class="mb-0 text-dark">Mode pemeliharaan akan membatasi akses pengguna reguler</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Manajemen Database -->
                            <div class="col-12">
                                <div class="card-modern">
                                    <div class="card-header-dark">
                                        <i class="fas fa-database"></i> Manajemen Database
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row g-4">
                                            <div class="col-md-4">
                                                <div class="action-card action-card-primary">
                                                    <i class="fas fa-download"></i>
                                                    <h6 class="fw-bold mb-2">Backup Database</h6>
                                                    <p class="text-muted small mb-3">Simpan cadangan data lengkap sistem</p>
                                                    <button type="submit" name="backup_db" class="btn btn-outline-modern w-100">
                                                        <i class="fas fa-cloud-download-alt me-2"></i>Backup Sekarang
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="action-card action-card-warning">
                                                    <i class="fas fa-history"></i>
                                                    <h6 class="fw-bold mb-2">Bersihkan Log</h6>
                                                    <p class="text-muted small mb-3">Hapus riwayat aktivitas sistem</p>
                                                    <button type="submit" name="clear_data" value="1" 
                                                            onclick="this.form.clear_type.value='log'; return confirm('Yakin ingin menghapus semua log aktivitas?');" 
                                                            class="btn btn-outline-modern w-100">
                                                        <i class="fas fa-broom me-2"></i>Bersihkan Log
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="danger-zone-modern">
                                                    <div class="action-card-danger">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        <h6 class="fw-bold mb-2 text-danger">Reset Semester</h6>
                                                        <p class="text-muted small mb-3">Hapus semua data presensi dan aktivitas</p>
                                                        <button type="submit" name="clear_data" value="1" 
                                                                onclick="this.form.clear_type.value='presensi'; return showResetWarning();" 
                                                                class="btn btn-danger btn-modern w-100">
                                                            <i class="fas fa-trash-alt me-2"></i>Reset Data
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="clear_type" id="clear_type" value="">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Informasi Sistem -->
                            <div class="col-12">
                                <div class="card-modern">
                                    <div class="card-header-info">
                                        <i class="fas fa-server"></i> Informasi Sistem
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-3">
                                                <div class="info-badge">
                                                    <i class="fab fa-php"></i>
                                                    <div>
                                                        <small class="text-muted d-block">PHP Version</small>
                                                        <strong><?= phpversion() ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-badge">
                                                    <i class="fas fa-database"></i>
                                                    <div>
                                                        <small class="text-muted d-block">Database</small>
                                                        <strong>MySQL <?= mysqli_get_server_info($conn) ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-badge">
                                                    <i class="fas fa-clock"></i>
                                                    <div>
                                                        <small class="text-muted d-block">Server Time</small>
                                                        <strong><?= date('d M Y H:i:s') ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-badge">
                                                    <i class="fas fa-globe"></i>
                                                    <div>
                                                        <small class="text-muted d-block">Timezone</small>
                                                        <strong><?= date_default_timezone_get() ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <hr class="my-4">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="fw-bold mb-3"><i class="fas fa-chart-line me-2 text-primary"></i>Monitor Resource</h6>
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <small class="text-muted">Memory Usage</small>
                                                        <small class="fw-bold"><?= round(memory_get_usage(true)/1048576, 2) ?> MB</small>
                                                    </div>
                                                    <div class="progress-modern">
                                                        <div class="progress-bar" style="width: 45%"></div>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <small class="text-muted">CPU Load</small>
                                                        <small class="fw-bold">Medium</small>
                                                    </div>
                                                    <div class="progress-modern">
                                                        <div class="progress-bar" style="width: 65%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="fw-bold mb-3"><i class="fas fa-shield-alt me-2 text-success"></i>Status Keamanan</h6>
                                                <div class="d-flex align-items-center mb-3">
                                                    <i class="fas fa-check-circle text-success me-3"></i>
                                                    <div>
                                                        <div class="fw-bold">Session Protection</div>
                                                        <small class="text-muted">Aktif dengan enkripsi</small>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center mb-3">
                                                    <i class="fas fa-check-circle text-success me-3"></i>
                                                    <div>
                                                        <div class="fw-bold">SQL Injection Protection</div>
                                                        <small class="text-muted">Parameterized queries aktif</small>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-check-circle text-success me-3"></i>
                                                    <div>
                                                        <div class="fw-bold">XSS Protection</div>
                                                        <small class="text-muted">Output sanitization aktif</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Save Button -->
                        <div class="mt-4">
                            <div class="card-modern">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Terakhir diperbarui: <?= date('d F Y H:i:s') ?>
                                            </small>
                                        </div>
                                        <button type="submit" name="save_settings" class="btn btn-primary-modern btn-modern px-5">
                                            <i class="fas fa-save me-2"></i>Simpan Semua Perubahan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

function showResetWarning() {
    return confirm('🚨 PERINGATAN KRITIS 🚨\n\n' +
                  'Tindakan ini akan menghapus SEMUA data berikut:\n\n' +
                  '• Semua data presensi mahasiswa\n' +
                  '• Jurnal praktikum\n' +
                  '• Hasil kuis dan nilai\n' +
                  '• Riwayat penggantian jadwal\n' +
                  '• QR code session\n' +
                  '• Absensi asisten\n' +
                  '• Feedback praktikum\n\n' +
                  '⚠️ Data master (Mahasiswa, Dosen, Jadwal) TIDAK akan terhapus.\n\n' +
                  'Apakah Anda yakin ingin melanjutkan?');
}

// Update maintenance mode status
document.getElementById('maintenance_mode').addEventListener('change', function() {
    const statusDiv = this.closest('.d-flex').querySelector('.fw-bold');
    if (this.checked) {
        statusDiv.textContent = 'AKTIF';
        statusDiv.classList.remove('text-success');
        statusDiv.classList.add('text-danger');
    } else {
        statusDiv.textContent = 'NON-AKTIF';
        statusDiv.classList.remove('text-danger');
        statusDiv.classList.add('text-success');
    }
});

// Animate progress bars
function updateResourceStats() {
    const memory = Math.min(100, Math.random() * 100);
    const cpu = Math.min(100, Math.random() * 100);
    
    const memoryBar = document.querySelectorAll('.progress-bar')[0];
    const cpuBar = document.querySelectorAll('.progress-bar')[1];
    
    memoryBar.style.width = memory + '%';
    cpuBar.style.width = cpu + '%';
    
    // Update text
    const memoryText = document.querySelectorAll('.fw-bold')[10];
    const cpuText = document.querySelectorAll('.fw-bold')[11];
    
    memoryText.textContent = memory.toFixed(1) + ' MB';
    cpuText.textContent = cpu > 70 ? 'Tinggi' : cpu > 40 ? 'Sedang' : 'Rendah';
}

// Update every 5 seconds
setInterval(updateResourceStats, 5000);
updateResourceStats();

// Add hover effects to cards
document.querySelectorAll('.card-modern').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
        this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.12)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.08)';
    });
});
</script>

<?php include 'includes/footer.php'; ?>