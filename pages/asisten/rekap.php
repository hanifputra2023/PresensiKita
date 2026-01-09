<?php
$page = 'asisten_rekap';
$asisten = get_asisten_login();

// Validasi data asisten
if (!$asisten) {
    echo '<div class="alert alert-danger m-4">Data asisten tidak ditemukan. Pastikan akun Anda sudah terdaftar sebagai asisten.</div>';
    return;
}

$kode_asisten = $asisten['kode_asisten'];

// Helper clause: asisten bisa lihat jadwal sendiri ATAU jadwal yang digantikan
// - Jadwal sendiri: kode_asisten_1 atau kode_asisten_2
// - Jadwal asli yang diizinkan: dari absen_asisten.kode_asisten (asisten yang izin tetap bisa lihat)
// - Jadwal pengganti: dari absen_asisten.pengganti dengan status_approval = 'approved'
$jadwal_asisten_clause = "(
    (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
    OR j.id IN (SELECT jadwal_id FROM absen_asisten WHERE kode_asisten = '$kode_asisten' AND status IN ('izin', 'sakit') AND status_approval = 'approved')
    OR j.id IN (SELECT jadwal_id FROM absen_asisten WHERE pengganti = '$kode_asisten' AND status IN ('izin', 'sakit') AND status_approval = 'approved')
)";

// Versi dengan alias j2 untuk subquery
$jadwal_asisten_clause_j2 = "(
    (j2.kode_asisten_1 = '$kode_asisten' OR j2.kode_asisten_2 = '$kode_asisten')
    OR j2.id IN (SELECT jadwal_id FROM absen_asisten WHERE kode_asisten = '$kode_asisten' AND status IN ('izin', 'sakit') AND status_approval = 'approved')
    OR j2.id IN (SELECT jadwal_id FROM absen_asisten WHERE pengganti = '$kode_asisten' AND status IN ('izin', 'sakit') AND status_approval = 'approved')
)";

$filter_kelas = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';
$filter_lab = isset($_GET['lab']) ? escape($_GET['lab']) : '';
$filter_mk = isset($_GET['mk']) ? escape($_GET['mk']) : '';
$start_date = isset($_GET['start_date']) ? escape($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? escape($_GET['end_date']) : date('Y-m-t');

// [BARU] Handler untuk AJAX Detail Presensi (Sama seperti admin)
if (isset($_GET['ajax_detail'])) {
    if (ob_get_length()) ob_end_clean();
    
    $nim = escape($_GET['nim']);
    $kelas = escape($_GET['kelas']);
    $mk = escape($_GET['mk']);
    $lab = escape($_GET['lab']);
    $start_date_detail = escape($_GET['start_date']);
    $end_date_detail = escape($_GET['end_date']);

    $mk_condition = $mk ? "AND j.kode_mk = '$mk'" : "";
    $lab_condition = $lab ? "AND j.kode_lab = '$lab'" : "";
    
    // Query detail
    $detail_query = mysqli_query($conn, "SELECT j.id as jadwal_id, j.pertemuan_ke, j.tanggal, j.jam_mulai, j.jam_selesai, j.materi, j.jenis,
                                         p.status, p.waktu_presensi, mk.nama_mk, j.kode_mk, l.nama_lab
                                         FROM jadwal j
                                         JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                         LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                         LEFT JOIN presensi_mahasiswa p ON j.id = p.jadwal_id AND p.nim = '$nim'
                                         WHERE j.kode_kelas = '$kelas' 
                                         AND $jadwal_asisten_clause
                                         AND j.tanggal BETWEEN '$start_date_detail' AND '$end_date_detail'
                                         $mk_condition
                                         $lab_condition
                                         ORDER BY j.pertemuan_ke ASC");
    
    if (mysqli_num_rows($detail_query) > 0) {
        $grouped_data = [];
        while ($row = mysqli_fetch_assoc($detail_query)) {
            $key = $row['pertemuan_ke'] . '_' . $row['tanggal'] . '_' . $row['jam_mulai'] . '_' . $row['kode_mk'];
            if (!isset($grouped_data[$key])) {
                $grouped_data[$key] = $row;
            } else {
                if (empty($grouped_data[$key]['status']) && !empty($row['status'])) {
                    $grouped_data[$key] = $row;
                }
            }
        }

        foreach ($grouped_data as $d) {
            $status = $d['status'];
            $is_past = strtotime($d['tanggal'] . ' ' . $d['jam_selesai']) < time();
            
            if (!$status) {
                $status = $is_past ? 'alpha' : 'belum';
            }
            
            $badge_color = $status == 'hadir' ? 'success' : ($status == 'izin' ? 'warning' : ($status == 'sakit' ? 'info' : ($status == 'belum' ? 'secondary' : 'danger')));
            
            echo "<tr>
                <td class='text-center'>{$d['pertemuan_ke']}</td>
                <td>" . format_tanggal($d['tanggal']) . " <br><small class='text-muted'>" . format_waktu($d['jam_mulai']) . " - " . format_waktu($d['jam_selesai']) . "</small><br><small class='text-primary'><i class='fas fa-map-marker-alt me-1'></i>" . ($d['nama_lab'] ?: '-') . "</small></td>
                <td><strong>{$d['nama_mk']}</strong><br><small class='text-muted'>{$d['materi']}</small> <span class='badge bg-light text-dark border'>{$d['jenis']}</span></td>
                <td class='text-center'><span class='badge bg-$badge_color'>" . ucfirst($status) . "</span></td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='4' class='text-center text-muted'>Belum ada pertemuan.</td></tr>";
    }
    exit;
}

// [BARU] Handler Export Detail Mahasiswa (Single - Excel) - Ported from Admin
if (isset($_GET['export_detail_mhs'])) {
    if (ob_get_length()) ob_end_clean();
    
    $nim = escape($_GET['nim']);
    $kelas = escape($_GET['kelas']);
    $mk = escape($_GET['mk']);
    $lab = escape($_GET['lab']);
    $start_date_exp = escape($_GET['start_date']);
    $end_date_exp = escape($_GET['end_date']);
    
    // Ambil data mahasiswa
    $mhs_qry = mysqli_query($conn, "SELECT m.nama, k.nama_kelas FROM mahasiswa m LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas WHERE m.nim = '$nim'");
    $mhs_data = mysqli_fetch_assoc($mhs_qry);
    $nama_mhs = $mhs_data['nama'] ?? $nim;
    $nama_kelas = $mhs_data['nama_kelas'] ?? $kelas;

    $mk_condition = $mk ? "AND j.kode_mk = '$mk'" : "";
    $lab_condition = $lab ? "AND j.kode_lab = '$lab'" : "";
    
    $query = mysqli_query($conn, "SELECT j.pertemuan_ke, j.tanggal, j.jam_mulai, j.jam_selesai, j.materi, j.jenis,
                                         p.status, p.waktu_presensi, mk.nama_mk, l.nama_lab
                                         FROM jadwal j
                                         JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                         LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                         LEFT JOIN presensi_mahasiswa p ON j.id = p.jadwal_id AND p.nim = '$nim'
                                         WHERE j.kode_kelas = '$kelas' 
                                         AND $jadwal_asisten_clause
                                         AND j.tanggal BETWEEN '$start_date_exp' AND '$end_date_exp'
                                         $mk_condition
                                         $lab_condition
                                         ORDER BY j.pertemuan_ke ASC");

    $filename = 'Rincian_' . preg_replace('/[^A-Za-z0-9]/', '_', $nama_mhs) . '.xls';
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");

    echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><style>
            body { font-family: Arial, sans-serif; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000; padding: 5px; }
            th { background-color: #f0f0f0; }
            .text-center { text-align: center; }
          </style></head><body>';
    echo "<h3 class='text-center'>RINCIAN KEHADIRAN MAHASISWA</h3>";
    echo "<p><strong>Nama:</strong> $nama_mhs ($nim)<br><strong>Kelas:</strong> $nama_kelas<br><strong>Periode:</strong> " . date('d-m-Y', strtotime($start_date_exp)) . " s/d " . date('d-m-Y', strtotime($end_date_exp)) . "</p>";
    echo '<table><thead><tr><th>No</th><th>Pertemuan</th><th>Tanggal</th><th>Jam</th><th>Mata Kuliah</th><th>Materi</th><th>Lab</th><th>Status</th></tr></thead><tbody>';
          
    $no = 1;
    while ($row = mysqli_fetch_assoc($query)) {
        $status = $row['status'];
        if (!$status) {
            $jadwal_end = $row['tanggal'] . ' ' . $row['jam_selesai'];
            $status = (strtotime($jadwal_end) < time()) ? 'Alpha' : 'Belum';
        }
        
        echo "<tr>
            <td style='text-align:center'>" . $no++ . "</td>
            <td style='text-align:center'>" . $row['pertemuan_ke'] . "</td>
            <td>" . $row['tanggal'] . "</td>
            <td>" . substr($row['jam_mulai'], 0, 5) . " - " . substr($row['jam_selesai'], 0, 5) . "</td>
            <td>" . $row['nama_mk'] . "</td>
            <td>" . $row['materi'] . "</td>
            <td>" . ($row['nama_lab'] ?: '-') . "</td>
            <td style='text-align:center'>" . ucfirst($status) . "</td>
        </tr>";
    }
    echo '</tbody></table></body></html>';
    exit;
}

// Ambil jadwal yang pernah diajar (termasuk jadwal yang digantikan)
$jadwal_diajar = mysqli_query($conn, "SELECT DISTINCT j.kode_kelas, k.nama_kelas 
                                       FROM jadwal j 
                                       JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                       WHERE $jadwal_asisten_clause");

// Ambil lab yang pernah diajar (termasuk jadwal yang digantikan)
$lab_diajar = mysqli_query($conn, "SELECT DISTINCT j.kode_lab, l.nama_lab 
                                   FROM jadwal j 
                                   JOIN lab l ON j.kode_lab = l.kode_lab
                                   WHERE $jadwal_asisten_clause");

// Ambil mata kuliah yang pernah diajar (termasuk jadwal yang digantikan)
$mk_diajar = mysqli_query($conn, "SELECT DISTINCT j.kode_mk, mk.nama_mk 
                                  FROM jadwal j 
                                  JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                  WHERE $jadwal_asisten_clause");

// Pagination
$per_page = 20;
$current_page = get_current_page();

$where_kelas = $filter_kelas ? "AND j.kode_kelas = '$filter_kelas'" : '';
$where_lab = $filter_lab ? "AND j.kode_lab = '$filter_lab'" : '';
$where_mk = $filter_mk ? "AND j.kode_mk = '$filter_mk'" : '';

// Export Excel Logic (Updated to match Admin features)
if (isset($_GET['export'])) {
    // Hentikan dan bersihkan output buffer yang mungkin sudah terisi oleh index.php
    if (ob_get_length()) ob_end_clean();
    
    $filename = 'rekap_presensi_' . date('Y-m-d_His') . '.xls';
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    
    $sertakan_detail = isset($_GET['detail']) && $_GET['detail'] == '1';
    $attendance_map = [];
    $meetings = [];

    // Jika detail disertakan, ambil data pertemuan (Restricted to Assistant)
    if ($sertakan_detail) {
        $detail_sql = "SELECT m.nim, j.pertemuan_ke, j.tanggal, j.jam_selesai, l.nama_lab, p.status
                       FROM mahasiswa m
                       JOIN jadwal j ON m.kode_kelas = j.kode_kelas
                       LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                       LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
                       WHERE (SELECT COUNT(*) FROM jadwal j2 
                              WHERE j2.kode_kelas = m.kode_kelas 
                              AND $jadwal_asisten_clause_j2) > 0
                       AND $jadwal_asisten_clause
                       AND j.tanggal BETWEEN '$start_date' AND '$end_date' 
                       $where_kelas $where_lab $where_mk
                       AND j.jenis != 'inhall'
                       ORDER BY j.pertemuan_ke";
        
        $detail_res = mysqli_query($conn, $detail_sql);
        while ($d = mysqli_fetch_assoc($detail_res)) {
            $p_ke = $d['pertemuan_ke'];
            $meetings[$p_ke] = true;
            
            $status = $d['status'];
            if (!$status) {
                 $is_past = strtotime($d['tanggal'] . ' ' . $d['jam_selesai']) < time();
                 $status = $is_past ? 'Alpha' : 'Belum';
            } else {
                $status = ucfirst($status);
            }
            $info = "$status (" . ($d['nama_lab'] ?: '-') . ")";
            
            if (isset($attendance_map[$d['nim']][$p_ke])) {
                $attendance_map[$d['nim']][$p_ke] .= " | " . $info;
            } else {
                $attendance_map[$d['nim']][$p_ke] = $info;
            }
        }
        ksort($meetings);
    }

    echo '<html>';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo '<style>
            body { font-family: Arial, sans-serif; }
            .table-data { border-collapse: collapse; width: 100%; }
            .table-data th, .table-data td { border: 1px solid #000; padding: 5px; }
            .table-data th { background-color: #f0f0f0; font-weight: bold; text-align: center; }
            .text-center { text-align: center; }
          </style>';
    echo '</head>';
    echo '<body>';

    echo '<h3 style="text-align:center">REKAP PRESENSI (ASISTEN)</h3>';
    echo '<p><strong>Periode:</strong> ' . date('d-m-Y', strtotime($start_date)) . ' s/d ' . date('d-m-Y', strtotime($end_date)) . '<br>';
    echo '<strong>Tanggal Cetak:</strong> ' . date('d-m-Y H:i') . '</p>';
    
    echo '<table class="table-data" border="1"><thead><tr>
            <th>No</th><th>NIM</th><th>Nama</th><th>Kelas</th><th>Daftar MK</th><th>Daftar Lab</th>
            <th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpha</th><th>Belum</th><th>Persentase</th>';
    
    if ($sertakan_detail) {
        foreach ($meetings as $m => $val) echo "<th>P$m</th>";
    }
    echo '</tr></thead><tbody>';
    
    $query_export = "SELECT m.nim, m.nama, k.nama_kelas,
                       GROUP_CONCAT(DISTINCT mk.nama_mk SEPARATOR ', ') as all_mk,
                       GROUP_CONCAT(DISTINCT l.nama_lab SEPARATOR ', ') as all_lab,
                       SUM(CASE WHEN p.status = 'hadir' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as hadir,
                       SUM(CASE WHEN p.status = 'izin' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as izin,
                       SUM(CASE WHEN p.status = 'sakit' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as sakit,
                       SUM(CASE WHEN j.jenis != 'inhall' AND (p.status = 'alpha' OR ((p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit', 'alpha')) AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai))) THEN 1 ELSE 0 END) as alpha,
                       SUM(CASE WHEN j.jenis != 'inhall' AND (p.status = 'belum' OR p.status IS NULL) AND CONCAT(j.tanggal, ' ', j.jam_selesai) >= NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as belum
                       FROM mahasiswa m
                       JOIN kelas k ON m.kode_kelas = k.kode_kelas
                       LEFT JOIN jadwal j ON m.kode_kelas = j.kode_kelas AND j.tanggal BETWEEN '$start_date' AND '$end_date'
                           AND $jadwal_asisten_clause
                       LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                       LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                       LEFT JOIN presensi_mahasiswa p ON p.nim = m.nim AND p.jadwal_id = j.id
                       WHERE (SELECT COUNT(*) FROM jadwal j2 
                              WHERE j2.kode_kelas = m.kode_kelas 
                              AND $jadwal_asisten_clause_j2) > 0 
                              $where_kelas $where_lab $where_mk
                       GROUP BY m.nim, m.nama, k.nama_kelas
                       ORDER BY k.nama_kelas, m.nama";
                       
    $result_export = mysqli_query($conn, $query_export);
    $no = 1;
    while ($row = mysqli_fetch_assoc($result_export)) {
        $sudah_presensi = $row['hadir'] + $row['izin'] + $row['sakit'] + $row['alpha'];
        $persen = $sudah_presensi > 0 ? round(($row['hadir'] / $sudah_presensi) * 100) : 0;
        
        echo "<tr>
            <td style='text-align:center'>{$no}</td>
            <td>{$row['nim']}</td>
            <td>{$row['nama']}</td>
            <td>{$row['nama_kelas']}</td>
            <td>" . ($row['all_mk'] ?: '-') . "</td>
            <td>" . ($row['all_lab'] ?: '-') . "</td>
            <td style='text-align:center'>{$row['hadir']}</td>
            <td style='text-align:center'>{$row['izin']}</td>
            <td style='text-align:center'>{$row['sakit']}</td>
            <td style='text-align:center'>{$row['alpha']}</td>
            <td style='text-align:center'>{$row['belum']}</td>
            <td style='text-align:center'>{$persen}%</td>";
            
        if ($sertakan_detail) {
            foreach ($meetings as $m => $val) {
                echo "<td>" . ($attendance_map[$row['nim']][$m] ?? '-') . "</td>";
            }
        }
        echo "</tr>";
        $no++;
    }
    
    echo '</tbody></table></body></html>';
    exit;
}

// Hitung total - ambil mahasiswa dari kelas yang pernah diajar asisten (tanpa group by lab)
$count_sql = "SELECT COUNT(*) as total FROM (
                SELECT 1
                FROM mahasiswa m
                JOIN kelas k ON m.kode_kelas = k.kode_kelas
                LEFT JOIN jadwal j ON m.kode_kelas = j.kode_kelas AND $jadwal_asisten_clause
                    AND j.tanggal BETWEEN '$start_date' AND '$end_date'
                LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                WHERE (SELECT COUNT(*) FROM jadwal j2 WHERE j2.kode_kelas = m.kode_kelas AND $jadwal_asisten_clause_j2) > 0
                $where_kelas $where_lab $where_mk
                GROUP BY m.nim
              ) as subquery";
$count_query = mysqli_query($conn, $count_sql);
$total_data = mysqli_fetch_assoc($count_query)['total'] ?? 0;
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

// Rekap per mahasiswa
// Status dihitung berdasarkan waktu SEKARANG (NOW()) untuk stabilitas
// Alpha: Jadwal sudah lewat dan tidak ada presensi (hadir/izin/sakit)
// Belum: Jadwal belum selesai
// EXCLUDE jadwal inhall dari statistik (inhall bersifat opsional, tidak mempengaruhi persentase)
$rekap = mysqli_query($conn, "SELECT m.nim, m.nama, k.nama_kelas, m.kode_kelas,
                               SUM(CASE WHEN p.status = 'hadir' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as hadir,
                               SUM(CASE WHEN p.status = 'izin' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as izin,
                               SUM(CASE WHEN p.status = 'sakit' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as sakit,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status = 'alpha' OR ((p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit', 'alpha')) AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai))) THEN 1 ELSE 0 END) as alpha,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status = 'belum' OR p.status IS NULL) AND CONCAT(j.tanggal, ' ', j.jam_selesai) >= NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as belum,
                               COUNT(DISTINCT CASE WHEN j.jenis != 'inhall' AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN j.id END) as total_pertemuan
                               FROM mahasiswa m
                               JOIN kelas k ON m.kode_kelas = k.kode_kelas
                               LEFT JOIN jadwal j ON m.kode_kelas = j.kode_kelas AND j.tanggal BETWEEN '$start_date' AND '$end_date'
                                   AND $jadwal_asisten_clause
                               LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                               LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                               LEFT JOIN presensi_mahasiswa p ON p.nim = m.nim AND p.jadwal_id = j.id
                               WHERE (SELECT COUNT(*) FROM jadwal j2 
                                      WHERE j2.kode_kelas = m.kode_kelas 
                                      AND $jadwal_asisten_clause_j2) > 0 
                                      $where_kelas $where_lab $where_mk
                               GROUP BY m.nim, m.nama, k.nama_kelas, m.kode_kelas
                               ORDER BY k.nama_kelas, m.nama
                               LIMIT $offset, $per_page");
?>
<?php
// Query for printing all data (without pagination)
$rekap_print = mysqli_query($conn, "SELECT m.nim, m.nama, k.nama_kelas,
                               SUM(CASE WHEN p.status = 'hadir' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as hadir,
                               SUM(CASE WHEN p.status = 'izin' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as izin,
                               SUM(CASE WHEN p.status = 'sakit' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as sakit,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status = 'alpha' OR ((p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit', 'alpha')) AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai))) THEN 1 ELSE 0 END) as alpha,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status = 'belum' OR p.status IS NULL) AND CONCAT(j.tanggal, ' ', j.jam_selesai) >= NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as belum
                               FROM mahasiswa m
                               JOIN kelas k ON m.kode_kelas = k.kode_kelas
                               LEFT JOIN jadwal j ON m.kode_kelas = j.kode_kelas AND j.tanggal BETWEEN '$start_date' AND '$end_date'
                                   AND $jadwal_asisten_clause
                               LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                               LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                               LEFT JOIN presensi_mahasiswa p ON p.nim = m.nim AND p.jadwal_id = j.id
                               WHERE (SELECT COUNT(*) FROM jadwal j2 
                                      WHERE j2.kode_kelas = m.kode_kelas 
                                      AND $jadwal_asisten_clause_j2) > 0 
                                      $where_kelas $where_lab $where_mk
                               GROUP BY m.nim, m.nama, k.nama_kelas
                               ORDER BY k.nama_kelas, m.nama");
?>
<?php
// [BARU] Ambil data detail pertemuan untuk tampilan PDF (Ported from Admin & Adapted for Asisten)
$print_details = [];
$meetings = [];

$detail_print_sql = "SELECT m.nim, j.pertemuan_ke, j.tanggal, j.jam_mulai, j.jam_selesai, l.nama_lab, p.status, j.kode_mk
               FROM mahasiswa m
               JOIN jadwal j ON m.kode_kelas = j.kode_kelas
               LEFT JOIN lab l ON j.kode_lab = l.kode_lab
               LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
               WHERE (SELECT COUNT(*) FROM jadwal j2 
                      WHERE j2.kode_kelas = m.kode_kelas 
                      AND $jadwal_asisten_clause_j2) > 0 
               AND $jadwal_asisten_clause
               AND j.tanggal BETWEEN '$start_date' AND '$end_date' 
               $where_kelas $where_lab $where_mk
               AND j.jenis != 'inhall'
               ORDER BY m.nim, j.pertemuan_ke, j.kode_mk, j.jam_mulai";

$detail_print_res = mysqli_query($conn, $detail_print_sql);

$grouped_details = [];
while ($d = mysqli_fetch_assoc($detail_print_res)) {
    $nim = $d['nim'];
    $p_ke = $d['pertemuan_ke'];
    $meetings[$p_ke] = true;
    
    // Key untuk grouping jadwal paralel
    $key = $p_ke . '_' . $d['tanggal'] . '_' . $d['jam_mulai'] . '_' . $d['kode_mk'];

    if (!isset($grouped_details[$nim][$key])) {
        $grouped_details[$nim][$key] = [
            'status' => null,
            'attended_lab' => null,
            'all_labs' => [],
            'tanggal' => $d['tanggal'],
            'jam_selesai' => $d['jam_selesai']
        ];
    }
    
    if (!empty($d['nama_lab'])) {
        $grouped_details[$nim][$key]['all_labs'][] = $d['nama_lab'];
    }
    
    if (!empty($d['status'])) {
        $grouped_details[$nim][$key]['status'] = $d['status'];
        $grouped_details[$nim][$key]['attended_lab'] = $d['nama_lab'];
    }
}

foreach ($grouped_details as $nim => $meetings_data) {
    foreach ($meetings_data as $key => $data) {
        $p_ke = explode('_', $key)[0];
        $info = '';
        
        $status = $data['status'];
        if (!$status) {
            $is_past = strtotime($data['tanggal'] . ' ' . $data['jam_selesai']) < time();
            $status = $is_past ? 'alpha' : 'belum';
        }
        
        $status_display = ucfirst($status);
        
        if ($status === 'hadir' || $status === 'izin' || $status === 'sakit') {
            $lab_name = $data['attended_lab'] ?: '-';
            $info = "$status_display ($lab_name)";
        } else {
            $unique_labs = array_unique($data['all_labs']);
            $lab_list = !empty($unique_labs) ? implode('/', $unique_labs) : '-';
            $info = "$status_display ($lab_list)";
        }
        
        if (isset($print_details[$nim][$p_ke])) {
            $print_details[$nim][$p_ke] .= " | " . $info;
        } else {
            $print_details[$nim][$p_ke] = $info;
        }
    }
}
ksort($meetings);
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 mb-4 pt-2 no-print">
                    <h4 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Rekap Presensi</h4>
                    <div class="d-grid d-md-flex gap-2 justify-content-md-end align-items-center">
                        <div class="form-check form-switch me-md-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="sertakanDetail" checked>
                            <label class="form-check-label small" for="sertakanDetail">Sertakan Detail Pertemuan</label>
                        </div>
                        <button onclick="exportExcel()" class="btn btn-success">
                            <i class="fas fa-file-excel me-1"></i>Export Excel
                        </button>
                        <button class="btn btn-danger" onclick="exportPDF()">
                            <i class="fas fa-file-pdf me-1"></i>Export PDF
                        </button>
                        <button class="btn btn-secondary" onclick="printPage()">
                            <i class="fas fa-print me-1"></i>Cetak
                        </button>
                    </div>
                </div>
                
                <div class="card mb-4 no-print">
                    <div class="card-body p-2 p-md-3">
                        <form method="GET" class="row g-2 align-items-end">
                            <input type="hidden" name="page" value="asisten_rekap">
                            <div class="col-6 col-md-2">
                                <label class="form-label small">Dari Tanggal</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date ?>">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small">Sampai Tanggal</label>
                                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date ?>">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small">Filter Kelas</label>
                                <select name="kelas" class="form-select form-select-sm">
                                    <option value="">Semua Kelas</option>
                                    <?php 
                                    mysqli_data_seek($jadwal_diajar, 0);
                                    while ($j = mysqli_fetch_assoc($jadwal_diajar)): ?>
                                        <option value="<?= $j['kode_kelas'] ?>" <?= $filter_kelas == $j['kode_kelas'] ? 'selected' : '' ?>>
                                            <?= $j['nama_kelas'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small">Filter Mata Kuliah</label>
                                <select name="mk" class="form-select form-select-sm">
                                    <option value="">Semua MK</option>
                                    <?php 
                                    while ($m = mysqli_fetch_assoc($mk_diajar)): ?>
                                        <option value="<?= $m['kode_mk'] ?>" <?= $filter_mk == $m['kode_mk'] ? 'selected' : '' ?>>
                                            <?= $m['nama_mk'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small">Filter Lab</label>
                                <select name="lab" class="form-select form-select-sm">
                                    <option value="">Semua Lab</option>
                                    <?php 
                                    while ($l = mysqli_fetch_assoc($lab_diajar)): ?>
                                        <option value="<?= $l['kode_lab'] ?>" <?= $filter_lab == $l['kode_lab'] ? 'selected' : '' ?>>
                                            <?= $l['nama_lab'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Link ke Statistik -->
                <div class="alert alert-info mb-4 no-print">
                    <i class="fas fa-chart-pie me-2"></i>
                    Lihat statistik per kelas, mata kuliah, dan lab di halaman 
                    <a href="index.php?page=asisten_statistik" class="alert-link">Statistik Presensi</a>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <strong><i class="fas fa-list me-1"></i> Rekap Per Mahasiswa</strong>
                    </div>
                    <div class="card-body p-0 p-md-3">
                        <!-- ==================== FOR SCREEN VIEW (PAGINATED) ==================== -->
                        <div class="no-print">
                            <!-- Desktop Table -->
                            <div class="table-responsive d-none d-lg-block">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No</th>
                                            <th>NIM</th>
                                            <th>Nama</th>
                                            <th>Kelas</th>
                                            <th class="text-center text-success">Hadir</th>
                                            <th class="text-center text-warning">Izin</th>
                                            <th class="text-center text-info">Sakit</th>
                                            <th class="text-center text-danger">Alpha</th>
                                            <th class="text-center text-secondary">Belum</th>
                                            <th class="text-center">%</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        mysqli_data_seek($rekap, 0);
                                        $no = get_offset($current_page, $per_page) + 1; 
                                        while ($r = mysqli_fetch_assoc($rekap)): ?>
                                            <?php 
                                            $sudah_presensi = $r['hadir'] + $r['izin'] + $r['sakit'] + $r['alpha'];
                                            $persen = $sudah_presensi > 0 ? round(($r['hadir'] / $sudah_presensi) * 100) : 0;
                                            ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= $r['nim'] ?></td>
                                                <td><?= $r['nama'] ?></td>
                                                <td><span class="badge bg-primary"><?= $r['nama_kelas'] ?></span></td>
                                                <td class="text-center"><?= $r['hadir'] ?></td>
                                                <td class="text-center"><?= $r['izin'] ?></td>
                                                <td class="text-center"><?= $r['sakit'] ?></td>
                                                <td class="text-center"><?= $r['alpha'] ?></td>
                                                <td class="text-center">
                                                    <?php if ($r['belum'] > 0): ?>
                                                        <span class="badge bg-secondary"><?= $r['belum'] ?></span>
                                                    <?php else: ?>
                                                        0
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge <?= $persen >= 75 ? 'bg-success' : ($persen >= 50 ? 'bg-warning' : 'bg-danger') ?>">
                                                        <?= $persen ?>%
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-info text-white" 
                                                            onclick="showDetail('<?= $r['nim'] ?>', '<?= $r['nama'] ?>', '<?= $r['kode_kelas'] ?>')">
                                                        <i class="fas fa-list"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Mobile Cards -->
                            <div class="d-lg-none p-2">
                                <?php 
                                mysqli_data_seek($rekap, 0);
                                while ($r = mysqli_fetch_assoc($rekap)): 
                                    $sudah_presensi = $r['hadir'] + $r['izin'] + $r['sakit'] + $r['alpha'];
                                    $persen = $sudah_presensi > 0 ? round(($r['hadir'] / $sudah_presensi) * 100) : 0;
                                ?>
                                    <div class="card mb-2 border">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-1"><?= $r['nama'] ?></h6>
                                                    <div class="small text-muted">
                                                        <span class="badge bg-secondary"><?= $r['nim'] ?></span>
                                                        <span class="badge bg-primary ms-1"><?= $r['nama_kelas'] ?></span>
                                                    </div>
                                                </div>
                                                <span class="badge <?= $persen >= 75 ? 'bg-success' : ($persen >= 50 ? 'bg-warning' : 'bg-danger') ?>" style="font-size: 0.9rem;">
                                                    <?= $persen ?>%
                                                </span>
                                            </div>
                                            <div class="mb-2">
                                                <button class="btn btn-sm btn-outline-info w-100" onclick="showDetail('<?= $r['nim'] ?>', '<?= $r['nama'] ?>', '<?= $r['kode_kelas'] ?>')">
                                                    <i class="fas fa-list me-1"></i> Rincian
                                                </button>
                                            </div>
                                            <div class="d-flex justify-content-between small flex-wrap gap-1">
                                                <span class="text-success"><i class="fas fa-check me-1"></i>H: <?= $r['hadir'] ?></span>
                                                <span class="text-warning"><i class="fas fa-clock me-1"></i>I: <?= $r['izin'] ?></span>
                                                <span class="text-info"><i class="fas fa-medkit me-1"></i>S: <?= $r['sakit'] ?></span>
                                                <span class="text-danger"><i class="fas fa-times me-1"></i>A: <?= $r['alpha'] ?></span>
                                                <?php if ($r['belum'] > 0): ?>
                                                    <span class="text-secondary"><i class="fas fa-question me-1"></i>B: <?= $r['belum'] ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            
                            <!-- Pagination -->
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2 p-2">
                                <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                                <?= render_pagination($current_page, $total_pages, 'index.php?page=asisten_rekap', ['kelas' => $filter_kelas, 'lab' => $filter_lab, 'mk' => $filter_mk, 'start_date' => $start_date, 'end_date' => $end_date]) ?>
                            </div>
                        </div>

                        <!-- ==================== FOR PRINT VIEW (ALL DATA) ==================== -->
                        <div class="print-only">
                            <h4 class="mb-3 text-center">Rekap Presensi</h4>
                            <p class="text-center text-muted">Periode: <?= date('d M Y', strtotime($start_date)) ?> s/d <?= date('d M Y', strtotime($end_date)) ?></p>
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>NIM</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th class="text-center">H</th>
                                        <th class="text-center">I</th>
                                        <th class="text-center">S</th>
                                        <th class="text-center">A</th>
                                        <th class="text-center">Belum</th>
                                        <?php foreach ($meetings as $pm => $val): ?>
                                            <th class="text-center detail-col">P<?= $pm ?></th>
                                        <?php endforeach; ?>
                                        <th class="text-center">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    mysqli_data_seek($rekap_print, 0);
                                    $no_print = 1; 
                                    while ($r_print = mysqli_fetch_assoc($rekap_print)): 
                                        $sudah_presensi_print = $r_print['hadir'] + $r_print['izin'] + $r_print['sakit'] + $r_print['alpha'];
                                        $persen_print = $sudah_presensi_print > 0 ? round(($r_print['hadir'] / $sudah_presensi_print) * 100) : 0;
                                    ?>
                                        <tr>
                                            <td><?= $no_print++ ?></td>
                                            <td><?= $r_print['nim'] ?></td>
                                            <td><?= $r_print['nama'] ?></td>
                                            <td><?= $r_print['nama_kelas'] ?></td>
                                            <td class="text-center"><?= $r_print['hadir'] ?></td>
                                            <td class="text-center"><?= $r_print['izin'] ?></td>
                                            <td class="text-center"><?= $r_print['sakit'] ?></td>
                                            <td class="text-center"><?= $r_print['alpha'] ?></td>
                                            <td class="text-center"><?= $r_print['belum'] ?></td>
                                            <?php foreach ($meetings as $pm => $val): ?>
                                                <td class="text-center detail-col"><?= $print_details[$r_print['nim']][$pm] ?? '-' ?></td>
                                            <?php endforeach; ?>
                                            <td class="text-center"><?= $persen_print ?>%</td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Presensi -->
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-history me-2"></i>Rincian Kehadiran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 id="detailNama" class="fw-bold mb-3"></h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light"><tr><th class="text-center" width="50">P</th><th>Waktu</th><th>Materi</th><th class="text-center">Status</th></tr></thead>
                        <tbody id="detailContent"><tr><td colspan="4" class="text-center">Memuat data...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="btnExportDetail" class="btn btn-success"><i class="fas fa-file-excel me-1"></i>Export Excel</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<style>
.print-only {
    display: none;
}

@media print {
    /* Sembunyikan elemen yang tidak perlu */
    .sidebar, .no-print, #mobileHeader { 
        display: none !important; 
    }

    .print-only {
        display: block !important;
    }

    /* Buat konten tabel menjadi lebar penuh */
    .col-md-3.col-lg-2.px-0 {
        display: none !important;
    }
    .col-md-9.col-lg-10 {
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .content-wrapper {
        padding: 0 !important;
    }

    /* Atur gaya dasar untuk cetak */
    body {
        background: #fff !important;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
    }
    .table thead.table-light {
        background-color: #f8f9fa !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .hide-detail-print .detail-col {
        display: none !important;
    }
    .table-bordered th,
    .table-bordered td {
        border: 1px solid #000 !important;
    }
    a {
        text-decoration: none;
        color: inherit;
    }
}
</style>

<!-- Library html2pdf.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function exportExcel() {
    const sertakanDetail = document.getElementById('sertakanDetail').checked;
    let url = `index.php?page=asisten_rekap&export=1&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&kelas=<?= $filter_kelas ?>&mk=<?= $filter_mk ?>&lab=<?= $filter_lab ?>`;
    if (sertakanDetail) {
        url += '&detail=1';
    }
    window.location.href = url;
}

function showDetail(nim, nama, kelas) {
    document.getElementById('detailNama').innerText = nama + ' (' + nim + ')';
    document.getElementById('detailContent').innerHTML = '<tr><td colspan="4" class="text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Memuat...</td></tr>';
    
    var myModal = new bootstrap.Modal(document.getElementById('modalDetail'));
    myModal.show();
    
    const mk = document.querySelector('select[name="mk"]').value;
    const lab = document.querySelector('select[name="lab"]').value;
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    
    document.getElementById('btnExportDetail').href = `index.php?page=asisten_rekap&export_detail_mhs=1&nim=${nim}&kelas=${kelas}&mk=${mk}&lab=${lab}&start_date=${startDate}&end_date=${endDate}`;
    const url = `index.php?page=asisten_rekap&ajax_detail=1&nim=${nim}&kelas=${kelas}&mk=${mk}&lab=${lab}&start_date=${startDate}&end_date=${endDate}`;

    fetch(url)
        .then(response => response.text())
        .then(html => { document.getElementById('detailContent').innerHTML = html; });
}

function printPage() {
    const sertakanDetail = document.getElementById('sertakanDetail').checked;
    const printSection = document.querySelector('.print-only');
    
    if (!sertakanDetail) {
        printSection.classList.add('hide-detail-print');
    } else {
        printSection.classList.remove('hide-detail-print');
    }
    window.print();
}

function exportPDF() {
    const originalElement = document.querySelector('.print-only');
    const elementToPrint = originalElement.cloneNode(true);
    
    // Hapus class 'print-only' agar tidak terpengaruh style display:none
    elementToPrint.classList.remove('print-only');
    
    // Atur style agar terlihat dan terbaca jelas di PDF (mengatasi isu Dark Mode)
    elementToPrint.style.display = 'block';
    elementToPrint.style.backgroundColor = '#ffffff';
    elementToPrint.style.color = '#000000';
    elementToPrint.style.padding = '20px';
    elementToPrint.style.fontSize = '12px';
    
    // Paksa warna teks hitam untuk semua elemen di dalamnya
    elementToPrint.querySelectorAll('*').forEach(el => {
        el.style.color = '#000000';
    });
    
    // Handle detail columns based on checkbox
    const sertakanDetail = document.getElementById('sertakanDetail').checked;
    if (!sertakanDetail) {
        elementToPrint.querySelectorAll('.detail-col').forEach(el => el.remove());
    }
    
    // Fix table borders explicitly (Paksa border hitam agar terlihat jelas)
    elementToPrint.querySelectorAll('table, th, td').forEach(el => {
        el.style.border = '1px solid #000000';
        el.style.borderCollapse = 'collapse';
    });
    
    // Style header tabel secara spesifik agar lebih kontras
    const tableHeader = elementToPrint.querySelector('thead');
    if (tableHeader) {
        tableHeader.style.backgroundColor = '#0066cc'; // Warna biru primer
        // Ganti warna teks di dalam header menjadi putih
        tableHeader.querySelectorAll('th').forEach(th => {
            th.style.color = '#ffffff';
            th.style.backgroundColor = '#0066cc'; // Pastikan background header tetap biru
        });
    }
    
    // Gunakan wrapper untuk menyembunyikan dari view user tapi tetap renderable
    const wrapper = document.createElement('div');
    wrapper.style.position = 'fixed';
    wrapper.style.left = '-10000px';
    wrapper.style.top = '0';
    wrapper.style.width = '1100px'; // Lebar A4 Landscape
    wrapper.appendChild(elementToPrint);
    
    document.body.appendChild(wrapper);

    const opt = {
        margin:       10,
        filename:     'rekap_presensi_<?= date("Y-m-d_His") ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }
    };

    // Generate PDF dari elemen clone, lalu hapus elemen tersebut setelah selesai
    html2pdf().set(opt).from(elementToPrint).save().then(function() {
        document.body.removeChild(wrapper);
    });
}
</script>
<?php include 'includes/footer.php'; ?>
