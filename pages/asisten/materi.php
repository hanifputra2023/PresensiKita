<?php
$page = 'asisten_materi'; // For sidebar active state
$asisten = get_asisten_login();

// --- SECURITY CHECKS ---
if (!$asisten) {
    echo '<div class="alert alert-danger m-4">Akses ditolak. Anda harus login sebagai asisten.</div>';
    return;
}

$kode_asisten = $asisten['kode_asisten'];

if (!isset($_GET['jadwal']) || !is_numeric($_GET['jadwal'])) {
    echo '<div class="alert alert-danger m-4">Parameter jadwal tidak valid.</div>';
    return;
}
$jadwal_id = (int)$_GET['jadwal'];

// --- FETCH JADWAL & VERIFY OWNERSHIP ---
$jadwal_query = mysqli_query($conn, "SELECT j.*, k.nama_kelas, mk.nama_mk 
                                    FROM jadwal j 
                                    JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                    JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                    WHERE j.id = $jadwal_id 
                                    AND (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')");
$jadwal = mysqli_fetch_assoc($jadwal_query);

if (!$jadwal) {
    echo '<div class="alert alert-danger m-4">Jadwal tidak ditemukan atau Anda tidak memiliki akses ke jadwal ini.</div>';
    return;
}

// --- LOGIC: HANDLE DELETE ---
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id_materi_hapus = (int)$_GET['hapus'];
    $materi_hapus_q = mysqli_query($conn, "SELECT mp.* FROM materi_perkuliahan mp JOIN jadwal j ON mp.id_jadwal = j.id WHERE mp.id_materi = $id_materi_hapus AND j.id = $jadwal_id");
    if ($materi_hapus = mysqli_fetch_assoc($materi_hapus_q)) {
        if ($materi_hapus['path_file'] && file_exists($materi_hapus['path_file'])) {
            unlink($materi_hapus['path_file']);
        }
        mysqli_query($conn, "DELETE FROM materi_perkuliahan WHERE id_materi = $id_materi_hapus");
        header("Location: index.php?page=asisten_materi&jadwal=$jadwal_id&status=hapus_sukses");
        exit;
    } else {
        header("Location: index.php?page=asisten_materi&jadwal=$jadwal_id&status=hapus_gagal");
        exit;
    }
}

// --- LOGIC: HANDLE UPLOAD/SUBMIT ---
$upload_error = '';
$upload_sukses = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_materi'])) {
    $judul_materi = mysqli_real_escape_string($conn, $_POST['judul_materi']);
    $tipe_materi = $_POST['tipe_materi'] ?? 'file'; // 'file' or 'teks'
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $uploader_id = $_SESSION['user_id'];
    
    if (empty($judul_materi)) {
        $upload_error = 'Judul materi tidak boleh kosong.';
    } else {
        $nama_file = null;
        $path_file = null;

        if ($tipe_materi === 'file') {
            if (empty($_FILES['file_materi']['name'])) {
                $upload_error = 'File tidak boleh kosong untuk tipe materi file.';
            } else {
                $file = $_FILES['file_materi'];
                $file_name = $file['name'];
                $file_tmp = $file['tmp_name'];
                $file_size = $file['size'];
                $file_error = $file['error'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_ext = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar', 'txt', 'jpg', 'jpeg', 'png'];

                if (in_array($file_ext, $allowed_ext)) {
                    if ($file_error === 0) {
                        if ($file_size <= 10 * 1024 * 1024) { // Max 10MB
                            $new_file_name = 'materi_' . $jadwal_id . '_' . time() . '_' . uniqid() . '.' . $file_ext;
                            $upload_path = 'uploads/materi/' . $new_file_name;
                            if (move_uploaded_file($file_tmp, $upload_path)) {
                                $nama_file = mysqli_real_escape_string($conn, $file_name);
                                $path_file = mysqli_real_escape_string($conn, $upload_path);
                            } else {
                                $upload_error = 'Gagal memindahkan file yang diunggah.';
                            }
                        } else {
                            $upload_error = 'Ukuran file terlalu besar. Maksimal 10MB.';
                        }
                    } else {
                        $upload_error = 'Terjadi kesalahan saat mengunggah file.';
                    }
                } else {
                    $upload_error = 'Ekstensi file tidak diizinkan. Hanya ' . implode(', ', $allowed_ext) . '.';
                }
            }
        } elseif ($tipe_materi === 'teks') {
            if (empty($deskripsi)) {
                $upload_error = 'Materi Teks tidak boleh kosong.';
            }
        } else {
            $upload_error = 'Tipe materi tidak valid.';
        }

        if (empty($upload_error)) {
            $deskripsi_val = (!empty($deskripsi)) ? "'$deskripsi'" : "NULL";
            $nama_file_val = $nama_file ? "'$nama_file'" : "NULL";
            $path_file_val = $path_file ? "'$path_file'" : "NULL";

            $sql_insert = "INSERT INTO materi_perkuliahan (id_jadwal, judul_materi, deskripsi, nama_file, path_file, uploader_id) 
                           VALUES ($jadwal_id, '$judul_materi', $deskripsi_val, $nama_file_val, $path_file_val, $uploader_id)";
            
            if (mysqli_query($conn, $sql_insert)) {
                 header("Location: index.php?page=asisten_materi&jadwal=$jadwal_id&status=upload_sukses");
                 exit;
            } else {
                $upload_error = "Gagal menyimpan data ke database: " . mysqli_error($conn);
                if ($path_file && file_exists($path_file)) {
                    unlink($path_file);
                }
            }
        }
    }
}

// --- FETCH MATERI LIST ---
$materi_list_q = mysqli_query($conn, "SELECT mp.*, u.username as uploader_name FROM materi_perkuliahan mp JOIN users u ON mp.uploader_id = u.id WHERE mp.id_jadwal = $jadwal_id ORDER BY mp.tgl_upload DESC");

// Handle status messages from redirects
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'upload_sukses') $upload_sukses = 'Materi berhasil ditambahkan.';
    if ($_GET['status'] == 'hapus_sukses') $upload_sukses = 'Materi berhasil dihapus.';
    if ($_GET['status'] == 'hapus_gagal') $upload_error = 'Gagal menghapus materi.';
}

?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                

                <div class="card bg-light border-primary mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-book-open me-2"></i>Kelola Materi Perkuliahan</h5>
                        <p class="card-text mb-1">
                            <strong>Mata Kuliah:</strong> <?= htmlspecialchars($jadwal['nama_mk']) ?> (Pertemuan ke-<?= $jadwal['pertemuan_ke'] ?>)
                        </p>
                        <p class="card-text">
                            <strong>Tanggal:</strong> <?= format_tanggal($jadwal['tanggal']) ?> | <strong>Kelas:</strong> <?= htmlspecialchars($jadwal['nama_kelas']) ?>
                        </p>
                    </div>
                </div>

                <?php if ($upload_error): ?>
                    <div class="alert alert-danger"><?= $upload_error ?></div>
                <?php endif; ?>
                <?php if ($upload_sukses): ?>
                    <div class="alert alert-success"><?= $upload_sukses ?></div>
                <?php endif; ?>

                <!-- Upload Form Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-plus me-2"></i>Tambah Materi Baru</h6>
                    </div>
                    <div class="card-body">
                        <form action="index.php?page=asisten_materi&jadwal=<?= $jadwal_id ?>" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="upload_materi" value="1">
                            <div class="mb-3">
                                <label for="judul_materi" class="form-label">Judul Materi</label>
                                <input type="text" class="form-control" id="judul_materi" name="judul_materi" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Tipe Materi</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipe_materi" id="tipe_file" value="file" checked>
                                    <label class="form-check-label" for="tipe_file">Upload File</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipe_materi" id="tipe_teks" value="teks">
                                    <label class="form-check-label" for="tipe_teks">Materi Teks</label>
                                </div>
                            </div>

                            <div id="input_file" class="mb-3">
                                <label for="file_materi" class="form-label">Pilih File</label>
                                <input class="form-control" type="file" id="file_materi" name="file_materi">
                                <div class="form-text">Maks. 10MB. Format: pdf, doc(x), ppt(x), xls(x), zip, rar, txt, gambar.</div>
                            </div>

                            <div id="input_teks" class="mb-3" style="display: none;">
                                <label for="deskripsi" class="form-label">Isi Materi Teks</label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Tambah</button>
                        </form>
                    </div>
                </div>

                <!-- Materi List Card -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Materi</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Judul</th>
                                        <th>Materi</th>
                                        <th>Tgl Upload</th>
                                        <th>Pengunggah</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($materi_list_q) > 0): ?>
                                        <?php $no = 1; while($m = mysqli_fetch_assoc($materi_list_q)): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= htmlspecialchars($m['judul_materi']) ?></td>
                                                <td>
                                                    <?php if (!empty($m['path_file'])): ?>
                                                        <a href="<?= $m['path_file'] ?>" target="_blank" title="Download <?= htmlspecialchars($m['nama_file']) ?>">
                                                            <i class="fas fa-file-download me-2"></i><?= htmlspecialchars($m['nama_file']) ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <div style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($m['deskripsi'])) ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= format_tanggal_waktu($m['tgl_upload']) ?></td>
                                                <td><span class="badge bg-secondary"><?= htmlspecialchars($m['uploader_name']) ?></span></td>
                                                <td>
                                                    <?php if (!empty($m['path_file'])): ?>
                                                    <a href="<?= $m['path_file'] ?>" class="btn btn-sm btn-success" download title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <a href="index.php?page=asisten_materi&jadwal=<?= $jadwal_id ?>&hapus=<?= $m['id_materi'] ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Apakah Anda yakin ingin menghapus materi ini?')" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Belum ada materi yang ditambahkan.</td>
                                        </tr>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radioFile = document.getElementById('tipe_file');
    const radioTeks = document.getElementById('tipe_teks');
    const inputFileDiv = document.getElementById('input_file');
    const inputTeksDiv = document.getElementById('input_teks');
    const fileInput = document.getElementById('file_materi');
    const deskripsiTextarea = document.getElementById('deskripsi');

    function toggleMateriInput() {
        if (radioFile.checked) {
            inputFileDiv.style.display = 'block';
            inputTeksDiv.style.display = 'none';
            fileInput.required = true;
            deskripsiTextarea.required = false;
        } else {
            inputFileDiv.style.display = 'none';
            inputTeksDiv.style.display = 'block';
            fileInput.required = false;
            deskripsiTextarea.required = true;
        }
    }

    radioFile.addEventListener('change', toggleMateriInput);
    radioTeks.addEventListener('change', toggleMateriInput);

    // Initial call to set the correct state on page load
    toggleMateriInput();
});
</script>

<?php include 'includes/footer.php'; ?>
