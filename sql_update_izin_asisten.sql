-- SQL untuk menambah fitur approval izin asisten
-- Jalankan query ini di phpMyAdmin

-- Tambah kolom untuk approval izin asisten
ALTER TABLE `absen_asisten` 
ADD COLUMN `status_approval` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER `catatan`,
ADD COLUMN `approved_by` INT(11) DEFAULT NULL AFTER `status_approval`,
ADD COLUMN `approved_at` DATETIME DEFAULT NULL AFTER `approved_by`,
ADD COLUMN `alasan_reject` TEXT DEFAULT NULL AFTER `approved_at`;

-- Tambah foreign key untuk approved_by (opsional)
ALTER TABLE `absen_asisten`
ADD CONSTRAINT `fk_absen_asisten_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- Update existing records yang izin/sakit menjadi approved (agar data lama tidak bermasalah)
UPDATE `absen_asisten` 
SET `status_approval` = 'approved' 
WHERE `status` IN ('izin', 'sakit') AND `status_approval` IS NULL;
