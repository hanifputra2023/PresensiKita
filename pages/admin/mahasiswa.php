<?php
$page = 'admin_mahasiswa';

// ... (PHP code for export, import, CRUD remains the same) ...
// Download template
if (isset($_GET['download_template'])) {
    // [FIX] Bersihkan output buffer agar tidak ada HTML dari index.php yang ikut terunduh
    if (ob_get_length()) ob_end_clean();
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=template_mahasiswa.csv');
    
    // BOM untuk Excel agar UTF-8 terbaca dengan benar
    echo chr(0xEF) . chr(0xBB) . chr(0xBF);
    
    // Header dengan semicolon sebagai delimiter (lebih kompatibel dengan Excel Indonesia)
    echo "NIM;Username;Nama;Kode Kelas;Program Studi;No HP;Password;Sesi\n";
    // Contoh data
    echo "12345678;budi.santoso;Budi Santoso;TI-1A;Teknik Informatika;081234567890;123456;1\n";
    echo "12345679;ani.wijaya;Ani Wijaya;TI-1A;Teknik Informatika;081234567891;123456;2\n";
    exit;
}

// Export data mahasiswa
if (isset($_GET['export'])) {
    // [FIX] Bersihkan output buffer
    if (ob_get_length()) ob_end_clean();
    
    $filter_kelas_exp = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';
    $where_exp = $filter_kelas_exp ? "WHERE m.kode_kelas = '$filter_kelas_exp'" : '';
    
    $data_export = mysqli_query($conn, "SELECT m.nim, u.username, m.nama, m.kode_kelas, k.nama_kelas, m.sesi, m.prodi, m.no_hp 
                                         FROM mahasiswa m 
                                         LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas 
                                         JOIN users u ON m.user_id = u.id 
                                         $where_exp ORDER BY m.nim");
    
    $filename = 'data_mahasiswa_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    // BOM untuk Excel
    echo chr(0xEF) . chr(0xBB) . chr(0xBF);
    
    // Header
    echo "No;NIM;Username;Nama;Kode Kelas;Nama Kelas;Sesi;Program Studi;No HP\n";
    
    // Data
    $no = 1;
    while ($row = mysqli_fetch_assoc($data_export)) {
        echo $no . ";" . 
             $row['nim'] . ";" . 
             $row['username'] . ";" . 
             $row['nama'] . ";" . 
             $row['kode_kelas'] . ";" . 
             $row['nama_kelas'] . ";" . 
             $row['sesi'] . ";" . 
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
                    $delimiter = (strpos($first_line, ';') !== false) ? ';' : ',';
                    rewind($handle); // Kembali ke awal file setelah cek delimiter
                    
                    // [BARU] Baca header dan petakan kolom
                    $header = fgetcsv($handle, 0, $delimiter);
                    $header_map = [];
                    foreach ($header as $index => $col) {
                        $col_name = strtolower(trim($col));
                        if (strpos($col_name, 'nim') !== false) $header_map['nim'] = $index;
                        if (strpos($col_name, 'username') !== false) $header_map['username'] = $index;
                        if (strpos($col_name, 'nama') !== false) $header_map['nama'] = $index;
                        if (strpos($col_name, 'kelas') !== false) $header_map['kelas'] = $index;
                        if (strpos($col_name, 'prodi') !== false || strpos($col_name, 'program studi') !== false) $header_map['prodi'] = $index;
                        if (strpos($col_name, 'hp') !== false) $header_map['hp'] = $index;
                        if (strpos($col_name, 'password') !== false) $header_map['password'] = $index;
                        if (strpos($col_name, 'sesi') !== false) $header_map['sesi'] = $index;
                    }

                    // Validasi header
                    if (!isset($header_map['nim']) || !isset($header_map['nama'])) {
                        set_alert('danger', "File CSV tidak valid! Pastikan memiliki kolom 'NIM' dan 'Nama'.");
                        header("Location: index.php?page=admin_mahasiswa");
                        exit;
                    }

                    while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                        $data_rows[] = $data;
                    }
                    fclose($handle);
                }
            } else {
                set_alert('danger', 'Format file tidak didukung! Gunakan file .csv');
                header("Location: index.php?page=admin_mahasiswa");
                exit;
            }
            
            $tanggal_daftar = (isset($_POST['tanggal_daftar']) && !empty($_POST['tanggal_daftar'])) ? escape($_POST['tanggal_daftar']) : date('Y-m-d');
            $waktu_daftar = (isset($_POST['waktu_daftar']) && !empty($_POST['waktu_daftar'])) ? escape($_POST['waktu_daftar']) . ':00' : date('H:i:s');
            $tanggal_daftar = $tanggal_daftar . ' ' . $waktu_daftar;
            
            foreach ($data_rows as $i => $data) {
                $row_num = $i + 2; // +2 karena header dan 0-based index
                if (count($data) > 0) {
                    $nim = escape(trim($data[$header_map['nim']] ?? ''));
                    $nama = escape(trim($data[$header_map['nama']] ?? ''));
                    // Ambil username jika ada, jika tidak gunakan NIM
                    $username_raw = isset($header_map['username']) ? trim($data[$header_map['username']]) : '';
                    $username = !empty($username_raw) ? escape($username_raw) : $nim;
                    $kelas = isset($header_map['kelas']) ? escape(trim($data[$header_map['kelas']])) : '';
                    $prodi = isset($header_map['prodi']) ? escape(trim($data[$header_map['prodi']])) : '';
                    $hp = isset($header_map['hp']) ? escape(trim($data[$header_map['hp']])) : '';
                    $password = isset($header_map['password']) && !empty(trim($data[$header_map['password']])) ? trim($data[$header_map['password']]) : '123456';
                    $sesi = isset($header_map['sesi']) && !empty(trim($data[$header_map['sesi']])) ? (int)trim($data[$header_map['sesi']]) : 1;
                    
                    if (empty($nim) && empty($nama)) continue;
                    if (!preg_match('/^[a-zA-Z0-9]+$/', $nim)) { $gagal++; $errors[] = "Baris $row_num: NIM tidak valid."; continue; }
                    if (empty($nim) || empty($nama)) { $gagal++; $errors[] = "Baris $row_num: NIM atau Nama kosong."; continue; }
                    
                    // Prepared statement untuk cek NIM
                    $stmt_cek = mysqli_prepare($conn, "SELECT * FROM mahasiswa WHERE nim = ?");
                    mysqli_stmt_bind_param($stmt_cek, "s", $nim);
                    mysqli_stmt_execute($stmt_cek);
                    $cek = mysqli_stmt_get_result($stmt_cek);
                    if (mysqli_num_rows($cek) > 0) { $gagal++; $errors[] = "Baris $row_num: NIM $nim sudah terdaftar."; continue; }
                    
                    // Cek Username
                    $stmt_cek_user = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
                    mysqli_stmt_bind_param($stmt_cek_user, "s", $username);
                    mysqli_stmt_execute($stmt_cek_user);
                    if (mysqli_num_rows(mysqli_stmt_get_result($stmt_cek_user)) > 0) { $gagal++; $errors[] = "Baris $row_num: Username '$username' sudah digunakan."; continue; }
                    
                    
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    // [FIX] Jalankan query terpisah agar tidak out of sync
                    $stmt_user = mysqli_prepare($conn, "INSERT INTO users (username, password, role) VALUES (?, ?, 'mahasiswa')");

                    mysqli_stmt_bind_param($stmt_user, "ss", $username, $hashed_password);
                    mysqli_stmt_execute($stmt_user);
                    $user_id = mysqli_insert_id($conn);
                    
                    $stmt_mhs = mysqli_prepare($conn, "INSERT INTO mahasiswa (nim, user_id, nama, kode_kelas, prodi, no_hp, tanggal_daftar, sesi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt_mhs, "sisssssi", $nim, $user_id, $nama, $kelas, $prodi, $hp, $tanggal_daftar, $sesi);
                    $q = mysqli_stmt_execute($stmt_mhs);
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
        $username = escape($_POST['username']);
        
        // Jika username kosong, otomatis gunakan NIM
        if (empty($username)) {
            $username = $nim;
        }
        
        $nama = escape($_POST['nama']);
        $kelas = escape($_POST['kode_kelas']);
        $prodi = escape($_POST['prodi']);
        $hp = escape($_POST['no_hp']);
        $password = $_POST['password'];
        $sesi = (int)($_POST['sesi'] ?? 1);
        $tanggal_daftar = (isset($_POST['tanggal_daftar']) && !empty($_POST['tanggal_daftar'])) ? escape($_POST['tanggal_daftar']) : date('Y-m-d');
        $waktu_daftar = (isset($_POST['waktu_daftar']) && !empty($_POST['waktu_daftar'])) ? escape($_POST['waktu_daftar']) . ':00' : date('H:i:s');
        $tanggal_daftar = $tanggal_daftar . ' ' . $waktu_daftar;
        
        // Prepared statement untuk cek NIM
        $stmt_cek = mysqli_prepare($conn, "SELECT * FROM mahasiswa WHERE nim = ?");
        mysqli_stmt_bind_param($stmt_cek, "s", $nim);
        mysqli_stmt_execute($stmt_cek);
        $cek = mysqli_stmt_get_result($stmt_cek);
        if (mysqli_num_rows($cek) > 0) {
            set_alert('danger', 'NIM sudah terdaftar!');
        } else {
            // Cek Username apakah sudah ada di tabel users
            $stmt_cek_user = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
            mysqli_stmt_bind_param($stmt_cek_user, "s", $username);
            mysqli_stmt_execute($stmt_cek_user);
            if (mysqli_num_rows(mysqli_stmt_get_result($stmt_cek_user)) > 0) {
                set_alert('danger', 'Username sudah digunakan! Silakan pilih username lain.');
            } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_user = mysqli_prepare($conn, "INSERT INTO users (username, password, role) VALUES (?, ?, 'mahasiswa')");
            mysqli_stmt_bind_param($stmt_user, "ss", $username, $hashed_password);
            mysqli_stmt_execute($stmt_user);
            $user_id = mysqli_insert_id($conn);
            $stmt_mhs = mysqli_prepare($conn, "INSERT INTO mahasiswa (nim, user_id, nama, kode_kelas, prodi, no_hp, tanggal_daftar, sesi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_mhs, "sisssssi", $nim, $user_id, $nama, $kelas, $prodi, $hp, $tanggal_daftar, $sesi);
            mysqli_stmt_execute($stmt_mhs);
            set_alert('success', 'Mahasiswa berhasil ditambahkan!');
            }
        }
    } elseif ($aksi == 'edit') {
        $id = (int)$_POST['id'];
        $nama = escape($_POST['nama']);
        $kelas = escape($_POST['kode_kelas']);
        $prodi = escape($_POST['prodi']);
        $hp = escape($_POST['no_hp']);
        $sesi = (int)($_POST['sesi'] ?? 1);
        
        $stmt_upd = mysqli_prepare($conn, "UPDATE mahasiswa SET nama=?, kode_kelas=?, prodi=?, no_hp=?, sesi=? WHERE id=?");
        mysqli_stmt_bind_param($stmt_upd, "ssssii", $nama, $kelas, $prodi, $hp, $sesi, $id);
        mysqli_stmt_execute($stmt_upd);
        set_alert('success', 'Data mahasiswa berhasil diupdate!');
    } elseif ($aksi == 'hapus') {
        $id = (int)$_POST['id'];
        // Prepared statement untuk get user_id
        $stmt_get = mysqli_prepare($conn, "SELECT user_id FROM mahasiswa WHERE id = ?");
        mysqli_stmt_bind_param($stmt_get, "i", $id);
        mysqli_stmt_execute($stmt_get);
        $mhs = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_get));
        if ($mhs && $mhs['user_id']) {
            $stmt_del_user = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
            mysqli_stmt_bind_param($stmt_del_user, "i", $mhs['user_id']);
            mysqli_stmt_execute($stmt_del_user);
        }
        $stmt_del_mhs = mysqli_prepare($conn, "DELETE FROM mahasiswa WHERE id = ?");
        mysqli_stmt_bind_param($stmt_del_mhs, "i", $id);
        mysqli_stmt_execute($stmt_del_mhs);
        set_alert('success', 'Mahasiswa berhasil dihapus!');
    } elseif ($aksi == 'hapus_banyak') {
        if (isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = $_POST['ids'];
            $success_count = 0;
            
            $stmt_get = mysqli_prepare($conn, "SELECT user_id FROM mahasiswa WHERE id = ?");
            $stmt_del_user = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
            $stmt_del_mhs = mysqli_prepare($conn, "DELETE FROM mahasiswa WHERE id = ?");
            
            foreach ($ids as $id) {
                $safe_id = (int)$id;
                mysqli_stmt_bind_param($stmt_get, "i", $safe_id);
                mysqli_stmt_execute($stmt_get);
                $mhs = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_get));
                if ($mhs && $mhs['user_id']) {
                    mysqli_stmt_bind_param($stmt_del_user, "i", $mhs['user_id']);
                    mysqli_stmt_execute($stmt_del_user);
                }
                mysqli_stmt_bind_param($stmt_del_mhs, "i", $safe_id);
                if(mysqli_stmt_execute($stmt_del_mhs)) $success_count++;
            }
            set_alert('success', $success_count . ' Mahasiswa berhasil dihapus!');
        }
    } elseif ($aksi == 'toggle_status') {
        $id = (int)$_POST['id'];
        $current_status = escape($_POST['status']);
        $new_status = ($current_status == 'aktif') ? 'nonaktif' : 'aktif';
        
        $stmt = mysqli_prepare($conn, "UPDATE mahasiswa SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $id);
        mysqli_stmt_execute($stmt);
        
        set_alert('success', 'Status mahasiswa berhasil diubah menjadi ' . ucfirst($new_status) . '!');
    }
    
    header("Location: index.php?page=admin_mahasiswa");
    exit;
}

$filter_kelas = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';
$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$search_param = '%' . $search . '%';

$per_page = 12;
$current_page = get_current_page();

// Prepared statement untuk count dan fetch mahasiswa
if ($filter_kelas && $search) {
    $stmt_count = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM mahasiswa m WHERE m.kode_kelas = ? AND (m.nama LIKE ? OR m.nim LIKE ?)");
    mysqli_stmt_bind_param($stmt_count, "sss", $filter_kelas, $search_param, $search_param);
    mysqli_stmt_execute($stmt_count);
    $count_result = mysqli_stmt_get_result($stmt_count);
} elseif ($filter_kelas) {
    $stmt_count = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM mahasiswa m WHERE m.kode_kelas = ?");
    mysqli_stmt_bind_param($stmt_count, "s", $filter_kelas);
    mysqli_stmt_execute($stmt_count);
    $count_result = mysqli_stmt_get_result($stmt_count);
} elseif ($search) {
    $stmt_count = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM mahasiswa m WHERE m.nama LIKE ? OR m.nim LIKE ?");
    mysqli_stmt_bind_param($stmt_count, "ss", $search_param, $search_param);
    mysqli_stmt_execute($stmt_count);
    $count_result = mysqli_stmt_get_result($stmt_count);
} else {
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM mahasiswa");
}
$total_data = mysqli_fetch_assoc($count_result)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

if ($filter_kelas && $search) {
    $stmt_mhs = mysqli_prepare($conn, "SELECT m.id, m.nim, m.nama, m.prodi, m.no_hp, m.foto, m.kode_kelas, m.status, m.sesi, k.nama_kelas, u.username 
                                   FROM mahasiswa m 
                                   LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas 
                                   JOIN users u ON m.user_id = u.id
                                   WHERE m.kode_kelas = ? AND (m.nama LIKE ? OR m.nim LIKE ?)
                                   ORDER BY m.nim LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt_mhs, "sssii", $filter_kelas, $search_param, $search_param, $offset, $per_page);
} elseif ($filter_kelas) {
    $stmt_mhs = mysqli_prepare($conn, "SELECT m.id, m.nim, m.nama, m.prodi, m.no_hp, m.foto, m.kode_kelas, m.status, m.sesi, k.nama_kelas, u.username 
                                   FROM mahasiswa m 
                                   LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas 
                                   JOIN users u ON m.user_id = u.id
                                   WHERE m.kode_kelas = ?
                                   ORDER BY m.nim LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt_mhs, "sii", $filter_kelas, $offset, $per_page);
} elseif ($search) {
    $stmt_mhs = mysqli_prepare($conn, "SELECT m.id, m.nim, m.nama, m.prodi, m.no_hp, m.foto, m.kode_kelas, m.status, m.sesi, k.nama_kelas, u.username 
                                   FROM mahasiswa m 
                                   LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas 
                                   JOIN users u ON m.user_id = u.id
                                   WHERE m.nama LIKE ? OR m.nim LIKE ?
                                   ORDER BY m.nim LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt_mhs, "ssii", $search_param, $search_param, $offset, $per_page);
} else {
    $stmt_mhs = mysqli_prepare($conn, "SELECT m.id, m.nim, m.nama, m.prodi, m.no_hp, m.foto, m.kode_kelas, m.status, m.sesi, k.nama_kelas, u.username 
                                   FROM mahasiswa m 
                                   LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas 
                                   JOIN users u ON m.user_id = u.id
                                   ORDER BY m.nim LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt_mhs, "ii", $offset, $per_page);
}
mysqli_stmt_execute($stmt_mhs);
$mahasiswa = mysqli_stmt_get_result($stmt_mhs);
$kelas_list = mysqli_query($conn, "SELECT * FROM kelas ORDER BY kode_kelas");

// Handle AJAX Search
if (isset($_GET['ajax_search'])) {
    ?>
    <div class="row">
        <?php if (mysqli_num_rows($mahasiswa) > 0): ?>
            <?php while ($m = mysqli_fetch_assoc($mahasiswa)): ?>
                <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                    <div class="card h-100 mahasiswa-card position-relative" id="card-<?= $m['id'] ?>">
                        <div class="card-select-overlay">
                            <input type="checkbox" class="form-check-input item-checkbox" 
                                   value="<?= $m['id'] ?>" 
                                   onchange="toggleSelection(<?= $m['id'] ?>)">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center gap-3">
                                <?php 
                                $foto_profil = (!empty($m['foto']) && file_exists($m['foto'])) ? $m['foto'] : 'https://ui-avatars.com/api/?name=' . urlencode($m['nama']) . '&background=random&color=fff&rounded=true';
                                ?>
                                    <img src="<?= $foto_profil ?>" alt="<?= htmlspecialchars($m['nama']) ?>" class="rounded-circle border" style="width: 50px; height: 50px; object-fit: cover;" loading="lazy">
                                    <div>
                                        <h5 class="card-title mb-0"><?= htmlspecialchars($m['nama']) ?></h5>
                                        <small class="text-muted d-block mb-1">@<?= htmlspecialchars($m['username']) ?></small>
                                        <span class="badge bg-info"><?= htmlspecialchars($m['nim']) ?></span>
                                    </div>
                                </div>
                                <?php $status = $m['status'] ?? 'aktif'; ?>
                                <span class="badge <?= $status == 'aktif' ? 'bg-success' : 'bg-secondary' ?> text-capitalize">
                                    <?= ucfirst($status) ?>
                                </span>
                            </div>
                            
                            <p class="text-muted mb-1"><i class="fas fa-university me-2" style="width:20px;text-align:center;"></i><?= htmlspecialchars($m['prodi']) ?: '-' ?></p>
                            <p class="text-muted mb-1"><i class="fas fa-chalkboard-teacher me-2" style="width:20px;text-align:center;"></i><?= htmlspecialchars($m['nama_kelas']) ?: '-' ?></p>
                            <p class="text-muted mb-3"><i class="fas fa-phone-alt me-2" style="width:20px;text-align:center;"></i><?= htmlspecialchars($m['no_hp']) ?: '-' ?></p>
                            
                            <div class="mt-auto action-buttons">
                                <button class="btn btn-sm <?= $status == 'aktif' ? 'btn-outline-secondary' : 'btn-outline-success' ?>" onclick="toggleStatus(<?= $m['id'] ?>, '<?= $status ?>')" title="<?= $status == 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                    <i class="fas <?= $status == 'aktif' ? 'fa-ban' : 'fa-check' ?>"></i>
                                </button>
                                <button class="btn btn-sm btn-warning text-dark" onclick="editMhs(<?= $m['id'] ?>, '<?= htmlspecialchars($m['nama'], ENT_QUOTES) ?>', '<?= $m['kode_kelas'] ?>', '<?= htmlspecialchars($m['prodi'], ENT_QUOTES) ?>', '<?= htmlspecialchars($m['no_hp'], ENT_QUOTES) ?>', '<?= $m['sesi'] ?? 1 ?>')">
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
    <?php
    exit;
}
?>
<?php include 'includes/header.php'; ?>

<style>
    /* Card Selection Styles */
    .mahasiswa-card { transition: all 0.2s; border: 1px solid var(--border-color); }
    .mahasiswa-card.selected { border-color: var(--primary-color); background-color: rgba(0, 102, 204, 0.05); box-shadow: 0 0 0 1px var(--primary-color); }
    [data-theme="dark"] .mahasiswa-card.selected { background-color: rgba(0, 102, 204, 0.15); }
    .card-select-overlay { position: absolute; top: 10px; left: 10px; z-index: 5; display: none; opacity: 0; transition: opacity 0.3s; }
    .select-mode .card-select-overlay { display: block; opacity: 1; }
    .mahasiswa-card .card-body { transition: padding-top 0.3s; }
    .select-mode .mahasiswa-card .card-body { padding-top: 2.5rem; }
    .item-checkbox { width: 22px; height: 22px; cursor: pointer; border: 2px solid var(--text-muted); border-radius: 50%; }
    .item-checkbox:checked { background-color: var(--primary-color); border-color: var(--primary-color); }
    
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
    /* Bulk Action Bar */
    #bulkActionBar { position: fixed; bottom: -100px; left: 0; right: 0; background: var(--bg-card); box-shadow: 0 -5px 20px rgba(0,0,0,0.1); padding: 15px 30px; z-index: 1000; transition: bottom 0.3s ease-in-out; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); }
    #bulkActionBar.show { bottom: 0; }
    [data-theme="dark"] #bulkActionBar { box-shadow: 0 -5px 20px rgba(0,0,0,0.3); }
    body { padding-bottom: 80px; }
    
    /* Slider Confirm */
    .slider-container { position: relative; width: 100%; height: 55px; background: #f0f2f5; border-radius: 30px; user-select: none; overflow: hidden; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1); }
    [data-theme="dark"] .slider-container { background: var(--bg-input); box-shadow: inset 0 2px 5px rgba(0,0,0,0.3); }
    .slider-text { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #888; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; z-index: 1; pointer-events: none; transition: opacity 0.3s; }
    .slider-handle { position: absolute; top: 5px; left: 5px; width: 45px; height: 45px; background: #dc3545; border-radius: 50%; cursor: pointer; z-index: 2; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: transform 0.1s; }
    .slider-handle:active { cursor: grabbing; transform: scale(0.95); }
    .slider-progress { position: absolute; top: 0; left: 0; height: 100%; background: rgba(220, 53, 69, 0.2); width: 0; z-index: 0; }
    .slider-container.unlocked .slider-handle { width: calc(100% - 10px); border-radius: 30px; }
    .slider-container.unlocked .slider-text { opacity: 0; }
    @media (max-width: 576px) {
        #bulkActionBar { flex-direction: column; gap: 10px; padding: 15px; }
        #bulkActionBar > div { width: 100%; display: flex; justify-content: space-between; }
        #bulkActionBar button { flex: 1; }
    }
    /* Dark Mode Fixes */
    [data-theme="dark"] .btn-warning,
    [data-theme="dark"] .btn-info {
        color: #212529 !important;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
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
                        <form method="GET" class="row g-3 align-items-end" onsubmit="return false;">
                            <input type="hidden" name="page" value="admin_mahasiswa">
                            <div class="col-12 col-md-5">
                                <label for="search" class="form-label">Cari Nama atau NIM</label>
                                <input type="text" name="search" id="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Masukkan nama atau NIM...">
                            </div>
                            <div class="col-12 col-md-5">
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
                            <div class="col-12 col-md-2 d-flex flex-column flex-md-row align-items-stretch align-items-md-end justify-content-md-end gap-2">
                                <button type="button" class="btn btn-outline-secondary" id="btnSelectMode" onclick="toggleSelectMode()">
                                    <i class="fas fa-check-square me-1"></i> Pilih
                                </button>
                                <div class="d-none d-flex align-items-center justify-content-center justify-content-md-start mb-0" id="selectAllContainer">
                                    <input class="form-check-input item-checkbox m-0" type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                    <label class="form-check-label fw-bold ms-2 small" for="selectAll" style="cursor:pointer">Semua</label>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div id="mahasiswaContainer">
                <div class="row">
                    <?php if (mysqli_num_rows($mahasiswa) > 0): ?>
                        <?php while ($m = mysqli_fetch_assoc($mahasiswa)): ?>
                            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                                <div class="card h-100 mahasiswa-card position-relative" id="card-<?= $m['id'] ?>">
                                    <div class="card-select-overlay">
                                        <input type="checkbox" class="form-check-input item-checkbox" 
                                               value="<?= $m['id'] ?>" 
                                               onchange="toggleSelection(<?= $m['id'] ?>)">
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="d-flex align-items-center gap-3">
                                            <?php 
                                            $foto_profil = (!empty($m['foto']) && file_exists($m['foto'])) ? $m['foto'] : 'https://ui-avatars.com/api/?name=' . urlencode($m['nama']) . '&background=random&color=fff&rounded=true';
                                            ?>
                                                <img src="<?= $foto_profil ?>" alt="<?= htmlspecialchars($m['nama']) ?>" class="rounded-circle border" style="width: 50px; height: 50px; object-fit: cover;" loading="lazy">
                                                <div>
                                                    <h5 class="card-title mb-0"><?= htmlspecialchars($m['nama']) ?></h5>
                                                    <small class="text-muted d-block mb-1">@<?= htmlspecialchars($m['username']) ?></small>
                                                    <span class="badge bg-info"><?= htmlspecialchars($m['nim']) ?></span>
                                                </div>
                                            </div>
                                            <?php $status = $m['status'] ?? 'aktif'; ?>
                                            <span class="badge <?= $status == 'aktif' ? 'bg-success' : 'bg-secondary' ?> text-capitalize">
                                                <?= ucfirst($status) ?>
                                            </span>
                                        </div>
                                        
                                        <p class="text-muted mb-1"><i class="fas fa-university me-2" style="width:20px;text-align:center;"></i><?= htmlspecialchars($m['prodi']) ?: '-' ?></p>
                                        <p class="text-muted mb-1"><i class="fas fa-chalkboard-teacher me-2" style="width:20px;text-align:center;"></i><?= htmlspecialchars($m['nama_kelas']) ?: '-' ?></p>
                                        <p class="text-muted mb-3"><i class="fas fa-phone-alt me-2" style="width:20px;text-align:center;"></i><?= htmlspecialchars($m['no_hp']) ?: '-' ?></p>
                                        
                                        <div class="mt-auto action-buttons">
                                            <button class="btn btn-sm <?= $status == 'aktif' ? 'btn-outline-secondary' : 'btn-outline-success' ?>" onclick="toggleStatus(<?= $m['id'] ?>, '<?= $status ?>')" title="<?= $status == 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                <i class="fas <?= $status == 'aktif' ? 'fa-ban' : 'fa-check' ?>"></i>
                                            </button>
                                <button class="btn btn-sm btn-warning text-dark" onclick="editMhs(<?= $m['id'] ?>, '<?= htmlspecialchars($m['nama'], ENT_QUOTES) ?>', '<?= $m['kode_kelas'] ?>', '<?= htmlspecialchars($m['prodi'], ENT_QUOTES) ?>', '<?= htmlspecialchars($m['no_hp'], ENT_QUOTES) ?>', '<?= $m['sesi'] ?? 1 ?>')">
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
</div>

<div id="bulkActionBar">
    <div class="d-flex align-items-center">
        <span class="badge bg-primary me-2" style="font-size: 1rem;"><span id="selectedCount">0</span></span>
        <span class="text-dark fw-bold">Mahasiswa Dipilih</span>
    </div>
    <div>
        <button class="btn btn-secondary me-2" onclick="toggleSelectMode()">Batal</button>
        <button class="btn btn-danger" onclick="confirmSlideDelete('bulk')"><i class="fas fa-trash-alt me-2"></i>Hapus Terpilih</button>
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
                    <div class="mb-3"><label class="form-label">Username <small class="text-muted">(Opsional)</small></label><input type="text" name="username" class="form-control" placeholder="Kosongkan jika sama dengan NIM"></div>
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
                        <label class="form-label">Sesi</label>
                        <select name="sesi" class="form-select">
                            <option value="1" selected>Sesi 1</option>
                            <option value="2">Sesi 2</option>
                            <option value="3">Sesi 3</option>
                        </select>
                    </div>
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
                    <div class="mb-3">
                        <label class="form-label">Sesi</label>
                        <select name="sesi" id="edit_sesi" class="form-select">
                            <option value="1">Sesi 1</option>
                            <option value="2">Sesi 2</option>
                            <option value="3">Sesi 3</option>
                        </select>
                    </div>
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
                    <div class="alert alert-info p-3">
                        <h6 class="alert-heading fw-bold"><i class="fas fa-info-circle me-2"></i>Petunjuk Penting!</h6>
                        <strong>Petunjuk:</strong>
                        <ol class="mb-0 mt-2 small">
                            <li>Jika file Anda dalam format Excel (<strong>.xlsx</strong>), buka file tersebut, lalu pilih <strong>File > Save As</strong>.</li>
                            <li>Pada bagian "Save as type", pilih <strong>CSV (Comma delimited) (*.csv)</strong> atau <strong>CSV (Pemisah titik koma)</strong>.</li>
                            <li>Pastikan file CSV yang disimpan memiliki kolom header: <strong>NIM</strong> dan <strong>Nama</strong> (wajib).</li>
                            <li>Kolom opsional: `Kode Kelas`, `Program Studi`, `No HP`, `Password`.</li>
                            <li>Urutan kolom tidak menjadi masalah. <a href="index.php?page=admin_mahasiswa&download_template=1" class="fw-bold">Download template</a> untuk contoh.</li>
                        </ol>
                    </div>
                    <div class="mb-3"><label class="form-label">Pilih File CSV</label><input type="file" name="file_import" class="form-control" accept=".csv" required></div>
                    <div class="mb-3">
                        <label class="form-label">Tgl & Waktu Mulai Aktif <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-7"><input type="date" name="tanggal_daftar" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                            <div class="col-5"><input type="time" name="waktu_daftar" class="form-control" value="<?= date('H:i') ?>"></div>
                        </div>
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Mahasiswa bisa ikut jadwal sejak waktu ini.</small>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success"><i class="fas fa-upload me-1"></i>Import Data</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSlideConfirm" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3 text-danger"><i class="fas fa-exclamation-triangle fa-3x"></i></div>
                <h4 class="fw-bold text-danger mb-2">Konfirmasi Hapus</h4>
                <p class="text-muted mb-4" id="slideConfirmMsg">Apakah Anda yakin? Data yang dihapus tidak dapat dikembalikan.</p>
                <div class="slider-container" id="deleteSlider">
                    <div class="slider-progress" id="sliderProgress"></div>
                    <div class="slider-text">GESER UNTUK MENGHAPUS >></div>
                    <div class="slider-handle" id="sliderHandle"><i class="fas fa-trash"></i></div>
                </div>
                <button type="button" class="btn btn-link text-muted mt-3 text-decoration-none" data-bs-dismiss="modal" id="btnCancelSlide">Batal</button>
            </div>
        </div>
    </div>
</div>

<form id="formHapus" method="POST" class="d-none"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" id="hapus_id"></form>
<form id="formHapusBulk" method="POST" class="d-none"><input type="hidden" name="aksi" value="hapus_banyak"><div id="bulkInputs"></div></form>

<form id="formToggle" method="POST" class="d-none">
    <input type="hidden" name="aksi" value="toggle_status">
    <input type="hidden" name="id" id="toggle_id">
    <input type="hidden" name="status" id="toggle_status">
</form>

<script>
// --- CRUD Functions ---
function editMhs(id, nama, kelas, prodi, hp, sesi) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_kelas').value = kelas;
    document.getElementById('edit_prodi').value = prodi;
    document.getElementById('edit_hp').value = hp;
    document.getElementById('edit_sesi').value = sesi;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function hapusMhs(id) {
    confirmSlideDelete('single', id);
}

function toggleStatus(id, currentStatus) {
    document.getElementById('toggle_id').value = id;
    document.getElementById('toggle_status').value = currentStatus;
    document.getElementById('formToggle').submit();
}

// --- Selection & Bulk Action Logic ---
let selectedItems = new Set();
let isSelectMode = false;

function toggleSelectMode() {
    isSelectMode = !isSelectMode;
    const container = document.getElementById('mahasiswaContainer');
    const btn = document.getElementById('btnSelectMode');
    const selectAllContainer = document.getElementById('selectAllContainer');
    
    if (isSelectMode) {
        container.classList.add('select-mode');
        btn.classList.replace('btn-outline-secondary', 'btn-secondary');
        btn.innerHTML = '<i class="fas fa-times me-1"></i> Batal';
        selectAllContainer.classList.remove('d-none');
        selectAllContainer.classList.add('d-flex');
    } else {
        container.classList.remove('select-mode');
        btn.classList.replace('btn-secondary', 'btn-outline-secondary');
        btn.innerHTML = '<i class="fas fa-check-square me-1"></i> Pilih';
        selectAllContainer.classList.add('d-none');
        selectAllContainer.classList.remove('d-flex');
        selectedItems.clear();
        document.getElementById('selectAll').checked = false;
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
        document.querySelectorAll('.mahasiswa-card').forEach(c => c.classList.remove('selected'));
        updateBulkUI();
    }
}

function toggleSelection(id) {
    const card = document.getElementById('card-' + id);
    const checkbox = card.querySelector('.item-checkbox');
    const idStr = String(id);
    if (checkbox.checked) { selectedItems.add(idStr); card.classList.add('selected'); }
    else { selectedItems.delete(idStr); card.classList.remove('selected'); }
    updateBulkUI();
}

function toggleSelectAll() {
    const isChecked = document.getElementById('selectAll').checked;
    document.querySelectorAll('#mahasiswaContainer .item-checkbox').forEach(cb => {
        const id = String(cb.value);
        const card = document.getElementById('card-' + id);
        if (cb.checked !== isChecked) {
            cb.checked = isChecked;
            if (isChecked) { selectedItems.add(id); card.classList.add('selected'); }
            else { selectedItems.delete(id); card.classList.remove('selected'); }
        }
    });
    updateBulkUI();
}

function updateBulkUI() {
    const bar = document.getElementById('bulkActionBar');
    document.getElementById('selectedCount').innerText = selectedItems.size;
    if (selectedItems.size > 0) bar.classList.add('show'); else bar.classList.remove('show');
}

// --- Slide to Confirm Logic ---
let deleteType = ''; let deleteTargetId = '';
function confirmSlideDelete(type, id = null) {
    deleteType = type; deleteTargetId = id;
    const modal = new bootstrap.Modal(document.getElementById('modalSlideConfirm'));
    const msg = document.getElementById('slideConfirmMsg');
    if (type === 'bulk') msg.innerHTML = `Anda akan menghapus <b>${selectedItems.size} mahasiswa</b> terpilih.<br>Akun login terkait juga akan dihapus.`;
    else msg.innerHTML = `Anda akan menghapus mahasiswa ini.<br>Akun login terkait juga akan dihapus.`;
    resetSlider(); modal.show();
}

const sliderContainer = document.getElementById('deleteSlider');
const sliderHandle = document.getElementById('sliderHandle');
const sliderProgress = document.getElementById('sliderProgress');
let isDragging = false;

sliderHandle.addEventListener('mousedown', startDrag); sliderHandle.addEventListener('touchstart', startDrag);
document.addEventListener('mouseup', endDrag); document.addEventListener('touchend', endDrag);
document.addEventListener('mousemove', drag); document.addEventListener('touchmove', drag);

function startDrag(e) { isDragging = true; }
function drag(e) { if(!isDragging) return; let clientX = e.clientX || e.touches[0].clientX; let rect = sliderContainer.getBoundingClientRect(); let x = clientX - rect.left - (sliderHandle.offsetWidth/2); let max = rect.width - sliderHandle.offsetWidth; if(x<0)x=0; if(x>max)x=max; sliderHandle.style.left = x+'px'; sliderProgress.style.width = (x+20)+'px'; if(x>=max*0.95) { isDragging=false; sliderContainer.classList.add('unlocked'); sliderHandle.style.left=max+'px'; sliderProgress.style.width='100%'; performDelete(); } }
function endDrag() { if(!isDragging) return; isDragging=false; if(!sliderContainer.classList.contains('unlocked')) { sliderHandle.style.left='5px'; sliderProgress.style.width='0'; } }
function resetSlider() { sliderContainer.classList.remove('unlocked'); sliderHandle.style.left='5px'; sliderProgress.style.width='0'; document.querySelector('.slider-text').style.opacity='1'; }

function performDelete() {
    setTimeout(() => {
        if (deleteType === 'single') { document.getElementById('hapus_id').value = deleteTargetId; document.getElementById('formHapus').submit(); }
        else {
            const container = document.getElementById('bulkInputs'); container.innerHTML = '';
            selectedItems.forEach(id => { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'ids[]'; input.value = id; container.appendChild(input); });
            document.getElementById('formHapusBulk').submit();
        }
    }, 300);
}

// Live Search
let searchTimeout = null;
const searchInput = document.getElementById('search');
const kelasSelect = document.getElementById('kelas');

function performSearch() {
    clearTimeout(searchTimeout);
    const searchValue = searchInput.value;
    const kelasValue = kelasSelect.value;
    const container = document.getElementById('mahasiswaContainer');
    
    searchTimeout = setTimeout(function() {
        fetch(`index.php?page=admin_mahasiswa&ajax_search=1&search=${encodeURIComponent(searchValue)}&kelas=${encodeURIComponent(kelasValue)}`)
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
                // Re-apply selection state
                selectedItems.forEach(id => {
                    const cb = container.querySelector(`.item-checkbox[value="${id}"]`); if(cb) cb.checked=true;
                    const card = document.getElementById('card-'+id); if(card) card.classList.add('selected');
                });
                if(isSelectMode) container.classList.add('select-mode');
            })
            .catch(error => console.error('Error:', error));
    }, 300);
}

searchInput.addEventListener('input', performSearch);
kelasSelect.addEventListener('change', performSearch);
</script>

<?php include 'includes/footer.php'; ?>
