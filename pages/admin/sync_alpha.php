<?php
// Pastikan hanya admin yang bisa akses
cek_role(['admin']);

// Jalankan fungsi auto set alpha yang sudah ada di fungsi.php
$jumlah_update = auto_set_alpha();

if ($jumlah_update > 0) {
    set_alert('success', "Sinkronisasi Alpha berhasil! $jumlah_update data presensi diperbarui menjadi Alpha.");
} else {
    set_alert('info', "Data sudah sinkron. Tidak ada data presensi yang perlu diubah menjadi Alpha.");
}
header("Location: index.php?page=admin_dashboard");
exit;