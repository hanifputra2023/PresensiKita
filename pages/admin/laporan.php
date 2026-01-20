<?php
$page = 'admin_laporan';

// [BARU] Handler untuk AJAX Detail Presensi
if (isset($_GET['ajax_detail'])) {
    // Bersihkan buffer agar tidak ada output HTML lain
    if (ob_get_length()) ob_end_clean();
    
    $nim = escape($_GET['nim']);
    $kelas = escape($_GET['kelas']);
    $mk = escape($_GET['mk']);
    $lab = escape($_GET['lab']);
    
    // [FIX] Ambil tanggal daftar mahasiswa untuk validasi status (agar sesuai laporan)
    $mhs_qry = mysqli_query($conn, "SELECT tanggal_daftar FROM mahasiswa WHERE nim = '$nim'");
    $mhs_data = mysqli_fetch_assoc($mhs_qry);
    $tanggal_daftar = $mhs_data['tanggal_daftar'] ?? '2099-12-31';

    // [MODIFIED] Gunakan Rentang Tanggal
    $start_date_detail = escape($_GET['start_date']);
    $end_date_detail = escape($_GET['end_date']);
    
    // [FIX] Kondisi filter yang benar
    $mk_condition = $mk ? "AND j.kode_mk = '$mk'" : "";
    $lab_condition = $lab ? "AND j.kode_lab = '$lab'" : "";
    
    $detail_query = mysqli_query($conn, "SELECT j.id as jadwal_id, j.pertemuan_ke, j.tanggal, j.jam_mulai, j.jam_selesai, j.materi, j.jenis,
                                         p.status, p.waktu_presensi, mk.nama_mk, j.kode_mk, l.nama_lab
                                         FROM jadwal j
                                         JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                         LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                         LEFT JOIN presensi_mahasiswa p ON j.id = p.jadwal_id AND p.nim = '$nim'
                                         WHERE j.kode_kelas = '$kelas' 
                                         AND j.tanggal BETWEEN '$start_date_detail' AND '$end_date_detail'
                                         $mk_condition
                                         $lab_condition
                                         ORDER BY j.pertemuan_ke ASC");
    
    if (mysqli_num_rows($detail_query) > 0) {
        $grouped_data = [];
        while ($row = mysqli_fetch_assoc($detail_query)) {
            // Grouping untuk menghindari duplikasi jika ada split lab (jadwal paralel)
            // Key: Pertemuan + Tanggal + Jam + MK
            $key = $row['pertemuan_ke'] . '_' . $row['tanggal'] . '_' . $row['jam_mulai'] . '_' . $row['kode_mk'];
            
            if (!isset($grouped_data[$key])) {
                // Inisialisasi grup dengan baris pertama sebagai basis
                $grouped_data[$key] = $row;
                $grouped_data[$key]['all_labs'] = [];
                $grouped_data[$key]['attended_lab'] = null;
            }

            // Kumpulkan semua kemungkinan lab untuk pertemuan ini
            if (!empty($row['nama_lab'])) {
                $grouped_data[$key]['all_labs'][] = $row['nama_lab'];
            }
            
            // Jika baris ini memiliki status presensi, ini adalah data yang valid
            if (!empty($row['status'])) {
                $grouped_data[$key]['status'] = $row['status'];
                $grouped_data[$key]['waktu_presensi'] = $row['waktu_presensi'];
                $grouped_data[$key]['attended_lab'] = $row['nama_lab']; // Simpan lab spesifik tempat presensi
                $grouped_data[$key]['jadwal_id'] = $row['jadwal_id']; // Penting untuk update status
            }
        }

        foreach ($grouped_data as $d) {
            $status = $d['status'];
            $jadwal_end_time = $d['tanggal'] . ' ' . $d['jam_selesai'];
            $is_past = strtotime($jadwal_end_time) < time();
            $is_registered = $tanggal_daftar < $jadwal_end_time;

            if (!$status) {
                if ($d['jenis'] == 'inhall') {
                    $status = 'inhall_skip'; // Bukan Alpha karena Inhall opsional
                } elseif (!$is_registered) {
                    $status = 'unregistered'; // Bukan Alpha karena belum terdaftar
                } else {
                    $status = $is_past ? 'alpha' : 'belum';
                }
            }

            $status_badge_map = [
                'hadir' => 'bg-success text-white',
                'izin' => 'bg-warning text-dark',
                'sakit' => 'bg-info text-dark',
                'alpha' => 'bg-danger text-white',
                'belum' => 'bg-secondary text-white',
                'inhall_skip' => 'bg-light text-dark border',
                'unregistered' => 'bg-light text-muted border'
            ];
            $select_class = $status_badge_map[$status] ?? 'bg-light';
            
            $status_labels = [
                'hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpha' => 'Alpha',
                'belum' => 'Belum', 'inhall_skip' => 'Tidak Ikut', 'unregistered' => 'Belum Daftar'
            ];
            $status_label = $status_labels[$status] ?? ucfirst($status);
            $badge_class = $status_badge_map[$status] ?? 'bg-secondary';

            // [FIX] Tampilkan opsi edit untuk Praresponsi/Responsi meskipun belum lewat waktu.
            // Opsi edit hanya disembunyikan untuk jadwal 'materi' yang akan datang.
            $is_editable_always = in_array($d['jenis'], ['praresponsi', 'responsi', 'inhall']);
            
            if ($status == 'unregistered') {
                $status_cell = "<td class='text-center'><span class='badge bg-light text-muted border'>Belum Terdaftar</span></td>";
            } elseif (!$is_past && !$is_editable_always && $status == 'belum') {
                $status_cell = "<td class='text-center'><span class='badge bg-secondary'>Belum Terlaksana</span></td>";
            } else {
                $options = ['hadir', 'izin', 'sakit', 'alpha'];
                $select_options = "";
                if ($status == 'inhall_skip') {
                    $select_options .= "<option value='' selected disabled>- Pilih -</option>";
                }
                foreach ($options as $opt) {
                    $selected = ($status == $opt) ? 'selected' : '';
                    $select_options .= "<option value='{$opt}' {$selected}>" . ucfirst($opt) . "</option>";
                }
                $status_cell = "<td class='text-center'><select class='form-select form-select-sm status-select {$select_class}' onchange='updateStatus(this, {$d['jadwal_id']}, \"{$nim}\")' data-initial-status='{$status}'>{$select_options}</select><span class='save-status-indicator ms-2'></span></td>";
            }
            
            // Tentukan tampilan lab berdasarkan status
            $lab_display = '';
            if (!empty($d['attended_lab'])) {
                // Jika mahasiswa hadir/izin/sakit, tampilkan lab spesifik
                $lab_display = "<i class='fas fa-map-marker-alt me-1'></i>" . htmlspecialchars($d['attended_lab']);
            } else {
                // Jika alpha/belum, tampilkan semua kemungkinan lab
                $unique_labs = array_unique($d['all_labs']);
                $lab_list = !empty($unique_labs) ? implode(' / ', $unique_labs) : '-';
                $lab_display = "<i class='fas fa-th-list me-1'></i>" . htmlspecialchars($lab_list);
            }

            echo "<tr>
                <td class='text-center'>{$d['pertemuan_ke']}</td>
                <td>" . format_tanggal($d['tanggal']) . " <br><small class='text-muted'>" . format_waktu($d['jam_mulai']) . " - " . format_waktu($d['jam_selesai']) . "</small><br><small class='text-primary'>$lab_display</small></td>
                <td><strong>" . htmlspecialchars($d['nama_mk']) . "</strong><br><small class='text-muted'>" . htmlspecialchars($d['materi']) . "</small> <span class='badge bg-light text-dark border'>{$d['jenis']}</span></td>
                <td class='text-center'><span class='badge $badge_class'>$status_label</span></td>
                $status_cell
            </tr>";
        }
    } else {
        echo "<tr><td colspan='4' class='text-center text-muted'>Belum ada pertemuan yang terlaksana.</td></tr>";
    }
    exit;
}

// [BARU] Handler Export Detail Mahasiswa (Single - Excel)
if (isset($_GET['export_detail_mhs'])) {
    if (ob_get_length()) ob_end_clean();
    
    $nim = escape($_GET['nim']);
    $kelas = escape($_GET['kelas']);
    $mk = escape($_GET['mk']);
    $lab = escape($_GET['lab']);
    $start_date = escape($_GET['start_date']);
    $end_date = escape($_GET['end_date']);
    
    // Ambil data mahasiswa & kelas untuk judul
    $mhs_qry = mysqli_query($conn, "SELECT m.nama, k.nama_kelas, m.tanggal_daftar FROM mahasiswa m LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas WHERE m.nim = '$nim'");
    $mhs_data = mysqli_fetch_assoc($mhs_qry);
    $nama_mhs = $mhs_data['nama'] ?? $nim;
    $nama_kelas = $mhs_data['nama_kelas'] ?? $kelas;
    $tanggal_daftar = $mhs_data['tanggal_daftar'] ?? '2099-12-31';

    // Query Data (Menggunakan logika yang sama dengan ajax_detail)
    $mk_condition = $mk ? "AND j.kode_mk = '$mk'" : "";
    $lab_condition = $lab ? "AND j.kode_lab = '$lab'" : "";
    
    $query = mysqli_query($conn, "SELECT j.pertemuan_ke, j.tanggal, j.jam_mulai, j.jam_selesai, j.materi, j.jenis,
                                         p.status, p.waktu_presensi, mk.nama_mk, l.nama_lab, j.kode_mk
                                         FROM jadwal j
                                         JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                         LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                         LEFT JOIN presensi_mahasiswa p ON j.id = p.jadwal_id AND p.nim = '$nim'
                                         WHERE j.kode_kelas = '$kelas' 
                                         AND j.tanggal BETWEEN '$start_date' AND '$end_date'
                                         $mk_condition
                                         $lab_condition
                                         ORDER BY j.pertemuan_ke ASC, j.tanggal ASC, j.jam_mulai ASC");

    $filename = 'Rincian_' . preg_replace('/[^A-Za-z0-9]/', '_', $nama_mhs) . '_' . $start_date . '_sd_' . $end_date . '.xls';
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
    echo "<p><strong>Nama:</strong> $nama_mhs ($nim)<br>";
    echo "<strong>Kelas:</strong> $nama_kelas<br>";
    echo "<strong>Periode:</strong> " . date('d-m-Y', strtotime($start_date)) . " s/d " . date('d-m-Y', strtotime($end_date)) . "</p>";
    
    echo '<table><thead><tr>
            <th>No</th><th>Pertemuan</th><th>Tanggal</th><th>Jam</th><th>Mata Kuliah</th><th>Materi</th><th>Jenis</th><th>Lab</th><th>Status</th><th>Waktu Presensi</th>
          </tr></thead><tbody>';
          
    $no = 1;
    // Gunakan grouping sederhana untuk menghindari duplikat jadwal paralel di Excel
    $processed_keys = [];
    
    while ($row = mysqli_fetch_assoc($query)) {
        $key = $row['pertemuan_ke'] . '_' . $row['tanggal'] . '_' . $row['jam_mulai'] . '_' . $row['kode_mk'];
        if (in_array($key, $processed_keys) && empty($row['status'])) continue; // Skip duplikat jika belum presensi
        $processed_keys[] = $key;

        $status = $row['status'];
        if (!$status) {
            $jadwal_end = $row['tanggal'] . ' ' . $row['jam_selesai'];

           // [FIX] Validasi terhadap tanggal daftar mahasiswa
           if ($tanggal_daftar > $jadwal_end) {
               $status = 'Belum Daftar';
           } else {
            $status = (strtotime($jadwal_end) < time()) ? 'Alpha' : 'Belum';
           }
        } else {
            $status = ucfirst($status);
        }
        
        echo "<tr>
            <td class='text-center'>" . $no++ . "</td>
            <td class='text-center'>" . $row['pertemuan_ke'] . "</td>
            <td>" . $row['tanggal'] . "</td>
            <td>" . substr($row['jam_mulai'], 0, 5) . " - " . substr($row['jam_selesai'], 0, 5) . "</td>
            <td>" . $row['nama_mk'] . "</td>
            <td>" . $row['materi'] . "</td>
            <td class='text-center'>" . ucfirst($row['jenis']) . "</td>
            <td>" . ($row['nama_lab'] ?: '-') . "</td>
            <td class='text-center'>" . $status . "</td>
            <td class='text-center'>" . ($row['waktu_presensi'] ?: '-') . "</td>
        </tr>";
    }
    echo '</tbody></table></body></html>';
    exit;
}

// Export Excel (CSV format)
if (isset($_GET['export'])) {
    // Hentikan dan bersihkan output buffer yang mungkin sudah terisi oleh index.php
    if (ob_get_length()) ob_end_clean();
    
    $filter_kelas_exp = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';
    $filter_mk_exp = isset($_GET['mk']) ? escape($_GET['mk']) : '';
    $filter_lab_exp = isset($_GET['lab']) ? escape($_GET['lab']) : '';
    $start_date_exp = isset($_GET['start_date']) ? escape($_GET['start_date']) : date('Y-m-01');
    $end_date_exp = isset($_GET['end_date']) ? escape($_GET['end_date']) : date('Y-m-t');

    $where_mhs_exp = ["1=1"];
    $where_jadwal_exp_arr = [];
    if ($filter_kelas_exp) $where_mhs_exp[] = "m.kode_kelas = '$filter_kelas_exp'";
    if ($filter_mk_exp) $where_jadwal_exp_arr[] = "j.kode_mk = '$filter_mk_exp'";
    if ($filter_lab_exp) $where_jadwal_exp_arr[] = "j.kode_lab = '$filter_lab_exp'";
    $where_jadwal_exp = !empty($where_jadwal_exp_arr) ? " AND " . implode(" AND ", $where_jadwal_exp_arr) : "";
    $where_mhs_sql_exp = implode(" AND ", $where_mhs_exp);

    // FIX: Filter mahasiswa yang memiliki jadwal sesuai filter MK/Lab
    if (!empty($where_jadwal_exp)) {
        $where_mhs_sql_exp .= " AND EXISTS (
            SELECT 1 FROM jadwal j_check 
            WHERE j_check.kode_kelas = m.kode_kelas 
            AND j_check.tanggal BETWEEN '$start_date_exp' AND '$end_date_exp'
            " . str_replace('j.', 'j_check.', $where_jadwal_exp) . "
        )";
    }

    // [IMPROVED] Ambil nama kelas untuk nama file dan judul laporan agar spesifik
    $nama_kelas_label = "Semua Kelas";
    $nama_kelas_file = "Semua_Kelas";
    if ($filter_kelas_exp) {
        $q_kelas = mysqli_query($conn, "SELECT nama_kelas FROM kelas WHERE kode_kelas = '$filter_kelas_exp'");
        if ($row_k = mysqli_fetch_assoc($q_kelas)) {
            $nama_kelas_label = $row_k['nama_kelas'];
            $nama_kelas_file = preg_replace('/[^A-Za-z0-9]/', '_', $row_k['nama_kelas']);
        }
    }

    $filename = 'Laporan_' . $nama_kelas_file . '_' . $start_date_exp . '_sd_' . $end_date_exp . '.xls';
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");

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

    echo '<h3 style="text-align:center">LAPORAN PRESENSI MAHASISWA</h3>';
    echo '<p><strong>Periode:</strong> ' . date('d-m-Y', strtotime($start_date_exp)) . ' s/d ' . date('d-m-Y', strtotime($end_date_exp)) . '<br>';
    echo '<strong>Kelas:</strong> ' . $nama_kelas_label . '<br>';
    echo '<strong>Tanggal Cetak:</strong> ' . date('d-m-Y H:i') . '</p>';

    $where_jadwal_for_inhall_exp_arr = [];
    if ($filter_mk_exp) $where_jadwal_for_inhall_exp_arr[] = "jpi.kode_mk = '$filter_mk_exp'";
    if ($filter_lab_exp) $where_jadwal_for_inhall_exp_arr[] = "jpi.kode_lab = '$filter_lab_exp'";
    $where_jadwal_for_inhall_exp = !empty($where_jadwal_for_inhall_exp_arr) ? " AND " . implode(" AND ", $where_jadwal_for_inhall_exp_arr) : "";

    $rekap_export_query = "SELECT m.nim, m.nama, k.nama_kelas, 
                               GROUP_CONCAT(DISTINCT mk.nama_mk SEPARATOR ', ') as all_mk,
                               GROUP_CONCAT(DISTINCT l.nama_lab SEPARATOR ', ') as all_lab,
                               SUM(CASE WHEN p.status = 'hadir' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as hadir,
                               SUM(CASE WHEN p.status = 'izin' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as izin,
                               SUM(CASE WHEN p.status = 'sakit' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as sakit,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status = 'alpha' OR ((p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit', 'alpha')) AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai))) THEN 1 ELSE 0 END) as alpha,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status = 'belum' OR p.status IS NULL) AND CONCAT(j.tanggal, ' ', j.jam_selesai) >= NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as belum,
                               COUNT(DISTINCT CASE WHEN j.jenis != 'inhall' AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN j.id END) as total_pertemuan,
                               (SELECT COUNT(*) FROM penggantian_inhall pi JOIN jadwal jpi ON pi.jadwal_asli_id = jpi.id WHERE pi.nim = m.nim AND pi.status = 'terdaftar' AND pi.status_approval = 'approved' AND jpi.tanggal BETWEEN '$start_date_exp' AND '$end_date_exp' $where_jadwal_for_inhall_exp) as perlu_inhall,
                               (SELECT COUNT(*) FROM penggantian_inhall pi JOIN jadwal jpi ON pi.jadwal_asli_id = jpi.id WHERE pi.nim = m.nim AND pi.status = 'hadir' AND pi.status_approval = 'approved' AND jpi.tanggal BETWEEN '$start_date_exp' AND '$end_date_exp' $where_jadwal_for_inhall_exp) as sudah_inhall
                               FROM mahasiswa m LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas LEFT JOIN jadwal j ON j.kode_kelas = m.kode_kelas AND j.tanggal BETWEEN '$start_date_exp' AND '$end_date_exp' $where_jadwal_exp 
                               LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                               LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                               LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
                               WHERE $where_mhs_sql_exp GROUP BY m.nim, m.nama, k.nama_kelas ORDER BY k.nama_kelas, m.nama";
    $rekap_export = mysqli_query($conn, $rekap_export_query);
    
    $sertakan_detail = isset($_GET['detail']) && $_GET['detail'] == '1';
    $attendance_map = [];
    $meetings = [];

    if ($sertakan_detail) {
        // Ambil detail pertemuan untuk kolom tambahan (P1, P2, dst)
        $detail_sql = "SELECT m.nim, m.tanggal_daftar, j.pertemuan_ke, j.tanggal, j.jam_selesai, l.nama_lab, p.status
                       FROM mahasiswa m
                       JOIN jadwal j ON m.kode_kelas = j.kode_kelas
                       LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                       LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
                       WHERE $where_mhs_sql_exp 
                       AND j.tanggal BETWEEN '$start_date_exp' AND '$end_date_exp' 
                       $where_jadwal_exp
                       AND j.jenis != 'inhall'
                       ORDER BY j.pertemuan_ke";
        
        $detail_res = mysqli_query($conn, $detail_sql);
        
        while ($d = mysqli_fetch_assoc($detail_res)) {
            $p_ke = $d['pertemuan_ke'];
            $meetings[$p_ke] = true;
            
            $status = $d['status'];
            if (!$status) {
                 $jadwal_end = $d['tanggal'] . ' ' . $d['jam_selesai'];
                 $is_past = strtotime($jadwal_end) < time();
                 if ($d['tanggal_daftar'] > $jadwal_end) {
                     $status = 'Belum Daftar';
                 } else {
                     $status = $is_past ? 'Alpha' : 'Belum';
                 }
            } else {
                $status = ucfirst($status);
            }
            
            $lab = $d['nama_lab'] ?: '-';
            $info = "$status ($lab)";
            
            // Jika ada multiple jadwal di pertemuan yang sama (misal beda MK), gabungkan
            if (isset($attendance_map[$d['nim']][$p_ke])) {
                $attendance_map[$d['nim']][$p_ke] .= " | " . $info;
            } else {
                $attendance_map[$d['nim']][$p_ke] = $info;
            }
        }
        
        ksort($meetings); // Urutkan pertemuan P1, P2, dst
    }

    // Header CSV (menggunakan semicolon)
    $header = ["No", "NIM", "Nama", "Kelas", "Daftar Mata Kuliah", "Daftar Lab", "Hadir", "Izin", "Sakit", "Alpha", "Belum", "Perlu Inhall", "Sudah Inhall", "Total Pertemuan", "Persentase Kehadiran"];
    
    if ($sertakan_detail) {
        // Tambahkan kolom pertemuan dinamis ke header
        foreach ($meetings as $m => $val) {
            $header[] = "P$m";
        }
    }
    
    echo '<table class="table-data" border="1">';
    echo '<thead><tr>';
    foreach ($header as $h) {
        echo '<th>' . htmlspecialchars($h) . '</th>';
    }
    echo '</tr></thead>';
    echo '<tbody>';

    $no = 1;
    while ($row = mysqli_fetch_assoc($rekap_export)) {
        $sudah_presensi = $row['hadir'] + $row['izin'] + $row['sakit'] + $row['alpha'];
        $persen = $sudah_presensi > 0 ? round(($row['hadir'] / $sudah_presensi) * 100) : 0;
        
        $line = [$no++, $row['nim'], $row['nama'], $row['nama_kelas'], $row['all_mk'] ?: '-', $row['all_lab'] ?: '-', $row['hadir'], $row['izin'], $row['sakit'], $row['alpha'], $row['belum'], $row['perlu_inhall'], $row['sudah_inhall'], $row['total_pertemuan'], $persen . '%'];
        
        if ($sertakan_detail) {
            // Isi data pertemuan per mahasiswa
            foreach ($meetings as $m => $val) {
                $line[] = isset($attendance_map[$row['nim']][$m]) ? $attendance_map[$row['nim']][$m] : '-';
            }
        }

        echo '<tr>';
        foreach ($line as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</body></html>';
    exit;
}

// Ambil data kelas untuk dropdown filter
$kelas_data = [];
$kelas_qry = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
while ($k = mysqli_fetch_assoc($kelas_qry)) {
    $kelas_data[] = $k;
}

// Default filter adalah string kosong (semua kelas)
$filter_kelas = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';

// [BARU] Ambil nama kelas terpilih untuk ditampilkan di judul PDF/Cetak
$nama_kelas_terpilih = 'Semua Kelas';
foreach ($kelas_data as $k) {
    if ($k['kode_kelas'] == $filter_kelas) {
        $nama_kelas_terpilih = $k['nama_kelas'];
        break;
    }
}

$filter_mk = isset($_GET['mk']) ? escape($_GET['mk']) : '';
$filter_lab = isset($_GET['lab']) ? escape($_GET['lab']) : '';

$start_date = isset($_GET['start_date']) ? escape($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? escape($_GET['end_date']) : date('Y-m-t');

$where_mhs = ["1=1"];
$where_jadwal_arr = [];
if ($filter_kelas) $where_mhs[] = "m.kode_kelas = '$filter_kelas'";
if ($filter_mk) $where_jadwal_arr[] = "j.kode_mk = '$filter_mk'";
if ($filter_lab) $where_jadwal_arr[] = "j.kode_lab = '$filter_lab'";
$where_jadwal = !empty($where_jadwal_arr) ? " AND " . implode(" AND ", $where_jadwal_arr) : "";
$where_mhs_sql = implode(" AND ", $where_mhs);

// FIX: Filter mahasiswa yang memiliki jadwal sesuai filter MK/Lab
if (!empty($where_jadwal)) {
    $where_mhs_sql .= " AND EXISTS (
        SELECT 1 FROM jadwal j_check 
        WHERE j_check.kode_kelas = m.kode_kelas 
        AND j_check.tanggal BETWEEN '$start_date' AND '$end_date'
        " . str_replace('j.', 'j_check.', $where_jadwal) . "
    )";
}

// Pagination
$per_page = 20;
$current_page = get_current_page();

// Hitung total data untuk pagination - hitung berdasarkan grouping
$count_sql = "SELECT COUNT(DISTINCT m.nim) as total
              FROM mahasiswa m
              LEFT JOIN jadwal j ON j.kode_kelas = m.kode_kelas AND j.tanggal BETWEEN '$start_date' AND '$end_date' $where_jadwal
              WHERE $where_mhs_sql";
$count_query = mysqli_query($conn, $count_sql);
$total_data = mysqli_fetch_assoc($count_query)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

$where_jadwal_for_inhall_arr = [];
if ($filter_mk) $where_jadwal_for_inhall_arr[] = "jpi.kode_mk = '$filter_mk'";
if ($filter_lab) $where_jadwal_for_inhall_arr[] = "jpi.kode_lab = '$filter_lab'";
$where_jadwal_for_inhall = !empty($where_jadwal_for_inhall_arr) ? " AND " . implode(" AND ", $where_jadwal_for_inhall_arr) : "";

$base_query = "SELECT m.nim, m.nama, k.nama_kelas, m.kode_kelas,
                               GROUP_CONCAT(DISTINCT mk.nama_mk SEPARATOR ', ') as all_mk,
                               SUM(CASE WHEN p.status = 'hadir' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as hadir,
                               SUM(CASE WHEN p.status = 'izin' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as izin,
                               SUM(CASE WHEN p.status = 'sakit' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as sakit,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status = 'alpha' OR ((p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit', 'alpha')) AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai))) THEN 1 ELSE 0 END) as alpha,
                               SUM(CASE WHEN j.jenis != 'inhall' AND (p.status = 'belum' OR p.status IS NULL) AND CONCAT(j.tanggal, ' ', j.jam_selesai) >= NOW() AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as belum,
                               COUNT(DISTINCT CASE WHEN j.jenis != 'inhall' AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN j.id END) as total_pertemuan,
                               (SELECT COUNT(*) FROM penggantian_inhall pi JOIN jadwal jpi ON pi.jadwal_asli_id = jpi.id WHERE pi.nim = m.nim AND pi.status = 'terdaftar' AND pi.status_approval = 'approved' AND jpi.tanggal BETWEEN '$start_date' AND '$end_date' $where_jadwal_for_inhall) as perlu_inhall,
                               (SELECT COUNT(*) FROM penggantian_inhall pi JOIN jadwal jpi ON pi.jadwal_asli_id = jpi.id WHERE pi.nim = m.nim AND pi.status = 'hadir' AND pi.status_approval = 'approved' AND jpi.tanggal BETWEEN '$start_date' AND '$end_date' $where_jadwal_for_inhall) as sudah_inhall
                               FROM mahasiswa m 
                               LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas
                               LEFT JOIN jadwal j ON j.kode_kelas = m.kode_kelas AND j.tanggal BETWEEN '$start_date' AND '$end_date' $where_jadwal
                               LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                               LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                               LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
                               WHERE $where_mhs_sql
                               GROUP BY m.nim, m.nama, k.nama_kelas, m.kode_kelas
                               ORDER BY k.nama_kelas, m.nama";

// Data rekap dengan pagination (menambahkan LIMIT ke query dasar)
// Status dihitung berdasarkan waktu SEKARANG (NOW()) untuk stabilitas
// Alpha: Jadwal sudah lewat dan tidak ada presensi (hadir/izin/sakit)
// Belum: Jadwal belum selesai
// EXCLUDE jadwal inhall dari statistik (inhall bersifat opsional, tidak mempengaruhi persentase)
$rekap = mysqli_query($conn, $base_query . " LIMIT $offset, $per_page");

// Query untuk cetak/PDF (tanpa pagination) - menggunakan query dasar yang sama
$rekap_print = mysqli_query($conn, $base_query);

// [BARU] Ambil data detail pertemuan untuk tampilan PDF
$print_details = [];
$meetings = [];

// Query diperbarui untuk mengambil kode_mk untuk grouping
$detail_print_sql = "SELECT m.nim, m.tanggal_daftar, j.pertemuan_ke, j.tanggal, j.jam_mulai, j.jam_selesai, l.nama_lab, p.status, j.kode_mk
               FROM mahasiswa m
               JOIN jadwal j ON m.kode_kelas = j.kode_kelas
               LEFT JOIN lab l ON j.kode_lab = l.kode_lab
               LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
               WHERE $where_mhs_sql 
               AND j.tanggal BETWEEN '$start_date' AND '$end_date' 
               $where_jadwal
               AND j.jenis != 'inhall'
               ORDER BY m.nim, j.pertemuan_ke, j.kode_mk, j.jam_mulai";

$detail_print_res = mysqli_query($conn, $detail_print_sql);

// Group data by student and meeting key to handle split labs
$grouped_details = [];
while ($d = mysqli_fetch_assoc($detail_print_res)) {
    $nim = $d['nim'];
    $p_ke = $d['pertemuan_ke'];
    $meetings[$p_ke] = true; // Collect unique meetings
    
    // Key to group parallel schedules (same meeting, same time, same MK)
    $key = $p_ke . '_' . $d['tanggal'] . '_' . $d['jam_mulai'] . '_' . $d['kode_mk'];

    if (!isset($grouped_details[$nim][$key])) {
        $grouped_details[$nim][$key] = [
            'status' => null,
            'attended_lab' => null,
            'all_labs' => [],
            'tanggal' => $d['tanggal'],
            'jam_selesai' => $d['jam_selesai'],
            'tanggal_daftar' => $d['tanggal_daftar'] ?? '2099-12-31'
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

// Process the grouped data to create the final display string for each student's meeting
foreach ($grouped_details as $nim => $meetings_data) {
    foreach ($meetings_data as $key => $data) {
        $p_ke = explode('_', $key)[0];
        $info = '';
        
        $status = $data['status'];
        if (!$status) {
            $jadwal_end = $data['tanggal'] . ' ' . $data['jam_selesai'];
            $is_past = strtotime($jadwal_end) < time();
            if ($data['tanggal_daftar'] > $jadwal_end) {
                $status = 'Belum Daftar';
            } else {
                $status = $is_past ? 'alpha' : 'belum';
            }
        }
        
        $status_display = ucfirst($status);
        
        if ($status === 'hadir' || $status === 'izin' || $status === 'sakit') {
            $lab_name = $data['attended_lab'] ?: '-';
            $info = "$status_display ($lab_name)";
        } else { // Alpha or Belum
            $unique_labs = array_unique($data['all_labs']);
            $lab_list = !empty($unique_labs) ? implode('/', $unique_labs) : '-';
            $info = "$status_display ($lab_list)";
        }
        
        // In case of multiple MKs at the same time for a student (unlikely but possible)
        if (isset($print_details[$nim][$p_ke])) {
            $print_details[$nim][$p_ke] .= " | " . $info;
        } else {
            $print_details[$nim][$p_ke] = $info;
        }
    }
}

ksort($meetings);

$mk_list = mysqli_query($conn, "SELECT * FROM mata_kuliah ORDER BY kode_mk");
$lab_list = mysqli_query($conn, "SELECT * FROM lab ORDER BY kode_lab");
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
                    <h4 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Laporan Presensi</h4>
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
                
                <!-- Filter -->
                <div class="card mb-4 no-print">
                    <div class="card-body p-2 p-md-3">
                        <form method="GET" class="row g-2 g-md-3 align-items-end">
                            <input type="hidden" name="page" value="admin_laporan">
                            <div class="col-6 col-md-2">
                                <label class="form-label small">Dari Tanggal</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date ?>">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small">Sampai Tanggal</label>
                                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date ?>">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small">Kelas</label>
                                <select name="kelas" class="form-select form-select-sm">
                                    <option value="">Semua Kelas</option>
                                <?php foreach ($kelas_data as $k): ?>
                                        <option value="<?= $k['kode_kelas'] ?>" <?= $filter_kelas == $k['kode_kelas'] ? 'selected' : '' ?>>
                                            <?= $k['nama_kelas'] ?>
                                        </option>
                                <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small">Mata Kuliah</label>
                                <select name="mk" class="form-select form-select-sm">
                                    <option value="">Semua MK</option>
                                    <?php while ($m = mysqli_fetch_assoc($mk_list)): ?>
                                        <option value="<?= $m['kode_mk'] ?>" <?= $filter_mk == $m['kode_mk'] ? 'selected' : '' ?>>
                                            <?= $m['nama_mk'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small">Lab</label>
                                <select name="lab" class="form-select form-select-sm">
                                    <option value="">Semua Lab</option>
                                    <?php while ($l = mysqli_fetch_assoc($lab_list)): ?>
                                        <option value="<?= $l['kode_lab'] ?>" <?= $filter_lab == $l['kode_lab'] ? 'selected' : '' ?>>
                                            <?= $l['nama_lab'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-filter me-1"></i>Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Link ke Statistik -->
                <div class="alert alert-info mb-4 no-print">
                    <i class="fas fa-chart-pie me-2"></i>
                    Lihat statistik per kelas, mata kuliah, dan lab di halaman 
                    <a href="index.php?page=admin_statistik" class="alert-link">Statistik Presensi</a>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <strong>Rekap Presensi Per Mahasiswa - <?= date('d M Y', strtotime($start_date)) ?> s/d <?= date('d M Y', strtotime($end_date)) ?></strong>
                    </div>
                    <div class="card-body p-0 p-md-3">
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-lg-block no-print" style="max-height: 70vh; overflow-y: auto;">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>NIM</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Mata Kuliah</th>
                                        <th class="text-center text-success">Hadir</th>
                                        <th class="text-center text-warning">Izin</th>
                                        <th class="text-center text-info">Sakit</th>
                                        <th class="text-center text-danger">Alpha</th>
                                        <th class="text-center text-secondary">Belum</th>
                                        <th class="text-center text-purple">Inhall</th>
                                        <th class="text-center">%</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    mysqli_data_seek($rekap, 0);
                                    $no = 1; 
                                    while ($r = mysqli_fetch_assoc($rekap)): ?>
                                        <?php 
                                        // Persentase dihitung dari yang sudah ada status (bukan belum presensi)
                                        $sudah_presensi = $r['hadir'] + $r['izin'] + $r['sakit'] + $r['alpha'];
                                        $persen = $sudah_presensi > 0 ? round(($r['hadir'] / $sudah_presensi) * 100) : 0;
                                        $total_inhall = $r['perlu_inhall'] + $r['sudah_inhall'];
                                        ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= $r['nim'] ?></td>
                                            <td><?= $r['nama'] ?></td>
                                            <td><span class="badge bg-primary"><?= $r['nama_kelas'] ?></span></td>
                                            <td><small class="text-muted"><?= $r['all_mk'] ?: '-' ?></small></td>
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
                                                <?php if ($total_inhall > 0): ?>
                                                    <span class="badge <?= $r['perlu_inhall'] > 0 ? 'bg-warning' : 'bg-success' ?>" 
                                                          title="<?= $r['sudah_inhall'] ?> sudah diganti, <?= $r['perlu_inhall'] ?> belum">
                                                        <?= $r['sudah_inhall'] ?>/<?= $total_inhall ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
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
                        
                        <!-- Mobile/Tablet Cards -->
                        <div class="d-lg-none no-print">
                            <?php 
                            mysqli_data_seek($rekap, 0);
                            while ($r = mysqli_fetch_assoc($rekap)): 
                                $sudah_presensi = $r['hadir'] + $r['izin'] + $r['sakit'] + $r['alpha'];
                                $persen = $sudah_presensi > 0 ? round(($r['hadir'] / $sudah_presensi) * 100) : 0;
                                $total_inhall = $r['perlu_inhall'] + $r['sudah_inhall'];
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
                                                <div class="small text-muted mt-1">
                                                    <i class="fas fa-book me-1"></i><?= $r['all_mk'] ?: '-' ?>
                                                </div>
                                            </div>
                                            <span class="badge <?= $persen >= 75 ? 'bg-success' : ($persen >= 50 ? 'bg-warning' : 'bg-danger') ?>" style="font-size: 0.9rem;">
                                                <?= $persen ?>%
                                            </span>
                                        </div>
                                        <div class="mb-2">
                                            <button class="btn btn-sm btn-outline-info w-100" onclick="showDetail('<?= $r['nim'] ?>', '<?= $r['nama'] ?>', '<?= $r['kode_kelas'] ?>')">
                                                <i class="fas fa-list me-1"></i> Lihat Rincian Kehadiran
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
                                            <?php if ($total_inhall > 0): ?>
                                                <span class="text-purple"><i class="fas fa-redo me-1"></i>Inhall: <?= $r['sudah_inhall'] ?>/<?= $total_inhall ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2 no-print">
                            <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                            <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_laporan', ['kelas' => $filter_kelas, 'mk' => $filter_mk, 'lab' => $filter_lab, 'start_date' => $start_date, 'end_date' => $end_date]) ?>
                        </div>

                        <!-- ==================== FOR PRINT/PDF VIEW (ALL DATA) ==================== -->
                        <div class="print-only">
                            <h4 class="mb-3 text-center">Laporan Presensi Mahasiswa</h4>
                            <p class="text-center text-muted">Periode: <?= date('d M Y', strtotime($start_date)) ?> s/d <?= date('d M Y', strtotime($end_date)) ?> | Kelas: <?= $nama_kelas_terpilih ?></p>
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>NIM</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Mata Kuliah</th>
                                        <th class="text-center">H</th>
                                        <th class="text-center">I</th>
                                        <th class="text-center">S</th>
                                        <th class="text-center">A</th>
                                        <th class="text-center">Inhall</th>
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
                                        $total_inhall_print = $r_print['perlu_inhall'] + $r_print['sudah_inhall'];
                                    ?>
                                        <tr>
                                            <td><?= $no_print++ ?></td>
                                            <td><?= $r_print['nim'] ?></td>
                                            <td><?= $r_print['nama'] ?></td>
                                            <td><?= $r_print['nama_kelas'] ?></td>
                                            <td><?= $r_print['all_mk'] ?: '-' ?></td>
                                            <td class="text-center"><?= $r_print['hadir'] ?></td>
                                            <td class="text-center"><?= $r_print['izin'] ?></td>
                                            <td class="text-center"><?= $r_print['sakit'] ?></td>
                                            <td class="text-center"><?= $r_print['alpha'] ?></td>
                                            <td class="text-center">
                                                <?php if ($total_inhall_print > 0): ?>
                                                    <?= $r_print['sudah_inhall'] ?>/<?= $total_inhall_print ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
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
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-history me-2"></i>Rincian Kehadiran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 id="detailNama" class="fw-bold mb-3"></h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light"><tr><th class="text-center" width="50">P</th><th>Waktu</th><th>Materi</th><th class="text-center">Status Asli</th><th class="text-center" width="150">Ubah Status</th></tr></thead>
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
    .sidebar, .no-print, #mobileHeader { 
        display: none !important; 
    }
    .print-only {
        display: block !important;
    }
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
    /* Helper untuk menyembunyikan detail saat print biasa jika checkbox tidak dicentang */
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

[data-theme="dark"] .alert-info {
    background-color: rgba(13, 202, 240, 0.15);
    border-color: rgba(13, 202, 240, 0.3);
    color: #6edff6;
}
[data-theme="dark"] .alert-info .alert-link { color: #fff; }

.status-select.bg-success,
.status-select.bg-danger {
    color: white;
}
.status-select.bg-warning,
.status-select.bg-info,
.status-select.bg-secondary {
    color: #212529;
}
[data-theme="dark"] .status-select.bg-warning,
[data-theme="dark"] .status-select.bg-info {
    color: #212529 !important; /* Bootstrap override */
}

/* Sticky Header agar judul kolom tetap terlihat saat scroll ke bawah */
.table-responsive thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
}

/* Custom Colors */
.text-purple { color: #6f42c1; }
[data-theme="dark"] .text-purple { color: #a685e0 !important; }
</style>

<!-- Library html2pdf.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function exportExcel() {
    const sertakanDetail = document.getElementById('sertakanDetail').checked;
    let url = `index.php?page=admin_laporan&export=1&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&kelas=<?= $filter_kelas ?>&mk=<?= $filter_mk ?>&lab=<?= $filter_lab ?>`;
    if (sertakanDetail) {
        url += '&detail=1';
    }
    window.location.href = url;
}

function printPage() {
    // Sinkronisasi checkbox dengan tampilan cetak native
    const sertakanDetail = document.getElementById('sertakanDetail').checked;
    const printSection = document.querySelector('.print-only');
    
    if (!sertakanDetail) {
        printSection.classList.add('hide-detail-print');
    } else {
        printSection.classList.remove('hide-detail-print');
    }
    window.print();
}

function showDetail(nim, nama, kelas) {
    document.getElementById('detailNama').innerText = nama + ' (' + nim + ')';
    document.getElementById('detailContent').innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Memuat...</td></tr>';
    
    var myModal = new bootstrap.Modal(document.getElementById('modalDetail'));
    myModal.show();
    
    // [BARU] Update link tombol export di modal sesuai mahasiswa yang dipilih
    const mk = document.querySelector('select[name="mk"]').value;
    const lab = document.querySelector('select[name="lab"]').value;
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    
    document.getElementById('btnExportDetail').href = `index.php?page=admin_laporan&export_detail_mhs=1&nim=${nim}&kelas=${kelas}&mk=${mk}&lab=${lab}&start_date=${startDate}&end_date=${endDate}`;
    const url = `index.php?page=admin_laporan&ajax_detail=1&nim=${nim}&kelas=${kelas}&mk=${mk}&lab=${lab}&start_date=${startDate}&end_date=${endDate}`;

    fetch(url)
        .then(response => response.text())
        .then(html => { document.getElementById('detailContent').innerHTML = html; });
}

function updateStatus(selectElement, jadwalId, nim) {
    const newStatus = selectElement.value;
    const initialStatus = selectElement.getAttribute('data-initial-status');
    
    // Mapping label untuk pesan konfirmasi yang lebih jelas
    const statusLabels = {
        'hadir': 'Hadir', 'izin': 'Izin', 'sakit': 'Sakit', 'alpha': 'Alpha',
        'inhall_skip': 'Tidak Ikut', 'unregistered': 'Belum Daftar', '': 'Belum Dipilih'
    };
    
    const oldLabel = statusLabels[initialStatus] || initialStatus || 'Belum';
    const newLabel = statusLabels[newStatus] || newStatus;

    // Konfirmasi perubahan untuk mencegah kesalahan (human error)
    if (!confirm(`Konfirmasi Perubahan:\nApakah Anda yakin ingin mengubah status dari "${oldLabel}" menjadi "${newLabel}"?`)) {
        // Jika batal, kembalikan ke status awal
        selectElement.value = (initialStatus === 'inhall_skip') ? '' : initialStatus;
        return;
    }

    const indicator = selectElement.nextElementSibling;
    
    indicator.innerHTML = '<i class="fas fa-spinner fa-spin text-primary"></i>';
    
    const formData = new FormData();
    formData.append('jadwal_id', jadwalId);
    formData.append('nim', nim);
    formData.append('status', newStatus);
    
    fetch('index.php?page=api_update_presensi_admin', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            indicator.innerHTML = '<i class="fas fa-check-circle text-success" title="Tersimpan!"></i>';
            
            // Update select color
            selectElement.className = 'form-select form-select-sm status-select'; // Reset classes
            const colorMap = { 'hadir': 'bg-success text-white', 'izin': 'bg-warning text-dark', 'sakit': 'bg-info text-dark', 'alpha': 'bg-danger text-white' };
            selectElement.classList.add(...(colorMap[newStatus] || 'bg-light').split(' '));
            selectElement.setAttribute('data-initial-status', newStatus);

        } else {
            indicator.innerHTML = '<i class="fas fa-times-circle text-danger" title="' + data.message + '"></i>';
            // Revert to initial status on failure
            selectElement.value = selectElement.getAttribute('data-initial-status');
        }
        // Clear indicator after a few seconds
        setTimeout(() => { indicator.innerHTML = ''; }, 3000);
    })
    .catch(error => {
        indicator.innerHTML = '<i class="fas fa-exclamation-triangle text-danger" title="Network error"></i>';
        console.error('Error:', error);
        setTimeout(() => { indicator.innerHTML = ''; }, 3000);
    });
}

function exportPDF() {
    const originalElement = document.querySelector('.print-only');
    const elementToPrint = originalElement.cloneNode(true);
    
    elementToPrint.classList.remove('print-only');
    elementToPrint.style.display = 'block';
    elementToPrint.style.backgroundColor = '#ffffff';
    elementToPrint.style.color = '#000000'; 
    elementToPrint.style.padding = '20px';
    elementToPrint.style.fontSize = '11px'; // Perkecil font agar muat
    
    elementToPrint.querySelectorAll('*').forEach(el => { el.style.color = '#000000'; });
    
    // [FIX] Handle detail columns based on checkbox
    const sertakanDetail = document.getElementById('sertakanDetail').checked;
    if (!sertakanDetail) {
        elementToPrint.querySelectorAll('.detail-col').forEach(el => el.remove());
    }

    // Fix table borders explicitly (Paksa border hitam)
    elementToPrint.querySelectorAll('table, th, td').forEach(el => {
        el.style.border = '1px solid #000000';
        el.style.borderCollapse = 'collapse';
    });
    
    const tableHeader = elementToPrint.querySelector('thead');
    if (tableHeader) {
        tableHeader.style.backgroundColor = '#0066cc';
        tableHeader.querySelectorAll('th').forEach(th => { 
            th.style.color = '#ffffff'; 
            th.style.backgroundColor = '#0066cc';
        });
    }
    
    // Perkecil padding tabel agar lebih hemat tempat
    elementToPrint.querySelectorAll('th, td').forEach(cell => {
        cell.style.padding = '4px 5px';
    });
    
    const wrapper = document.createElement('div');
    wrapper.style.position = 'fixed';
    wrapper.style.left = '-10000px';
    wrapper.style.top = '0';
    wrapper.style.width = '1100px';
    wrapper.appendChild(elementToPrint);
    document.body.appendChild(wrapper);

    const opt = {
        margin: 10,
        filename: 'laporan_presensi_<?= date("Y-m-d_His") ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, scrollY: 0 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
        pagebreak: { mode: ['css', 'legacy'] }
    };

    html2pdf().set(opt).from(elementToPrint).save().then(function() {
        document.body.removeChild(wrapper);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
