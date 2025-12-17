<?php
$page = 'admin_jadwal';

// Fungsi untuk cek konflik jadwal lab
function cekKonflikLab($conn, $tanggal, $jam_mulai, $jam_selesai, $kode_lab, $exclude_id = null) {
    if (empty($kode_lab)) return false; // Jika tidak ada lab, tidak perlu cek
    
    $exclude_sql = $exclude_id ? "AND j.id != '$exclude_id'" : "";
    
    $query = "SELECT j.*, k.nama_kelas, l.nama_lab 
              FROM jadwal j 
              LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
              LEFT JOIN lab l ON j.kode_lab = l.kode_lab
              WHERE j.kode_lab = '$kode_lab' 
              AND j.tanggal = '$tanggal' 
              AND j.jam_mulai < '$jam_selesai' 
              AND j.jam_selesai > '$jam_mulai'
              $exclude_sql
              LIMIT 1";
    
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = $_POST['aksi'];
    
    if ($aksi == 'tambah') {
        $pertemuan = (int)$_POST['pertemuan_ke'];
        $tanggal = escape($_POST['tanggal']);
        $jam_mulai = escape($_POST['jam_mulai']);
        $jam_selesai = escape($_POST['jam_selesai']);
        $lab = escape($_POST['kode_lab']);
        $kelas = escape($_POST['kode_kelas']);
        $mk = escape($_POST['kode_mk']);
        $materi = escape($_POST['materi']);
        $jenis = escape($_POST['jenis']);
        $asisten1 = escape($_POST['kode_asisten_1']) ?: null;
        $asisten2 = escape($_POST['kode_asisten_2']) ?: null;
        
        $konflik = cekKonflikLab($conn, $tanggal, $jam_mulai, $jam_selesai, $lab);
        
        if ($konflik) {
            set_alert('danger', 'Konflik jadwal! Lab <strong>' . htmlspecialchars($konflik['nama_lab']) . '</strong> sudah digunakan oleh kelas <strong>' . htmlspecialchars($konflik['nama_kelas']) . '</strong> pada tanggal ' . format_tanggal($konflik['tanggal']) . ' pukul ' . $konflik['jam_mulai'] . ' - ' . $konflik['jam_selesai']);
        } else {
            $lab_sql = $lab ? "'$lab'" : "NULL";
            $ast1_sql = $asisten1 ? "'$asisten1'" : "NULL";
            $ast2_sql = $asisten2 ? "'$asisten2'" : "NULL";
            
            mysqli_query($conn, "INSERT INTO jadwal (pertemuan_ke, tanggal, jam_mulai, jam_selesai, kode_lab, kode_kelas, kode_mk, materi, jenis, kode_asisten_1, kode_asisten_2) 
                                 VALUES ('$pertemuan', '$tanggal', '$jam_mulai', '$jam_selesai', $lab_sql, '$kelas', '$mk', '$materi', '$jenis', $ast1_sql, $ast2_sql)");
            set_alert('success', 'Jadwal berhasil ditambahkan!');
        }
    } elseif ($aksi == 'edit') {
        $id = (int)$_POST['id'];
        $pertemuan = (int)$_POST['pertemuan_ke'];
        $tanggal = escape($_POST['tanggal']);
        $jam_mulai = escape($_POST['jam_mulai']);
        $jam_selesai = escape($_POST['jam_selesai']);
        $lab = escape($_POST['kode_lab']);
        $kelas = escape($_POST['kode_kelas']);
        $mk = escape($_POST['kode_mk']);
        $materi = escape($_POST['materi']);
        $jenis = escape($_POST['jenis']);
        $asisten1 = escape($_POST['kode_asisten_1']) ?: null;
        $asisten2 = escape($_POST['kode_asisten_2']) ?: null;
        
        $konflik = cekKonflikLab($conn, $tanggal, $jam_mulai, $jam_selesai, $lab, $id);
        
        if ($konflik) {
            set_alert('danger', 'Konflik jadwal! Lab <strong>' . htmlspecialchars($konflik['nama_lab']) . '</strong> sudah digunakan oleh kelas <strong>' . htmlspecialchars($konflik['nama_kelas']) . '</strong> pada tanggal ' . format_tanggal($konflik['tanggal']) . ' pukul ' . $konflik['jam_mulai'] . ' - ' . $konflik['jam_selesai']);
        } else {
            $lab_sql = $lab ? "'$lab'" : "NULL";
            $ast1_sql = $asisten1 ? "'$asisten1'" : "NULL";
            $ast2_sql = $asisten2 ? "'$asisten2'" : "NULL";
            
            mysqli_query($conn, "UPDATE jadwal SET pertemuan_ke='$pertemuan', tanggal='$tanggal', jam_mulai='$jam_mulai', 
                                 jam_selesai='$jam_selesai', kode_lab=$lab_sql, kode_kelas='$kelas', kode_mk='$mk', 
                                 materi='$materi', jenis='$jenis', kode_asisten_1=$ast1_sql, kode_asisten_2=$ast2_sql WHERE id='$id'");
            set_alert('success', 'Jadwal berhasil diupdate!');
        }
    } elseif ($aksi == 'hapus') {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "DELETE FROM jadwal WHERE id = '$id'");
        set_alert('success', 'Jadwal berhasil dihapus!');
    } elseif ($aksi == 'generate') {
        $kelas = escape($_POST['kode_kelas']);
        $mk = escape($_POST['kode_mk']);
        $tanggal_mulai = escape($_POST['tanggal_mulai']);
        $jam_mulai = escape($_POST['jam_mulai']);
        $jam_selesai = escape($_POST['jam_selesai']);
        $asisten1 = escape($_POST['kode_asisten_1']) ?: null;
        $asisten2 = escape($_POST['kode_asisten_2']) ?: null;
        $hari = (int)$_POST['hari'];
        $rotasi_offset = isset($_POST['rotasi_offset']) ? (int)$_POST['rotasi_offset'] : 0;
        
        // Ambil daftar lab aktif yang sesuai dengan mata kuliah yang dipilih (SUDAH DIPERBAIKI)
        $labs = [];
        $lab_query_sql = "
            SELECT l.kode_lab, l.nama_lab 
            FROM lab l
            JOIN lab_matakuliah lm ON l.id = lm.id_lab
            WHERE l.status = 'active' AND lm.kode_mk = '$mk' 
            ORDER BY l.kode_lab";
        $lab_query = mysqli_query($conn, $lab_query_sql);
        while ($l = mysqli_fetch_assoc($lab_query)) {
            $labs[] = $l['kode_lab'];
        }
        
        $mk_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_mk FROM mata_kuliah WHERE kode_mk = '$mk'"));
        $nama_mk = $mk_info ? $mk_info['nama_mk'] : $mk;
        
        if (empty($labs)) {
            set_alert('danger', 'Tidak ada lab aktif untuk mata kuliah <strong>' . htmlspecialchars($nama_mk) . '</strong>! Silakan set mata kuliah di menu Kelola Lab terlebih dahulu.');
        } else {
            mysqli_begin_transaction($conn);
            try {
                $ast1_sql = $asisten1 ? "'$asisten1'" : "NULL";
                $ast2_sql = $asisten2 ? "'$asisten2'" : "NULL";
                $lab_count = count($labs);
                $tanggal = strtotime($tanggal_mulai);
                    
                // while (date('w', $tanggal) != $hari) {
                //     $tanggal = strtotime('+1 day', $tanggal);
                // }
                
                $materi_list = [ 'Pertemuan 1 - Pengenalan', 'Pertemuan 2 - Dasar', 'Pertemuan 3 - Lanjutan I', 'Pertemuan 4 - Lanjutan II', 'Pertemuan 5 - Praktik I', 'Pertemuan 6 - Praktik II', 'Pertemuan 7 - Praktik III', 'Pertemuan 8 - Review', 'Praresponsi', 'Inhall', 'Responsi' ];
                
                $konflik_list = [];
                $jadwal_temp = [];
                
                $temp_tanggal = $tanggal;
                for ($i = 1; $i <= 11; $i++) {
                    $is_inhall = ($i == 10);
                    $pertemuan_ke = ($i <= 9) ? $i : ($is_inhall ? 9 : 10);
                    $current_jam_mulai = $jam_mulai;
                    $current_jam_selesai = $jam_selesai;
                
                    if (!$is_inhall) {
                        $tgl_str = date('Y-m-d', $temp_tanggal);
                    } else {
                        $tgl_str = $jadwal_temp[8]['tanggal'];
                        $praresponsi_schedule = $jadwal_temp[8];
                        $praresponsi_end_time = strtotime($praresponsi_schedule['jam_selesai']);
                        $duration = $praresponsi_end_time - strtotime($praresponsi_schedule['jam_mulai']);
                        $current_jam_mulai = date('H:i:s', $praresponsi_end_time);
                        $current_jam_selesai = date('H:i:s', $praresponsi_end_time + $duration);
                    }
                
                    $kode_lab = null;
                    $p_label = $is_inhall ? "Inhall (P9)" : "Pertemuan $pertemuan_ke";

                    if ($is_inhall) {
                        // Untuk INHALL, lab harus sama dengan PRARESPONSI.
                        // Praresponsi adalah jadwal ke-9 yang di-generate (indeks array 8).
                        if (isset($jadwal_temp[8]) && $jadwal_temp[8]['kode_lab']) {
                            $kode_lab = $jadwal_temp[8]['kode_lab'];

                            // Tetap cek konflik waktu, seandainya ada jadwal kelas lain yang masuk di antara praresponsi dan inhall
                            $konflik_db = cekKonflikLab($conn, $tgl_str, $current_jam_mulai, $current_jam_selesai, $kode_lab);
                            if ($konflik_db) {
                                $konflik_list[] = "$p_label (" . format_tanggal($tgl_str) . ") - Lab " . htmlspecialchars($konflik_db['nama_lab']) . " (mengikuti Praresponsi) bentrok dengan jadwal kelas " . htmlspecialchars($konflik_db['nama_kelas']);
                                continue;
                            }
                        } else {
                            // Ini seharusnya tidak terjadi jika alur normal, tapi sebagai pengaman
                            $konflik_list[] = "$p_label - Gagal menemukan jadwal Praresponsi / lab Praresponsi tidak diset. Tidak dapat menyamakan lab.";
                            continue;
                        }
                    } else {
                        // Untuk semua pertemuan lain (termasuk PRARESPONSI), cari lab yang tersedia
                        $lab_ditemukan = false;
                        if ($lab_count > 0) {
                            for ($j = 0; $j < $lab_count; $j++) {
                                $lab_index_coba = ($pertemuan_ke - 1 + $rotasi_offset + $j) % $lab_count;
                                $lab_coba = $labs[$lab_index_coba];

                                // Cek konflik dengan jadwal lain yang sudah ada di database
                                $konflik_db = cekKonflikLab($conn, $tgl_str, $current_jam_mulai, $current_jam_selesai, $lab_coba);
                                if (!$konflik_db) {
                                    $kode_lab = $lab_coba;
                                    $lab_ditemukan = true;
                                    break;
                                }
                            }
                        }

                        if (!$lab_ditemukan) {
                            $konflik_list[] = "$p_label (" . format_tanggal($tgl_str) . ") - Semua lab untuk mata kuliah ini penuh pada jam tersebut.";
                            continue;
                        }
                    }
                
                    $jenis = ($pertemuan_ke <= 8) ? 'materi' : (($pertemuan_ke == 9) ? ($is_inhall ? 'inhall' : 'praresponsi') : 'responsi');
                    $materi = $materi_list[$i - 1];
                
                    $jadwal_temp[] = [ 'pertemuan' => $pertemuan_ke, 'tanggal' => $tgl_str, 'jam_mulai' => $current_jam_mulai, 'jam_selesai' => $current_jam_selesai, 'kode_lab' => $kode_lab, 'jenis' => $jenis, 'materi' => $materi ];
                
                    if (!$is_inhall) {
                        $temp_tanggal = strtotime('+1 week', $temp_tanggal);
                    }
                }
                
                if (!empty($konflik_list)) {
                    throw new Exception('Tidak dapat generate jadwal karena ada konflik:<br>- ' . implode('<br>- ', array_unique($konflik_list)));
                }
                
                foreach ($jadwal_temp as $jt) {
                    $q = mysqli_query($conn, "INSERT INTO jadwal (pertemuan_ke, tanggal, jam_mulai, jam_selesai, kode_lab, kode_kelas, kode_mk, materi, jenis, kode_asisten_1, kode_asisten_2) 
                                         VALUES ('{$jt['pertemuan']}', '{$jt['tanggal']}', '{$jt['jam_mulai']}', '{$jt['jam_selesai']}', '{$jt['kode_lab']}', '$kelas', '$mk', '{$jt['materi']}', '{$jt['jenis']}', $ast1_sql, $ast2_sql)");
                    if (!$q) throw new Exception("Gagal menyimpan jadwal ke database.");
                }
                
                mysqli_commit($conn);
                set_alert('success', 'Berhasil generate jadwal! Praresponsi dan Inhall pada pertemuan 9 telah dibuat.');

            } catch (Exception $e) {
                mysqli_rollback($conn);
                set_alert('danger', $e->getMessage());
            }
        }
    }
    
    header("Location: index.php?page=admin_jadwal");
    exit;
}

// Filter
$filter_kelas = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';
$filter_tanggal = isset($_GET['tanggal']) ? escape($_GET['tanggal']) : '';
$where = [];
if ($filter_kelas) $where[] = "j.kode_kelas = '$filter_kelas'";
if ($filter_tanggal) $where[] = "j.tanggal = '$filter_tanggal'";
$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : '';

$jadwal = mysqli_query($conn, "SELECT j.*, k.nama_kelas, l.nama_lab, mk.nama_mk, 
                                a1.nama as asisten1_nama, a2.nama as asisten2_nama
                                FROM jadwal j 
                                LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                LEFT JOIN asisten a1 ON j.kode_asisten_1 = a1.kode_asisten
                                LEFT JOIN asisten a2 ON j.kode_asisten_2 = a2.kode_asisten
                                $where_sql
                                ORDER BY j.pertemuan_ke, j.kode_kelas, j.tanggal, j.jam_mulai");

$jadwal_grouped = [];
while ($row = mysqli_fetch_assoc($jadwal)) {
    $pertemuan = $row['pertemuan_ke'];
    if (!isset($jadwal_grouped[$pertemuan])) $jadwal_grouped[$pertemuan] = [];
    $jadwal_grouped[$pertemuan][] = $row;
}

$kelas_list = mysqli_query($conn, "SELECT * FROM kelas ORDER BY kode_kelas");
$mk_list = mysqli_query($conn, "SELECT * FROM mata_kuliah ORDER BY kode_mk");
$asisten_list = mysqli_query($conn, "SELECT a.*, mk.nama_mk as mata_kuliah_diampu 
                                      FROM asisten a 
                                      LEFT JOIN mata_kuliah mk ON a.kode_mk = mk.kode_mk 
                                      WHERE a.status = 'aktif' 
                                      ORDER BY a.nama");

$mhs_per_kelas = [];
$result_mhs = mysqli_query($conn, "SELECT kode_kelas, COUNT(*) as jumlah FROM mahasiswa GROUP BY kode_kelas");
while ($row = mysqli_fetch_assoc($result_mhs)) {
    $mhs_per_kelas[$row['kode_kelas']] = (int)$row['jumlah'];
}

// Buat array lab untuk JavaScript (filter berdasarkan mata kuliah) (SUDAH DIPERBAIKI)
$lab_array = [];
$lab_list_query = mysqli_query($conn, "
    SELECT l.kode_lab, l.nama_lab, GROUP_CONCAT(lm.kode_mk) as associated_mks
    FROM lab l
    LEFT JOIN lab_matakuliah lm ON l.id = lm.id_lab
    WHERE l.status = 'active'
    GROUP BY l.id
    ORDER BY l.kode_lab");
if ($lab_list_query) {
    while ($lab = mysqli_fetch_assoc($lab_list_query)) {
        $lab_array[] = [
            'kode_lab' => $lab['kode_lab'],
            'nama_lab' => $lab['nama_lab'],
            'associated_mks' => $lab['associated_mks'] ? explode(',', $lab['associated_mks']) : []
        ];
    }
}
?>
<?php include 'includes/header.php'; ?>

<style>
/* Custom responsive styles for Jadwal page */
@media (max-width: 767.98px) {
    .card.mb-2 .card-body .d-flex.justify-content-between { flex-wrap: wrap; gap: 0.75rem; }
    .card.mb-2 .btn-group-vertical { flex-basis: 100%; flex-direction: row; justify-content: flex-end; }
    .card.mb-2 .btn-group-vertical .btn { flex: 1 1 auto; }
}
@media (max-width: 575.98px) {
    .content-wrapper.p-4 { padding: 1.5rem 1rem !important; }
    .d-flex.flex-column.flex-md-row .d-grid { display: flex !important; flex-direction: column; gap: .5rem !important; }
    .card-body form.row > .col-5, .card-body form.row > .col-2 { flex: 0 0 100%; max-width: 100%; }
    .card-body form.row > .col-2 .btn { margin-top: 0.5rem; }
    .pertemuan-header .d-flex { flex-direction: column; align-items: flex-start !important; }
    .card.mb-2 .btn-group-vertical { justify-content: stretch; }
}
.modal-header {
    background: var(--banner-gradient);
    color: #fff;
}
/* Dark Mode Fixes for Jadwal */
[data-theme="dark"] .pertemuan-header { border-top-color: var(--border-color) !important; }
[data-theme="dark"] .card.mb-2.border { border-color: var(--border-color) !important; }

/* Fix for bg-light badges in dark mode */
[data-theme="dark"] .badge.bg-light {
    background-color: rgba(255,255,255,0.1) !important;
    color: var(--text-main) !important;
    border: 1px solid var(--border-color);
}

/* Dark Mode Table Fixes */
[data-theme="dark"] .table {
    color: var(--text-main);
    border-color: var(--border-color);
}
[data-theme="dark"] .table-light th {
    background-color: var(--bg-body) !important;
    color: var(--text-main) !important;
    border-color: var(--border-color) !important;
}

/* Mobile Card Content Fixes for Dark Mode */
[data-theme="dark"] .card h6 {
    color: var(--text-main);
}
[data-theme="dark"] .card .small,
[data-theme="dark"] .card small {
    color: var(--text-muted);
}
[data-theme="dark"] .btn-warning.text-dark {
    color: #212529 !important;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar_admin.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 mb-4 pt-2">
                    <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Kelola Jadwal</h4>
                    <div class="d-grid d-md-flex gap-2">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalGenerate">
                            <i class="fas fa-magic me-1"></i>Generate Rolling
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                            <i class="fas fa-plus me-1"></i>Tambah Manual
                        </button>
                    </div>
                </div>
                
                <?= show_alert() ?>
                
                <div class="card mb-4">
                    <div class="card-body p-2 p-md-3">
                        <form method="GET" class="row g-2 g-md-3 align-items-end">
                            <input type="hidden" name="page" value="admin_jadwal">
                            <div class="col-5 col-md-3">
                                <label class="form-label small">Kelas</label>
                                <select name="kelas" class="form-select form-select-sm">
                                    <option value="">Semua Kelas</option>
                                    <?php mysqli_data_seek($kelas_list, 0); while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                        <option value="<?= $k['kode_kelas'] ?>" <?= $filter_kelas == $k['kode_kelas'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-5 col-md-3">
                                <label class="form-label small">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_tanggal) ?>">
                            </div>
                            <div class="col-2 col-md-auto">
                                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter"></i><span class="d-none d-md-inline ms-1">Filter</span></button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($jadwal_grouped)): ?>
                            <div class="text-center text-muted py-4"><i class="fas fa-calendar-times fa-3x mb-3"></i><p>Belum ada jadwal.</p></div>
                        <?php else: ?>
                            <?php foreach ($jadwal_grouped as $pertemuan => $jadwal_list): ?>
                                <div class="pertemuan-header mb-3 <?= $pertemuan > 1 ? 'mt-4 pt-3 border-top' : '' ?>">
                                    <?php
                                    $first_jenis = $jadwal_list[0]['jenis'] ?? 'materi';
                                    if ($first_jenis == 'inhall') { $h_color = 'warning'; $h_icon = 'sync-alt'; $j_label = 'INHALL'; } 
                                    elseif ($first_jenis == 'praresponsi') { $h_color = 'info'; $h_icon = 'tasks'; $j_label = 'PRARESPONSI'; }
                                    elseif ($first_jenis == 'responsi') { $h_color = 'danger'; $h_icon = 'file-alt'; $j_label = 'RESPONSI'; }
                                    else { $h_color = 'primary'; $h_icon = 'book'; $j_label = 'MATERI'; }
                                    ?>
                                    <span class="badge bg-<?= $h_color ?> fs-6 py-2 px-3"><i class="fas fa-<?= $h_icon ?> me-1"></i> Pertemuan <?= $pertemuan ?></span>
                                    <small class="text-muted"><span class="badge bg-light text-dark"><?= $j_label ?></span> &mdash; <?= count($jadwal_list) ?> kelas</small>
                                </div>
                                
                                <div class="table-responsive mb-3 d-none d-lg-block">
                                    <table class="table table-hover table-sm table-bordered">
                                        <thead class="table-light"><tr><th>Kelas</th><th>Tanggal</th><th>Waktu</th><th>Lab</th><th>Mata Kuliah</th><th>Materi</th><th>Asisten</th><th style="width: 150px;">Aksi</th></tr></thead>
                                        <tbody>
                                            <?php foreach ($jadwal_list as $j): ?>
                                                <tr>
                                                    <td><span class="badge bg-primary"><?= htmlspecialchars($j['nama_kelas']) ?></span></td>
                                                    <td><?= format_tanggal($j['tanggal']) ?></td>
                                                    <td><?= format_waktu($j['jam_mulai']) ?> - <?= format_waktu($j['jam_selesai']) ?></td>
                                                    <td><?= htmlspecialchars($j['nama_lab'] ?: '-') ?></td>
                                                    <td><?= htmlspecialchars($j['nama_mk']) ?></td>
                                                    <td><?= htmlspecialchars($j['materi']) ?></td>
                                                    <td><?= htmlspecialchars($j['asisten1_nama'] ?: '-') ?><?= $j['asisten2_nama'] ? ', ' . htmlspecialchars($j['asisten2_nama']) : '' ?></td>
                                                    <td>
                                                        <div class="d-flex gap-1 justify-content-center">
                                                            <a href="index.php?page=admin_materi&jadwal=<?= $j['id'] ?>" class="btn btn-sm btn-info text-white" title="Kelola Materi"><i class="fas fa-book"></i></a>
                                                            <button class="btn btn-sm btn-warning text-dark" onclick='editJadwal(<?= json_encode($j) ?>)' title="Edit"><i class="fas fa-edit"></i></button>
                                                            <button class="btn btn-sm btn-danger" onclick="hapusJadwal(<?= $j['id'] ?>)" title="Hapus"><i class="fas fa-trash"></i></button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="d-lg-none mb-3">
                                    <?php foreach ($jadwal_list as $j): ?>
                                        <div class="card mb-2 border"><div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <span class="badge bg-primary"><?= htmlspecialchars($j['nama_kelas']) ?></span><small class="text-muted"><?= format_tanggal($j['tanggal']) ?></small>
                                                    </div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($j['nama_mk']) ?></h6>
                                                    <div class="small text-muted mb-1"><?= htmlspecialchars($j['materi']) ?></div>
                                                    <div class="small">
                                                        <i class="fas fa-clock me-1 text-primary"></i><?= format_waktu($j['jam_mulai']) ?> - <?= format_waktu($j['jam_selesai']) ?><br>
                                                        <i class="fas fa-map-marker-alt me-1 text-danger"></i><?= htmlspecialchars($j['nama_lab'] ?: '-') ?><br>
                                                        <i class="fas fa-user me-1 text-success"></i><?= htmlspecialchars($j['asisten1_nama'] ?: '-') ?><?= $j['asisten2_nama'] ? ', ' . htmlspecialchars($j['asisten2_nama']) : '' ?>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <a href="index.php?page=admin_materi&jadwal=<?= $j['id'] ?>" class="btn btn-sm btn-info text-white"><i class="fas fa-book"></i></a>
                                                    <button class="btn btn-sm btn-warning text-dark" onclick='editJadwal(<?= json_encode($j) ?>)'><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-sm btn-danger" onclick="hapusJadwal(<?= $j['id'] ?>)"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </div>
                                        </div></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit/Generate/Hapus -->
<div class="modal fade" id="modalTambah" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST"><input type="hidden" name="aksi" value="tambah"><div class="modal-header"><h5 class="modal-title">Tambah Jadwal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Pertemuan Ke</label><select name="pertemuan_ke" class="form-select" required><?php for($i=1;$i<=10;$i++):?><option value="<?=$i?>"><?=$i?></option><?php endfor;?></select></div>
    <div class="col-md-6 mb-3"><label class="form-label">Jenis</label><select name="jenis" class="form-select" required><option value="materi">Materi</option><option value="inhall">Inhall</option><option value="praresponsi">Praresponsi</option><option value="responsi">Responsi</option></select></div>
    <div class="col-md-4 mb-3"><label class="form-label">Tanggal</label><input type="date" name="tanggal" class="form-control" required></div>
    <div class="col-md-4 mb-3"><label class="form-label">Jam Mulai</label><input type="time" name="jam_mulai" class="form-control" required></div>
    <div class="col-md-4 mb-3"><label class="form-label">Jam Selesai</label><input type="time" name="jam_selesai" class="form-control" required></div>
    <div class="col-md-4 mb-3"><label class="form-label">Kelas</label><select name="kode_kelas" class="form-select" required onchange="checkAsisten2Warning(this, 'warning_asisten2_tambah')"><?php mysqli_data_seek($kelas_list,0);while($k=mysqli_fetch_assoc($kelas_list)):?><option value="<?=$k['kode_kelas']?>"><?=htmlspecialchars($k['nama_kelas'])?></option><?php endwhile;?></select></div>
    <div class="col-md-4 mb-3"><label class="form-label">Mata Kuliah</label><select name="kode_mk" id="tambah_mk" class="form-select" required onchange="filterLabTambah()"><option value="">-- Pilih --</option><?php mysqli_data_seek($mk_list,0);while($m=mysqli_fetch_assoc($mk_list)):?><option value="<?=$m['kode_mk']?>"><?=htmlspecialchars($m['nama_mk'])?></option><?php endwhile;?></select></div>
    <div class="col-md-4 mb-3"><label class="form-label">Lab</label><select name="kode_lab" id="tambah_lab" class="form-select"><option value="">-- Pilih MK dulu --</option></select><small class="text-muted">Otomatis filter lab sesuai MK</small></div>
    <div class="col-md-12 mb-3"><label class="form-label">Materi</label><input type="text" name="materi" class="form-control" required></div>
    <div class="col-md-6 mb-3"><label class="form-label">Asisten 1</label><select name="kode_asisten_1" class="form-select"><option value="">-- Pilih --</option><?php mysqli_data_seek($asisten_list,0);while($a=mysqli_fetch_assoc($asisten_list)):?><option value="<?=$a['kode_asisten']?>"><?=htmlspecialchars($a['nama'])?><?= $a['mata_kuliah_diampu'] ? ' (Ahli: ' . htmlspecialchars($a['mata_kuliah_diampu']) . ')' : '' ?></option><?php endwhile;?></select></div>
    <div class="col-md-6 mb-3"><label class="form-label">Asisten 2 (Opsional)</label><select name="kode_asisten_2" class="form-select"><option value="">-- Tidak Ada --</option><?php mysqli_data_seek($asisten_list,0);while($a=mysqli_fetch_assoc($asisten_list)):?><option value="<?=$a['kode_asisten']?>"><?=htmlspecialchars($a['nama'])?><?= $a['mata_kuliah_diampu'] ? ' (Ahli: ' . htmlspecialchars($a['mata_kuliah_diampu']) . ')' : '' ?></option><?php endwhile;?></select></div>
    <div class="col-12"><div id="warning_asisten2_tambah" style="display: none;"></div></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
</form></div></div></div>

<div class="modal fade" id="modalEdit" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST"><input type="hidden" name="aksi" value="edit"><input type="hidden" name="id" id="edit_id"><div class="modal-header"><h5 class="modal-title">Edit Jadwal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Pertemuan Ke</label><select name="pertemuan_ke" id="edit_pertemuan" class="form-select" required><?php for($i=1;$i<=10;$i++):?><option value="<?=$i?>"><?=$i?></option><?php endfor;?></select></div>
    <div class="col-md-6 mb-3"><label class="form-label">Jenis</label><select name="jenis" id="edit_jenis" class="form-select" required><option value="materi">Materi</option><option value="inhall">Inhall</option><option value="praresponsi">Praresponsi</option><option value="responsi">Responsi</option></select></div>
    <div class="col-md-4 mb-3"><label class="form-label">Tanggal</label><input type="date" name="tanggal" id="edit_tanggal" class="form-control" required></div>
    <div class="col-md-4 mb-3"><label class="form-label">Jam Mulai</label><input type="time" name="jam_mulai" id="edit_jam_mulai" class="form-control" required></div>
    <div class="col-md-4 mb-3"><label class="form-label">Jam Selesai</label><input type="time" name="jam_selesai" id="edit_jam_selesai" class="form-control" required></div>
    <div class="col-md-4 mb-3"><label class="form-label">Kelas</label><select name="kode_kelas" id="edit_kelas" class="form-select" required onchange="checkAsisten2Warning(this, 'warning_asisten2_edit')"><?php mysqli_data_seek($kelas_list,0);while($k=mysqli_fetch_assoc($kelas_list)):?><option value="<?=$k['kode_kelas']?>"><?=htmlspecialchars($k['nama_kelas'])?></option><?php endwhile;?></select></div>
    <div class="col-md-4 mb-3"><label class="form-label">Mata Kuliah</label><select name="kode_mk" id="edit_mk" class="form-select" required onchange="filterLabEdit()"><?php mysqli_data_seek($mk_list,0);while($m=mysqli_fetch_assoc($mk_list)):?><option value="<?=$m['kode_mk']?>"><?=htmlspecialchars($m['nama_mk'])?></option><?php endwhile;?></select></div>
    <div class="col-md-4 mb-3"><label class="form-label">Lab</label><select name="kode_lab" id="edit_lab" class="form-select"><option value="">-- Pilih Lab --</option></select><small class="text-muted">Otomatis filter lab sesuai MK</small></div>
    <div class="col-md-12 mb-3"><label class="form-label">Materi</label><input type="text" name="materi" id="edit_materi" class="form-control" required></div>
    <div class="col-md-6 mb-3"><label class="form-label">Asisten 1</label><select name="kode_asisten_1" id="edit_asisten1" class="form-select"><option value="">-- Pilih --</option><?php mysqli_data_seek($asisten_list,0);while($a=mysqli_fetch_assoc($asisten_list)):?><option value="<?=$a['kode_asisten']?>"><?=htmlspecialchars($a['nama'])?><?= $a['mata_kuliah_diampu'] ? ' (Ahli: ' . htmlspecialchars($a['mata_kuliah_diampu']) . ')' : '' ?></option><?php endwhile;?></select></div>
    <div class="col-md-6 mb-3"><label class="form-label">Asisten 2 (Opsional)</label><select name="kode_asisten_2" id="edit_asisten2" class="form-select"><option value="">-- Tidak Ada --</option><?php mysqli_data_seek($asisten_list,0);while($a=mysqli_fetch_assoc($asisten_list)):?><option value="<?=$a['kode_asisten']?>"><?=htmlspecialchars($a['nama'])?><?= $a['mata_kuliah_diampu'] ? ' (Ahli: ' . htmlspecialchars($a['mata_kuliah_diampu']) . ')' : '' ?></option><?php endwhile;?></select></div>
    <div class="col-12"><div id="warning_asisten2_edit" style="display: none;"></div></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Update</button></div>
</form></div></div></div>

<form id="formHapus" method="POST" style="display:none;"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" id="hapus_id"></form>

<div class="modal fade" id="modalGenerate" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="aksi" value="generate"><div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-magic me-2"></i>Generate Jadwal Rolling</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><strong>Fitur ini akan:</strong><ul class="mb-0 mt-2"><li>Generate <strong>10 pertemuan</strong> (8 materi + Praresponsi + Responsi) & <strong>Inhall</strong></li><li>Lab akan <strong>rolling/berputar</strong> setiap pertemuan</li><li>Jadwal mingguan pada hari yang dipilih</li></ul></div>
    <div class="mb-3"><label class="form-label">Kelas <span class="text-danger">*</span></label><select name="kode_kelas" class="form-select" required><option value="">-- Pilih --</option><?php mysqli_data_seek($kelas_list, 0); while ($k = mysqli_fetch_assoc($kelas_list)): ?><option value="<?= $k['kode_kelas'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option><?php endwhile; ?></select></div>
    <div class="mb-3"><label class="form-label">Mata Kuliah <span class="text-danger">*</span></label><select name="kode_mk" class="form-select" required><option value="">-- Pilih --</option><?php mysqli_data_seek($mk_list, 0); while ($mk = mysqli_fetch_assoc($mk_list)): ?><option value="<?= $mk['kode_mk'] ?>"><?= htmlspecialchars($mk['nama_mk']) ?></option><?php endwhile; ?></select></div>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Asisten 1</label><select name="kode_asisten_1" class="form-select"><option value="">-- Pilih --</option><?php mysqli_data_seek($asisten_list, 0); while ($a = mysqli_fetch_assoc($asisten_list)): ?><option value="<?= $a['kode_asisten'] ?>"><?= htmlspecialchars($a['nama']) ?></option><?php endwhile; ?></select></div>
        <div class="col-md-6 mb-3"><label class="form-label">Asisten 2 (Opsional)</label><select name="kode_asisten_2" class="form-select"><option value="">-- Tidak Ada --</option><?php mysqli_data_seek($asisten_list, 0); while ($a = mysqli_fetch_assoc($asisten_list)): ?><option value="<?= $a['kode_asisten'] ?>"><?= htmlspecialchars($a['nama']) ?></option><?php endwhile; ?></select></div>
    </div>
    <div id="warning_asisten2_generate" style="display: none;" class="mb-3"></div>
    <div class="mb-3"><label class="form-label">Urutan Rotasi Lab (Offset)</label><input type="number" name="rotasi_offset" class="form-control" value="0" min="0"><small class="text-muted">0 = Mulai Lab ke-1, 1 = Mulai Lab ke-2. Gunakan angka berbeda untuk kelas yang jadwalnya bentrok.</small></div>
    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Hari Praktikum <span class="text-danger">*</span></label><select name="hari" class="form-select" required><option value="1">Senin</option><option value="2">Selasa</option><option value="3">Rabu</option><option value="4">Kamis</option><option value="5">Jumat</option><option value="6">Sabtu</option></select></div><div class="col-md-6 mb-3"><label class="form-label">Mulai Tanggal <span class="text-danger">*</span></label><input type="date" name="tanggal_mulai" class="form-control" required value="<?= date('Y-m-d') ?>"></div></div>
    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Jam Mulai <span class="text-danger">*</span></label><input type="time" name="jam_mulai" class="form-control" required value="08:00"></div><div class="col-md-6 mb-3"><label class="form-label">Jam Selesai <span class="text-danger">*</span></label><input type="time" name="jam_selesai" class="form-control" required value="10:00"></div></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success"><i class="fas fa-magic me-2"></i>Generate Jadwal</button></div>
</form></div></div></div>

<script>
const labData = <?= json_encode($lab_array) ?>;
const mahasiswaPerKelas = <?= json_encode($mhs_per_kelas) ?>;
const BATAS_ASISTEN2 = 20;

function filterLab(mkSelectId, labSelectId, selectedLabValue = '') {
    const mkSelect = document.getElementById(mkSelectId);
    const labSelect = document.getElementById(labSelectId);
    const selectedMk = mkSelect.value;
    
    labSelect.innerHTML = '<option value="">-- Pilih Lab --</option>';
    if (!selectedMk) {
        labSelect.innerHTML = '<option value="">-- Pilih MK dulu --</option>';
        return;
    }
    
    const filteredLabs = labData.filter(lab => lab.associated_mks.includes(selectedMk));
    
    if (filteredLabs.length === 0) {
        labSelect.innerHTML = '<option value="">-- Tidak ada lab untuk MK ini --</option>';
    } else {
        filteredLabs.forEach(lab => {
            const option = document.createElement('option');
            option.value = lab.kode_lab;
            option.textContent = lab.nama_lab;
            if (lab.kode_lab === selectedLabValue) {
                option.selected = true;
            }
            labSelect.appendChild(option);
        });
    }
}

function filterLabTambah() { filterLab('tambah_mk', 'tambah_lab'); }
function filterLabEdit(lab) { filterLab('edit_mk', 'edit_lab', lab); }

function editJadwal(j) {
    document.getElementById('edit_id').value = j.id;
    document.getElementById('edit_pertemuan').value = j.pertemuan_ke;
    document.getElementById('edit_jenis').value = j.jenis;
    document.getElementById('edit_tanggal').value = j.tanggal;
    document.getElementById('edit_jam_mulai').value = j.jam_mulai;
    document.getElementById('edit_jam_selesai').value = j.jam_selesai;
    document.getElementById('edit_kelas').value = j.kode_kelas;
    document.getElementById('edit_mk').value = j.kode_mk;
    filterLabEdit(j.kode_lab || '');
    document.getElementById('edit_materi').value = j.materi;
    document.getElementById('edit_asisten1').value = j.kode_asisten_1 || '';
    document.getElementById('edit_asisten2').value = j.kode_asisten_2 || '';
    checkAsisten2Warning(document.getElementById('edit_kelas'), 'warning_asisten2_edit');
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function hapusJadwal(id) {
    if (confirm('Yakin ingin menghapus jadwal ini?')) {
        document.getElementById('hapus_id').value = id;
        document.getElementById('formHapus').submit();
    }
}

function checkAsisten2Warning(kelasSelect, warningElementId) {
    const kodeKelas = kelasSelect.value;
    const warningEl = document.getElementById(warningElementId);
    const jumlahMhs = mahasiswaPerKelas[kodeKelas] || 0;
    
    if (jumlahMhs > BATAS_ASISTEN2) {
        warningEl.innerHTML = `<div class="alert alert-warning py-2 mb-0"><i class="fas fa-exclamation-triangle me-2"></i><strong>Perhatian!</strong> Kelas ini memiliki <strong>${jumlahMhs} mahasiswa</strong>. Disarankan menambah <strong>Asisten 2</strong>.</div>`;
        warningEl.style.display = 'block';
    } else {
        warningEl.style.display = 'none';
    }
}

function validateAsisten2(formElement) {
    const kelasSelect = formElement.querySelector('select[name="kode_kelas"]');
    const asisten2Select = formElement.querySelector('select[name="kode_asisten_2"]');
    
    if (kelasSelect && asisten2Select) {
        const kodeKelas = kelasSelect.value;
        const jumlahMhs = mahasiswaPerKelas[kodeKelas] || 0;
        
        if (jumlahMhs > BATAS_ASISTEN2 && asisten2Select.value === "") {
            return confirm(`PERINGATAN: Kelas ini memiliki ${jumlahMhs} mahasiswa.\n\nAnda belum memilih Asisten 2. Disarankan untuk menambahkan asisten pendamping agar praktikum berjalan efektif.\n\nApakah Anda yakin ingin tetap menyimpan tanpa Asisten 2?`);
        }
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    const tambahKelas = document.querySelector('#modalTambah select[name="kode_kelas"]');
    if (tambahKelas) {
        tambahKelas.addEventListener('change', function() {
            checkAsisten2Warning(this, 'warning_asisten2_tambah');
        });
    }
    
    const generateKelas = document.querySelector('#modalGenerate select[name="kode_kelas"]');
    if (generateKelas) {
        generateKelas.addEventListener('change', function() {
            checkAsisten2Warning(this, 'warning_asisten2_generate');
        });
    }
    
    // Tambahkan validasi saat submit form (Tambah, Edit, Generate)
    const forms = [
        document.querySelector('#modalTambah form'),
        document.querySelector('#modalEdit form'),
        document.querySelector('#modalGenerate form')
    ];
    
    forms.forEach(form => {
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!validateAsisten2(this)) {
                    e.preventDefault(); // Batalkan submit jika user klik Cancel di confirm dialog
                }
            });
        }
    });
    
    const inputTanggalMulai = document.querySelector('#modalGenerate input[name="tanggal_mulai"]');
    const selectHari = document.querySelector('#modalGenerate select[name="hari"]');
    if (inputTanggalMulai && selectHari) {
        inputTanggalMulai.addEventListener('change', function() {
            const d = new Date(this.value);
            const day = d.getDay();
            if (day >= 1 && day <= 6) selectHari.value = day;
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
