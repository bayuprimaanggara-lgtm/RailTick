<?php
session_start();
include "../config/koneksi.php";
include "../config/operational_status.php";
sync_operational_status($conn);

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";
$filter_date = isset($_GET['tanggal']) && $_GET['tanggal'] !== "" ? mysqli_real_escape_string($conn, $_GET['tanggal']) : date('Y-m-d');
$role_filter = isset($_GET['role']) && $_GET['role'] !== "" ? mysqli_real_escape_string($conn, $_GET['role']) : 'masinis';

if (isset($_POST['tambah'])) {
    $id_run = (int) $_POST['id_run'];
    $id_staff = (int) $_POST['id_staff'];
    $role_tugas = mysqli_real_escape_string($conn, $_POST['role_tugas']);

    $run_q = mysqli_query($conn, "
        SELECT run.tanggal_berangkat, run.status_run,
               TIMESTAMP(run.tanggal_berangkat, s.jam_berangkat) AS mulai_tugas,
               TIMESTAMP(
                   DATE_ADD(run.tanggal_berangkat, INTERVAL IF(s.jam_tiba < s.jam_berangkat, 1, 0) DAY),
                   s.jam_tiba
               ) AS selesai_tugas
        FROM train_runs run
        JOIN schedules s ON s.id_schedule = run.id_schedule
        WHERE run.id_run='$id_run'
        LIMIT 1
    ");
    $staff_q = mysqli_query($conn, "SELECT jabatan, status_staff FROM staff WHERE id_staff='$id_staff' LIMIT 1");
    $run = mysqli_fetch_assoc($run_q);
    $staff = mysqli_fetch_assoc($staff_q);

    if (!$run || !$staff) {
        $message = "Data perjalanan atau petugas tidak ditemukan.";
    } elseif ($staff['status_staff'] !== 'aktif') {
        $message = "Petugas tidak aktif.";
    } elseif ($staff['jabatan'] !== $role_tugas) {
        $message = "Jabatan petugas tidak sesuai dengan peran tugas.";
    } elseif (!in_array($run['status_run'], ['terjadwal', 'berjalan'], true)) {
        $message = "Perjalanan ini sudah selesai atau batal.";
    } else {
        $tanggal = mysqli_real_escape_string($conn, $run['tanggal_berangkat']);
        $mulai_tugas = mysqli_real_escape_string($conn, $run['mulai_tugas']);
        $selesai_tugas = mysqli_real_escape_string($conn, $run['selesai_tugas']);
        $already_q = mysqli_query($conn, "
            SELECT ca.id_assignment
            FROM crew_assignments ca
            JOIN train_runs tr ON tr.id_run = ca.id_run
            JOIN schedules sc ON sc.id_schedule = tr.id_schedule
            WHERE ca.id_staff='$id_staff'
              AND tr.status_run IN ('terjadwal', 'berjalan')
              AND NOT (
                  TIMESTAMP(
                      DATE_ADD(tr.tanggal_berangkat, INTERVAL IF(sc.jam_tiba < sc.jam_berangkat, 1, 0) DAY),
                      sc.jam_tiba
                  ) <= '$mulai_tugas'
                  OR TIMESTAMP(tr.tanggal_berangkat, sc.jam_berangkat) >= '$selesai_tugas'
              )
            LIMIT 1
        ");
        $count_role_q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM crew_assignments WHERE id_run='$id_run' AND role_tugas='$role_tugas'");
        $count_role = mysqli_fetch_assoc($count_role_q);
        $limit = $role_tugas === 'pramuniaga' ? 3 : 1;

        if (mysqli_num_rows($already_q) > 0) {
            $message = "Petugas ini masih punya tugas aktif yang waktunya bentrok.";
        } elseif ((int) $count_role['total'] >= $limit) {
            $message = "Kuota $role_tugas untuk perjalanan ini sudah penuh.";
        } else {
            mysqli_query($conn, "INSERT INTO crew_assignments (id_run, id_staff, role_tugas) VALUES ('$id_run', '$id_staff', '$role_tugas')");
            header("Location: assignments.php?tanggal=$tanggal&role=$role_tugas&status=added");
            exit();
        }
    }
}

if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM crew_assignments WHERE id_assignment='$id'");
    header("Location: assignments.php?tanggal=$filter_date&role=$role_filter");
    exit();
}

$counts = [];
foreach (['masinis','kondektur','pramuniaga'] as $role) {
    $q = mysqli_query($conn, "
        SELECT
            (SELECT COUNT(*) FROM staff WHERE jabatan='$role' AND status_staff='aktif') AS total_staff,
            (SELECT COUNT(DISTINCT ca.id_staff)
             FROM crew_assignments ca
             JOIN train_runs tr ON tr.id_run = ca.id_run
             JOIN staff st ON st.id_staff = ca.id_staff
             WHERE st.jabatan='$role'
               AND tr.tanggal_berangkat='$filter_date'
               AND tr.status_run IN ('terjadwal', 'berjalan')) AS bertugas
    ");
    $counts[$role] = mysqli_fetch_assoc($q);
}

$runs = mysqli_query($conn, "
    SELECT run.id_run, s.nama_kereta, s.jam_berangkat,
           COALESCE(sa.nama_station, r.asal) AS asal,
           COALESCE(st.nama_station, r.tujuan) AS tujuan
    FROM train_runs run
    JOIN schedules s ON s.id_schedule = run.id_schedule
    LEFT JOIN train_routes tr ON tr.id_route_kai = s.id_route_kai
    LEFT JOIN stations sa ON sa.id_station = tr.asal_awal_station_id
    LEFT JOIN stations st ON st.id_station = tr.tujuan_akhir_station_id
    LEFT JOIN routes r ON r.id_route = s.id_route
    WHERE run.tanggal_berangkat='$filter_date' AND run.status_run IN ('terjadwal', 'berjalan')
    ORDER BY s.jam_berangkat ASC, s.nama_kereta ASC
");

$available_staff = mysqli_query($conn, "
    SELECT st.*
    FROM staff st
    WHERE st.jabatan='$role_filter'
      AND st.status_staff='aktif'
      AND NOT EXISTS (
          SELECT 1
          FROM crew_assignments ca
          JOIN train_runs tr ON tr.id_run = ca.id_run
          WHERE ca.id_staff = st.id_staff
            AND tr.tanggal_berangkat = '$filter_date'
            AND tr.status_run IN ('terjadwal', 'berjalan')
      )
    ORDER BY st.nama_staff ASC
");

$assignments = mysqli_query($conn, "
    SELECT ca.*, staff.nama_staff, staff.nip, run.tanggal_berangkat, run.status_run, s.nama_kereta, s.jam_berangkat,
           COALESCE(sa.nama_station, r.asal) AS asal,
           COALESCE(st.nama_station, r.tujuan) AS tujuan
    FROM crew_assignments ca
    JOIN staff ON staff.id_staff = ca.id_staff
    JOIN train_runs run ON run.id_run = ca.id_run
    JOIN schedules s ON s.id_schedule = run.id_schedule
    LEFT JOIN train_routes tr ON tr.id_route_kai = s.id_route_kai
    LEFT JOIN stations sa ON sa.id_station = tr.asal_awal_station_id
    LEFT JOIN stations st ON st.id_station = tr.tujuan_akhir_station_id
    LEFT JOIN routes r ON r.id_route = s.id_route
    WHERE run.tanggal_berangkat='$filter_date'
    ORDER BY s.jam_berangkat ASC, ca.role_tugas ASC, staff.nama_staff ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penugasan Kru - RailTick Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap'); body{font-family:'Poppins',sans-serif}</style>
</head>
<body class="bg-gray-50 flex">
<?php include "layout/sidebar.php"; ?>
<main class="flex-1 min-h-screen">
    <header class="h-20 bg-white border-b border-slate-800/10 flex items-center justify-between px-8 sticky top-0 z-30">
        <h1 class="text-xl font-bold text-gray-800">Penugasan Petugas</h1>
        <div class="text-right hidden sm:block">
            <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'Administrator'); ?></p>
            <p class="text-[10px] text-green-500 font-black uppercase tracking-widest">Admin Online</p>
        </div>
    </header>
    <div class="p-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
            <?php foreach (['masinis','kondektur','pramuniaga'] as $role): ?>
                <?php $tersedia = (int)$counts[$role]['total_staff'] - (int)$counts[$role]['bertugas']; ?>
                <div class="bg-white p-6 rounded-[1.5rem] border border-gray-100 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1"><?php echo $role; ?> tersedia</p>
                    <h3 class="text-3xl font-black text-slate-800"><?php echo max(0, $tersedia); ?></h3>
                    <p class="text-xs text-gray-400 mt-1">Total aktif <?php echo (int)$counts[$role]['total_staff']; ?>, bertugas <?php echo (int)$counts[$role]['bertugas']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <section class="bg-white p-8 rounded-[2rem] border border-gray-100 shadow-sm h-fit">
                <h2 class="font-bold text-gray-800 mb-2">Tambah Penugasan</h2>
                <p class="text-xs text-gray-400 mb-6">Masinis dan kondektur maksimal 1 orang per perjalanan; pramuniaga maksimal 3 orang.</p>
                <?php if ($message): ?>
                    <div class="mb-5 bg-red-50 text-red-600 p-4 rounded-xl text-sm font-semibold"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <form method="GET" class="grid grid-cols-2 gap-3 mb-5">
                    <input type="date" name="tanggal" value="<?php echo htmlspecialchars($filter_date); ?>" class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none">
                    <select name="role" onchange="this.form.submit()" class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none">
                        <?php foreach (['masinis','kondektur','pramuniaga'] as $role): ?>
                            <option value="<?php echo $role; ?>" <?php echo $role_filter==$role?'selected':''; ?>><?php echo ucfirst($role); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="col-span-2 bg-slate-900 text-white py-3 rounded-xl text-xs font-bold uppercase">Terapkan Filter</button>
                </form>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="tambah" value="1">
                    <input type="hidden" name="role_tugas" value="<?php echo htmlspecialchars($role_filter); ?>">
                    <select name="id_run" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                        <option value="">Pilih perjalanan</option>
                        <?php while ($run = mysqli_fetch_assoc($runs)): ?>
                            <option value="<?php echo $run['id_run']; ?>">
                                <?php echo htmlspecialchars(date('H:i', strtotime($run['jam_berangkat'])) . " / " . $run['nama_kereta'] . " / " . $run['asal'] . " - " . $run['tujuan']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <select name="id_staff" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                        <option value="">Pilih <?php echo htmlspecialchars($role_filter); ?> tersedia</option>
                        <?php while ($staf = mysqli_fetch_assoc($available_staff)): ?>
                            <option value="<?php echo $staf['id_staff']; ?>"><?php echo htmlspecialchars($staf['nama_staff'] . " / " . $staf['nip']); ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-xl uppercase text-xs tracking-widest">Simpan Penugasan</button>
                </form>
            </section>
            <section class="xl:col-span-2 bg-white rounded-[2rem] border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-bold text-gray-800">Daftar Tugas <?php echo date('d M Y', strtotime($filter_date)); ?></h2>
                    <span class="text-[10px] font-black uppercase bg-blue-50 text-blue-600 px-4 py-1.5 rounded-full"><?php echo mysqli_num_rows($assignments); ?> tugas</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-400 text-[10px] uppercase tracking-widest">
                        <tr><th class="px-6 py-4">Petugas</th><th class="px-6 py-4">Tugas</th><th class="px-6 py-4">Perjalanan</th><th class="px-6 py-4">Jam</th><th class="px-6 py-4">Status</th><th class="px-6 py-4 text-center">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                        <?php if (mysqli_num_rows($assignments) == 0): ?>
                            <tr><td colspan="6" class="px-6 py-16 text-center text-gray-400 text-sm">Belum ada petugas yang ditugaskan.</td></tr>
                        <?php endif; ?>
                        <?php while ($row = mysqli_fetch_assoc($assignments)): ?>
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-6 py-4"><p class="font-bold text-gray-800"><?php echo htmlspecialchars($row['nama_staff']); ?></p><p class="text-[10px] text-gray-400 font-bold uppercase"><?php echo htmlspecialchars($row['nip']); ?></p></td>
                                <td class="px-6 py-4 capitalize font-black text-blue-600"><?php echo htmlspecialchars($row['role_tugas']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($row['nama_kereta']); ?> / <?php echo htmlspecialchars($row['asal']); ?> - <?php echo htmlspecialchars($row['tujuan']); ?></td>
                                <td class="px-6 py-4 font-black text-slate-700"><?php echo date('H:i', strtotime($row['jam_berangkat'])); ?></td>
                                <td class="px-6 py-4"><span class="text-[10px] font-black uppercase px-3 py-1 rounded-full <?php echo operational_status_badge_class($row['status_run']); ?>"><?php echo htmlspecialchars($row['status_run']); ?></span></td>
                                <td class="px-6 py-4 text-center"><a href="?tanggal=<?php echo htmlspecialchars($filter_date); ?>&role=<?php echo htmlspecialchars($role_filter); ?>&hapus=<?php echo $row['id_assignment']; ?>" onclick="return confirm('Hapus penugasan ini?')" class="text-xs font-bold text-red-500 hover:underline">Hapus</a></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</main>
</body>
</html>
