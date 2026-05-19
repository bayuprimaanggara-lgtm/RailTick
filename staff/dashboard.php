<?php
session_start();
include "../config/koneksi.php";
include "../config/operational_status.php";
sync_operational_status($conn);

$allowed_roles = ['masinis', 'kondektur', 'pramuniaga'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles, true)) {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = (int) $_SESSION['user_id'];
$staff_q = mysqli_query($conn, "SELECT * FROM staff WHERE id_user='$id_user' LIMIT 1");
$staff = mysqli_fetch_assoc($staff_q);
$today = date('Y-m-d');

$assignments = false;
if ($staff) {
    $id_staff = (int) $staff['id_staff'];
    $assignments = mysqli_query($conn, "
        SELECT ca.role_tugas, trun.tanggal_berangkat, trun.status_run,
               s.nama_kereta, s.jam_berangkat, s.jam_tiba,
               COALESCE(sa.nama_station, r.asal) AS asal,
               COALESCE(st.nama_station, r.tujuan) AS tujuan
        FROM crew_assignments ca
        JOIN train_runs trun ON trun.id_run = ca.id_run
        JOIN schedules s ON s.id_schedule = trun.id_schedule
        LEFT JOIN train_routes tr ON tr.id_route_kai = s.id_route_kai
        LEFT JOIN stations sa ON sa.id_station = tr.asal_awal_station_id
        LEFT JOIN stations st ON st.id_station = tr.tujuan_akhir_station_id
        LEFT JOIN routes r ON r.id_route = s.id_route
        WHERE ca.id_staff='$id_staff'
          AND trun.tanggal_berangkat >= '$today'
        ORDER BY trun.tanggal_berangkat ASC, s.jam_berangkat ASC
    ");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas - RailTick</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap'); body{font-family:'Poppins',sans-serif}</style>
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-slate-900 text-white px-8 py-5 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-red-500 rounded-xl flex items-center justify-center"><i class="fas fa-train"></i></div>
            <div>
                <h1 class="font-black tracking-tight">RailTick Petugas</h1>
                <p class="text-[10px] uppercase tracking-widest text-slate-400"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
            </div>
        </div>
        <a href="../auth/logout.php" class="bg-white/10 hover:bg-red-500 px-5 py-2 rounded-xl text-xs font-bold uppercase tracking-widest">Logout</a>
    </header>
    <main class="p-8 max-w-6xl mx-auto">
        <section class="bg-white p-8 rounded-[2rem] border border-gray-100 shadow-sm mb-8">
            <p class="text-xs text-gray-400 uppercase tracking-widest font-black mb-1">Selamat bertugas</p>
            <h2 class="text-3xl font-black text-slate-800"><?php echo htmlspecialchars($staff['nama_staff'] ?? ($_SESSION['full_name'] ?? $_SESSION['nama'])); ?></h2>
            <p class="text-sm text-gray-500 mt-2">Dashboard ini menampilkan tugas operasional yang diberikan admin.</p>
        </section>

        <section class="bg-white rounded-[2rem] border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-bold text-gray-800">Jadwal Tugas Mendatang</h3>
                <span class="text-[10px] font-black uppercase bg-blue-50 text-blue-600 px-4 py-1.5 rounded-full">Mulai <?php echo date('d M Y'); ?></span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-400 text-[10px] uppercase tracking-widest">
                    <tr><th class="px-6 py-4">Tanggal</th><th class="px-6 py-4">Kereta</th><th class="px-6 py-4">Rute</th><th class="px-6 py-4">Jam</th><th class="px-6 py-4">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                    <?php if (!$assignments || mysqli_num_rows($assignments) == 0): ?>
                        <tr><td colspan="5" class="px-6 py-16 text-center text-gray-400 text-sm">Belum ada tugas mendatang.</td></tr>
                    <?php endif; ?>
                    <?php if ($assignments): ?>
                        <?php while ($row = mysqli_fetch_assoc($assignments)): ?>
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-6 py-4 font-black text-blue-600"><?php echo date('d M Y', strtotime($row['tanggal_berangkat'])); ?></td>
                                <td class="px-6 py-4 font-bold text-gray-800"><?php echo htmlspecialchars($row['nama_kereta']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($row['asal']); ?> <i class="fas fa-arrow-right text-gray-300 mx-2"></i> <?php echo htmlspecialchars($row['tujuan']); ?></td>
                                <td class="px-6 py-4 font-black text-slate-700"><?php echo date('H:i', strtotime($row['jam_berangkat'])); ?> - <?php echo date('H:i', strtotime($row['jam_tiba'])); ?></td>
                                <td class="px-6 py-4"><span class="text-[10px] font-black uppercase px-3 py-1 rounded-full bg-green-50 text-green-600"><?php echo htmlspecialchars($row['status_run']); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
