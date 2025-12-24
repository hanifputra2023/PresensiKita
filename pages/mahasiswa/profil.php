<?php
require_once 'includes/header.php'; 

cek_login();
cek_role(['mahasiswa']);
$user_id = $_SESSION['user_id'];

$query = "SELECT m.*, k.nama_kelas, u.username, u.password as current_hash 
          FROM mahasiswa m 
          JOIN users u ON m.user_id = u.id 
          LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas 
          WHERE u.id = '$user_id'";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo "<script>alert('Data mahasiswa tidak ditemukan!'); window.location='logout.php';</script>";
    exit;
}

$nama_lengkap = $data['nama'];
$nim = $data['nim'];
$kelas = $data['nama_kelas'] ?? $data['kode_kelas'];
$prodi = $data['prodi'];
$no_hp = $data['no_hp'];
$tanggal_daftar = $data['tanggal_daftar'];
$kode_kelas = $data['kode_kelas'];

$query_presensi = "SELECT 
    COUNT(j.id) as total_jadwal,
    SUM(CASE WHEN pm.status = 'hadir' THEN 1 ELSE 0 END) as total_hadir,
    SUM(CASE WHEN pm.status = 'izin' THEN 1 ELSE 0 END) as total_izin,
    SUM(CASE WHEN pm.status = 'sakit' THEN 1 ELSE 0 END) as total_sakit,
    SUM(CASE 
        WHEN pm.status = 'alpha' THEN 1
        WHEN (pm.status IS NULL OR pm.status = 'belum') AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() THEN 1 
        ELSE 0 
    END) as total_alpha,
    SUM(CASE 
        WHEN (pm.status IS NULL OR pm.status = 'belum') AND CONCAT(j.tanggal, ' ', j.jam_selesai) >= NOW() THEN 1 
        ELSE 0 
    END) as total_belum,
    SUM(CASE 
        WHEN CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() OR pm.status IN ('hadir', 'izin', 'sakit', 'alpha') THEN 1 
        ELSE 0 
    END) as total_completed
    FROM jadwal j
    LEFT JOIN presensi_mahasiswa pm ON j.id = pm.jadwal_id AND pm.nim = '$nim'
    JOIN mahasiswa m ON m.nim = '$nim'
    WHERE j.kode_kelas = '$kode_kelas'
    AND j.jenis != 'inhall'
    AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai)";
$stat_result = mysqli_query($conn, $query_presensi);
$stat_data = mysqli_fetch_assoc($stat_result);

$total_completed = $stat_data['total_completed'] ?? 0;
$persentase_kehadiran = 0;
if ($total_completed > 0) {
    $persentase_kehadiran = round(($stat_data['total_hadir'] / $total_completed) * 100);
}

$foto_profil = $data['foto'] ?? null;

if (isset($_POST['upload_foto'])) {
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024;
        
        $file_type = $_FILES['foto']['type'];
        $file_size = $_FILES['foto']['size'];
        $file_tmp = $_FILES['foto']['tmp_name'];
        
        if (!in_array($file_type, $allowed_types)) {
            set_alert('danger', 'Format file tidak didukung! Gunakan JPG, PNG, GIF, atau WEBP.');
        } elseif ($file_size > $max_size) {
            set_alert('danger', 'Ukuran file terlalu besar! Maksimal 2MB.');
        } else {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $new_filename = 'mhs_' . $nim . '_' . time() . '.' . $ext;
            
            // Pastikan folder uploads/profil ada
            $upload_dir = 'uploads/profil/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $upload_path = $upload_dir . $new_filename;
            
            if ($foto_profil && file_exists($foto_profil)) {
                unlink($foto_profil);
            }
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $update = mysqli_query($conn, "UPDATE mahasiswa SET foto = '$upload_path' WHERE nim = '$nim'");
                if ($update) {
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

if (isset($_POST['hapus_foto'])) {
    if ($foto_profil && file_exists($foto_profil)) {
        unlink($foto_profil);
    }
    mysqli_query($conn, "UPDATE mahasiswa SET foto = NULL WHERE nim = '$nim'");
    set_alert('success', 'Foto profil berhasil dihapus!');
    echo "<meta http-equiv='refresh' content='1'>";
}

$data = mysqli_fetch_assoc(mysqli_query($conn, $query));
$foto_profil = $data['foto'] ?? null;

if (isset($_POST['update_profil'])) {
    $new_hp = htmlspecialchars($_POST['no_hp']);
    
    if (!is_numeric($new_hp)) {
        set_alert('danger', 'Nomor HP harus berupa angka!');
    } else {
        $update = mysqli_query($conn, "UPDATE mahasiswa SET no_hp = '$new_hp' WHERE nim = '$nim'");
        if ($update) {
            set_alert('success', 'Informasi kontak berhasil diperbarui!');
            echo "<meta http-equiv='refresh' content='1'>";
        } else {
            set_alert('danger', 'Gagal memperbarui profil: ' . mysqli_error($conn));
        }
    }
}

if (isset($_POST['ganti_password'])) {
    $pass_lama = $_POST['password_lama'];
    $pass_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi_password'];
    $password_db = $data['current_hash'];

    if ($pass_lama !== $password_db) {
        set_alert('danger', 'Password lama salah!');
    } else {
        if ($pass_baru !== $konfirmasi) {
            set_alert('danger', 'Konfirmasi password baru tidak cocok!');
        } elseif (strlen($pass_baru) < 6) {
            set_alert('danger', 'Password minimal 6 karakter!');
        } else {
            $update_pass = mysqli_query($conn, "UPDATE users SET password = '$pass_baru' WHERE id = '$user_id'");
            if ($update_pass) {
                set_alert('success', 'Password berhasil diubah! Silakan login ulang nanti.');
            } else {
                set_alert('danger', 'Gagal mengubah password.');
            }
        }
    }
}

function get_inisial($nama) {
    $words = explode(' ', trim($nama));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
    }
    return strtoupper(substr($nama, 0, 2));
}

function get_avatar_color($nim) {
    $colors = [
        'linear-gradient(135deg, #2550d1 0%, #1a3db3 100%)',
        'linear-gradient(135deg, #d12525 0%, #b31a1a 100%)',
        'linear-gradient(135deg, #25d165 0%, #1ab34b 100%)',
        'linear-gradient(135deg, #d1b325 0%, #b39b1a 100%)',
        'linear-gradient(135deg, #8f25d1 0%, #761ab3 100%)'
    ];
    $hash = crc32($nim);
    return $colors[abs($hash) % count($colors)];
}

$inisial = get_inisial($nama_lengkap);
$avatar_color = get_avatar_color($nim);
?>

<style>
    /* Main Container */
    .profile-container {
        background: linear-gradient(135deg, #f0f7ff 0%, #e6f2ff 100%);
        min-height: 100vh;
    }
    
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
        border-color: #2550d1;
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
        background: linear-gradient(135deg, #2550d1 0%, #1a3db3 100%);
        color: white;
    }
    
    .form-control-custom {
        border: 2px solid #eef1ff;
        border-radius: 10px;
        padding: 14px 18px;
        font-size: 15px;
        transition: all 0.3s ease;
        background: #ffffff;
    }
    
    .form-control-custom:focus {
        border-color: #2550d1;
        box-shadow: 0 0 0 3px rgba(37, 80, 209, 0.1);
    }
    
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
        color: #2550d1;
        background: #eef1ff;
    }
    
    .nav-tabs-custom .nav-link.active {
        color: #2550d1;
        background: white;
        border-bottom: 3px solid #2550d1;
    }
    
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
        border-color: #2550d1;
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
        background: linear-gradient(135deg, #66cc00 0%, #55aa00 100%);
    }
    
    .stat-icon-warning {
        background: linear-gradient(135deg, #ffaa00 0%, #ff8800 100%);
    }
    
    .stat-icon-info {
        background: linear-gradient(135deg, #00ccff 0%, #0099cc 100%);
    }
    
    .stat-icon-danger {
        background: linear-gradient(135deg, #ff3333 0%, #cc0000 100%);
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
    
    .modal-foto-custom .modal-body {
        padding: 30px;
        background: #f8f9ff;
    }
    
    .modal-foto-custom .modal-footer {
        background: white;
        border-top: 1px solid #eef1ff;
        padding: 20px 30px;
    }
    
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
        box-shadow: 0 5px 15px rgba(0, 102, 204, 0.1);
    }
    
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
        cursor: pointer;
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
        cursor: pointer;
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
        cursor: pointer;
    }
    
    /* Tablet (max-width: 991px) */
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
    
    /* Mobile (max-width: 767px) */
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
    
    /* Extra Small (max-width: 480px) */
    @media (max-width: 480px) {
        .profile-header {
            padding: 20px 8px;
        }
        
        .avatar-container {
            width: 80px;
            height: 80px;
            font-size: 28px;
        }
        
        .profile-header h1.display-6 {
            font-size: 1.1rem;
        }
        
        .info-badge {
            padding: 3px 8px;
            font-size: 0.7rem;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .stat-card {
            padding: 12px;
        }
        
        .stat-value {
            font-size: 1.1rem;
        }
        
        .nav-tabs-custom {
            gap: 4px;
        }
        
        .nav-tabs-custom .nav-link {
            padding: 10px 6px;
            font-size: 0.75rem;
        }
        
        .profile-card .card-body {
            padding: 12px !important;
        }
        
        .info-item {
            padding: 10px;
        }
        
        .info-icon {
            width: 36px;
            height: 36px;
            font-size: 14px;
            margin-right: 8px;
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
                                <h1 class="display-6 mb-2 fw-bold text-center text-md-start text-white"><?= $nama_lengkap ?></h1>
                                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                                    <span class="info-badge">
                                        <i class="fas fa-id-card me-2"></i>NIM: <?= $nim ?>
                                    </span>
                                    <span class="info-badge">
                                        <i class="fas fa-user-check me-2"></i>Mahasiswa Aktif
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
                                            <div class="info-icon"><i class="fas fa-user"></i></div>
                                            <div>
                                                <small class="text-muted d-block">Nama Lengkap</small>
                                                <strong class="fs-6"><?= $nama_lengkap ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon"><i class="fas fa-id-badge"></i></div>
                                            <div>
                                                <small class="text-muted d-block">NIM</small>
                                                <strong class="fs-6"><?= $nim ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon"><i class="fas fa-users"></i></div>
                                            <div>
                                                <small class="text-muted d-block">Kelas</small>
                                                <strong class="fs-6"><?= $kelas ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon"><i class="fas fa-graduation-cap"></i></div>
                                            <div>
                                                <small class="text-muted d-block">Program Studi</small>
                                                <strong class="fs-6"><?= $prodi ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon"><i class="fas fa-phone"></i></div>
                                            <div>
                                                <small class="text-muted d-block">Nomor HP</small>
                                                <strong class="fs-6"><?= $no_hp ?: 'Belum diatur' ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="d-flex align-items-center">
                                            <div class="info-icon"><i class="fas fa-calendar-alt"></i></div>
                                            <div>
                                                <small class="text-muted d-block">Terdaftar Sejak</small>
                                                <strong class="fs-6"><?= date('d F Y', strtotime($tanggal_daftar)) ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 pt-3 border-top">
                                        <button type="button" class="btn-upload-foto" data-bs-toggle="modal" data-bs-target="#modalFoto">
                                            <i class="fas fa-camera"></i>
                                            <?= $foto_profil ? 'Ganti Foto Profil' : 'Upload Foto Profil' ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="profile-card">
                                <div class="card-body p-4">
                                    <h5 class="mb-4 fw-bold text-primary">
                                        <i class="fas fa-chart-pie me-2"></i>Statistik Kehadiran
                                    </h5>
                                    
                                    <div class="text-center mb-4">
                                        <div class="d-inline-block position-relative">
                                            <div class="position-relative" style="width: 150px; height: 150px;">
                                                <svg width="150" height="150" viewBox="0 0 150 150">
                                                    <circle cx="75" cy="75" r="70" stroke="#eef1ff" stroke-width="10" fill="none"/>
                                                    <circle cx="75" cy="75" r="70" stroke="#2550d1" stroke-width="10" fill="none" stroke-linecap="round" stroke-dasharray="440" stroke-dashoffset="<?= 440 - (440 * $persentase_kehadiran / 100) ?>" transform="rotate(-90 75 75)"/>
                                                </svg>
                                                <div class="position-absolute top-50 start-50 translate-middle text-center">
                                                    <h2 class="fw-bold mb-0 text-primary"><?= $persentase_kehadiran ?>%</h2>
                                                    <small class="text-muted">Kehadiran</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="progress-custom">
                                        <div class="progress-bar-custom bg-primary" role="progressbar" style="width: <?= $persentase_kehadiran ?>%"></div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mt-3">
                                        <small class="text-muted">Hadir: <?= $stat_data['total_hadir'] ?? 0 ?>/<?= $total_completed ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-8">
                            <div class="profile-card mb-4">
                                <div class="card-body p-4">
                                    <h5 class="mb-4 fw-bold text-primary">
                                        <i class="fas fa-chart-bar me-2"></i>Statistik Detail
                                    </h5>
                                    
                                    <div class="stats-grid">
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-primary"><i class="fas fa-calendar-alt"></i></div>
                                            <div class="stat-value text-primary"><?= $stat_data['total_jadwal'] ?></div>
                                            <div class="stat-label">Total Jadwal</div>
                                        </div>
                                        
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-success"><i class="fas fa-user-check"></i></div>
                                            <div class="stat-value text-success"><?= $stat_data['total_hadir'] ?? 0 ?></div>
                                            <div class="stat-label">Hadir</div>
                                        </div>
                                        
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-warning"><i class="fas fa-file-alt"></i></div>
                                            <div class="stat-value text-warning"><?= $stat_data['total_izin'] ?? 0 ?></div>
                                            <div class="stat-label">Izin</div>
                                        </div>
                                        
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-info"><i class="fas fa-hospital-user"></i></div>
                                            <div class="stat-value text-info"><?= $stat_data['total_sakit'] ?? 0 ?></div>
                                            <div class="stat-label">Sakit</div>
                                        </div>
                                        
                                        <div class="stat-card">
                                            <div class="stat-icon stat-icon-danger"><i class="fas fa-times-circle"></i></div>
                                            <div class="stat-value text-danger"><?= $stat_data['total_alpha'] ?? 0 ?></div>
                                            <div class="stat-label">Alpha</div>
                                        </div>
                                        
                                        <div class="stat-card">
                                            <div class="stat-icon" style="background: linear-gradient(135deg, #b3b3b3 0%, #8d8d8d 100%);"><i class="fas fa-clock"></i></div>
                                            <div class="stat-value" style="color: #8d8d8d;"><?= $stat_data['total_belum'] ?? 0 ?></div>
                                            <div class="stat-label">Belum Hadir</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="profile-card">
                                <div class="card-body p-4">
                                    <ul class="nav nav-tabs nav-tabs-custom mb-4" id="profileTab" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="edit-profile-tab" data-bs-toggle="tab" data-bs-target="#edit-profile" type="button" role="tab">
                                                <i class="fas fa-edit me-2"></i>Informasi Kontak
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab">
                                                <i class="fas fa-lock me-2"></i>Keamanan Akun
                                            </button>
                                        </li>
                                    </ul>
                                    
                                    <div class="tab-content" id="profileTabContent">
                                        <div class="tab-pane fade show active" id="edit-profile" role="tabpanel">
                                            <form method="POST">
                                                <div class="row g-4">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold"><i class="fas fa-id-badge me-2 text-primary"></i>NIM</label>
                                                        <input type="text" class="form-control form-control-custom" value="<?= $nim ?>" readonly style="background: #f8f9ff;">
                                                        <small class="text-muted">NIM tidak dapat diubah</small>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold"><i class="fas fa-user me-2 text-primary"></i>Nama Lengkap</label>
                                                        <input type="text" class="form-control form-control-custom" value="<?= $nama_lengkap ?>" readonly style="background: #f8f9ff;">
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold"><i class="fas fa-users me-2 text-primary"></i>Kelas</label>
                                                        <input type="text" class="form-control form-control-custom" value="<?= $kelas ?>" readonly style="background: #f8f9ff;">
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold"><i class="fas fa-graduation-cap me-2 text-primary"></i>Program Studi</label>
                                                        <input type="text" class="form-control form-control-custom" value="<?= $prodi ?>" readonly style="background: #f8f9ff;">
                                                    </div>
                                                    
                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold"><i class="fas fa-mobile-alt me-2 text-primary"></i>Nomor Handphone <span class="text-danger">*</span></label>
                                                        <input type="tel" name="no_hp" class="form-control form-control-custom" value="<?= $no_hp ?>" placeholder="08xxxxxxxxxx" pattern="[0-9]{10,13}" required>
                                                        <small class="text-muted"><i class="fas fa-info-circle me-1 text-primary"></i>Digunakan untuk notifikasi penting</small>
                                                    </div>
                                                    
                                                    <div class="col-12">
                                                        <div class="d-flex justify-content-end gap-3 mt-4 pt-4 border-top">
                                                            <a href="index.php" class="btn btn-outline-secondary px-4 py-2">Batal</a>
                                                            <button type="submit" name="update_profil" class="btn-gradient px-4 py-2">Simpan</button>
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
                                                        <label class="form-label fw-semibold"><i class="fas fa-lock me-2 text-primary"></i>Password Lama <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <input type="password" name="password_lama" class="form-control form-control-custom password-input" placeholder="Masukkan password saat ini" required>
                                                            <button class="btn btn-outline-primary toggle-password" type="button"><i class="fas fa-eye"></i></button>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold"><i class="fas fa-key me-2 text-primary"></i>Password Baru <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <input type="password" name="password_baru" class="form-control form-control-custom password-input" placeholder="Minimal 6 karakter" minlength="6" required>
                                                            <button class="btn btn-outline-primary toggle-password" type="button"><i class="fas fa-eye"></i></button>
                                                        </div>
                                                        <div class="password-strength mt-3">
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <small class="text-muted">Kekuatan password</small>
                                                                <small class="strength-text fw-semibold text-danger">Lemah</small>
                                                            </div>
                                                            <div class="progress progress-custom">
                                                                <div class="progress-bar progress-bar-custom bg-danger" role="progressbar" style="width: 0%"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold"><i class="fas fa-check-double me-2 text-primary"></i>Konfirmasi Password <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <input type="password" name="konfirmasi_password" class="form-control form-control-custom password-input" placeholder="Ulangi password baru" required>
                                                            <button class="btn btn-outline-primary toggle-password" type="button"><i class="fas fa-eye"></i></button>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-12">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="showPasswords">
                                                            <label class="form-check-label" for="showPasswords"><i class="fas fa-eye me-1 text-primary"></i>Tampilkan password</label>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-12">
                                                        <div class="d-flex justify-content-end gap-3 mt-4 pt-4 border-top">
                                                            <a href="index.php" class="btn btn-outline-secondary px-4 py-2">Batal</a>
                                                            <button type="submit" name="ganti_password" class="btn-gradient px-4 py-2">Perbarui Password</button>
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
                        
                        <h5 class="mb-3 fw-semibold"><?= $nama_lengkap ?></h5>
                        <p class="text-muted mb-4"><?= $nim ?></p>
                    </div>
                    
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
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Format: JPG, PNG, GIF, WEBP. Maksimal 2MB.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div>
                        <?php if($foto_profil): ?>
                            <button type="button" class="btn-modal-danger" onclick="if(confirm('Yakin ingin menghapus foto?')) document.getElementById('formHapusFoto').submit();">
                                Hapus
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-3">
                        <button type="button" class="btn-modal-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="upload_foto" class="btn-modal-primary">Upload</button>
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
            inputs.forEach(input => input.setAttribute('type', type));
            
            document.querySelectorAll('.toggle-password i').forEach(icon => {
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
            
            let width = 33, color = '#dc3545', text = 'Lemah';
            if (strength > 4) {
                width = 100;
                color = '#28a745';
                text = 'Kuat';
            } else if (strength > 2) {
                width = 66;
                color = '#ffc107';
                text = 'Sedang';
            }
            
            progressBar.style.width = width + '%';
            progressBar.style.backgroundColor = color;
            strengthText.textContent = text;
            strengthText.style.color = color;
        });
    }
});

document.getElementById('inputFoto')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            const previewImg = document.getElementById('previewImg');
            const placeholder = document.getElementById('previewPlaceholder');
            previewImg.src = ev.target.result;
            previewImg.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
