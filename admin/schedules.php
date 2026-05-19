<?php
session_start();
include "../config/koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";

if (isset($_POST['tambah'])) {
    $id_route_kai = (int) $_POST['id_route_kai'];
    $kapasitas = (int) $_POST['kapasitas'];

    $detail_q = mysqli_query($conn, "
        SELECT tr.id_route_kai, t.nama_kereta,
               MIN(CASE WHEN rs.urutan = first_stop.min_urutan THEN rs.jam_berangkat END) AS jam_berangkat,
               MAX(CASE WHEN rs.urutan = last_stop.max_urutan THEN rs.jam_tiba END) AS jam_tiba
        FROM train_routes tr
        JOIN trains t ON t.id_train = tr.id_train
        JOIN route_stations rs ON rs.id_route_kai = tr.id_route_kai
        JOIN (SELECT id_route_kai, MIN(urutan) AS min_urutan FROM route_stations GROUP BY id_route_kai) first_stop ON first_stop.id_route_kai = tr.id_route_kai
        JOIN (SELECT id_route_kai, MAX(urutan) AS max_urutan FROM route_stations GROUP BY id_route_kai) last_stop ON last_stop.id_route_kai = tr.id_route_kai
        WHERE tr.id_route_kai = '$id_route_kai'
        GROUP BY tr.id_route_kai, t.nama_kereta
        LIMIT 1
    ");
    $detail = mysqli_fetch_assoc($detail_q);

    if (!$detail || empty($detail['jam_berangkat']) || empty($detail['jam_tiba'])) {
        $message = "Rute KAI belum punya jam awal dan jam akhir yang lengkap.";
    } else {
        $nama_kereta = mysqli_real_escape_string($conn, $detail['nama_kereta']);
        $jam_berangkat = mysqli_real_escape_string($conn, $detail['jam_berangkat']);
        $jam_tiba = mysqli_real_escape_string($conn, $detail['jam_tiba']);

        $insert = mysqli_query($conn, "
            INSERT INTO schedules (id_route, id_route_kai, nama_kereta, jam_berangkat, jam_tiba, kapasitas_kursi, status_jadwal)
            VALUES (NULL, '$id_route_kai', '$nama_kereta', '$jam_berangkat', '$jam_tiba', '$kapasitas', 'aktif')
        ");

        if ($insert) {
            $id_schedule = mysqli_insert_id($conn);
            mysqli_query($conn, "
                INSERT IGNORE INTO train_runs (id_schedule, tanggal_berangkat, status_run)
                SELECT '$id_schedule', DATE_ADD(CURDATE(), INTERVAL d.n DAY), 'terjadwal'
                FROM (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6) d
            ");
            header("Location: schedules.php?status=added");
            exit();
        }
        $message = mysqli_error($conn);
    }
}

if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    mysqli_query($conn, "UPDATE schedules SET status_jadwal = IF(status_jadwal='aktif','batal','aktif') WHERE id_schedule='$id'");
    header("Location: schedules.php");
    exit();
}

if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM schedules WHERE id_schedule='$id'");
    header("Location: schedules.php?status=deleted");
    exit();
}

$route_options = mysqli_query($conn, "
    SELECT tr.id_route_kai, tr.kode_route, t.nama_kereta, sa.nama_station AS asal, st.nama_station AS tujuan
    FROM train_routes tr
    JOIN trains t ON t.id_train = tr.id_train
    JOIN stations sa ON sa.id_station = tr.asal_awal_station_id
    JOIN stations st ON st.id_station = tr.tujuan_akhir_station_id
    WHERE tr.status_route='aktif'
    ORDER BY t.nama_kereta ASC, tr.kode_route ASC
");

$schedules = mysqli_query($conn, "
    SELECT s.*, tr.kode_route, t.nama_kereta AS train_master,
           COALESCE(sa.nama_station, r.asal) AS asal,
           COALESCE(st.nama_station, r.tujuan) AS tujuan,
           COUNT(DISTINCT run.id_run) AS total_run
    FROM schedules s
    LEFT JOIN train_routes tr ON tr.id_route_kai = s.id_route_kai
    LEFT JOIN trains t ON t.id_train = tr.id_train
    LEFT JOIN stations sa ON sa.id_station = tr.asal_awal_station_id
    LEFT JOIN stations st ON st.id_station = tr.tujuan_akhir_station_id
    LEFT JOIN routes r ON r.id_route = s.id_route
    LEFT JOIN train_runs run ON run.id_schedule = s.id_schedule
    GROUP BY s.id_schedule
    ORDER BY s.id_schedule DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal - RailTick Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap'); body{font-family:'Poppins',sans-serif}</style>
</head>
<body class="bg-gray-50 flex">
<?php include "layout/sidebar.php"; ?>
<main class="flex-1 min-h-screen">
    <header class="h-20 bg-white border-b border-slate-800/10 flex items-center justify-between px-8 sticky top-0 z-30">
        <h1 class="text-xl font-bold text-gray-800">Jadwal Dasar Kereta</h1>
        <div class="text-right hidden sm:block">
            <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'Administrator'); ?></p>
            <p class="text-[10px] text-green-500 font-black uppercase tracking-widest">Admin Online</p>
        </div>
    </header>
    <div class="p-8 grid grid-cols-1 xl:grid-cols-3 gap-8">
        <section class="bg-white p-8 rounded-[2rem] border border-gray-100 shadow-sm h-fit">
            <h2 class="font-bold text-gray-800 mb-2">Buat Jadwal dari Rute KAI</h2>
            <p class="text-xs text-gray-400 mb-6">Jadwal ini menjadi template. Keberangkatan per tanggal diatur di menu Perjalanan Harian.</p>
            <?php if ($message): ?>
                <div class="mb-5 bg-red-50 text-red-600 p-4 rounded-xl text-sm font-semibold"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="tambah" value="1">
                <select name="id_route_kai" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                    <option value="">Pilih rute KAI</option>
                    <?php while ($r = mysqli_fetch_assoc($route_options)): ?>
                        <option value="<?php echo $r['id_route_kai']; ?>">
                            <?php echo htmlspecialchars($r['nama_kereta'] . " / " . $r['kode_route'] . " / " . $r['asal'] . " - " . $r['tujuan']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="number" name="kapasitas" min="1" value="50" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                <button class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-xl uppercase text-xs tracking-widest">Simpan Jadwal</button>
            </form>
        </section>
        <section class="xl:col-span-2 bg-white rounded-[2rem] border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-800">Daftar Jadwal</h2>
                <span class="text-[10px] font-black uppercase bg-blue-50 text-blue-600 px-4 py-1.5 rounded-full"><?php echo mysqli_num_rows($schedules); ?> jadwal</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-400 text-[10px] uppercase tracking-widest">
                    <tr><th class="px-6 py-4">Kereta</th><th class="px-6 py-4">Rute</th><th class="px-6 py-4">Jam</th><th class="px-6 py-4">Run</th><th class="px-6 py-4">Status</th><th class="px-6 py-4 text-center">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                    <?php while ($row = mysqli_fetch_assoc($schedules)): ?>
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-800"><?php echo htmlspecialchars($row['nama_kereta']); ?></p>
                                <p class="text-[10px] text-gray-400 font-bold uppercase"><?php echo $row['kode_route'] ? htmlspecialchars($row['kode_route']) : 'legacy'; ?></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($row['asal'] ?? '-'); ?> <i class="fas fa-arrow-right text-gray-300 mx-2"></i> <?php echo htmlspecialchars($row['tujuan'] ?? '-'); ?></td>
                            <td class="px-6 py-4 font-black text-blue-600"><?php echo date('H:i', strtotime($row['jam_berangkat'])); ?> - <?php echo date('H:i', strtotime($row['jam_tiba'])); ?></td>
                            <td class="px-6 py-4 text-xs text-gray-500"><?php echo (int) $row['total_run']; ?> tanggal</td>
                            <td class="px-6 py-4"><span class="text-[10px] font-black uppercase px-3 py-1 rounded-full <?php echo $row['status_jadwal']=='aktif'?'bg-green-50 text-green-600':'bg-red-50 text-red-600'; ?>"><?php echo htmlspecialchars($row['status_jadwal']); ?></span></td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <a href="?toggle=<?php echo $row['id_schedule']; ?>" class="text-xs font-bold text-blue-600 hover:underline mr-3">Status</a>
                                <a href="?hapus=<?php echo $row['id_schedule']; ?>" onclick="return confirm('Hapus jadwal ini beserta perjalanan hariannya?')" class="text-xs font-bold text-red-500 hover:underline">Hapus</a>
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
