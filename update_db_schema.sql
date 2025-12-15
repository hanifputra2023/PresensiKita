-- SQL Commands to update the database schema for many-to-many relationship between labs and courses.

-- Step 1: Drop the existing foreign key constraint from the 'lab' table.
ALTER TABLE `lab` DROP FOREIGN KEY `fk_lab_matakuliah`;

-- Step 2: Drop the 'kode_mk' column from the 'lab' table.
ALTER TABLE `lab` DROP COLUMN `kode_mk`;

-- Step 3: Create the new junction table 'lab_matakuliah'.
CREATE TABLE `lab_matakuliah` (
  `id_lab` INT(11) NOT NULL,
  `kode_mk` VARCHAR(10) NOT NULL,
  PRIMARY KEY (`id_lab`, `kode_mk`),
  INDEX `idx_lab_matakuliah_lab` (`id_lab`),
  INDEX `idx_lab_matakuliah_mk` (`kode_mk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Step 4: Add foreign key constraints to the new junction table.
ALTER TABLE `lab_matakuliah`
  ADD CONSTRAINT `fk_labmatakuliah_lab` FOREIGN KEY (`id_lab`) REFERENCES `lab` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_labmatakuliah_matakuliah` FOREIGN KEY (`kode_mk`) REFERENCES `mata_kuliah` (`kode_mk`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Step 5: Migrate existing data from the old structure to the new junction table.
-- This assumes the old 'lab' table had IDs 1, 2, 3, 4 corresponding to MK001, MK002, MK003, MK004 respectively.
INSERT INTO `lab_matakuliah` (`id_lab`, `kode_mk`) VALUES
(1, 'MK001'),
(2, 'MK002'),
(3, 'MK003'),
(4, 'MK004');

COMMIT;

