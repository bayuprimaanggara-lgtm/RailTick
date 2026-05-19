ALTER TABLE users
    MODIFY role enum('user','admin','masinis','kondektur','pramuniaga') NOT NULL DEFAULT 'user';

ALTER TABLE schedules
    MODIFY id_route int(11) NULL;

CREATE TABLE IF NOT EXISTS staff (
    id_staff int(11) NOT NULL AUTO_INCREMENT,
    id_user int(11) DEFAULT NULL,
    nama_staff varchar(100) NOT NULL,
    nip varchar(30) NOT NULL,
    jabatan enum('masinis','kondektur','pramuniaga') NOT NULL,
    no_telp varchar(20) DEFAULT NULL,
    status_staff enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id_staff),
    UNIQUE KEY uq_staff_nip (nip),
    UNIQUE KEY uq_staff_user (id_user),
    CONSTRAINT fk_staff_user FOREIGN KEY (id_user) REFERENCES users(id_user)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS train_runs (
    id_run int(11) NOT NULL AUTO_INCREMENT,
    id_schedule int(11) NOT NULL,
    tanggal_berangkat date NOT NULL,
    status_run enum('terjadwal','berjalan','selesai','batal') NOT NULL DEFAULT 'terjadwal',
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id_run),
    UNIQUE KEY uq_train_run_schedule_date (id_schedule, tanggal_berangkat),
    KEY idx_train_runs_date (tanggal_berangkat),
    CONSTRAINT fk_train_runs_schedule FOREIGN KEY (id_schedule) REFERENCES schedules(id_schedule)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crew_assignments (
    id_assignment int(11) NOT NULL AUTO_INCREMENT,
    id_run int(11) NOT NULL,
    id_staff int(11) NOT NULL,
    role_tugas enum('masinis','kondektur','pramuniaga') NOT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id_assignment),
    UNIQUE KEY uq_assignment_run_staff (id_run, id_staff),
    KEY idx_assignment_staff (id_staff),
    CONSTRAINT fk_assignment_run FOREIGN KEY (id_run) REFERENCES train_runs(id_run)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_assignment_staff FOREIGN KEY (id_staff) REFERENCES staff(id_staff)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO train_runs (id_schedule, tanggal_berangkat, status_run)
SELECT s.id_schedule, DATE_ADD(CURDATE(), INTERVAL d.n DAY), 'terjadwal'
FROM schedules s
JOIN (
    SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3
    UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6
) d
WHERE s.id_route_kai IS NOT NULL
  AND s.status_jadwal = 'aktif';

INSERT IGNORE INTO staff (nama_staff, nip, jabatan, no_telp, status_staff) VALUES
('Budi Santoso','M001','masinis','081100000001','aktif'),
('Agus Pratama','M002','masinis','081100000002','aktif'),
('Dewi Lestari','K001','kondektur','081100000003','aktif'),
('Rizky Ramadhan','K002','kondektur','081100000004','aktif'),
('Sinta Amelia','P001','pramuniaga','081100000005','aktif'),
('Nadia Putri','P002','pramuniaga','081100000006','aktif'),
('Fajar Nugroho','P003','pramuniaga','081100000007','aktif');

INSERT IGNORE INTO users (nama_lengkap, email, username, password, no_telp, nik, role) VALUES
('Budi Santoso','masinis1@railtick.local','masinis1','masinis123','081100000001','M001','masinis'),
('Dewi Lestari','kondektur1@railtick.local','kondektur1','kondektur123','081100000003','K001','kondektur'),
('Sinta Amelia','pramuniaga1@railtick.local','pramuniaga1','pramuniaga123','081100000005','P001','pramuniaga');

UPDATE staff SET id_user = (SELECT id_user FROM users WHERE username='masinis1') WHERE nip='M001';
UPDATE staff SET id_user = (SELECT id_user FROM users WHERE username='kondektur1') WHERE nip='K001';
UPDATE staff SET id_user = (SELECT id_user FROM users WHERE username='pramuniaga1') WHERE nip='P001';

SET @first_run_id := (
    SELECT run.id_run
    FROM train_runs run
    JOIN schedules s ON s.id_schedule = run.id_schedule
    WHERE run.tanggal_berangkat = CURDATE()
      AND run.status_run = 'terjadwal'
    ORDER BY s.jam_berangkat ASC
    LIMIT 1
);

INSERT IGNORE INTO crew_assignments (id_run, id_staff, role_tugas)
SELECT @first_run_id, id_staff, jabatan
FROM staff
WHERE nip IN ('M001','K001','P001','P002')
  AND @first_run_id IS NOT NULL;
