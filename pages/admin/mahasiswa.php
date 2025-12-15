<?php
$page = 'admin_mahasiswa';

// ... (PHP code for export, import, CRUD remains the same) ...
// Download template
if (isset($_GET['download_template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=template_mahasiswa.csv');
    
    // BOM untuk Excel agar UTF-8 terbaca dengan benar
    echo chr(0xEF) . chr(0xBB) . chr(0xBF);
    
    // Header dengan semicolon sebagai delimiter (lebih kompatibel dengan Excel Indonesia)
    echo "NIM;Nama;Kode Kelas;Program Studi;No HP;Password\n";
    // Contoh data
    echo "12345678;Budi Santoso;TI-1A;Teknik Informatika;081234567890;123456\n";
    echo "12345679;Ani Wijaya;TI-1A;Teknik Informatika;081234567891;123456\n";
    exit;
}

// Export data mahasiswa
if (isset($_GET['export'])) {
    $filter_kelas_exp = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';
    $where_exp = $filter_kelas_exp ? "WHERE m.kode_kelas = '$filter_kelas_exp'" : '';
    
    $data_export = mysqli_query($conn, "SELECT m.nim, m.nama, m.kode_kelas, k.nama_kelas, m.prodi, m.no_hp 
                                         FROM mahasiswa m 
                                         LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas 
                                         $where_exp ORDER BY m.nim");
    
    $filename = 'data_mahasiswa_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    // BOM untuk Excel
    echo chr(0xEF) . chr(0xBB) . chr(0xBF);
    
    // Header
    echo "No;NIM;Nama;Kode Kelas;Nama Kelas;Program Studi;No HP\n";
    
    // Data
    $no = 1;
    while ($row = mysqli_fetch_assoc($data_export)) {
        echo $no . ";" . 
             $row['nim'] . ";" . 
             $row['nama'] . ";" . 
             $row['kode_kelas'] . ";" . 
             $row['nama_kelas'] . ";" . 
             $row['prodi'] . ";" . 
             $row['no_hp'] . "\n";
        $no++;
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = $_POST['aksi'];
    
    // Import dari CSV/Excel
    if ($aksi == 'import') {
        if (isset($_FILES['file_import']) && $_FILES['file_import']['error'] == 0) {
            $file = $_FILES['file_import']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['file_import']['name'], PATHINFO_EXTENSION));
            
            $success = 0;
            $gagal = 0;
            $errors = [];
            $data_rows = [];
            
            if ($ext == 'csv') {
                if (($handle = fopen($file, 'r')) !== FALSE) {
                    $first_line = fgets($handle);
                    rewind($handle);
                    $delimiter = (strpos($first_line, ';') !== false) ? ';' : ',';
                    $header = fgetcsv($handle, 0, $delimiter);
                    while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                        $data_rows[] = $data;
                    }
                    fclose($handle);
                }
            } else {
                set_alert('danger', 'Format file tidak didukung! Gunakan file CSV.');
                header("Location: index.php?page=admin_mahasiswa");
                exit;
            }
            
            $tanggal_daftar = (isset($_POST['tanggal_daftar']) && !empty($_POST['tanggal_daftar'])) ? escape($_POST['tanggal_daftar']) : date('Y-m-d');
            $waktu_daftar = (isset($_POST['waktu_daftar']) && !empty($_POST['waktu_daftar'])) ? escape($_POST['waktu_daftar']) . ':00' : date('H:i:s');
            $tanggal_daftar = $tanggal_daftar . ' ' . $waktu_daftar;
            
            $row_num = 1;
            foreach ($data_rows as $data) {
                $row_num++;
                if (count($data) >= 2) {
                    $nim = escape(trim(html_entity_decode(strip_tags($data[0]))));
                    $nama = escape(trim(html_entity_decode(strip_tags($data[1]))));
                    $kelas = isset($data[2]) ? escape(trim(html_entity_decode(strip_tags($data[2])))) : '';
                    $prodi = isset($data[3]) ? escape(trim(html_entity_decode(strip_tags($data[3])))) : '';
                    $hp = isset($data[4]) ? escape(trim(html_entity_decode(strip_tags($data[4])))) : '';
                    $password = isset($data[5]) && !empty(trim($data[5])) ? trim($data[5]) : '123456';
                    
                    if (empty($nim) && empty($nama)) continue;
                    if (!preg_match('/^[a-zA-Z0-9]+$/', $nim)) { $gagal++; $errors[] = "Baris $row_num: NIM tidak valid."; continue; }
                    if (empty($nim) || empty($nama)) { $gagal++; $errors[] = "Baris $row_num: NIM atau Nama kosong."; continue; }
                    
                    $cek = mysqli_query($conn, "SELECT * FROM mahasiswa WHERE nim = '$nim'");
                    if (mysqli_num_rows($cek) > 0) { $gagal++; $errors[] = "Baris $row_num: NIM $nim sudah terdaftar."; continue; }
                    
                    mysqli_query($conn, "INSERT INTO users (username, password, role) VALUES ('$nim', '$password', 'mahasiswa')");
                    $user_id = mysqli_insert_id($conn);
                    
                    $q = mysqli_query($conn, "INSERT INTO mahasiswa (nim, user_id, nama, kode_kelas, prodi, no_hp, tanggal_daftar) VALUES ('$nim', '$user_id', '$nama', '$kelas', '$prodi', '$hp', '$tanggal_daftar')");
                    if ($q) { $success++; } else { $gagal++; $errors[] = "Baris $row_num: Gagal insert database."; }
                }
            }
            
            $msg = "Import selesai! Berhasil: $success, Gagal: $gagal";
            if (count($errors) > 0) $msg .= "<br><small>" . implode("<br>", array_slice($errors, 0, 5)) . (count($errors) > 5 ? "<br>...dan " . (count($errors) - 5) . " error lainnya" : "") . "</small>";
            set_alert($gagal > 0 ? 'warning' : 'success', $msg);
            
        } else {
            set_alert('danger', 'Gagal upload file!');
        }
        
        header("Location: index.php?page=admin_mahasiswa");
        exit;
    }
    
    if ($aksi == 'tambah') {
        $nim = escape($_POST['nim']);
        $nama = escape($_POST['nama']);
        $kelas = escape($_POST['kode_kelas']);
        $prodi = escape($_POST['prodi']);
        $hp = escape($_POST['no_hp']);
        $password = $_POST['password'];
        $tanggal_daftar = (isset($_POST['tanggal_daftar']) && !empty($_POST['tanggal_daftar'])) ? escape($_POST['tanggal_daftar']) : date('Y-m-d');
        $waktu_daftar = (isset($_POST['waktu_daftar']) && !empty($_POST['waktu_daftar'])) ? escape($_POST['waktu_daftar']) . ':00' : date('H:i:s');
        $tanggal_daftar = $tanggal_daftar . ' ' . $waktu_daftar;
        
        $cek = mysqli_query($conn, "SELECT * FROM mahasiswa WHERE nim = '$nim'");
        if (mysqli_num_rows($cek) > 0) {
            set_alert('danger', 'NIM sudah terdaftar!');
        } else {
            mysqli_query($conn, "INSERT INTO users (username, password, role) VALUES ('$nim', '$password', 'mahasiswa')");
            $user_id = mysqli_insert_id($conn);
            mysqli_query($conn, "INSERT INTO mahasiswa (nim, user_id, nama, kode_kelas, prodi, no_hp, tanggal_daftar) VALUES ('$nim', '$user_id', '$nama', '$kelas', '$prodi', '$hp', '$tanggal_daftar')");
            set_alert('success', 'Mahasiswa berhasil ditambahkan!');
        }
    } elseif ($aksi == 'edit') {
        $id = (int)$_POST['id'];
        $nama = escape($_POST['nama']);
        $kelas = escape($_POST['kode_kelas']);
        $prodi = escape($_POST['prodi']);
        $hp = escape($_POST['no_hp']);
        
        mysqli_query($conn, "UPDATE mahasiswa SET nama='$nama', kode_kelas='$kelas', prodi='$prodi', no_hp='$hp' WHERE id='$id'");
        set_alert('success', 'Data mahasiswa berhasil diupdate!');
    } elseif ($aksi == 'hapus') {
        $id = (int)$_POST['id'];
        $mhs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id FROM mahasiswa WHERE id = '$id'"));
        if ($mhs && $mhs['user_id']) {
            mysqli_query($conn, "DELETE FROM users WHERE id = '{$mhs['user_id']}'");
        }
        mysqli_query($conn, "DELETE FROM mahasiswa WHERE id = '$id'");
        set_alert('success', 'Mahasiswa berhasil dihapus!');
    }
    
    header("Location: index.php?page=admin_mahasiswa");
    exit;
}

$filter_kelas = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';
$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$where_clauses = [];
if ($filter_kelas) $where_clauses[] = "m.kode_kelas = '$filter_kelas'";
if ($search) $where_clauses[] = "(m.nama LIKE '%$search%' OR m.nim LIKE '%$search%')";
$where = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : '';

$per_page = 12;
$current_page = get_current_page();
$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM mahasiswa m $where");
$total_data = mysqli_fetch_assoc($count_query)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

$mahasiswa = mysqli_query($conn, "SELECT m.id, m.nim, m.nama, m.prodi, m.no_hp, m.foto, m.kode_kelas, k.nama_kelas 

                                   FROM mahasiswa m 

                                   LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas 

                                   $where ORDER BY m.nim LIMIT $offset, $per_page");
$kelas_list = mysqli_query($conn, "SELECT * FROM kelas ORDER BY kode_kelas");

?>
<?php include 'includes/header.php'; ?>

<style>
    .page-header {
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
    }
    .page-header h4 {
        font-weight: 700;
        color: var(--text-main);
    }
    .page-header h4 i {
        color: var(--primary-color);
    }
    .mahasiswa-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .mahasiswa-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1.75rem rgba(58,59,69,.2) !important;
    }
    .mahasiswa-card .card-title {
        font-weight: 600;
        color: var(--text-main);
    }
    .mahasiswa-card .card-body p i {
        width: 20px;
        text-align: center;
        color: var(--text-muted);
    }
    .mahasiswa-card .action-buttons {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
        border-top: 1px solid var(--border-color);
        padding-top: 1rem;
    }
    .mahasiswa-card .action-buttons .btn {
        flex-grow: 1;
    }
    .modal-header {
        background: var(--banner-gradient);
        color: #fff;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar_admin.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 pt-2">
                    <h4 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Kelola Mahasiswa</h4>
                    <div class="d-flex gap-2 flex-wrap justify-content-md-end">
                        <a href="index.php?page=admin_mahasiswa&export=1<?= $filter_kelas ? '&kelas=' . $filter_kelas : '' ?>" class="btn btn-info">
                            <i class="fas fa-file-export me-1"></i>Export
                        </a>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalImport">
                            <i class="fas fa-file-import me-1"></i>Import
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                            <i class="fas fa-plus me-1"></i>Tambah
                        </button>
                    </div>
                </div>
                
                <?= show_alert() ?>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <input type="hidden" name="page" value="admin_mahasiswa">
                            <div class="col-12 col-md-6 col-lg-5">
                                <label for="search" class="form-label">Cari Nama atau NIM</label>
                                <input type="text" name="search" id="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Masukkan nama atau NIM...">
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <label for="kelas" class="form-label">Filter Kelas</label>
                                <select name="kelas" id="kelas" class="form-select">
                                    <option value="">Semua Kelas</option>
                                    <?php mysqli_data_seek($kelas_list, 0); while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                        <option value="<?= $k['kode_kelas'] ?>" <?= $filter_kelas == $k['kode_kelas'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($k['nama_kelas']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-12 col-lg-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-1"></i>Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="row">
                    <?php if (mysqli_num_rows($mahasiswa) > 0): ?>
                        <?php while ($m = mysqli_fetch_assoc($mahasiswa)): ?>
                            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                                <div class="card h-100 mahasiswa-card">
                                    <div class="card-body d-flex flex-column">
                                        <div class="text-center mb-3">
                                            <?php 
                                            $foto_profil = (!empty($m['foto']) && file_exists($m['foto'])) ? $m['foto'] : 'https://ui-avatars.com/api/?name=' . urlencode($m['nama']) . '&background=random&color=fff&rounded=true';
                                            ?>
                                            <img src="<?= $foto_profil ?>" alt="<?= htmlspecialchars($m['nama']) ?>" class="img-fluid" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%;">
                                        </div>
                                        <h5 class="card-title text-center mb-1"><?= htmlspecialchars($m['nama']) ?></h5>
                                        <p class="text-center text-muted small"><?= htmlspecialchars($m['nim']) ?></p>
                                        
                                        <p class="text-muted mb-2 mt-3"><i class="fas fa-university me-2"></i><?= htmlspecialchars($m['prodi']) ?: 'N/A' ?></p>
                                        <p class="text-muted mb-2"><i class="fas fa-chalkboard-teacher me-2"></i><?= htmlspecialchars($m['nama_kelas']) ?: 'N/A' ?></p>
                                        <p class="text-muted mb-2"><i class="fas fa-phone-alt me-2"></i><?= htmlspecialchars($m['no_hp']) ?: 'N/A' ?></p>
                                        
                                        <div class="mt-auto action-buttons">
                                            <button class="btn btn-sm btn-warning" onclick="editMhs(<?= $m['id'] ?>, '<?= htmlspecialchars($m['nama'], ENT_QUOTES) ?>', '<?= $m['kode_kelas'] ?>', '<?= htmlspecialchars($m['prodi'], ENT_QUOTES) ?>', '<?= htmlspecialchars($m['no_hp'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="hapusMhs(<?= $m['id'] ?>)">
                                                <i class="fas fa-trash me-1"></i>Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle me-2"></i>
                                Tidak ada data mahasiswa yang cocok dengan filter.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($total_data > 0): ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
                    <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                    <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_mahasiswa', ['kelas' => $filter_kelas, 'search' => $search]) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- All Modals (Tambah, Edit, Import) -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header"><h5 class="modal-title" id="modalTambahLabel">Tambah Mahasiswa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">NIM</label><input type="text" name="nim" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Nama</label><input type="text" name="nama" class="form-control" required></div>
                    <div class="mb-3">
                        <label class="form-label">Kelas</label>
                        <select name="kode_kelas" class="form-select" required>
                            <?php mysqli_data_seek($kelas_list, 0); while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                <option value="<?= $k['kode_kelas'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Program Studi</label><input type="text" name="prodi" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">No. HP</label><input type="text" name="no_hp" class="form-control"></div>
                    <div class="mb-3">
                        <label class="form-label">Tgl & Waktu Mulai Aktif</label>
                        <div class="row g-2">
                            <div class="col-7"><input type="date" name="tanggal_daftar" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                            <div class="col-5"><input type="time" name="waktu_daftar" class="form-control" value="<?= date('H:i') ?>"></div>
                        </div>
                        <small class="text-muted">Mahasiswa bisa ikut jadwal sejak waktu ini.</small>
                    </div>
                    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" value="123456" required><small class="text-muted">Default: 123456</small></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="aksi" value="edit"><input type="hidden" name="id" id="edit_id">
                <div class="modal-header"><h5 class="modal-title" id="modalEditLabel">Edit Mahasiswa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nama</label><input type="text" name="nama" id="edit_nama" class="form-control" required></div>
                    <div class="mb-3">
                        <label class="form-label">Kelas</label>
                        <select name="kode_kelas" id="edit_kelas" class="form-select" required>
                            <?php mysqli_data_seek($kelas_list, 0); while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                <option value="<?= $k['kode_kelas'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Program Studi</label><input type="text" name="prodi" id="edit_prodi" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">No. HP</label><input type="text" name="no_hp" id="edit_hp" class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Update</button></div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="modalImport" tabindex="-1" aria-labelledby="modalImportLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="aksi" value="import">
                <div class="modal-header"><h5 class="modal-title" id="modalImportLabel"><i class="fas fa-file-import me-2"></i>Import Mahasiswa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><strong>Petunjuk:</strong><ol class="mb-0 mt-2"><li>Download template CSV.</li><li>Buka & isi data di Excel.</li><li>Simpan sebagai CSV, lalu upload.</li></ol></div>
                    <div class="mb-3"><a href="index.php?page=admin_mahasiswa&download_template=1" class="btn btn-outline-success w-100"><i class="fas fa-download me-2"></i>Download Template CSV</a></div><hr>
                    <div class="mb-3"><label class="form-label">Pilih File CSV</label><input type="file" name="file_import" class="form-control" accept=".csv" required></div>
                    <div class="mb-3">
                        <label class="form-label">Tgl & Waktu Mulai Aktif <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-7"><input type="date" name="tanggal_daftar" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                            <div class="col-5"><input type="time" name="waktu_daftar" class="form-control" value="<?= date('H:i') ?>"></div>
                        </div>
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Mahasiswa bisa ikut jadwal sejak waktu ini.</small>
                    </div>
                    <div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle me-2"></i><strong>Format:</strong><div class="small mt-1"><code>NIM;Nama;Kd_Kelas;Prodi;NoHP;Pass</code></div><small class="text-muted d-block mt-1">* NIM & Nama wajib. Pass default: 123456</small></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success"><i class="fas fa-upload me-1"></i>Import Data</button></div>
            </form>
        </div>
    </div>
</div>

<form id="formHapus" method="POST" class="d-none"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" id="hapus_id"></form>

<script>
function editMhs(id, nama, kelas, prodi, hp) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_kelas').value = kelas;
    document.getElementById('edit_prodi').value = prodi;
    document.getElementById('edit_hp').value = hp;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}
function hapusMhs(id) {
    if (confirm('Yakin ingin menghapus mahasiswa ini? Akun login yang terkait juga akan dihapus.')) {
        document.getElementById('hapus_id').value = id;
        document.getElementById('formHapus').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
