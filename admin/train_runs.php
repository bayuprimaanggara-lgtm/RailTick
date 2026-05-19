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

if (isset($_POST['tambah'])) {
    $id_schedule = (int) $_POST['id_schedule'];
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal_berangkat']);

    $insert = mysqli_query($conn, "
        INSERT IGNORE INTO train_runs (id_schedule, tanggal_berangkat, status_run)
        VALUES ('$id_schedule', '$tanggal', 'terjadwal')
    ");
    if ($insert && mysqli_affected_rows($conn) > 0) {
        header("Location: train_runs.php?tanggal=$tanggal&status=added");
        exit();
    }
    $message = "Perjalanan untuk jadwal dan tanggal tersebut sudah ada.";
}

if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $allowed = ['terjadwal', 'berjalan', 'selesai', 'batal'];
    if (in_array($status, $allowed, true)) {
        mysqli_query($conn, "UPDATE train_runs SET status_run='$status' WHERE id_run='$id'");
    }
    header("Location: train_runs.php?tanggal=$filter_date");
    exit();
}

$schedule_options = mysqli_query($conn, "
    SELECT s.id_schedule, s.nama_kereta, s.jam_berangkat, s.jam_tiba,
           COALESCE(sa.nama_station, r.asal) AS asal,
           COALESCE(st.nama_station, r.tujuan) AS tujuan
    FROM schedules s
    LEFT JOIN train_routes tr ON tr.id_route_kai = s.id_route_kai
    LEFT JOIN stations sa ON sa.id_station = tr.asal_awal_station_id
    LEFT JOIN stations st ON st.id_station = tr.tujuan_akhir_station_id
    LEFT JOIN routes r ON r.id_route = s.id_route
    WHERE s.status_jadwal='aktif' AND s.id_route_kai IS NOT NULL
    ORDER BY s.nama_kereta ASC, s.jam_berangkat ASC
");

$runs = mysqli_query($conn, "
    SELECT run.*, s.nama_kereta, s.jam_berangkat, s.jam_tiba,
           COALESCE(sa.nama_station, r.asal) AS asal,
           COALESCE(st.nama_station, r.tujuan) AS tujuan,
           SUM(CASE WHEN ca.role_tugas='masinis' THEN 1 ELSE 0 END) AS total_masinis,
           SUM(CASE WHEN ca.role_tugas='kondektur' THEN 1 ELSE 0 END) AS total_kondektur,
           SUM(CASE WHEN ca.role_tugas='pramuniaga' THEN 1 ELSE 0 END) AS total_pramuniaga
    FROM train_runs run
    JOIN schedules s ON s.id_schedule = run.id_schedule
    LEFT JOIN train_routes tr ON tr.id_route_kai = s.id_route_kai
    LEFT JOIN stations sa ON sa.id_station = tr.asal_awal_station_id
    LEFT JOIN stations st ON st.id_station = tr.tujuan_akhir_station_id
    LEFT JOIN routes r ON r.id_route = s.id_route
    LEFT JOIN crew_assignments ca ON ca.id_run = run.id_run
    WHERE run.tanggal_berangkat = '$filter_date'
    GROUP BY run.id_run
    ORDER BY s.jam_berangkat ASC, s.nama_kereta ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perjalanan Harian - RailTick Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap'); body{font-family:'Poppins',sans-serif}</style>
</head>
<body class="bg-gray-50 flex">
<?php include "layout/sidebar.php"; ?>
<main class="flex-1 min-h-screen">
    <header class="h-20 bg-white border-b border-slate-800/10 flex items-center justify-between px-8 sticky top-0 z-30">
        <h1 class="text-xl font-bold text-gray-800">Perjalanan Harian</h1>
        <div class="text-right hidden sm:block">
            <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'Administrator'); ?></p>
            <p class="text-[10px] text-green-500 font-black uppercase tracking-widest">Admin Online</p>
        </div>
    </header>
    <div class="p-8 grid grid-cols-1 xl:grid-cols-3 gap-8">
        <section class="bg-white p-8 rounded-[2rem] border border-gray-100 shadow-sm h-fit">
            <h2 class="font-bold text-gray-800 mb-6">Tambah Keberangkatan</h2>
            <?php if ($message): ?>
                <div class="mb-5 bg-red-50 text-red-600 p-4 rounded-xl text-sm font-semibold"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="tambah" value="1">
                <select name="id_schedule" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                    <option value="">Pilih jadwal</option>
                    <?php while ($s = mysqli_fetch_assoc($schedule_options)): ?>
                        <option value="<?php echo $s['id_schedule']; ?>">
                            <?php echo htmlspecialchars($s['nama_kereta'] . " / " . $s['asal'] . " - " . $s['tujuan'] . " / " . date('H:i', strtotime($s['jam_berangkat']))); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="date" name="tanggal_berangkat" value="<?php echo htmlspecialchars($filter_date); ?>" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                <button class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-xl uppercase text-xs tracking-widest">Simpan Perjalanan</button>
            </form>
        </section>
        <section class="xl:col-span-2 bg-white rounded-[2rem] border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h2 class="font-bold text-gray-800">Daftar Keberangkatan</h2>
                <form method="GET" class="flex gap-3">
                    <input type="date" name="tanggal" value="<?php echo htmlspecialchars($filter_date); ?>" class="px-4 py-2 bg-gray-50 rounded-xl border border-gray-100 outline-none">
                    <button class="bg-slate-900 text-white px-5 py-2 rounded-xl text-xs font-bold uppercase">Filter</button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-400 text-[10px] uppercase tracking-widest">
                    <tr><th class="px-6 py-4">Kereta</th><th class="px-6 py-4">Rute</th><th class="px-6 py-4">Jam</th><th class="px-6 py-4">Kru</th><th class="px-6 py-4">Status</th><th class="px-6 py-4 text-center">Ubah</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                    <?php if (mysqli_num_rows($runs) == 0): ?>
                        <tr><td colspan="6" class="px-6 py-16 text-center text-gray-400 text-sm">Belum ada perjalanan untuk tanggal ini.</td></tr>
                    <?php endif; ?>
                    <?php while ($row = mysqli_fetch_assoc($runs)): ?>
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-6 py-4 font-bold text-gray-800"><?php echo htmlspecialchars($row['nama_kereta']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($row['asal'] ?? '-'); ?> <i class="fas fa-arrow-right text-gray-300 mx-2"></i> <?php echo htmlspecialchars($row['tujuan'] ?? '-'); ?></td>
                            <td class="px-6 py-4 font-black text-blue-600"><?php echo date('H:i', strtotime($row['jam_berangkat'])); ?> - <?php echo date('H:i', strtotime($row['jam_tiba'])); ?></td>
                            <td class="px-6 py-4 text-xs text-gray-500">
                                M: <?php echo (int)$row['total_masinis']; ?> / K: <?php echo (int)$row['total_kondektur']; ?> / P: <?php echo (int)$row['total_pramuniaga']; ?>
                            </td>
                            <td class="px-6 py-4"><span class="text-[10px] font-black uppercase px-3 py-1 rounded-full <?php echo operational_status_badge_class($row['status_run']); ?>"><?php echo htmlspecialchars($row['status_run']); ?></span></td>
                            <td class="px-6 py-4 text-center">
                                <select onchange="location.href='?tanggal=<?php echo htmlspecialchars($filter_date); ?>&id=<?php echo $row['id_run']; ?>&status='+this.value" class="text-xs bg-gray-50 border border-gray-100 rounded-lg px-2 py-2">
                                    <?php foreach (['terjadwal','berjalan','selesai','batal'] as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo $row['status_run']==$status?'selected':''; ?>><?php echo $status; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
</body>
</html>
