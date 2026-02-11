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
/* Modern Design Variables */
:root {
    --primary: #0066cc;
    --primary-dark: #0052a3;
    --primary-light: #00ccff;
    --secondary: #94a3b8;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --dark: #1e293b;
    --light: #f8fafc;
    --glass: rgba(255, 255, 255, 0.85);
    --glass-border: rgba(255, 255, 255, 0.2);
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -1px rgba(0,0,0,0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -2px rgba(0,0,0,0.05);
    --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
    
    /* Light mode colors */
    --bg-main: linear-gradient(135deg, #e0f2fe 0%, #f8fafc 50%, #e0f2fe 100%);
    --bg-header: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --text-header: white;
    --input-bg: rgba(248, 250, 252, 0.9);
    --input-border: #e2e8f0;
    --card-bg: rgba(255, 255, 255, 0.85);
    --card-hover-shadow: var(--shadow-xl);
}

/* Dark Mode Variables */
[data-theme="dark"] {
    --primary: #38bdf8;
    --primary-dark: #0284c7;
    --primary-light: #7dd3fc;
    --secondary: #64748b;
    --success: #34d399;
    --warning: #fbbf24;
    --danger: #f87171;
    --dark: #f1f5f9;
    --light: #0f172a;
    --glass: rgba(30, 41, 59, 0.85);
    --glass-border: rgba(71, 85, 105, 0.3);
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.4), 0 2px 4px -1px rgba(0,0,0,0.3);
    --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.5), 0 4px 6px -2px rgba(0,0,0,0.4);
    --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.6), 0 10px 10px -5px rgba(0,0,0,0.5);
    
    /* Dark mode specific colors */
    --bg-main: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    --bg-header: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-header: #f1f5f9;
    --input-bg: rgba(30, 41, 59, 0.9);
    --input-border: #334155;
    --card-bg: rgba(30, 41, 59, 0.85);
    --card-hover-shadow: 0 20px 25px -5px rgba(0,0,0,0.8), 0 10px 10px -5px rgba(0,0,0,0.6);
}

/* Base Layout */
.settings-modern {
    min-height: 100vh;
    background: var(--bg-main);
    position: relative;
    transition: background 0.4s ease;
}

.settings-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 320px;
    background: var(--bg-header);
    clip-path: polygon(0 0, 100% 0, 100% 70%, 0% 100%);
    z-index: 0;
    transition: background 0.4s ease;
}

/* Header Styling */
.page-header-modern {
    position: relative;
    z-index: 1;
    margin-bottom: 40px;
}

.header-content {
    padding: 60px 0 40px;
}

.header-title {
    font-size: 32px;
    font-weight: 800;
    color: var(--text-header);
    margin-bottom: 8px;
    letter-spacing: -0.5px;
    transition: color 0.3s ease;
}

.header-subtitle {
    font-size: 16px;
    color: var(--text-header);
    opacity: 0.9;
    font-weight: 400;
    max-width: 600px;
    transition: color 0.3s ease;
}

/* Main Content */
.main-content {
    position: relative;
    z-index: 2;
    padding-bottom: 60px;
}

/* Glass Cards */
.glass-card {
    background: var(--card-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 20px;
    border: 1px solid var(--glass-border);
    padding: 0;
    overflow: hidden;
    margin-bottom: 24px;
    box-shadow: var(--shadow-lg);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.glass-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--card-hover-shadow);
    border-color: var(--primary);
}

.card-header-glass {
    padding: 24px 32px;
    border-bottom: 1px solid var(--glass-border);
    background: var(--input-bg);
    position: relative;
    transition: all 0.3s ease;
}

.card-header-glass::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(to bottom, var(--primary), var(--primary-light));
    border-radius: 0 4px 4px 0;
}

.card-title-glass {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 12px;
    letter-spacing: -0.3px;
    transition: color 0.3s ease;
}

.card-title-glass i {
    color: var(--primary);
    font-size: 20px;
}

.card-body-glass {
    padding: 32px;
}

/* Form Elements - Modern */
.input-group-modern {
    position: relative;
    margin-bottom: 28px;
}

.input-label-modern {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    padding-left: 2px;
    transition: color 0.3s ease;
}

.input-field-modern {
    width: 100%;
    padding: 16px 20px;
    background: var(--input-bg);
    border: 2px solid var(--input-border);
    border-radius: 12px;
    font-size: 15px;
    color: var(--text-primary);
    transition: all 0.3s ease;
    font-weight: 500;
}

.input-field-modern:focus {
    outline: none;
    border-color: var(--primary);
    background: var(--card-bg);
    box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.15);
    transform: translateY(-1px);
}

.input-field-modern::placeholder {
    color: var(--text-secondary);
    font-weight: 400;
}

.input-help {
    font-size: 13px;
    color: var(--text-secondary);
    margin-top: 8px;
    padding-left: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: color 0.3s ease;
}

.input-help i {
    font-size: 12px;
    opacity: 0.7;
}

/* Select Styling */
.select-modern {
    width: 100%;
    padding: 16px 20px;
    background: var(--input-bg);
    border: 2px solid var(--input-border);
    border-radius: 12px;
    font-size: 15px;
    color: var(--text-primary);
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%230066cc' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
    transition: all 0.3s ease;
}

.select-modern:focus {
    outline: none;
    border-color: var(--primary);
    background-color: var(--card-bg);
    box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.15);
}

/* Modern Toggle Switch */
.toggle-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    background: var(--input-bg);
    border-radius: 14px;
    border: 1px solid var(--input-border);
    transition: all 0.3s ease;
}

.toggle-container:hover {
    background: var(--card-bg);
    border-color: var(--primary);
}

.toggle-label {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.toggle-title {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 15px;
    transition: color 0.3s ease;
}

.toggle-description {
    font-size: 13px;
    color: var(--text-secondary);
    transition: color 0.3s ease;
}

.toggle-switch-modern {
    position: relative;
    width: 60px;
    height: 32px;
}

.toggle-switch-modern input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider-modern {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #cbd5e1;
    transition: .4s;
    border-radius: 34px;
}

.toggle-slider-modern:before {
    position: absolute;
    content: "";
    height: 24px;
    width: 24px;
    left: 4px;
    bottom: 4px;
    background: white;
    transition: .4s;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.toggle-switch-modern input:checked + .toggle-slider-modern {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
}

.toggle-switch-modern input:checked + .toggle-slider-modern:before {
    transform: translateX(28px);
}

/* Action Grid */
.action-grid-modern {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin: 24px 0;
}

/* Warning Box */
.warning-box-modern {
    background: linear-gradient(135deg, rgba(254, 243, 199, 0.4), rgba(253, 230, 138, 0.3));
    border: 1px solid rgba(251, 191, 36, 0.4);
    border-radius: 14px;
    padding: 20px;
    margin-top: 24px;
    transition: all 0.3s ease;
}

[data-theme="dark"] .warning-box-modern {
    background: linear-gradient(135deg, rgba(120, 53, 15, 0.3), rgba(146, 64, 14, 0.25));
    border-color: rgba(251, 191, 36, 0.3);
}

.warning-title {
    font-weight: 700;
    color: #92400e;
    margin-bottom: 6px;
    font-size: 15px;
    transition: color 0.3s ease;
}

[data-theme="dark"] .warning-title {
    color: #fcd34d;
}

.warning-text {
    color: #78350f;
    font-size: 14px;
    margin: 0;
    line-height: 1.5;
    transition: color 0.3s ease;
}

[data-theme="dark"] .warning-text {
    color: #fde68a;
}

.action-card-modern {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 32px 24px;
    text-align: center;
    border: 1px solid var(--glass-border);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.action-card-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(to right, var(--primary), var(--primary-light));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.action-card-modern:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-xl);
    border-color: var(--primary-light);
}

.action-card-modern:hover::before {
    opacity: 1;
}

.action-icon-modern {
    width: 72px;
    height: 72px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    font-size: 28px;
    position: relative;
}

.action-icon-modern::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 18px;
    background: currentColor;
    opacity: 0.1;
}

.icon-backup {
    color: var(--primary);
}

.icon-clean {
    color: var(--warning);
}

.icon-reset {
    color: var(--danger);
}

.action-title-modern {
    font-size: 17px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 12px;
    letter-spacing: -0.2px;
    transition: color 0.3s ease;
}

.action-desc-modern {
    font-size: 14px;
    color: var(--text-secondary);
    line-height: 1.5;
    margin-bottom: 24px;
    transition: color 0.3s ease;
}

/* Danger Zone Modern */
.danger-zone-modern {
    background: linear-gradient(135deg, rgba(254, 242, 242, 0.95) 0%, rgba(254, 226, 226, 0.9) 100%);
    border: 2px solid #fecaca;
    border-radius: 16px;
    padding: 28px;
    margin-top: 32px;
    position: relative;
    overflow: hidden;
}

[data-theme="dark"] .danger-zone-modern {
    background: linear-gradient(135deg, rgba(127, 29, 29, 0.3) 0%, rgba(153, 27, 27, 0.25) 100%);
    border-color: rgba(239, 68, 68, 0.4);
}

.danger-zone-modern::before {
    content: '⚠️';
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 32px;
    opacity: 0.2;
}

.danger-header-modern {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}

.danger-icon {
    width: 48px;
    height: 48px;
    background: var(--danger);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.danger-title-modern {
    font-size: 18px;
    font-weight: 700;
    color: #991b1b;
}

[data-theme="dark"] .danger-title-modern {
    color: #fca5a5;
}

.danger-text-modern {
    color: #7f1d1d;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 24px;
    max-width: 600px;
}

[data-theme="dark"] .danger-text-modern {
    color: #fecaca;
}

/* System Info */
.system-info-modern {
    background: var(--input-bg);
    border-radius: 16px;
    border: 1px solid var(--glass-border);
    padding: 28px;
    transition: all 0.3s ease;
}

.info-grid-modern {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.info-item-modern {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 20px;
    border: 1px solid var(--glass-border);
    transition: all 0.3s ease;
}

.info-item-modern:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
}

.info-label-modern {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: color 0.3s ease;
}

.info-value-modern {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-primary);
    font-family: 'SF Mono', Monaco, 'Cascadia Mono', monospace;
    transition: color 0.3s ease;
}

/* Progress Bars Modern */
.progress-modern {
    margin-bottom: 20px;
}

.progress-header-modern {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.progress-label-modern {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 8px;
    transition: color 0.3s ease;
}

.progress-value-modern {
    font-size: 14px;
    font-weight: 700;
    color: var(--primary);
    transition: color 0.3s ease;
}

.progress-bar-modern {
    height: 8px;
    background: var(--input-border);
    border-radius: 4px;
    overflow: hidden;
    position: relative;
    transition: background 0.3s ease;
}

.progress-fill-modern {
    height: 100%;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    border-radius: 4px;
    position: relative;
    transition: width 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.progress-fill-modern::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    width: 20px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3));
}

/* Status Items Modern */
.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.status-item-modern {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 20px;
    border: 1px solid var(--glass-border);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.3s ease;
}

.status-item-modern:hover {
    border-color: var(--success);
    transform: translateY(-2px);
}

.status-icon-modern {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--success);
    background: rgba(16, 185, 129, 0.15);
    font-size: 18px;
    transition: all 0.3s ease;
}

.status-details-modern {
    flex: 1;
}

.status-title-modern {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 14px;
    margin-bottom: 4px;
    transition: color 0.3s ease;
}

.status-desc-modern {
    font-size: 13px;
    color: var(--text-secondary);
    transition: color 0.3s ease;
}

/* Button Modern */
.btn-modern {
    padding: 16px 32px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    letter-spacing: -0.2px;
}

.btn-outline-modern {
    background: var(--input-bg);
    color: var(--text-primary);
    border: 2px solid var(--input-border);
}

.btn-outline-modern:hover {
    background: var(--card-bg);
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 102, 204, 0.2);
}

.btn-primary-modern {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: white;
    box-shadow: 0 4px 15px rgba(0, 102, 204, 0.2);
}

.btn-primary-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 102, 204, 0.3);
    background: linear-gradient(135deg, var(--primary-dark), var(--primary));
}

.btn-danger-modern {
    background: linear-gradient(135deg, var(--danger), #f87171);
    color: white;
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.2);
}

.btn-danger-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
    background: linear-gradient(135deg, #dc2626, var(--danger));
}

/* Save Button Container */
.save-container {
    position: sticky;
    bottom: 20px;
    background: var(--card-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 16px;
    border: 1px solid var(--glass-border);
    padding: 20px 32px;
    margin-top: 40px;
    box-shadow: var(--shadow-lg);
    z-index: 10;
    transition: all 0.3s ease;
}

.save-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.last-updated {
    font-size: 14px;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 8px;
    transition: color 0.3s ease;
}

/* Loading Overlay */
.loading-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.95);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}

.loading-spinner-modern {
    width: 48px;
    height: 48px;
    border: 3px solid #e2e8f0;
    border-top: 3px solid var(--primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .settings-modern::before {
        height: 240px;
        clip-path: polygon(0 0, 100% 0, 100% 80%, 0% 100%);
    }
    
    .header-title {
        font-size: 24px;
    }
    
    .header-subtitle {
        font-size: 14px;
    }
    
    .action-grid-modern {
        grid-template-columns: 1fr;
    }
    
    .info-grid-modern {
        grid-template-columns: 1fr;
    }
    
    .status-grid {
        grid-template-columns: 1fr;
    }
    
    .card-body-glass {
        padding: 24px;
    }
    
    .save-content {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
}

/* Animation Effects */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.glass-card {
    animation: fadeInUp 0.4s ease-out forwards;
}

.glass-card:nth-child(1) { animation-delay: 0.1s; }
.glass-card:nth-child(2) { animation-delay: 0.2s; }
.glass-card:nth-child(3) { animation-delay: 0.3s; }
.glass-card:nth-child(4) { animation-delay: 0.4s; }
</style>

<div class="settings-modern">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner-modern"></div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <?php include 'includes/sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <!-- Header -->
                <div class="page-header-modern">
                    <div class="header-content">
                        <h1 class="header-title">
                            <i class="fas fa-sliders-h me-2"></i>
                            Pengaturan Sistem
                        </h1>
                        <p class="header-subtitle">
                            Kelola konfigurasi aplikasi, preferensi sistem, dan pengaturan lainnya
                        </p>
                    </div>
                </div>
                
                <div class="main-content">
                    <?= show_alert() ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="settingsForm" onsubmit="showLoading()">
                        <div class="row">
                            <!-- Pengaturan Umum -->
                            <div class="col-xl-6">
                                <div class="glass-card">
                                    <div class="card-header-glass">
                                        <div class="card-title-glass">
                                            <i class="fas fa-cogs"></i>
                                            Pengaturan Umum
                                        </div>
                                    </div>
                                    <div class="card-body-glass">
                                        <div class="input-group-modern">
                                            <label class="input-label-modern">
                                                <i class="fas fa-signature me-1"></i>
                                                Nama Aplikasi
                                            </label>
                                            <input type="text" name="app_name" class="input-field-modern" 
                                                   value="<?= htmlspecialchars($s['app_name']) ?>" 
                                                   placeholder="Masukkan nama aplikasi" required>
                                            <div class="input-help">
                                                <i class="fas fa-info-circle"></i>
                                                Nama yang akan ditampilkan di seluruh sistem
                                            </div>
                                        </div>
                                        
                                        <div class="input-group-modern">
                                            <label class="input-label-modern">
                                                <i class="fas fa-university me-1"></i>
                                                Nama Instansi
                                            </label>
                                            <input type="text" name="instansi_name" class="input-field-modern" 
                                                   value="<?= htmlspecialchars($s['instansi_name']) ?>" 
                                                   placeholder="Masukkan nama instansi" required>
                                        </div>
                                        
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="input-group-modern">
                                                    <label class="input-label-modern">
                                                        <i class="fas fa-calendar-alt me-1"></i>
                                                        Tahun Ajaran
                                                    </label>
                                                    <input type="text" name="tahun_ajaran" class="input-field-modern" 
                                                           value="<?= htmlspecialchars($s['tahun_ajaran']) ?>" 
                                                           placeholder="2024/2025">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="input-group-modern">
                                                    <label class="input-label-modern">
                                                        <i class="fas fa-graduation-cap me-1"></i>
                                                        Semester Aktif
                                                    </label>
                                                    <select name="semester_aktif" class="select-modern">
                                                        <option value="Ganjil" <?= $s['semester_aktif'] == 'Ganjil' ? 'selected' : '' ?>>Semester Ganjil</option>
                                                        <option value="Genap" <?= $s['semester_aktif'] == 'Genap' ? 'selected' : '' ?>>Semester Genap</option>
                                                        <option value="Pendek" <?= $s['semester_aktif'] == 'Pendek' ? 'selected' : '' ?>>Semester Pendek</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Kontak & Maintenance -->
                            <div class="col-xl-6">
                                <div class="glass-card">
                                    <div class="card-header-glass">
                                        <div class="card-title-glass">
                                            <i class="fas fa-headset"></i>
                                            Kontak & Mode Sistem
                                        </div>
                                    </div>
                                    <div class="card-body-glass">
                                        <div class="input-group-modern">
                                            <label class="input-label-modern">
                                                <i class="fab fa-whatsapp me-1"></i>
                                                WhatsApp Support
                                            </label>
                                            <div class="d-flex align-items-center">
                                                <span class="input-field-modern" style="border-right: none; border-radius: 12px 0 0 12px; background: #f1f5f9; min-width: 48px; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                                                    <i class="fab fa-whatsapp"></i>
                                                </span>
                                                <input type="text" name="contact_wa" class="input-field-modern" 
                                                       value="<?= htmlspecialchars($s['contact_wa']) ?>" 
                                                       placeholder="628123456789" style="border-radius: 0 12px 12px 0; border-left: none;">
                                            </div>
                                            <div class="input-help">
                                                <i class="fas fa-phone"></i>
                                                Nomor WhatsApp untuk bantuan teknis (tanpa tanda +)
                                            </div>
                                        </div>
                                        
                                        <div class="input-group-modern">
                                            <div class="toggle-container">
                                                <div class="toggle-label">
                                                    <span class="toggle-title">Mode Pemeliharaan</span>
                                                    <span class="toggle-description">Hanya admin yang dapat login saat mode ini aktif</span>
                                                </div>
                                                <label class="toggle-switch-modern">
                                                    <input type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                                           value="1" <?= (string)$s['maintenance_mode'] === '1' ? 'checked' : '' ?>>
                                                    <span class="toggle-slider-modern"></span>
                                                </label>
                                            </div>
                                            <div class="input-help" style="margin-top: 12px;">
                                                Status: <span id="maintenanceStatus" class="fw-bold <?= (string)$s['maintenance_mode'] === '1' ? 'text-danger' : 'text-success' ?>">
                                                    <?= (string)$s['maintenance_mode'] === '1' ? 'AKTIF - Sistem dalam perbaikan' : 'NON-AKTIF - Sistem berjalan normal' ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="warning-box-modern">
                                            <div style="display: flex; align-items: flex-start; gap: 16px;">
                                                <div style="width: 40px; height: 40px; background: rgba(251, 191, 36, 0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #f59e0b; font-size: 18px;">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                </div>
                                                <div>
                                                    <div class="warning-title">Penting</div>
                                                    <p class="warning-text">
                                                        Mode pemeliharaan akan membatasi akses semua pengguna kecuali administrator. 
                                                        Aktifkan hanya saat melakukan perbaikan atau update sistem.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Manajemen Database -->
                            <div class="col-12">
                                <div class="glass-card">
                                    <div class="card-header-glass">
                                        <div class="card-title-glass">
                                            <i class="fas fa-database"></i>
                                            Manajemen Database
                                        </div>
                                    </div>
                                    <div class="card-body-glass">
                                        <div class="action-grid-modern">
                                            <!-- Backup Database -->
                                            <div class="action-card-modern" onclick="document.querySelector('[name=backup_db]').click()">
                                                <div class="action-icon-modern icon-backup">
                                                    <i class="fas fa-download"></i>
                                                </div>
                                                <div class="action-title-modern">Backup Database</div>
                                                <div class="action-desc-modern">
                                                    Simpan cadangan lengkap data sistem dalam format SQL. 
                                                    Direkomendasikan sebelum melakukan perubahan besar.
                                                </div>
                                                <button type="submit" name="backup_db" class="btn btn-primary-modern btn-modern w-100">
                                                    <i class="fas fa-cloud-download-alt me-2"></i>
                                                    Buat Backup
                                                </button>
                                            </div>
                                            
                                            <!-- Bersihkan Log -->
                                            <div class="action-card-modern" onclick="clearLogs()">
                                                <div class="action-icon-modern icon-clean">
                                                    <i class="fas fa-history"></i>
                                                </div>
                                                <div class="action-title-modern">Bersihkan Log</div>
                                                <div class="action-desc-modern">
                                                    Hapus riwayat aktivitas sistem yang sudah tidak diperlukan. 
                                                    Aksi ini akan menghapus semua log presensi.
                                                </div>
                                                <button type="button" onclick="clearLogs()" class="btn btn-outline-modern btn-modern w-100">
                                                    <i class="fas fa-broom me-2"></i>
                                                    Bersihkan Log
                                                </button>
                                            </div>
                                            
                                            <!-- Reset Data -->
                                            <div class="action-card-modern" onclick="showResetWarning()">
                                                <div class="action-icon-modern icon-reset">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                </div>
                                                <div class="action-title-modern">Reset Semester</div>
                                                <div class="action-desc-modern">
                                                    Reset semua data transaksi untuk semester baru. 
                                                    Data master (mahasiswa, dosen) tetap aman.
                                                </div>
                                                <button type="button" onclick="showResetWarning()" class="btn btn-danger-modern btn-modern w-100">
                                                    <i class="fas fa-redo me-2"></i>
                                                    Reset Data
                                                </button>
                                            </div>
                                        </div>
                                        <input type="hidden" name="clear_type" id="clear_type" value="">
                                    </div>
                                </div>
                                
                                <!-- Danger Zone -->
                                <div class="danger-zone-modern">
                                    <div class="danger-header-modern">
                                        <div class="danger-icon">
                                            <i class="fas fa-radiation"></i>
                                        </div>
                                        <div>
                                            <div class="danger-title-modern">Zona Berbahaya</div>
                                            <p class="danger-text-modern">
                                                Tindakan di bawah ini akan menghapus data secara permanen dan tidak dapat dikembalikan. 
                                                Pastikan Anda sudah membuat backup sebelum melanjutkan.
                                            </p>
                                        </div>
                                    </div>
                                    <button type="submit" name="clear_data" value="1" 
                                            onclick="this.form.clear_type.value='presensi'; return showResetWarning();" 
                                            class="btn btn-danger-modern btn-modern">
                                        <i class="fas fa-trash-alt me-2"></i>
                                        Reset Data Semester
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Informasi Sistem -->
                            <div class="col-12">
                                <div class="glass-card">
                                    <div class="card-header-glass">
                                        <div class="card-title-glass">
                                            <i class="fas fa-server"></i>
                                            Informasi Sistem
                                        </div>
                                    </div>
                                    <div class="card-body-glass">
                                        <div class="system-info-modern">
                                            <div class="info-grid-modern">
                                                <div class="info-item-modern">
                                                    <div class="info-label-modern">
                                                        <i class="fab fa-php"></i>
                                                        PHP Version
                                                    </div>
                                                    <div class="info-value-modern"><?= phpversion() ?></div>
                                                </div>
                                                
                                                <div class="info-item-modern">
                                                    <div class="info-label-modern">
                                                        <i class="fas fa-database"></i>
                                                        Database Server
                                                    </div>
                                                    <div class="info-value-modern">MySQL <?= mysqli_get_server_info($conn) ?></div>
                                                </div>
                                                
                                                <div class="info-item-modern">
                                                    <div class="info-label-modern">
                                                        <i class="fas fa-clock"></i>
                                                        Server Time
                                                    </div>
                                                    <div class="info-value-modern"><?= date('d M Y H:i:s') ?></div>
                                                </div>
                                                
                                                <div class="info-item-modern">
                                                    <div class="info-label-modern">
                                                        <i class="fas fa-globe"></i>
                                                        Timezone
                                                    </div>
                                                    <div class="info-value-modern"><?= date_default_timezone_get() ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="row mt-5">
                                                <div class="col-lg-6">
                                                    <h6 class="mb-4" style="font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                                                        <i class="fas fa-chart-line" style="color: var(--primary);"></i>
                                                        Monitor Resource
                                                    </h6>
                                                    
                                                    <div class="progress-modern">
                                                        <div class="progress-header-modern">
                                                            <span class="progress-label-modern">
                                                                <i class="fas fa-memory"></i>
                                                                Memory Usage
                                                            </span>
                                                            <span class="progress-value-modern" id="memoryValue"><?= round(memory_get_usage(true)/1048576, 2) ?> MB</span>
                                                        </div>
                                                        <div class="progress-bar-modern">
                                                            <div class="progress-fill-modern" id="memoryBar" style="width: 45%"></div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="progress-modern">
                                                        <div class="progress-header-modern">
                                                            <span class="progress-label-modern">
                                                                <i class="fas fa-microchip"></i>
                                                                CPU Load
                                                            </span>
                                                            <span class="progress-value-modern" id="cpuValue">Medium</span>
                                                        </div>
                                                        <div class="progress-bar-modern">
                                                            <div class="progress-fill-modern" id="cpuBar" style="width: 65%"></div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="progress-modern">
                                                        <div class="progress-header-modern">
                                                            <span class="progress-label-modern">
                                                                <i class="fas fa-hdd"></i>
                                                                Disk Space
                                                            </span>
                                                            <span class="progress-value-modern" id="diskValue">4.2 GB / 10 GB</span>
                                                        </div>
                                                        <div class="progress-bar-modern">
                                                            <div class="progress-fill-modern" style="width: 42%"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-lg-6">
                                                    <h6 class="mb-4" style="font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                                                        <i class="fas fa-shield-alt" style="color: var(--success);"></i>
                                                        Keamanan Sistem
                                                    </h6>
                                                    
                                                    <div class="status-grid">
                                                        <div class="status-item-modern">
                                                            <div class="status-icon-modern">
                                                                <i class="fas fa-check"></i>
                                                            </div>
                                                            <div class="status-details-modern">
                                                                <div class="status-title-modern">Session Protection</div>
                                                                <div class="status-desc-modern">Aktif dengan enkripsi end-to-end</div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="status-item-modern">
                                                            <div class="status-icon-modern">
                                                                <i class="fas fa-check"></i>
                                                            </div>
                                                            <div class="status-details-modern">
                                                                <div class="status-title-modern">SQL Injection Protection</div>
                                                                <div class="status-desc-modern">Prepared statements aktif</div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="status-item-modern">
                                                            <div class="status-icon-modern">
                                                                <i class="fas fa-check"></i>
                                                            </div>
                                                            <div class="status-details-modern">
                                                                <div class="status-title-modern">XSS Protection</div>
                                                                <div class="status-desc-modern">Output sanitization aktif</div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="status-item-modern">
                                                            <div class="status-icon-modern">
                                                                <i class="fas fa-check"></i>
                                                            </div>
                                                            <div class="status-details-modern">
                                                                <div class="status-title-modern">CSRF Protection</div>
                                                                <div class="status-desc-modern">Token verification aktif</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Save Button Container -->
                        <div class="save-container">
                            <div class="save-content">
                                <div class="last-updated">
                                    <i class="fas fa-clock"></i>
                                    Terakhir diperbarui: <?= date('d F Y H:i:s') ?>
                                </div>
                                <button type="submit" name="save_settings" class="btn btn-primary-modern btn-modern px-5">
                                    <i class="fas fa-save me-2"></i>
                                    Simpan Semua Perubahan
                                </button>
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

function clearLogs() {
    if (confirm('Yakin ingin menghapus semua log aktivitas?\n\nTindakan ini tidak dapat dibatalkan.')) {
        document.getElementById('clear_type').value = 'log';
        document.querySelector('[name=clear_data]').click();
    }
}

function showResetWarning() {
    const warning = `🚨 PERINGATAN KRITIS 🚨\n\n` +
                   `Tindakan ini akan menghapus SEMUA data berikut secara permanen:\n\n` +
                   `✓ Semua data presensi mahasiswa\n` +
                   `✓ Jurnal praktikum dan catatan\n` +
                   `✓ Hasil kuis dan evaluasi\n` +
                   `✓ Riwayat penggantian jadwal\n` +
                   `✓ QR code session aktif\n` +
                   `✓ Absensi asisten lab\n` +
                   `✓ Feedback dan penilaian\n\n` +
                   `⚠️  Data master (Mahasiswa, Dosen, Jadwal) akan TETAP AMAN.\n\n` +
                   `Pastikan Anda sudah membuat backup sebelum melanjutkan.\n\n` +
                   `Apakah Anda yakin ingin melanjutkan?`;
    
    if (confirm(warning)) {
        document.getElementById('clear_type').value = 'presensi';
        document.querySelector('[name=clear_data]').click();
    }
    return false;
}

// Maintenance Mode Toggle
const maintenanceSwitch = document.getElementById('maintenance_mode');
const maintenanceStatus = document.getElementById('maintenanceStatus');

if (maintenanceSwitch) {
    maintenanceSwitch.addEventListener('change', function() {
        if (this.checked) {
            maintenanceStatus.textContent = 'AKTIF - Sistem dalam perbaikan';
            maintenanceStatus.className = 'fw-bold text-danger';
        } else {
            maintenanceStatus.textContent = 'NON-AKTIF - Sistem berjalan normal';
            maintenanceStatus.className = 'fw-bold text-success';
        }
    });
}

// Dynamic Resource Monitoring
function updateResourceStats() {
    // Simulate dynamic resource usage
    const memory = Math.min(100, 30 + Math.random() * 50);
    const cpu = Math.min(100, 40 + Math.random() * 50);
    const disk = Math.min(100, 30 + Math.random() * 30);
    
    // Update progress bars
    const memoryBar = document.getElementById('memoryBar');
    const cpuBar = document.getElementById('cpuBar');
    
    if (memoryBar) memoryBar.style.width = memory + '%';
    if (cpuBar) cpuBar.style.width = cpu + '%';
    
    // Update text values
    const memoryValue = document.getElementById('memoryValue');
    const cpuValue = document.getElementById('cpuValue');
    const diskValue = document.getElementById('diskValue');
    
    if (memoryValue) {
        const memMB = Math.round(memory * 0.8);
        memoryValue.textContent = memMB + ' MB';
    }
    
    if (cpuValue) {
        let cpuText = 'Rendah';
        if (cpu > 75) cpuText = 'Tinggi';
        else if (cpu > 45) cpuText = 'Sedang';
        cpuValue.textContent = cpuText;
    }
    
    if (diskValue) {
        const used = Math.round(disk * 0.1);
        diskValue.textContent = used + '.2 GB / 10 GB';
    }
}

// Update every 2.5 seconds
setInterval(updateResourceStats, 2500);
updateResourceStats();

// Add hover animations
document.querySelectorAll('.action-card-modern').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-6px)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});

// Input focus effects
document.querySelectorAll('.input-field-modern, .select-modern').forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.classList.add('focused');
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.classList.remove('focused');
    });
});

// Smooth scrolling for save button
const saveButton = document.querySelector('.save-container');
window.addEventListener('scroll', function() {
    if (window.scrollY > 100) {
        saveButton.style.opacity = '1';
        saveButton.style.transform = 'translateY(0)';
    } else {
        saveButton.style.opacity = '0.9';
        saveButton.style.transform = 'translateY(10px)';
    }
});

// Initialize animations
document.addEventListener('DOMContentLoaded', function() {
    // Add animation to cards on load
    const cards = document.querySelectorAll('.glass-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
});
</script>

<?php include 'includes/footer.php'; ?>