<?php
$page = 'admin_profil';
cek_login();
cek_role(['admin']);

$user = get_user_login();
$user_id = $_SESSION['user_id'];
$username = $user['username'];
$created_at = $user['created_at'];

// Ambil data admin dari tabel admin
$stmt_admin = mysqli_prepare($conn, "SELECT * FROM admin WHERE user_id = ?");
mysqli_stmt_bind_param($stmt_admin, "i", $user_id);
mysqli_stmt_execute($stmt_admin);
$admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_admin));

// Jika belum ada data di tabel admin, buat baru
if (!$admin) {
    $stmt_insert = mysqli_prepare($conn, "INSERT INTO admin (user_id, nama) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt_insert, "is", $user_id, $username);
    mysqli_stmt_execute($stmt_insert);
    
    // Ambil ulang
    $stmt_admin = mysqli_prepare($conn, "SELECT * FROM admin WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt_admin, "i", $user_id);
    mysqli_stmt_execute($stmt_admin);
    $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_admin));
}

$admin_id = $admin['id'];
$nama_admin = $admin['nama'];
$email_admin = $admin['email'];
$no_hp_admin = $admin['no_hp'];
$jabatan_admin = $admin['jabatan'];
$foto_profil = $admin['foto'];

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profil'])) {
    $new_username = htmlspecialchars(trim($_POST['username']));
    $new_nama = htmlspecialchars(trim($_POST['nama']));
    $new_email = htmlspecialchars(trim($_POST['email']));
    $new_no_hp = htmlspecialchars(trim($_POST['no_hp']));
    $new_jabatan = htmlspecialchars(trim($_POST['jabatan']));
    
    if (empty($new_username)) {
        set_alert('danger', 'Username tidak boleh kosong!');
    } elseif (empty($new_nama)) {
        set_alert('danger', 'Nama lengkap tidak boleh kosong!');
    } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $new_username)) {
        set_alert('danger', 'Username hanya boleh berisi huruf, angka, titik (.), underscore (_), dan strip (-)');
    } elseif (strlen($new_username) < 4) {
        set_alert('danger', 'Username minimal 4 karakter!');
    } elseif (!empty($new_email) && !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        set_alert('danger', 'Format email tidak valid!');
    } elseif (!empty($new_no_hp) && !preg_match('/^[0-9]{10,15}$/', $new_no_hp)) {
        set_alert('danger', 'Nomor HP harus berupa angka 10-15 digit!');
    } else {
        // Cek username unik
        $stmt_cek = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? AND id != ?");
        mysqli_stmt_bind_param($stmt_cek, "si", $new_username, $user_id);
        mysqli_stmt_execute($stmt_cek);
        
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt_cek)) > 0) {
            set_alert('danger', 'Username sudah digunakan, silakan pilih yang lain.');
        } else {
            // Update tabel users (username)
            $stmt_user = mysqli_prepare($conn, "UPDATE users SET username = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_user, "si", $new_username, $user_id);
            $upd_user = mysqli_stmt_execute($stmt_user);
            
            // Update tabel admin (nama, email, no_hp, jabatan)
            $stmt_admin_upd = mysqli_prepare($conn, "UPDATE admin SET nama = ?, email = ?, no_hp = ?, jabatan = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_admin_upd, "ssssi", $new_nama, $new_email, $new_no_hp, $new_jabatan, $admin_id);
            $upd_admin = mysqli_stmt_execute($stmt_admin_upd);
            
            if ($upd_user && $upd_admin) {
                $_SESSION['username'] = $new_username;
                log_aktivitas($user_id, 'UPDATE', 'admin', $admin_id, "Admin mengubah profil");
                set_alert('success', "Profil berhasil diperbarui!");
                echo "<meta http-equiv='refresh' content='1'>";
            } else {
                set_alert('danger', 'Gagal memperbarui profil.');
            }
        }
    }
}

// PROSES UPLOAD FOTO PROFIL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_foto'])) {
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 500 * 1024; // 500KB
        
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_size = $_FILES['foto']['size'];
        
        // Validasi Server-side (MIME Type)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
        
        if (!in_array($mime, $allowed_types)) {
            set_alert('danger', 'Format file tidak didukung! Gunakan JPG, PNG, GIF, atau WEBP.');
        } elseif ($file_size > $max_size) {
            set_alert('danger', 'Ukuran file terlalu besar! Maksimal 500KB.');
        } else {
            // Generate nama file unik
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $ext;
            
            // Pastikan folder uploads/profil ada
            $upload_dir = 'uploads/profil/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $upload_path = $upload_dir . $new_filename;
            
            // Hapus foto lama jika ada
            if ($foto_profil && file_exists($foto_profil)) {
                unlink($foto_profil);
            }
            
            // Upload dengan optimasi jika fungsi tersedia
            if (function_exists('optimize_and_save_image')) {
                $upload_success = optimize_and_save_image($file_tmp, $upload_path, 300, 300, 75);
            } else {
                $upload_success = move_uploaded_file($file_tmp, $upload_path);
            }
            
            if ($upload_success) {
                $stmt_foto = mysqli_prepare($conn, "UPDATE admin SET foto = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_foto, "si", $upload_path, $admin_id);
                $update = mysqli_stmt_execute($stmt_foto);
                if ($update) {
                    log_aktivitas($user_id, 'UPDATE', 'admin', $admin_id, "Admin mengupload foto profil");
                    set_alert('success', 'Foto profil berhasil diupload!');
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
    header("Location: index.php?page=admin_profil");
    exit;
}

// PROSES HAPUS FOTO PROFIL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_foto'])) {
    if ($foto_profil && file_exists($foto_profil)) {
        unlink($foto_profil);
    }
    $stmt_hapus = mysqli_prepare($conn, "UPDATE admin SET foto = NULL WHERE id = ?");
    mysqli_stmt_bind_param($stmt_hapus, "i", $admin_id);
    mysqli_stmt_execute($stmt_hapus);
    log_aktivitas($user_id, 'UPDATE', 'admin', $admin_id, "Admin menghapus foto profil");
    set_alert('success', 'Foto profil berhasil dihapus!');
    header("Location: index.php?page=admin_profil");
    exit;
}

// Proses ganti password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ganti_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi_password'];
    
    if (!password_verify($password_lama, $user['password'])) {
        set_alert('danger', 'Password lama salah!');
    } elseif ($password_baru !== $konfirmasi) {
        set_alert('danger', 'Konfirmasi password tidak cocok!');
    } elseif (strlen($password_baru) < 6) {
        set_alert('danger', 'Password minimal 6 karakter!');
    } elseif ($password_lama === $password_baru) {
        set_alert('danger', 'Password baru tidak boleh sama dengan password lama!');
    } else {
        $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
        $stmt_pass = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_pass, "si", $hashed_password, $user_id);
        
        if (mysqli_stmt_execute($stmt_pass)) {
            log_aktivitas($user_id, 'UPDATE', 'users', $user_id, "Admin mengubah password");
            set_alert('success', 'Password berhasil diubah!');
        } else {
            set_alert('danger', 'Gagal mengubah password.');
        }
    }
    header("Location: index.php?page=admin_profil");
    exit;
}

// Refresh user & admin data
$user = get_user_login();
$username = $user['username'];

// Refresh data admin
$stmt_admin = mysqli_prepare($conn, "SELECT * FROM admin WHERE user_id = ?");
mysqli_stmt_bind_param($stmt_admin, "i", $user_id);
mysqli_stmt_execute($stmt_admin);
$admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_admin));

$nama_admin = $admin['nama'];
$email_admin = $admin['email'];
$no_hp_admin = $admin['no_hp'];
$jabatan_admin = $admin['jabatan'];
$foto_profil = $admin['foto'];

// Statistik sistem untuk admin
$total_mahasiswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM mahasiswa"))['total'];
$total_asisten = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM asisten"))['total'];
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];
$total_jadwal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM jadwal"))['total'];
$total_kelas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kelas"))['total'];
$total_lab = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM lab"))['total'];

// Statistik presensi keseluruhan
$stat_presensi = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha,
        COUNT(*) as total
    FROM presensi_mahasiswa
"));

// Log aktivitas admin terbaru
$log_admin = mysqli_query($conn, "
    SELECT lp.*, u.username 
    FROM log_presensi lp 
    LEFT JOIN users u ON lp.user_id = u.id 
    WHERE lp.user_id = '$user_id' 
    ORDER BY lp.created_at DESC 
    LIMIT 10
");

// Helper function untuk inisial
function get_inisial_admin($nama) {
    $words = explode(' ', trim($nama));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
    }
    return strtoupper(substr($nama, 0, 2));
}

$inisial = get_inisial_admin($nama_admin);
$avatar_color = 'linear-gradient(135deg, #ff6b35 0%, #f7931e 100%)';

// Hitung persentase kehadiran sistem
$total_presensi = $stat_presensi['total'] ?? 0;
$total_hadir = $stat_presensi['hadir'] ?? 0;
$persentase_kehadiran = $total_presensi > 0 ? round(($total_hadir / $total_presensi) * 100) : 0;

// Jadwal bulan ini
$bulan_ini = date('m');
$tahun_ini = date('Y');
$total_jadwal_bulan_ini = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total FROM jadwal WHERE MONTH(tanggal) = '$bulan_ini' AND YEAR(tanggal) = '$tahun_ini'"
))['total'];
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
        width: 24px;
        height: 24px;
        background: #ffc107;
        border: 3px solid white;
        border-radius: 50%;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .avatar-badge i {
        font-size: 10px;
        color: #333;
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
    
    .btn-gradient::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transform: translateX(-100%);
    }
    
    .btn-gradient:hover::after {
        transform: translateX(100%);
        transition: transform 0.6s;
    }
    
    .btn-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(37, 80, 209, 0.25);
        color: white;
    }
    
    /* Button Upload Foto */
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
        cursor: pointer;
    }
    
    .btn-upload-foto:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(37, 80, 209, 0.3);
    }
    
    /* Modal Foto Styling */
    .modal-foto-custom .modal-content {
        border-radius: 16px;
        border: none;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(37, 80, 209, 0.25);
    }
    
    .modal-foto-custom .modal-header {
        background: linear-gradient(135deg, #0066ccff 0%, #0099ffff 100%);
        border-bottom: none;
        padding: 24px 30px;
        color: white;
    }
    
    .modal-foto-custom .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .modal-foto-custom .modal-body {
        padding: 30px;
        background: #f8f9ff;
    }
    
    .modal-foto-custom .modal-footer {
        background: white;
        border-top: 1px solid #eef1ff;
        padding: 20px 30px;
    }
    
    .modal-foto-custom .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
        transition: opacity 0.3s;
    }
    
    .modal-foto-custom .btn-close:hover {
        opacity: 1;
    }
    
    /* Preview Container */
    .preview-container {
        width: 180px;
        height: 180px;
        border-radius: 50%;
        overflow: hidden;
        border: 5px solid white;
        box-shadow: 0 8px 25px rgba(37, 80, 209, 0.15);
        margin: 0 auto 25px;
        background: linear-gradient(135deg, #f0f3ff 0%, #e4e8ff 100%);
        position: relative;
    }
    
    .preview-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .preview-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        font-weight: 700;
        color: white;
    }
    
    /* Input File Custom */
    .input-file-custom {
        position: relative;
    }
    
    .input-file-custom input[type="file"] {
        position: absolute;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }
    
    .input-file-custom .file-label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 14px 20px;
        background: white;
        border: 2px dashed #0066cc;
        border-radius: 12px;
        color: #0066cc;
        font-weight: 500;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .input-file-custom:hover .file-label {
        background: #f8f9ff;
        border-color: #0099ff;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37, 80, 209, 0.1);
    }
    
    .input-file-custom .file-label i {
        font-size: 1.2rem;
    }
    
    /* Button Modal */
    .btn-modal-primary {
        background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%);
        border: none;
        color: white;
        padding: 12px 28px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-width: 120px;
    }
    
    .btn-modal-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(37, 80, 209, 0.25);
        color: white;
    }
    
    .btn-modal-secondary {
        background: #f8f9ff;
        border: 2px solid #eef1ff;
        color: #64748b;
        padding: 12px 28px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-width: 120px;
    }
    
    .btn-modal-secondary:hover {
        background: #eef1ff;
        border-color: #0066cc;
        color: #0066cc;
        transform: translateY(-2px);
    }
    
    .btn-modal-danger {
        background: transparent;
        border: 2px solid #dc3545;
        color: #dc3545;
        padding: 12px 28px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .btn-modal-danger:hover {
        background: #dc3545;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
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
    
    /* Stats Cards */
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
    
    .stat-icon-primary {
        background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%);
    }
    
    .stat-icon-success {
        background: linear-gradient(135deg, #25d165 0%, #1ab34b 100%);
    }
    
    .stat-icon-warning {
        background: linear-gradient(135deg, #d1b325 0%, #b39b1a 100%);
    }
    
    .stat-icon-info {
        background: linear-gradient(135deg, #8f25d1 0%, #761ab3 100%);
    }
    
    .stat-icon-danger {
        background: linear-gradient(135deg, #d12525 0%, #b31a1a 100%);
    }
    
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
    
    /* Progress Bar */
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
    
    .text-primary {
        color: #0066cc !important;
    }
    
    /* Responsive Design */
    @media (max-width: 991.98px) {
        .profile-header {
            padding: 30px 15px;
        }
        
        .avatar-container {
            width: 100px;
            height: 100px;
            font-size: 36px;
        }
        
        .profile-header h1.display-6 {
            font-size: 1.5rem;
        }
        
        .info-badge {
            padding: 5px 12px;
            font-size: 0.8rem;
        }
        
        .info-item {
            padding: 14px;
        }
        
        .info-icon {
            width: 45px;
            height: 45px;
            font-size: 18px;
            margin-right: 12px;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .stat-card {
            padding: 16px;
        }
        
        .stat-icon {
            width: 45px;
            height: 45px;
            font-size: 18px;
        }
        
        .stat-value {
            font-size: 1.5rem;
        }
        
        .nav-tabs-custom {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .nav-tabs-custom::-webkit-scrollbar {
            display: none;
        }
        
        .nav-tabs-custom .nav-link {
            padding: 12px 18px;
            font-size: 0.9rem;
            white-space: nowrap;
            flex-shrink: 0;
        }
    }
    
    @media (max-width: 767.98px) {
        .profile-container {
            min-height: auto;
        }
        
        .profile-header {
            padding: 25px 10px;
        }
        
        .profile-header .row {
            flex-direction: column;
            text-align: center;
        }
        
        .avatar-container {
            width: 90px;
            height: 90px;
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .avatar-badge {
            width: 20px;
            height: 20px;
            bottom: 5px;
            right: 5px;
        }
        
        .avatar-badge i {
            font-size: 8px;
        }
        
        .profile-header h1.display-6 {
            font-size: 1.25rem;
            margin-bottom: 10px !important;
        }
        
        .info-badge {
            padding: 4px 10px;
            font-size: 0.75rem;
            margin: 2px;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .stat-card {
            padding: 14px;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 1.3rem;
        }
        
        .stat-label {
            font-size: 0.75rem;
        }
        
        .nav-tabs-custom {
            display: flex;
            flex-wrap: nowrap;
            border-bottom: 2px solid #f0f3ff;
            margin: 0;
            padding: 0;
            gap: 8px;
        }
        
        .nav-tabs-custom .nav-item {
            flex: 1;
            min-width: 0;
        }
        
        .nav-tabs-custom .nav-link {
            padding: 12px 8px;
            font-size: 0.85rem;
            white-space: nowrap;
            text-align: center;
            border-radius: 8px 8px 0 0;
            margin-right: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }
        
        .profile-card {
            border-radius: 12px;
            margin: 0 -5px;
        }
        
        .profile-card .card-body {
            padding: 15px !important;
        }
        
        .profile-card h4,
        .profile-card h5 {
            font-size: 1rem;
        }
        
        .info-item {
            padding: 12px;
            margin-bottom: 8px;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            font-size: 16px;
            margin-right: 10px;
            border-radius: 10px;
        }
        
        .form-control-custom {
            padding: 12px 14px;
            font-size: 14px;
        }
        
        .btn-gradient {
            padding: 12px 20px;
            font-size: 0.9rem;
            width: 100%;
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .stat-card {
            padding: 12px;
        }
        
        .stat-value {
            font-size: 1.1rem;
        }
    }

    /* Dark Mode Support */
    [data-theme="dark"] .profile-container {
        background: var(--bg-body);
    }
    
    [data-theme="dark"] .profile-card {
        background: var(--bg-card);
        border-color: var(--border-color);
        box-shadow: none;
    }
    
    [data-theme="dark"] .profile-header {
        background: var(--banner-gradient);
    }
    
    [data-theme="dark"] .info-badge {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.2);
        color: #e2e8f0;
    }
    
    [data-theme="dark"] .info-item {
        background: var(--bg-body);
        border-color: var(--border-color);
    }
    
    [data-theme="dark"] .info-item:hover {
        border-color: #66b0ff;
        background: rgba(255,255,255,0.05);
    }
    
    [data-theme="dark"] .info-icon {
        background: rgba(0, 102, 204, 0.2);
        color: #66b0ff;
    }
    
    [data-theme="dark"] .form-control-custom {
        background: var(--bg-input);
        border-color: var(--border-color);
        color: var(--text-main);
    }
    
    [data-theme="dark"] .form-control-custom:focus {
        border-color: #66b0ff;
    }
    
    [data-theme="dark"] .form-control-custom[readonly] {
        background: rgba(255,255,255,0.05) !important;
        color: var(--text-muted);
    }
    
    [data-theme="dark"] .nav-tabs-custom {
        border-bottom-color: var(--border-color);
    }
    
    [data-theme="dark"] .nav-tabs-custom .nav-link {
        background: rgba(255,255,255,0.05);
        color: var(--text-muted);
    }
    
    [data-theme="dark"] .nav-tabs-custom .nav-link:hover {
        background: rgba(255,255,255,0.1);
        color: #66b0ff;
    }
    
    [data-theme="dark"] .nav-tabs-custom .nav-link.active {
        background: var(--bg-card);
        color: #66b0ff;
        border-bottom-color: #66b0ff;
    }
    
    [data-theme="dark"] .stat-card {
        background: var(--bg-card);
        border-color: var(--border-color);
    }
    
    /* Stat Icons Dark Mode */
    [data-theme="dark"] .stat-icon-primary { background: rgba(0, 102, 204, 0.2); color: #66b0ff; }
    [data-theme="dark"] .stat-icon-success { background: rgba(102, 204, 0, 0.2); color: #85e085; }
    [data-theme="dark"] .stat-icon-warning { background: rgba(255, 170, 0, 0.2); color: #ffcc00; }
    [data-theme="dark"] .stat-icon-info { background: rgba(111, 66, 193, 0.2); color: #a685e0; }
    [data-theme="dark"] .stat-icon-danger { background: rgba(220, 53, 69, 0.2); color: #ea868f; }
    
    /* Text Colors Dark Mode */
    [data-theme="dark"] .text-primary { color: #66b0ff !important; }
    [data-theme="dark"] .text-success { color: #85e085 !important; }
    [data-theme="dark"] .text-warning { color: #ffcc00 !important; }
    [data-theme="dark"] .text-info { color: #a685e0 !important; }
    [data-theme="dark"] .text-danger { color: #ea868f !important; }
    
    [data-theme="dark"] .progress-custom {
        background: rgba(255,255,255,0.1);
    }
    
    [data-theme="dark"] .alert-info {
        background-color: rgba(13, 202, 240, 0.15);
        border-color: rgba(13, 202, 240, 0.3);
        color: #6edff6;
    }
    
    [data-theme="dark"] .modal-foto-custom .modal-content {
        background: var(--bg-card);
    }
    
    [data-theme="dark"] .modal-foto-custom .modal-body {
        background: var(--bg-body);
        color: var(--text-main);
    }
    
    [data-theme="dark"] .modal-foto-custom .modal-footer {
        background: var(--bg-card);
        border-top-color: var(--border-color);
    }
    
    [data-theme="dark"] .input-file-custom .file-label {
        background: var(--bg-input);
        border-color: #66b0ff;
        color: #66b0ff;
    }
    
    [data-theme="dark"] .input-file-custom:hover .file-label {
        background: rgba(102, 176, 255, 0.1);
    }
    
    [data-theme="dark"] .btn-modal-secondary {
        background: var(--bg-input);
        border-color: var(--border-color);
        color: var(--text-muted);
    }
    
    [data-theme="dark"] .btn-modal-secondary:hover {
        background: rgba(255,255,255,0.1);
        border-color: #66b0ff;
        color: #66b0ff;
    }
    
    [data-theme="dark"] .preview-container {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        border-color: var(--bg-card);
    }
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
                                    <div class="avatar-badge">
                                        <i class="fas fa-crown"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-9 text-white d-flex flex-column justify-content-center">
                                <h1 class="display-6 mb-2 fw-bold text-center text-md-start text-white"><?= $nama_admin ?></h1>
                                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                                    <span class="info-badge">
                                        <i class="fas fa-shield-alt me-2"></i><?= $jabatan_admin ?: 'Administrator' ?>
                                    </span>
                                    <span class="info-badge">
                                        <i class="fas fa-envelope me-2"></i><?= $email_admin ?: '-' ?>
                                    </span>
                                    <span class="info-badge">
                                        <i class="fas fa-calendar-alt me-2"></i>Bergabung: <?= date('d M Y', strtotime($created_at)) ?>
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
                                        <i class="fas fa-user-circle me-2"></i>Profil Admin
                                    </h4>
                                    
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon">
                                                <i class="fas fa-id-card"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">Nama Lengkap</small>
                                                <strong class="fs-6"><?= $nama_admin ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">Username</small>
                                                <strong class="fs-6"><?= $username ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon">
                                                <i class="fas fa-envelope"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">Email</small>
                                                <strong class="fs-6"><?= $email_admin ?: '<span class="text-muted">Belum diisi</span>' ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon">
                                                <i class="fas fa-phone"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">No. HP</small>
                                                <strong class="fs-6"><?= $no_hp_admin ?: '<span class="text-muted">Belum diisi</span>' ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon">
                                                <i class="fas fa-briefcase"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">Jabatan</small>
                                                <strong class="fs-6"><?= $jabatan_admin ?: '<span class="text-muted">Belum diisi</span>' ?></strong>
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
                                                <strong class="fs-6">
                                                    <span class="badge bg-warning text-dark">Administrator</span>
                                                </strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon">
                                                <i class="fas fa-calendar-plus"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">Terdaftar Sejak</small>
                                                <strong class="fs-6"><?= date('d F Y', strtotime($created_at)) ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">Login Terakhir</small>
                                                <strong class="fs-6"><?= date('d M Y, H:i') ?></strong>
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
                            
                            <!-- Statistik Kehadiran Sistem -->
                            <div class="profile-card">
                                <div class="card-body p-4">
                                    <h5 class="mb-4 fw-bold text-primary">
                                        <i class="fas fa-chart-pie me-2"></i>Tingkat Kehadiran Sistem
                                    </h5>
                                    
                                    <div class="text-center mb-4">
                                        <div class="d-inline-block position-relative">
                                            <div class="position-relative" style="width: 150px; height: 150px;">
                                                <svg width="150" height="150" viewBox="0 0 150 150">
                                                    <circle cx="75" cy="75" r="70" stroke="#eef1ff" stroke-width="10" fill="none"/>
                                                    <circle cx="75" cy="75" r="70" stroke="#0066cc" stroke-width="10" 
                                                            fill="none" stroke-linecap="round"
                                                            stroke-dasharray="440" 
                                                            stroke-dashoffset="<?= 440 - (440 * $persentase_kehadiran / 100) ?>"
                                                            transform="rotate(-90 75 75)"/>
                                                </svg>
                                                <div class="position-absolute top-50 start-50 translate-middle text-center">
                                                    <h2 class="fw-bold mb-0 text-primary"><?= $persentase_kehadiran ?>%</h2>
                                                    <small class="text-muted">Kehadiran</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="progress-custom">
                                        <div class="progress-bar-custom bg-primary" 
                                             role="progressbar" 
                                             style="width: <?= $persentase_kehadiran ?>%"></div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mt-3">
                                        <small class="text-muted">Total: <?= number_format($total_hadir) ?> dari <?= number_format($total_presensi) ?> presensi</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-8">
                            <!-- Statistik Grid -->
                            <div class="profile-card mb-4">
                                <div class="card-body p-4">
                                    <h5 class="mb-4 fw-bold text-primary">
                                        <i class="fas fa-chart-bar me-2"></i>Statistik Sistem
                                    </h5>
                                    
                                    <div class="stats-grid">
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-primary">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <div class="stat-value text-primary"><?= number_format($total_mahasiswa) ?></div>
                                            <div class="stat-label">Total Mahasiswa</div>
                                            <small class="text-muted">Terdaftar</small>
                                        </div>
                                        
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-success">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <div class="stat-value text-success"><?= number_format($total_asisten) ?></div>
                                            <div class="stat-label">Total Asisten</div>
                                            <small class="text-muted">Aktif</small>
                                        </div>
                                        
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-warning">
                                                <i class="fas fa-calendar-alt"></i>
                                            </div>
                                            <div class="stat-value text-warning"><?= number_format($total_jadwal) ?></div>
                                            <div class="stat-label">Total Jadwal</div>
                                            <small class="text-muted">Semua Jenis</small>
                                        </div>
                                        
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-info">
                                                <i class="fas fa-chalkboard"></i>
                                            </div>
                                            <div class="stat-value text-info"><?= number_format($total_kelas) ?></div>
                                            <div class="stat-label">Total Kelas</div>
                                            <small class="text-muted">Terdaftar</small>
                                        </div>
                                        
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-danger">
                                                <i class="fas fa-desktop"></i>
                                            </div>
                                            <div class="stat-value text-danger"><?= number_format($total_lab) ?></div>
                                            <div class="stat-label">Total Lab</div>
                                            <small class="text-muted">Tersedia</small>
                                        </div>
                                        
                                        <div class="stat-card">
                                            <div class="stat-icon" style="background: linear-gradient(135deg, #25d1d1 0%, #1ab3b3 100%);">
                                                <i class="fas fa-calendar-day"></i>
                                            </div>
                                            <div class="stat-value" style="color: #25d1d1;"><?= number_format($total_jadwal_bulan_ini) ?></div>
                                            <div class="stat-label">Jadwal Bulan Ini</div>
                                            <small class="text-muted"><?= date('F Y') ?></small>
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
                                                <i class="fas fa-history me-2"></i>Log Aktivitas
                                            </button>
                                        </li>
                                    </ul>
                                    
                                    <div class="tab-content" id="profileTabContent">
                                        <div class="tab-pane fade show active" id="edit-profile" role="tabpanel">
                                            <form method="POST">
                                                <div class="row g-4">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">
                                                            <i class="fas fa-id-card me-2 text-primary"></i>Nama Lengkap
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text" name="nama" class="form-control form-control-custom" 
                                                               value="<?= $nama_admin ?>" required>
                                                        <small class="text-muted">Nama lengkap Anda</small>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">
                                                            <i class="fas fa-user-tag me-2 text-primary"></i>Username
                                                        </label>
                                                        <input type="text" name="username" class="form-control form-control-custom" 
                                                               value="<?= $username ?>" required minlength="4">
                                                        <small class="text-muted">Username digunakan untuk login</small>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">
                                                            <i class="fas fa-envelope me-2 text-primary"></i>Email
                                                        </label>
                                                        <input type="email" name="email" class="form-control form-control-custom" 
                                                               value="<?= $email_admin ?>" placeholder="contoh@email.com">
                                                        <small class="text-muted">Email aktif untuk notifikasi</small>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">
                                                            <i class="fas fa-phone me-2 text-primary"></i>No. HP
                                                        </label>
                                                        <input type="text" name="no_hp" class="form-control form-control-custom" 
                                                               value="<?= $no_hp_admin ?>" placeholder="08xxxxxxxxxx">
                                                        <small class="text-muted">Nomor HP aktif</small>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">
                                                            <i class="fas fa-briefcase me-2 text-primary"></i>Jabatan
                                                        </label>
                                                        <input type="text" name="jabatan" class="form-control form-control-custom" 
                                                               value="<?= $jabatan_admin ?>" placeholder="Kepala Lab / Admin Sistem">
                                                        <small class="text-muted">Jabatan atau posisi Anda</small>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">
                                                            <i class="fas fa-user-shield me-2 text-primary"></i>Role
                                                        </label>
                                                        <input type="text" class="form-control form-control-custom" 
                                                               value="Administrator" readonly style="background: #f8f9ff;">
                                                    </div>
                                                    
                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold">
                                                            <i class="fas fa-calendar-alt me-2 text-primary"></i>Terdaftar Sejak
                                                        </label>
                                                        <input type="text" class="form-control form-control-custom" 
                                                               value="<?= date('d F Y', strtotime($created_at)) ?>" readonly style="background: #f8f9ff;">
                                                    </div>
                                                    
                                                    <div class="col-12">
                                                        <div class="d-flex justify-content-end gap-3 mt-4 pt-4 border-top">
                                                            <a href="index.php?page=dashboard" class="btn btn-outline-secondary px-4 py-2">
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
                                                            <input type="password" name="password_lama" id="password_lama"
                                                                   class="form-control form-control-custom password-input" 
                                                                   placeholder="Masukkan password saat ini" 
                                                                   required>
                                                            <button class="btn btn-outline-primary toggle-password" type="button">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-outline-secondary" type="button" id="btn_cek_password" title="Cek Password">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </div>
                                                        <div id="password_lama_feedback" class="mt-2" style="display: none;">
                                                            <small class="feedback-text"></small>
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
                                                            <a href="index.php?page=dashboard" class="btn btn-outline-secondary px-4 py-2">
                                                                Batal
                                                            </a>
                                                            <button type="submit" name="ganti_password" class="btn-gradient px-4 py-2">
                                                                <i class="fas fa-key me-2"></i>Perbarui Password
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        
                                        <!-- Tab Log Aktivitas -->
                                        <div class="tab-pane fade" id="activity" role="tabpanel">
                                            <h6 class="mb-3 fw-bold text-primary">Log Aktivitas Terakhir</h6>
                                            <div class="table-responsive">
                                                <table class="table table-hover table-borderless align-middle">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Waktu</th>
                                                            <th>Aksi</th>
                                                            <th>Detail</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while($log = mysqli_fetch_assoc($log_admin)): ?>
                                                        <tr>
                                                            <td><small><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></small></td>
                                                            <td>
                                                                <?php 
                                                                $badge_class = 'bg-secondary';
                                                                if (strpos($log['aksi'], 'LOGIN') !== false) $badge_class = 'bg-success';
                                                                elseif (strpos($log['aksi'], 'CREATE') !== false) $badge_class = 'bg-primary';
                                                                elseif (strpos($log['aksi'], 'UPDATE') !== false) $badge_class = 'bg-warning text-dark';
                                                                elseif (strpos($log['aksi'], 'DELETE') !== false) $badge_class = 'bg-danger';
                                                                ?>
                                                                <span class="badge <?= $badge_class ?>"><?= $log['aksi'] ?></span>
                                                            </td>
                                                            <td class="small text-muted"><?= $log['detail'] ?: '-' ?></td>
                                                        </tr>
                                                        <?php endwhile; ?>
                                                        <?php if(mysqli_num_rows($log_admin) == 0): ?>
                                                            <tr><td colspan="3" class="text-center text-muted">Belum ada log aktivitas.</td></tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
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
</div>

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
    
    // Validasi password lama dengan button
    const passwordLamaInput = document.getElementById('password_lama');
    const passwordLamaFeedback = document.getElementById('password_lama_feedback');
    const btnCekPassword = document.getElementById('btn_cek_password');
    
    if (btnCekPassword && passwordLamaInput && passwordLamaFeedback) {
        const feedbackText = passwordLamaFeedback.querySelector('.feedback-text');
        
        btnCekPassword.addEventListener('click', function() {
            const password = passwordLamaInput.value;
            
            // Validasi input kosong
            if (password.length === 0) {
                passwordLamaFeedback.style.display = 'block';
                feedbackText.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> Masukkan password terlebih dahulu';
                feedbackText.className = 'feedback-text text-warning';
                passwordLamaInput.focus();
                return;
            }
            
            // Tampilkan loading
            btnCekPassword.disabled = true;
            btnCekPassword.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            passwordLamaFeedback.style.display = 'block';
            feedbackText.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memeriksa...';
            feedbackText.className = 'feedback-text text-muted';
            
            // Kirim request AJAX ke API terpisah
            const formData = new FormData();
            formData.append('password_lama', password);
            
            fetch('api/check_password.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    feedbackText.innerHTML = '<i class="fas fa-check-circle me-1"></i> ' + data.message;
                    feedbackText.className = 'feedback-text text-success';
                    passwordLamaInput.classList.remove('is-invalid');
                    passwordLamaInput.classList.add('is-valid');
                } else {
                    feedbackText.innerHTML = '<i class="fas fa-times-circle me-1"></i> ' + data.message;
                    feedbackText.className = 'feedback-text text-danger';
                    passwordLamaInput.classList.remove('is-valid');
                    passwordLamaInput.classList.add('is-invalid');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                feedbackText.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> Gagal memeriksa, coba lagi';
                feedbackText.className = 'feedback-text text-danger';
            })
            .finally(() => {
                btnCekPassword.disabled = false;
                btnCekPassword.innerHTML = '<i class="fas fa-check-circle"></i> Cek';
            });
        });
        
        // Reset feedback saat input berubah
        passwordLamaInput.addEventListener('input', function() {
            passwordLamaFeedback.style.display = 'none';
            this.classList.remove('is-valid', 'is-invalid');
        });
    }
    
    // Client-side Image Compression & Preview
    document.getElementById('inputFoto')?.addEventListener('change', async function(e) {
        const input = e.target;
        const file = input.files[0];
        
        if (!file) return;

        if (!file.type.match(/image.*/)) {
            alert('File harus berupa gambar!');
            input.value = '';
            return;
        }

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
                
                if (previewImg) {
                    previewImg.src = ev.target.result;
                    previewImg.style.display = 'block';
                }
                
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
            }
            reader.readAsDataURL(compressedFile);
            
            label.innerHTML = '<i class="fas fa-check text-success"></i> Siap (' + (compressedFile.size/1024).toFixed(0) + 'KB)';
            
        } catch (error) {
            console.error(error);
            alert("Gagal memproses gambar.");
            input.value = '';
            label.innerText = originalText;
        } finally {
            input.disabled = false;
        }
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

                if (width > maxWidth) {
                    height = Math.round(height * (maxWidth / width));
                    width = maxWidth;
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                let currentQuality = quality;
                const minQuality = 0.5;
                
                const tryCompress = (q) => {
                    canvas.toBlob(blob => {
                        if (!blob) {
                            reject(new Error('Canvas error'));
                            return;
                        }
                        
                        if (blob.size > maxBytes && q > minQuality) {
                            tryCompress(q - 0.1);
                        } else {
                            const newFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });
                            resolve(newFile);
                        }
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
                        <!-- Preview Foto -->
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
                        
                        <h5 class="mb-3 fw-semibold"><?= $username ?></h5>
                        <p class="text-muted mb-4">Administrator</p>
                    </div>
                    
                    <!-- Input File -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-3">Pilih Foto Baru <span class="text-danger">*</span></label>
                        <div class="input-file-custom">
                            <input type="file" name="foto" id="inputFoto" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp" required>
                            <div class="file-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Klik untuk memilih file</span>
                            </div>
                        </div>
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Format: JPG, PNG, GIF, WEBP. Maksimal 500KB.
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div>
                        <?php if($foto_profil): ?>
                            <button type="button" class="btn-modal-danger" onclick="if(confirm('Yakin ingin menghapus foto profil?')) document.getElementById('formHapusFoto').submit();">
                                Hapus Foto
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-3">
                        <button type="button" class="btn-modal-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" name="upload_foto" class="btn-modal-primary">
                            Upload
                        </button>
                    </div>
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

<?php include 'includes/footer.php'; ?>
