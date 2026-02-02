<?php
cek_login();
cek_role(['asisten']);
$page = 'asisten_profil';
$asisten = get_asisten_login();
$user = get_user_login();

// Variabel foto profil
$foto_profil = $asisten['foto'] ?? null;

// PROSES UPLOAD FOTO PROFIL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_foto'])) {
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        $file_type = $_FILES['foto']['type'];
        $file_size = $_FILES['foto']['size'];
        $file_tmp = $_FILES['foto']['tmp_name'];
        $kode_asisten = $asisten['kode_asisten'];
        
        if (!in_array($file_type, $allowed_types)) {
            set_alert('danger', 'Format file tidak didukung! Gunakan JPG, PNG, GIF, atau WEBP.');
        } elseif ($file_size > $max_size) {
            set_alert('danger', 'Ukuran file terlalu besar! Maksimal 2MB.');
        } else {
            // Generate nama file unik
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $new_filename = 'ast_' . $kode_asisten . '_' . time() . '.' . $ext;
            
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
            
            // [PERBAIKAN] Ganti move_uploaded_file dengan fungsi optimasi
            // Ukuran 300x300px sudah cukup untuk foto profil
            if (optimize_and_save_image($file_tmp, $upload_path, 300, 300, 75)) {
                 $id_asisten = $asisten['id'];
                // Prepared statement untuk update foto
                $stmt_foto = mysqli_prepare($conn, "UPDATE asisten SET foto = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_foto, "si", $upload_path, $id_asisten);
                $update = mysqli_stmt_execute($stmt_foto);
                if ($update) {
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
    header("Location: index.php?page=asisten_profil");
    exit;
}

// PROSES HAPUS FOTO PROFIL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_foto'])) {
    if ($foto_profil && file_exists($foto_profil)) {
        unlink($foto_profil);
    }
    $id_asisten = $asisten['id'];
    // Prepared statement untuk hapus foto
    $stmt_hapus = mysqli_prepare($conn, "UPDATE asisten SET foto = NULL WHERE id = ?");
    mysqli_stmt_bind_param($stmt_hapus, "i", $id_asisten);
    mysqli_stmt_execute($stmt_hapus);
    set_alert('success', 'Foto profil berhasil dihapus!');
    header("Location: index.php?page=asisten_profil");
    exit;
}

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profil'])) {
        $no_hp = escape($_POST['no_hp']);
        $new_username = htmlspecialchars(trim($_POST['username']));
        $id_asisten = $asisten['id'];
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
                // Update users (username)
                $stmt_user = mysqli_prepare($conn, "UPDATE users SET username = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_user, "si", $new_username, $user_id);
                $upd_user = mysqli_stmt_execute($stmt_user);

                // Update asisten (no_hp)
                $stmt_hp = mysqli_prepare($conn, "UPDATE asisten SET no_hp = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_hp, "si", $no_hp, $id_asisten);
                $upd_ast = mysqli_stmt_execute($stmt_hp);

                if ($upd_user && $upd_ast) {
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
            // Prepared statement untuk update password
            $stmt_pass = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_pass, "si", $hashed_password_baru, $user_id);
            mysqli_stmt_execute($stmt_pass);
            set_alert('success', 'Password berhasil diubah!');
        }
        header("Location: index.php?page=asisten_profil");
        exit;
    }
}

// Refresh data setelah update
$asisten = get_asisten_login();
$foto_profil = $asisten['foto'] ?? null;

// Helper function untuk inisial
function get_inisial_asisten($nama) {
    $words = explode(' ', trim($nama));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
    }
    return strtoupper(substr($nama, 0, 2));
}

// Helper function untuk warna avatar
function get_avatar_color($kode_asisten) {
    $colors = [
        'linear-gradient(135deg, #0066cc 0%, #0099ff 100%)',
        'linear-gradient(135deg, #d12525 0%, #b31a1a 100%)',
        'linear-gradient(135deg, #25d165 0%, #1ab34b 100%)',
        'linear-gradient(135deg, #d1b325 0%, #b39b1a 100%)',
        'linear-gradient(135deg, #8f25d1 0%, #761ab3 100%)'
    ];
    $hash = crc32($kode_asisten);
    return $colors[abs($hash) % count($colors)];
}

$inisial = get_inisial_asisten($asisten['nama']);
$avatar_color = get_avatar_color($asisten['kode_asisten']);

// Statistik asisten berdasarkan database
$kode_asisten = $asisten['kode_asisten'];

// 1. Total Jadwal (semua jenis)
$total_jadwal = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total 
     FROM jadwal 
     WHERE kode_asisten_1 = '$kode_asisten' OR kode_asisten_2 = '$kode_asisten'"
))['total'];

// 2. Total Kehadiran (hadir)
$total_kehadiran = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total 
     FROM absen_asisten 
     WHERE kode_asisten = '$kode_asisten' AND status = 'hadir'"
))['total'];

// 3. Total Jadwal Materi (jenis = 'materi')
$total_materi = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total 
     FROM jadwal 
     WHERE (kode_asisten_1 = '$kode_asisten' OR kode_asisten_2 = '$kode_asisten') 
     AND jenis = 'materi'"
))['total'];

// 4. Total Jadwal Inhall (jenis = 'inhall')
$total_inhall = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total 
     FROM jadwal 
     WHERE (kode_asisten_1 = '$kode_asisten' OR kode_asisten_2 = '$kode_asisten') 
     AND jenis = 'inhall'"
))['total'];

// 5. Total Jadwal Ujikom (jenis = 'ujikom')
$total_ujikom = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total 
     FROM jadwal 
     WHERE (kode_asisten_1 = '$kode_asisten' OR kode_asisten_2 = '$kode_asisten') 
     AND jenis = 'ujikom'"
))['total'];

// 6. Total Jadwal Bulan Ini
$bulan_ini = date('m');
$tahun_ini = date('Y');
$total_jadwal_bulan_ini = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total 
     FROM jadwal 
     WHERE (kode_asisten_1 = '$kode_asisten' OR kode_asisten_2 = '$kode_asisten') 
     AND MONTH(tanggal) = '$bulan_ini' 
     AND YEAR(tanggal) = '$tahun_ini'"
))['total'];

// Hitung persentase kehadiran
$persentase_kehadiran = 0;
if ($total_jadwal > 0) {
    $persentase_kehadiran = round(($total_kehadiran / $total_jadwal) * 100);
}

// Tanggal daftar dari user
$tanggal_daftar = $user['created_at'] ?? date('Y-m-d H:i:s');
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
    
    .btn-upload-foto::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transform: translateX(-100%);
        transition: transform 0.6s;
    }
    
    .btn-upload-foto:hover::before {
        transform: translateX(100%);
    }
    
    .btn-upload-foto:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(37, 80, 209, 0.3);
    }
    
    .btn-upload-foto i {
        font-size: 1.1rem;
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
        
        .preview-container {
            width: 150px;
            height: 150px;
        }
        
        .preview-placeholder {
            font-size: 3rem;
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
            width: 16px;
            height: 16px;
            bottom: 5px;
            right: 5px;
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
        
        .btn-gradient,
        .btn-upload-foto {
            padding: 12px 20px;
            font-size: 0.9rem;
            width: 100%;
        }
        
        .modal-foto-custom .modal-header,
        .modal-foto-custom .modal-body,
        .modal-foto-custom .modal-footer {
            padding: 20px;
        }
        
        .preview-container {
            width: 120px;
            height: 120px;
            margin-bottom: 20px;
        }
        
        .preview-placeholder {
            font-size: 2.5rem;
        }
        
        .btn-modal-primary,
        .btn-modal-secondary,
        .btn-modal-danger {
            padding: 10px 20px;
            min-width: auto;
            flex: 1;
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
        
        .preview-container {
            width: 100px;
            height: 100px;
        }
        
        .preview-placeholder {
            font-size: 2rem;
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
    
    /* Stat Icons Dark Mode - Matching Dashboard Style */
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
    
    [data-theme="dark"] .alert-info {
        background-color: rgba(13, 202, 240, 0.15);
        border-color: rgba(13, 202, 240, 0.3);
        color: #6edff6;
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
                                    <div class="avatar-badge"></div>
                                </div>
                            </div>
                            <div class="col-md-9 text-white d-flex flex-column justify-content-center">
                                <h1 class="display-6 mb-2 fw-bold text-center text-md-start text-white"><?= $asisten['nama'] ?></h1>
                                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                                    <span class="info-badge">
                                        <i class="fas fa-id-card me-2"></i>Kode: <?= $asisten['kode_asisten'] ?>
                                    </span>
                                    
                                    <span class="info-badge">
                                        <i class="fas fa-user-check me-2"></i>Asisten <?= ucfirst($asisten['status']) ?>
                                    </span>
                                    
                                    <?php if($asisten['nama_mk']): ?>
                                    <span class="info-badge">
                                        <i class="fas fa-book me-2"></i><?= $asisten['nama_mk'] ?>
                                    </span>
                                    <?php endif; ?>
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
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">Nama Lengkap</small>
                                                <strong class="fs-6"><?= $asisten['nama'] ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon">
                                                <i class="fas fa-id-badge"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">Kode Asisten</small>
                                                <strong class="fs-6"><?= $asisten['kode_asisten'] ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if($asisten['nama_mk']): ?>
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon">
                                                <i class="fas fa-book"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">Mata Kuliah</small>
                                                <strong class="fs-6"><?= $asisten['nama_mk'] ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon">
                                                <i class="fas fa-phone"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">Nomor HP</small>
                                                <strong class="fs-6"><?= $asisten['no_hp'] ?: 'Belum diatur' ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
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
                                                <i class="fas fa-calendar-alt"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">Status</small>
                                                <strong class="fs-6">
                                                    <?php if($asisten['status'] == 'aktif'): ?>
                                                        <span class="badge bg-success">Aktif</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Nonaktif</span>
                                                    <?php endif; ?>
                                                </strong>
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
                            
                            <!-- Statistik Persentase Kehadiran -->
                            <div class="profile-card">
                                <div class="card-body p-4">
                                    <h5 class="mb-4 fw-bold text-primary">
                                        <i class="fas fa-chart-pie me-2"></i>Statistik Kehadiran
                                    </h5>
                                    
                                    <div class="text-center mb-4">
                                        <div class="d-inline-block position-relative">
                                            <div class="position-relative" style="width: 150px; height: 150px;">
                                                <svg width="150" height="150" viewBox="0 0 150 150">
                                                    <!-- Background circle -->
                                                    <circle cx="75" cy="75" r="70" stroke="#eef1ff" stroke-width="10" fill="none"/>
                                                    <!-- Progress circle -->
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
                                             style="width: <?= $persentase_kehadiran ?>%"
                                             aria-valuenow="<?= $persentase_kehadiran ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100"></div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mt-3">
                                        <small class="text-muted">Total Hadir: <?= $total_kehadiran ?> dari <?= $total_jadwal ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-8">
                            <!-- Statistik Grid -->
                            <div class="profile-card mb-4">
                                <div class="card-body p-4">
                                    <h5 class="mb-4 fw-bold text-primary">
                                        <i class="fas fa-chart-bar me-2"></i>Statistik Detail
                                    </h5>
                                    
                                    <div class="stats-grid">
                                        <!-- Total Jadwal -->
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-primary">
                                                <i class="fas fa-calendar-alt"></i>
                                            </div>
                                            <div class="stat-value text-primary"><?= $total_jadwal ?></div>
                                            <div class="stat-label">Total Jadwal</div>
                                            <small class="text-muted">Semua Jenis</small>
                                        </div>
                                        
                                        <!-- Total Kehadiran -->
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-success">
                                                <i class="fas fa-user-check"></i>
                                            </div>
                                            <div class="stat-value text-success"><?= $total_kehadiran ?></div>
                                            <div class="stat-label">Kehadiran</div>
                                            <small class="text-muted">Hadir</small>
                                        </div>
                                        
                                        <!-- Total Materi -->
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-warning">
                                                <i class="fas fa-book"></i>
                                            </div>
                                            <div class="stat-value text-warning"><?= $total_materi ?></div>
                                            <div class="stat-label">Jadwal Materi</div>
                                            <small class="text-muted">Jenis: Materi</small>
                                        </div>
                                        
                                        <!-- Total Inhall -->
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-info">
                                                <i class="fas fa-exchange-alt"></i>
                                            </div>
                                            <div class="stat-value text-info"><?= $total_inhall ?></div>
                                            <div class="stat-label">Inhall</div>
                                            <small class="text-muted">Penggantian</small>
                                        </div>
                                        
                                        <!-- Total Ujikom -->
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-danger">
                                                <i class="fas fa-graduation-cap"></i>
                                            </div>
                                            <div class="stat-value text-danger"><?= $total_ujikom ?></div>
                                            <div class="stat-label">Ujian Kompetensi</div>
                                            <small class="text-muted">Ujikom</small>
                                        </div>
                                        
                                        <!-- Jadwal Bulan Ini -->
                                        <div class="stat-card">
                                            <div class="stat-icon" style="background: linear-gradient(135deg, #25d1d1 0%, #1ab3b3 100%);">
                                                <i class="fas fa-calendar-day"></i>
                                            </div>
                                            <div class="stat-value" style="color: #25d1d1;"><?= $total_jadwal_bulan_ini ?></div>
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
                                                <i class="fas fa-edit me-2"></i>Informasi Kontak
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="password-tab" data-bs-toggle="tab" 
                                                    data-bs-target="#password" type="button" role="tab">
                                                <i class="fas fa-lock me-2"></i>Keamanan Akun
                                            </button>
                                        </li>
                                    </ul>
                                    
                                    <div class="tab-content" id="profileTabContent">
                                        <div class="tab-pane fade show active" id="edit-profile" role="tabpanel">
                                            <form method="POST">
                                                <div class="row g-4">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">
                                                            <i class="fas fa-id-badge me-2 text-primary"></i>Kode Asisten
                                                        </label>
                                                        <div class="input-group">
                                                            <input type="text" class="form-control form-control-custom" 
                                                                   value="<?= $asisten['kode_asisten'] ?>" readonly style="background: #f8f9ff;">
                                                        </div>
                                                        <small class="text-muted">Kode asisten tidak dapat diubah</small>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">
                                                            <i class="fas fa-user me-2 text-primary"></i>Nama Lengkap
                                                        </label>
                                                        <div class="input-group">
                                                            <input type="text" class="form-control form-control-custom" 
                                                                   value="<?= $asisten['nama'] ?>" readonly style="background: #f8f9ff;">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">
                                                            <i class="fas fa-book me-2 text-primary"></i>Mata Kuliah
                                                        </label>
                                                        <div class="input-group">
                                                            <input type="text" class="form-control form-control-custom" 
                                                                   value="<?= $asisten['nama_mk'] ?: 'Belum ditugaskan' ?>" readonly style="background: #f8f9ff;">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">
                                                            <i class="fas fa-user-tag me-2 text-primary"></i>Username
                                                        </label>
                                                        <div class="input-group">
                                                            <input type="text" name="username" class="form-control form-control-custom" 
                                                                   value="<?= $user['username'] ?>" required>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold">
                                                            <i class="fas fa-mobile-alt me-2 text-primary"></i>Nomor Handphone
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <input type="tel" name="no_hp" class="form-control form-control-custom" 
                                                                   value="<?= $asisten['no_hp'] ?>" 
                                                                   placeholder="08xxxxxxxxxx" 
                                                                   pattern="[0-9]{10,13}"
                                                                   title="Masukkan nomor HP yang valid (10-13 digit)"
                                                                   required>
                                                        </div>
                                                        <div class="mt-2">
                                                            <small class="text-muted">
                                                                <i class="fas fa-info-circle me-1 text-primary"></i>
                                                                Digunakan untuk notifikasi dan koordinasi praktikum
                                                            </small>
                                                        </div>
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
                                                                <small class="strength-text fw-semibold text-danger">Lemah</small>
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
                        
                        <h5 class="mb-3 fw-semibold"><?= $asisten['nama'] ?></h5>
                        <p class="text-muted mb-4"><?= $asisten['kode_asisten'] ?></p>
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
                                Format: JPG, PNG, GIF, WEBP. Maksimal 2MB.
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
    
    // Preview gambar sebelum upload
    const inputFoto = document.getElementById('inputFoto');
    if (inputFoto) {
        inputFoto.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validasi ukuran file
                if (file.size > 2 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar! Maksimal 2MB.');
                    this.value = '';
                    return;
                }
                
                // Validasi tipe file
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung! Gunakan JPG, PNG, GIF, atau WEBP.');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(ev) {
                    const previewImg = document.getElementById('previewImg');
                    const placeholder = document.getElementById('previewPlaceholder');
                    
                    previewImg.src = ev.target.result;
                    previewImg.style.display = 'block';
                    
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Tambahkan efek hover untuk button upload foto di card
    const uploadFotoBtn = document.querySelector('.btn-upload-foto');
    if (uploadFotoBtn) {
        uploadFotoBtn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        
        uploadFotoBtn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
