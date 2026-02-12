<?php
include '../config/koneksi.php';
include '../includes/fungsi.php';

if (!isset($_GET['jadwal_id'])) {
    http_response_code(400);
    echo '<div class="alert alert-danger">Parameter tidak valid.</div>';
    exit;
}

$jadwal_id = mysqli_real_escape_string($conn, $_GET['jadwal_id']);

// Ambil detail jadwal untuk judul
$jadwal_query = mysqli_query($conn, "SELECT mk.nama_mk, j.pertemuan_ke, j.materi as topik_utama
                                     FROM jadwal j 
                                     JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                     WHERE j.id = '$jadwal_id'");

if (!$jadwal_query) {
    // Optionally handle this error, e.g., log to a file without exposing to user
}
$jadwal_detail = mysqli_fetch_assoc($jadwal_query);

// Ambil materi dari database
$materi_query = mysqli_query($conn, "SELECT * FROM materi_perkuliahan WHERE id_jadwal = '$jadwal_id'");

if (!$materi_query) {
    echo '<div class="alert alert-danger">Terjadi kesalahan saat mengambil data materi.</div>';
    exit;
}

if ($jadwal_detail) {
    echo '<h5 class="mb-3">Materi Pertemuan ' . $jadwal_detail['pertemuan_ke'] . ': ' . htmlspecialchars($jadwal_detail['nama_mk']) . '</h5>';
    echo '<p class="text-muted">Topik: ' . htmlspecialchars($jadwal_detail['topik_utama']) . '</p><hr>';
}

if (mysqli_num_rows($materi_query) > 0) {
    while ($materi = mysqli_fetch_assoc($materi_query)) {
        echo '<div class="card mb-3">';
        echo '  <div class="card-body">';
        
        if (!empty($materi['judul_materi'])) {
            echo '<h6 class="card-title">' . htmlspecialchars($materi['judul_materi']) . '</h6>';
        }

        if (!empty($materi['deskripsi'])) {
            echo '<p class="card-text">' . nl2br(htmlspecialchars($materi['deskripsi'])) . '</p>';
        }

        if (!empty($materi['nama_file'])) {
            $file_path = 'uploads/materi/' . $materi['nama_file'];
            if (file_exists('../' . $file_path)) {
                $file_info = pathinfo($materi['nama_file']);
                $icon = get_file_icon($file_info['extension']);
                
                echo '<div class="mt-3">';
                echo '  <p class="mb-2"><strong>Lampiran:</strong></p>';
                echo '  <a href="' . $file_path . '" download class="btn btn-outline-primary btn-sm">';
                echo '      <i class="' . $icon . ' me-2"></i>' . htmlspecialchars($materi['nama_file']);
                echo '  </a>';
                echo '</div>';
            } else {
                // Optionally display a message that the file is missing
                echo '<div class="alert alert-warning mt-3">File materi tidak ditemukan: ' . htmlspecialchars($materi['nama_file']) . '</div>';
            }
        }
        
        echo '  </div>';
        echo '</div>';
    }
} else {
    echo '<div class="alert alert-info">Belum ada materi yang ditambahkan untuk pertemuan ini.</div>';
}

function get_file_icon($extension) {
    $extension = strtolower($extension);
    switch ($extension) {
        case 'pdf':
            return 'fas fa-file-pdf text-danger';
        case 'doc':
        case 'docx':
            return 'fas fa-file-word text-primary';
        case 'xls':
        case 'xlsx':
            return 'fas fa-file-excel text-success';
        case 'ppt':
        case 'pptx':
            return 'fas fa-file-powerpoint text-warning';
        case 'zip':
        case 'rar':
            return 'fas fa-file-archive text-secondary';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return 'fas fa-file-image text-info';
        default:
            return 'fas fa-file-alt';
    }
}
?>