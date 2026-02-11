<?php
cek_login();
cek_role(['admin']);
$page = 'admin_profil';
$user = get_user_login();

// Variabel foto profil
$foto_profil = $user['foto'] ?? null;

// PROSES UPLOAD FOTO PROFIL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_foto'])) {
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_size = $_FILES['foto']['size'];
        $user_id = $user['id'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
        
        if (!in_array($mime, $allowed_types)) {
            set_alert('danger', 'Format file tidak didukung! Gunakan JPG, PNG, GIF, atau WEBP.');
        } elseif ($file_size > $max_size) {
            set_alert('danger', 'Ukuran file terlalu besar! Maksimal 2MB.');
        } else {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $new_filename = 'admin_' . $user_id . '_' . time() . '.' . $ext;
            
            $upload_dir = 'uploads/profil/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $upload_path = $upload_dir . $new_filename;
            
            if ($foto_profil && file_exists($foto_profil)) unlink($foto_profil);
            
            if (optimize_and_save_image($file_tmp, $upload_path, 300, 300, 75)) {
                $stmt_foto = mysqli_prepare($conn, "UPDATE users SET foto = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_foto, "si", $upload_path, $user_id);
                if (mysqli_stmt_execute($stmt_foto)) {
                    set_alert('success', 'Foto profil berhasil diupload!');
                    echo "<meta http-equiv='refresh' content='1'>";
                } else {
                    set_alert('danger', 'Gagal menyimpan ke database.');
                }
            } else {
                set_alert('danger', 'Gagal mengupload file.');
            }
        }
    } else {
        set_alert('danger', 'Pilih file foto terlebih dahulu!');
    }
}

// PROSES HAPUS FOTO PROFIL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_foto'])) {
    if ($foto_profil && file_exists($foto_profil)) unlink($foto_profil);
    $user_id = $user['id'];
    $stmt_hapus = mysqli_prepare($conn, "UPDATE users SET foto = NULL WHERE id = ?");
    mysqli_stmt_bind_param($stmt_hapus, "i", $user_id);
    mysqli_stmt_execute($stmt_hapus);
    set_alert('success', 'Foto profil berhasil dihapus!');
    echo "<meta http-equiv='refresh' content='1'>";
}

// Proses update profil (Username)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profil'])) {
        $new_username = htmlspecialchars(trim($_POST['username']));
        $user_id = $user['id'];
        
        if (empty($new_username)) {
            set_alert('danger', 'Username tidak boleh kosong!');
        } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $new_username)) {
            set_alert('danger', 'Username hanya boleh berisi huruf, angka, titik (.), underscore (_), dan strip (-)');
        } else {
            // Cek username unik
            $stmt_cek = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? AND id != ?");
            mysqli_stmt_bind_param($stmt_cek, "si", $new_username, $user_id);
            mysqli_stmt_execute($stmt_cek);
            
            if (mysqli_num_rows(mysqli_stmt_get_result($stmt_cek)) > 0) {
                set_alert('danger', 'Username sudah digunakan, silakan pilih yang lain.');
            } else {
                // Update users
                $stmt_user = mysqli_prepare($conn, "UPDATE users SET username = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_user, "si", $new_username, $user_id);
                
                if (mysqli_stmt_execute($stmt_user)) {
                    $_SESSION['username'] = $new_username;
                    set_alert('success', "Profil berhasil diperbarui! Username Anda sekarang: <strong>$new_username</strong>");
                    echo "<meta http-equiv='refresh' content='1'>";
                } else {
                    set_alert('danger', 'Gagal memperbarui profil.');
                }
            }
        }
    }
    
    if (isset($_POST['ganti_password'])) {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $konfirmasi = $_POST['konfirmasi_password'];
        
        // Cek password lama
        if (!password_verify($password_lama, $user['password'])) {
            set_alert('danger', 'Password lama salah!');
        } elseif ($password_baru != $konfirmasi) {
            set_alert('danger', 'Konfirmasi password tidak cocok!');
        } elseif (strlen($password_baru) < 6) {
            set_alert('danger', 'Password minimal 6 karakter!');
        } else {
            $hashed_password_baru = password_hash($password_baru, PASSWORD_DEFAULT);
            $user_id = $user['id'];
            $stmt_pass = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_pass, "si", $hashed_password_baru, $user_id);
            mysqli_stmt_execute($stmt_pass);
            set_alert('success', 'Password berhasil diubah!');
        }
        header("Location: index.php?page=admin_profil");
        exit;
    }
    
    // Proses Backup Database
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
}

// Refresh data
$user = get_user_login();
$foto_profil = $user['foto'] ?? null;

// Helper function untuk inisial
function get_inisial_admin($nama) {
    return strtoupper(substr($nama, 0, 2));
}

// Helper function untuk warna avatar
function get_avatar_color_admin($username) {
    $colors = [
        'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'linear-gradient(135deg, #2af598 0%, #009efd 100%)',
        'linear-gradient(135deg, #b721ff 0%, #21d4fd 100%)',
        'linear-gradient(135deg, #ff9a9e 0%, #fecfef 99%, #fecfef 100%)'
    ];
    $hash = crc32($username);
    return $colors[abs($hash) % count($colors)];
}

$inisial = get_inisial_admin($user['username']);
$avatar_color = get_avatar_color_admin($user['username']);

// Statistik Admin (Data Sistem)
$total_mhs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM mahasiswa"))['total'];
$total_asisten = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM asisten"))['total'];
$total_kelas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kelas"))['total'];
$total_lab = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM lab"))['total'];
$today = date('Y-m-d');
$total_jadwal_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM jadwal WHERE tanggal = '$today'"))['total'];

// Check if tiket_bantuan table exists before querying
$check_tiket_table = mysqli_query($conn, "SHOW TABLES LIKE 'tiket_bantuan'");
if (mysqli_num_rows($check_tiket_table) > 0) {
    $total_tiket = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tiket_bantuan WHERE status = 'pending'"))['total'];
} else {
    $total_tiket = 0;
}

// Fetch Login History
$user_id = $user['id'];
$log_login = mysqli_query($conn, "SELECT * FROM log_presensi WHERE user_id = '$user_id' AND aksi LIKE '%LOGIN%' ORDER BY created_at DESC LIMIT 5");
?>
<?php include 'includes/header.php'; ?>

<style>
    /* Main Container */
    .profile-container {
        background: #b8caff;
        min-height: 100vh;
    }
    
    /* Profile Card */
    .profile-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 30px rgba(37, 80, 209, 0.08);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid #f0f3ff;
    }
    
    .profile-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 35px rgba(37, 80, 209, 0.12);
    }
    
    /* Header Section */
    .profile-header {
        background: linear-gradient(135deg, #0066ccff 0%, #0099ffff 100%);
        padding: 40px 0;
        position: relative;
        overflow: hidden;
        border-radius: 10px;
    }
    
    .profile-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: 
            radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
            radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
    }
    
    /* Avatar */
    .avatar-container {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 42px;
        font-weight: 600;
        color: white;
        margin: 0 auto;
        position: relative;
        border: 4px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 25px rgba(37, 80, 209, 0.3);
    }
    
    .avatar-badge {
        position: absolute;
        bottom: 8px;
        right: 8px;
        width: 20px;
        height: 20px;
        background: #4ade80;
        border: 3px solid white;
        border-radius: 50%;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
    }
    
    /* Badge Styling */
    .info-badge {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.25);
        padding: 6px 16px;
        border-radius: 50px;
        font-weight: 500;
        color: white;
        transition: all 0.3s ease;
        font-size: 0.85rem;
    }
    
    .info-badge:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    /* Info Item */
    .info-item {
        padding: 18px;
        border-radius: 12px;
        margin-bottom: 12px;
        background: #ffffff;
        border: 1px solid #f0f3ff;
        transition: all 0.3s ease;
    }
    
    .info-item:hover {
        transform: translateX(8px);
        box-shadow: 0 8px 20px rgba(37, 80, 209, 0.08);
        border-color: #0066cc;
    }
    
    .info-icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        margin-right: 16px;
        flex-shrink: 0;
        background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%);
        color: white;
    }
    
    /* Form Styling */
    .form-control-custom {
        border: 2px solid #eef1ff;
        border-radius: 10px;
        padding: 14px 18px;
        font-size: 15px;
        transition: all 0.3s ease;
        background: #ffffff;
    }
    
    .form-control-custom:focus {
        border-color: #0066cc;
        box-shadow: 0 0 0 3px rgba(37, 80, 209, 0.1);
    }
    
    /* Button Styling */
    .btn-gradient {
        background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%);
        border: none;
        color: white;
        padding: 14px 28px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .btn-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(37, 80, 209, 0.25);
    }
    
    /* Button Upload Foto Khusus */
    .btn-upload-foto {
        background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%);
        border: none;
        color: white;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        position: relative;
        overflow: hidden;
    }
    .btn-upload-foto:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(37, 80, 209, 0.3);
    }
    
    /* Tabs Styling */
    .nav-tabs-custom {
        border-bottom: 2px solid #f0f3ff;
    }
    
    .nav-tabs-custom .nav-link {
        border: none;
        padding: 14px 24px;
        font-weight: 500;
        color: #64748b;
        border-radius: 8px 8px 0 0;
        margin-right: 4px;
        transition: all 0.3s ease;
        background: #f8f9ff;
    }
    
    .nav-tabs-custom .nav-link:hover {
        color: #0066cc;
        background: #eef1ff;
    }
    
    .nav-tabs-custom .nav-link.active {
        color: #0066cc;
        background: white;
        border-bottom: 3px solid #0066cc;
    }
    
    /* Stats Cards - Horizontal Layout */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }
    
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(37, 80, 209, 0.08);
        transition: all 0.3s ease;
        border: 1px solid #f0f3ff;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(37, 80, 209, 0.12);
        border-color: #0066cc;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 22px;
        color: white;
    }
    
    .stat-icon-primary { background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%); }
    .stat-icon-success { background: linear-gradient(135deg, #25d165 0%, #1ab34b 100%); }
    .stat-icon-warning { background: linear-gradient(135deg, #d1b325 0%, #b39b1a 100%); }
    .stat-icon-info { background: linear-gradient(135deg, #8f25d1 0%, #761ab3 100%); }
    .stat-icon-danger { background: linear-gradient(135deg, #d12525 0%, #b31a1a 100%); }
    
    .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 10px 0 5px;
        line-height: 1.2;
    }
    
    .stat-label {
        font-size: 0.85rem;
        color: #64748b;
        margin-bottom: 5px;
    }
    
    .progress-custom {
        height: 6px;
        border-radius: 3px;
        background: #eef1ff;
        margin-top: 10px;
    }
    
    .progress-bar-custom {
        border-radius: 3px;
        transition: width 0.5s ease;
    }
    
    .text-primary { color: #0066cc !important; }
    
    /* Modal Foto Styling */
    .modal-foto-custom .modal-content { border-radius: 16px; border: none; overflow: hidden; box-shadow: 0 20px 60px rgba(37, 80, 209, 0.25); }
    .modal-foto-custom .modal-header { background: linear-gradient(135deg, #0066ccff 0%, #0099ffff 100%); border-bottom: none; padding: 24px 30px; color: white; }
    .modal-foto-custom .modal-body { padding: 30px; background: #f8f9ff; }
    .modal-foto-custom .modal-footer { background: white; border-top: 1px solid #eef1ff; padding: 20px 30px; }
    
    /* Preview Container */
    .preview-container {
        width: 180px; height: 180px; border-radius: 50%; overflow: hidden;
        border: 5px solid white; box-shadow: 0 8px 25px rgba(37, 80, 209, 0.15);
        margin: 0 auto 25px; background: linear-gradient(135deg, #f0f3ff 0%, #e4e8ff 100%);
        position: relative;
    }
    .preview-img { width: 100%; height: 100%; object-fit: cover; }
    .preview-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 4rem; font-weight: 700; color: white; }
    
    /* Input File Custom */
    .input-file-custom { position: relative; }
    .input-file-custom input[type="file"] { position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 2; }
    .input-file-custom .file-label {
        display: flex; align-items: center; justify-content: center; gap: 10px;
        padding: 14px 20px; background: white; border: 2px dashed #0066cc;
        border-radius: 12px; color: #0066cc; font-weight: 500; transition: all 0.3s ease; cursor: pointer;
    }
    .input-file-custom:hover .file-label { background: #f8f9ff; border-color: #0099ff; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(37, 80, 209, 0.1); }
    
    /* Button Modal */
    .btn-modal-primary {
        background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%); border: none; color: white;
        padding: 12px 28px; border-radius: 10px; font-weight: 600; transition: all 0.3s ease;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-width: 120px;
    }
    .btn-modal-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37, 80, 209, 0.25); color: white; }
    
    .btn-modal-secondary {
        background: #f8f9ff; border: 2px solid #eef1ff; color: #64748b;
        padding: 12px 28px; border-radius: 10px; font-weight: 600; transition: all 0.3s ease;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-width: 120px;
    }
    .btn-modal-secondary:hover { background: #eef1ff; border-color: #0066cc; color: #0066cc; transform: translateY(-2px); }
    
    .btn-modal-danger {
        background: transparent; border: 2px solid #dc3545; color: #dc3545;
        padding: 12px 28px; border-radius: 10px; font-weight: 600; transition: all 0.3s ease;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-modal-danger:hover { background: #dc3545; color: white; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3); }

    /* Dark Mode Support */
    [data-theme="dark"] .profile-container { background: var(--bg-body); }
    [data-theme="dark"] .profile-card { background: var(--bg-card); border-color: var(--border-color); box-shadow: none; }
    [data-theme="dark"] .profile-header { background: var(--banner-gradient); }
    [data-theme="dark"] .info-badge { background: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.2); color: #e2e8f0; }
    [data-theme="dark"] .info-item { background: var(--bg-body); border-color: var(--border-color); }
    [data-theme="dark"] .info-item:hover { border-color: #66b0ff; background: rgba(255,255,255,0.05); }
    [data-theme="dark"] .info-icon { background: rgba(0, 102, 204, 0.2); color: #66b0ff; }
    [data-theme="dark"] .form-control-custom { background: var(--bg-input); border-color: var(--border-color); color: var(--text-main); }
    [data-theme="dark"] .form-control-custom:focus { border-color: #66b0ff; }
    [data-theme="dark"] .form-control-custom[readonly] { background: rgba(255,255,255,0.05) !important; color: var(--text-muted); }
    [data-theme="dark"] .nav-tabs-custom { border-bottom-color: var(--border-color); }
    [data-theme="dark"] .nav-tabs-custom .nav-link { background: rgba(255,255,255,0.05); color: var(--text-muted); }
    [data-theme="dark"] .nav-tabs-custom .nav-link:hover { background: rgba(255,255,255,0.1); color: #66b0ff; }
    [data-theme="dark"] .nav-tabs-custom .nav-link.active { background: var(--bg-card); color: #66b0ff; border-bottom-color: #66b0ff; }
    [data-theme="dark"] .stat-card { background: var(--bg-card); border-color: var(--border-color); }
    [data-theme="dark"] .stat-icon-primary { background: rgba(0, 102, 204, 0.2); color: #66b0ff; }
    [data-theme="dark"] .stat-icon-success { background: rgba(102, 204, 0, 0.2); color: #85e085; }
    [data-theme="dark"] .stat-icon-warning { background: rgba(255, 170, 0, 0.2); color: #ffcc00; }
    [data-theme="dark"] .stat-icon-info { background: rgba(111, 66, 193, 0.2); color: #a685e0; }
    [data-theme="dark"] .stat-icon-danger { background: rgba(220, 53, 69, 0.2); color: #ea868f; }
    [data-theme="dark"] .text-primary { color: #66b0ff !important; }
    [data-theme="dark"] .progress-custom { background: rgba(255,255,255,0.1); }
    [data-theme="dark"] .alert-info { background-color: rgba(13, 202, 240, 0.15); border-color: rgba(13, 202, 240, 0.3); color: #6edff6; }
    
    [data-theme="dark"] .modal-foto-custom .modal-content { background: var(--bg-card); }
    [data-theme="dark"] .modal-foto-custom .modal-body { background: var(--bg-body); color: var(--text-main); }
    [data-theme="dark"] .modal-foto-custom .modal-footer { background: var(--bg-card); border-top-color: var(--border-color); }
    [data-theme="dark"] .input-file-custom .file-label { background: var(--bg-input); border-color: #66b0ff; color: #66b0ff; }
    [data-theme="dark"] .input-file-custom:hover .file-label { background: rgba(102, 176, 255, 0.1); }
    [data-theme="dark"] .btn-modal-secondary { background: var(--bg-input); border-color: var(--border-color); color: var(--text-muted); }
    [data-theme="dark"] .btn-modal-secondary:hover { background: rgba(255,255,255,0.1); border-color: #66b0ff; color: #66b0ff; }
</style>

<div class="profile-container">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
            
            <div class="col-md-9 col-lg-10">
                <div class="profile-header">
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center d-flex flex-column justify-content-center align-items-center">
                                <div class="avatar-container position-relative" style="<?php if($foto_profil && file_exists($foto_profil)): ?>background-image: url('<?= $foto_profil ?>'); background-size: cover; background-position: center;<?php else: ?>background: <?= $avatar_color ?>;<?php endif; ?>">
                                    <?php if(!$foto_profil || !file_exists($foto_profil)): ?>
                                        <?= $inisial ?>
                                    <?php endif; ?>
                                    <div class="avatar-badge"></div>
                                </div>
                            </div>
                            <div class="col-md-9 text-white d-flex flex-column justify-content-center">
                                <h1 class="display-6 mb-2 fw-bold text-center text-md-start text-white"><?= $user['username'] ?></h1>
                                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                                    <span class="info-badge">
                                        <i class="fas fa-user-shield me-2"></i>Administrator
                                    </span>
                                    <span class="info-badge">
                                        <i class="fas fa-check-circle me-2"></i>Status: Aktif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="container py-4">
                    <?php show_alert(); ?>
                    
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="profile-card mb-4">
                                <div class="card-body p-4">
                                    <h4 class="mb-4 fw-bold text-primary">
                                        <i class="fas fa-user-circle me-2"></i>Profil Overview
                                    </h4>
                                    
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon">
                                                <i class="fas fa-user-tag"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">Username</small>
                                                <strong class="fs-6"><?= $user['username'] ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon">
                                                <i class="fas fa-user-shield"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">Role</small>
                                                <strong class="fs-6">Administrator</strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon">
                                                <i class="fas fa-calendar-alt"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">Bergabung Sejak</small>
                                                <strong class="fs-6"><?= date('d F Y', strtotime($user['created_at'])) ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Tombol Upload Foto Profil -->
                                    <div class="mt-4 pt-3 border-top">
                                        <button type="button" class="btn-upload-foto" data-bs-toggle="modal" data-bs-target="#modalFoto">
                                            <i class="fas fa-camera"></i>
                                            <?= $foto_profil ? 'Ganti Foto Profil' : 'Upload Foto Profil' ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Informasi Sistem Card -->
                            <div class="profile-card">
                                <div class="card-body p-4">
                                    <h5 class="mb-4 fw-bold text-primary">
                                        <i class="fas fa-info-circle me-2"></i>Informasi Sistem
                                    </h5>
                                    
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                            <span class="text-muted"><i class="fas fa-code-branch me-2"></i>Versi App</span>
                                            <span class="fw-bold">1.0.0</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                            <span class="text-muted"><i class="fab fa-php me-2"></i>PHP</span>
                                            <span class="fw-bold">v<?= phpversion() ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                            <span class="text-muted"><i class="fas fa-database me-2"></i>Database</span>
                                            <span class="badge bg-success bg-opacity-10 text-success">Terhubung</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                            <span class="text-muted"><i class="fas fa-clock me-2"></i>Waktu Server</span>
                                            <span class="fw-bold"><?= date('H:i') ?> WIB</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-8">
                            <!-- Statistik Grid (Admin Specific) -->
                            <div class="profile-card mb-4">
                                <div class="card-body p-4">
                                    <h5 class="mb-4 fw-bold text-primary">
                                        <i class="fas fa-chart-bar me-2"></i>Statistik Sistem
                                    </h5>
                                    
                                    <div class="stats-grid">
                                        <!-- Total Mahasiswa -->
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-primary">
                                                <i class="fas fa-user-graduate"></i>
                                            </div>
                                            <div class="stat-value text-primary"><?= $total_mhs ?></div>
                                            <div class="stat-label">Total Mahasiswa</div>
                                        </div>
                                        
                                        <!-- Total Asisten -->
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-success">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <div class="stat-value text-success"><?= $total_asisten ?></div>
                                            <div class="stat-label">Total Asisten</div>
                                        </div>
                                        
                                        <!-- Total Kelas -->
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-warning">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <div class="stat-value text-warning"><?= $total_kelas ?></div>
                                            <div class="stat-label">Total Kelas</div>
                                        </div>
                                        
                                        <!-- Total Lab -->
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-info">
                                                <i class="fas fa-flask"></i>
                                            </div>
                                            <div class="stat-value text-info"><?= $total_lab ?></div>
                                            <div class="stat-label">Total Lab</div>
                                        </div>
                                        
                                        <!-- Jadwal Hari Ini -->
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-danger">
                                                <i class="fas fa-calendar-day"></i>
                                            </div>
                                            <div class="stat-value text-danger"><?= $total_jadwal_today ?></div>
                                            <div class="stat-label">Jadwal Hari Ini</div>
                                        </div>
                                        
                                        <!-- Tiket Bantuan -->
                                        <div class="stat-card">
                                            <div class="stat-icon" style="background: linear-gradient(135deg, #6f42c1 0%, #a685e0 100%);">
                                                <i class="fas fa-headset"></i>
                                            </div>
                                            <div class="stat-value" style="color: #6f42c1;"><?= $total_tiket ?></div>
                                            <div class="stat-label">Tiket Pending</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tab Panel untuk Edit Profil -->
                            <div class="profile-card">
                                <div class="card-body p-4">
                                    <ul class="nav nav-tabs nav-tabs-custom mb-4" id="profileTab" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="edit-profile-tab" data-bs-toggle="tab" 
                                                    data-bs-target="#edit-profile" type="button" role="tab">
                                                <i class="fas fa-edit me-2"></i>Edit Profil
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="password-tab" data-bs-toggle="tab" 
                                                    data-bs-target="#password" type="button" role="tab">
                                                <i class="fas fa-lock me-2"></i>Keamanan Akun
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="activity-tab" data-bs-toggle="tab" 
                                                    data-bs-target="#activity" type="button" role="tab">
                                                <i class="fas fa-history me-2"></i>Riwayat Login
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="system-tab" data-bs-toggle="tab" 
                                                    data-bs-target="#system" type="button" role="tab">
                                                <i class="fas fa-cogs me-2"></i>Sistem
                                            </button>
                                        </li>
                                    </ul>
                                    
                                    <div class="tab-content" id="profileTabContent">
                                        <div class="tab-pane fade show active" id="edit-profile" role="tabpanel">
                                            <form method="POST">
                                                <div class="row g-4">
                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold">
                                                            <i class="fas fa-user-tag me-2 text-primary"></i>Username
                                                        </label>
                                                        <div class="input-group">
                                                            <input type="text" name="username" class="form-control form-control-custom" 
                                                                   value="<?= $user['username'] ?>" required>
                                                        </div>
                                                        <small class="text-muted">Username digunakan untuk login ke panel admin.</small>
                                                    </div>
                                                    
                                                    <div class="col-12">
                                                        <div class="d-flex justify-content-end gap-3 mt-4 pt-4 border-top">
                                                            <a href="index.php" class="btn btn-outline-secondary px-4 py-2">
                                                                Batal
                                                            </a>
                                                            <button type="submit" name="update_profil" class="btn-gradient px-4 py-2">
                                                                Simpan Perubahan
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        
                                        <div class="tab-pane fade" id="password" role="tabpanel">
                                            <form method="POST">
                                                <div class="row g-4">
                                                    <div class="col-12">
                                                        <div class="alert alert-info border-0 bg-light border-start border-3 border-primary">
                                                            <div class="d-flex align-items-center">
                                                                <i class="fas fa-shield-alt fs-5 me-3 text-primary"></i>
                                                                <div>
                                                                    <strong class="d-block mb-1 text-primary">Keamanan Akun</strong>
                                                                    <small class="text-muted">Gunakan password yang kuat dan jangan berbagi dengan siapapun.</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">
                                                            <i class="fas fa-lock me-2 text-primary"></i>Password Lama
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <input type="password" name="password_lama" 
                                                                   class="form-control form-control-custom password-input" 
                                                                   placeholder="Masukkan password saat ini" 
                                                                   required>
                                                            <button class="btn btn-outline-primary toggle-password" type="button">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">
                                                            <i class="fas fa-key me-2 text-primary"></i>Password Baru
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <input type="password" name="password_baru" 
                                                                   class="form-control form-control-custom password-input" 
                                                                   placeholder="Minimal 6 karakter" 
                                                                   minlength="6"
                                                                   required>
                                                            <button class="btn btn-outline-primary toggle-password" type="button">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </div>
                                                        <div class="password-strength mt-3">
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <small class="text-muted">Kekuatan password</small>
                                                                <small class="strength-text fw-semibold text-danger"></small>
                                                            </div>
                                                            <div class="progress progress-custom">
                                                                <div class="progress-bar progress-bar-custom bg-danger" 
                                                                     role="progressbar" style="width: 0%"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold">
                                                            <i class="fas fa-check-double me-2 text-primary"></i>Konfirmasi Password Baru
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <input type="password" name="konfirmasi_password" 
                                                                   class="form-control form-control-custom password-input" 
                                                                   placeholder="Ulangi password baru" 
                                                                   required>
                                                            <button class="btn btn-outline-primary toggle-password" type="button">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-12">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="showPasswords">
                                                            <label class="form-check-label" for="showPasswords">
                                                                <i class="fas fa-eye me-1 text-primary"></i>Tampilkan password
                                                            </label>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-12">
                                                        <div class="d-flex justify-content-end gap-3 mt-4 pt-4 border-top">
                                                            <a href="index.php" class="btn btn-outline-secondary px-4 py-2">
                                                                <i class="fas fa-times me-2"></i>Batal
                                                            </a>
                                                            <button type="submit" name="ganti_password" class="btn-gradient px-4 py-2">
                                                                <i class="fas fa-key me-2"></i>Perbarui Password
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Tab Riwayat Login -->
                                        <div class="tab-pane fade" id="activity" role="tabpanel">
                                            <h6 class="mb-3 fw-bold text-primary">Aktivitas Login Terakhir</h6>
                                            <div class="table-responsive">
                                                <table class="table table-hover table-borderless align-middle">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Waktu</th>
                                                            <th>Aktivitas</th>
                                                            <th>Detail</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while($log = mysqli_fetch_assoc($log_login)): ?>
                                                        <tr>
                                                            <td><small><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></small></td>
                                                            <td><span class="badge bg-success bg-opacity-10 text-success">Login Berhasil</span></td>
                                                            <td class="small text-muted"><?= $log['detail'] ?: 'Login ke sistem' ?></td>
                                                        </tr>
                                                        <?php endwhile; ?>
                                                        <?php if(mysqli_num_rows($log_login) == 0): ?>
                                                            <tr><td colspan="3" class="text-center text-muted">Belum ada riwayat login.</td></tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        
                                        <!-- Tab Sistem -->
                                        <div class="tab-pane fade" id="system" role="tabpanel">
                                            <h6 class="mb-3 fw-bold text-primary">Pemeliharaan Sistem</h6>
                                            <div class="alert alert-warning border-0 bg-light border-start border-3 border-warning">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-database fs-4 me-3 text-warning"></i>
                                                    <div>
                                                        <strong class="d-block mb-1 text-dark">Backup Database</strong>
                                                        <small class="text-muted">Unduh salinan database lengkap untuk keperluan backup.</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <form method="POST">
                                                <button type="submit" name="backup_db" class="btn btn-primary">
                                                    <i class="fas fa-download me-2"></i>Download Backup (.sql)
                                                </button>
                                            </form>
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
</div>

<!-- Modal Upload Foto Profil -->
<div class="modal fade modal-foto-custom" id="modalFoto" tabindex="-1" aria-labelledby="modalFotoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFotoLabel">
                    <i class="fas fa-camera"></i>
                    <?= $foto_profil ? 'Ganti Foto Profil' : 'Upload Foto Profil' ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="formUploadFoto">
                <div class="modal-body">
                    <div class="text-center">
                        <div class="preview-container mx-auto">
                            <?php if($foto_profil && file_exists($foto_profil)): ?>
                                <img src="<?= $foto_profil ?>" alt="Foto Profil" id="previewImg" class="preview-img">
                            <?php else: ?>
                                <div id="previewPlaceholder" class="preview-placeholder" style="background: <?= $avatar_color ?>;">
                                    <?= $inisial ?>
                                </div>
                                <img src="" alt="Preview" id="previewImg" class="preview-img" style="display: none;">
                            <?php endif; ?>
                        </div>
                        <h5 class="mb-3 fw-semibold"><?= $user['username'] ?></h5>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-3">Pilih Foto Baru <span class="text-danger">*</span></label>
                        <div class="input-file-custom">
                            <input type="file" name="foto" id="inputFoto" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp" required>
                            <div class="file-label"><i class="fas fa-cloud-upload-alt"></i><span>Klik untuk memilih file</span></div>
                        </div>
                        <div class="mt-3 text-center"><small class="text-muted"><i class="fas fa-info-circle me-1"></i>Format: JPG, PNG, GIF, WEBP. Maksimal 2MB.</small></div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div><?php if($foto_profil): ?><button type="button" class="btn-modal-danger" onclick="if(confirm('Yakin ingin menghapus foto profil?')) document.getElementById('formHapusFoto').submit();">Hapus Foto</button><?php endif; ?></div>
                    <div class="d-flex gap-3"><button type="button" class="btn-modal-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="upload_foto" class="btn-modal-primary">Upload</button></div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if($foto_profil): ?>
<form method="POST" id="formHapusFoto" style="display:none;">
    <input type="hidden" name="hapus_foto" value="1">
</form>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const toggleButtons = document.querySelectorAll('.toggle-password');
    const showPasswordsCheckbox = document.getElementById('showPasswords');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.password-input');
            const icon = this.querySelector('i');
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    });
    
    if (showPasswordsCheckbox) {
        showPasswordsCheckbox.addEventListener('change', function() {
            const inputs = document.querySelectorAll('.password-input');
            const type = this.checked ? 'text' : 'password';
            const buttons = document.querySelectorAll('.toggle-password i');
            
            inputs.forEach(input => {
                input.setAttribute('type', type);
            });
            
            buttons.forEach(icon => {
                if (this.checked) {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    }
    
    // Password strength checker
    const passwordInput = document.querySelector('input[name="password_baru"]');
    if (passwordInput) {
        const progressBar = passwordInput.closest('.col-md-6').querySelector('.progress-bar');
        const strengthText = passwordInput.closest('.col-md-6').querySelector('.strength-text');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            if (password === '') {
                progressBar.style.width = '0%';
                strengthText.textContent = '';
                return;
            }
            
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            let width, color, text;
            
            if (strength <= 2) {
                width = 33;
                color = '#dc3545';
                text = 'Lemah';
            } else if (strength <= 4) {
                width = 66;
                color = '#ffc107';
                text = 'Sedang';
            } else {
                width = 100;
                color = '#28a745';
                text = 'Kuat';
            }
            
            progressBar.style.width = width + '%';
            progressBar.style.backgroundColor = color;
            strengthText.textContent = text;
            strengthText.style.color = color;
        });
    }
    
    // Client-side Image Compression & Preview
    document.getElementById('inputFoto')?.addEventListener('change', async function(e) {
        const input = e.target;
        const file = input.files[0];
        if (!file) return;
        if (!file.type.match(/image.*/)) { alert('File harus berupa gambar!'); input.value = ''; return; }

        const label = input.nextElementSibling.querySelector('span');
        const originalText = label.innerText;
        label.innerText = 'Mengompres...';
        input.disabled = true;

        try {
            const compressedFile = await compressImage(file, 1280, 0.8, 500 * 1024);
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(compressedFile);
            input.files = dataTransfer.files;

            const reader = new FileReader();
            reader.onload = function(ev) {
                const previewImg = document.getElementById('previewImg');
                const placeholder = document.getElementById('previewPlaceholder');
                if (previewImg) { previewImg.src = ev.target.result; previewImg.style.display = 'block'; }
                if (placeholder) { placeholder.style.display = 'none'; }
            }
            reader.readAsDataURL(compressedFile);
            label.innerHTML = '<i class="fas fa-check text-success"></i> Siap (' + (compressedFile.size/1024).toFixed(0) + 'KB)';
        } catch (error) {
            console.error(error); alert("Gagal memproses gambar."); input.value = ''; label.innerText = originalText;
        } finally { input.disabled = false; }
    });
});

function compressImage(file, maxWidth, quality, maxBytes) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = event => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                let width = img.width;
                let height = img.height;
                if (width > maxWidth) { height = Math.round(height * (maxWidth / width)); width = maxWidth; }
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                let currentQuality = quality;
                const tryCompress = (q) => {
                    canvas.toBlob(blob => {
                        if (!blob) { reject(new Error('Canvas error')); return; }
                        if (blob.size > maxBytes && q > 0.5) { tryCompress(q - 0.1); } 
                        else { resolve(new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", { type: 'image/jpeg', lastModified: Date.now() })); }
                    }, 'image/jpeg', q);
                };
                tryCompress(currentQuality);
            };
            img.onerror = error => reject(error);
        };
        reader.onerror = error => reject(error);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
