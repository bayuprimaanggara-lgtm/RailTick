<?php
session_start();
include "../config/koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";

if (isset($_POST['tambah'])) {
    $id_train = (int) $_POST['id_train'];
    $kode_route = strtoupper(mysqli_real_escape_string($conn, $_POST['kode_route']));
    $asal_id = (int) $_POST['asal_awal_station_id'];
    $tujuan_id = (int) $_POST['tujuan_akhir_station_id'];
    $jam_berangkat = mysqli_real_escape_string($conn, $_POST['jam_berangkat']);
    $jam_tiba = mysqli_real_escape_string($conn, $_POST['jam_tiba']);
    $harga = (float) $_POST['harga'];

    if ($asal_id === $tujuan_id) {
        $message = "Stasiun awal dan akhir tidak boleh sama.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            $cek = mysqli_query($conn, "SELECT id_route_kai FROM train_routes WHERE kode_route='$kode_route' LIMIT 1");
            if (mysqli_num_rows($cek) > 0) {
                throw new Exception("Kode rute KAI sudah digunakan.");
            }

            $insert_route = mysqli_query($conn, "
                INSERT INTO train_routes (id_train, kode_route, asal_awal_station_id, tujuan_akhir_station_id, status_route)
                VALUES ('$id_train', '$kode_route', '$asal_id', '$tujuan_id', 'aktif')
            ");
            if (!$insert_route) {
                throw new Exception(mysqli_error($conn));
            }
            $id_route_kai = mysqli_insert_id($conn);

            $insert_start = mysqli_query($conn, "
                INSERT INTO route_stations (id_route_kai, id_station, urutan, jam_tiba, jam_berangkat, is_stop)
                VALUES ('$id_route_kai', '$asal_id', 1, NULL, '$jam_berangkat', 1)
            ");
            $insert_end = mysqli_query($conn, "
                INSERT INTO route_stations (id_route_kai, id_station, urutan, jam_tiba, jam_berangkat, is_stop)
                VALUES ('$id_route_kai', '$tujuan_id', 2, '$jam_tiba', NULL, 1)
            ");
            $insert_fare = mysqli_query($conn, "
                INSERT INTO route_fares (id_route_kai, asal_station_id, tujuan_station_id, harga, status_fare)
                VALUES ('$id_route_kai', '$asal_id', '$tujuan_id', '$harga', 'aktif')
            ");

            if (!$insert_start || !$insert_end || !$insert_fare) {
                throw new Exception(mysqli_error($conn));
            }

            mysqli_commit($conn);
            header("Location: train_routes.php?status=added");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = $e->getMessage();
        }
    }
}

if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    mysqli_query($conn, "UPDATE train_routes SET status_route = IF(status_route='aktif','nonaktif','aktif') WHERE id_route_kai='$id'");
    header("Location: train_routes.php");
    exit();
}

$trains = mysqli_query($conn, "SELECT id_train, nama_kereta FROM trains WHERE status_train='aktif' ORDER BY nama_kereta ASC");
$stations = mysqli_query($conn, "SELECT id_station, nama_station FROM stations WHERE status_station='aktif' ORDER BY nama_station ASC");
$station_options = [];
while ($st = mysqli_fetch_assoc($stations)) {
    $station_options[] = $st;
}

$routes = mysqli_query($conn, "
    SELECT tr.*, t.nama_kereta, sa.nama_station AS asal, st.nama_station AS tujuan,
           COUNT(DISTINCT rs.id_route_station) AS total_stop,
           COUNT(DISTINCT rf.id_fare) AS total_fare
    FROM train_routes tr
    JOIN trains t ON t.id_train = tr.id_train
    JOIN stations sa ON sa.id_station = tr.asal_awal_station_id
    JOIN stations st ON st.id_station = tr.tujuan_akhir_station_id
    LEFT JOIN route_stations rs ON rs.id_route_kai = tr.id_route_kai
    LEFT JOIN route_fares rf ON rf.id_route_kai = tr.id_route_kai AND rf.status_fare='aktif'
    GROUP BY tr.id_route_kai
    ORDER BY tr.status_route ASC, tr.id_route_kai DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rute RailTick - RailTick Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap'); body{font-family:'Poppins',sans-serif}</style>
</head>
<body class="bg-gray-50 flex">
<?php include "layout/sidebar.php"; ?>
<main class="flex-1 min-h-screen">
    <header class="h-20 bg-white border-b border-slate-800/10 flex items-center justify-between px-8 sticky top-0 z-30">
        <h1 class="text-xl font-bold text-gray-800">Rute RailTick Relasional</h1>
        <div class="text-right hidden sm:block">
            <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'Administrator'); ?></p>
            <p class="text-[10px] text-green-500 font-black KAIuppercase tracking-widest">Admin Online</p>
        </div>
    </header>
    <div class="p-8 grid grid-cols-1 xl:grid-cols-3 gap-8">
        <section class="bg-white p-8 rounded-[2rem] border border-gray-100 shadow-sm h-fit">
            <h2 class="font-bold text-gray-800 mb-2">Tambah Rute RailTick</h2>
            <p class="text-xs text-gray-400 mb-6">Form ini membuat rute langsung awal-akhir. Rute multi-stasiun tetap bisa disiapkan lewat data SQL.</p>
            <?php if ($message): ?>
                <div class="mb-5 bg-red-50 text-red-600 p-4 rounded-xl text-sm font-semibold"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="tambah" value="1">
                <select name="id_train" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                    <option value="">Pilih kereta</option>
                    <?php while ($train = mysqli_fetch_assoc($trains)): ?>
                        <option value="<?php echo $train['id_train']; ?>"><?php echo htmlspecialchars($train['nama_kereta']); ?></option>
                    <?php endwhile; ?>
                </select>
                <input name="kode_route" placeholder="Kode rute, contoh: AP-GMR-BD" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                <select name="asal_awal_station_id" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                    <option value="">Stasiun awal</option>
                    <?php foreach ($station_options as $st): ?><option value="<?php echo $st['id_station']; ?>"><?php echo htmlspecialchars($st['nama_station']); ?></option><?php endforeach; ?>
                </select>
                <select name="tujuan_akhir_station_id" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                    <option value="">Stasiun akhir</option>
                    <?php foreach ($station_options as $st): ?><option value="<?php echo $st['id_station']; ?>"><?php echo htmlspecialchars($st['nama_station']); ?></option><?php endforeach; ?>
                </select>
                <div class="grid grid-cols-2 gap-3">
                    <input type="time" name="jam_berangkat" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                    <input type="time" name="jam_tiba" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                </div>
                <input type="number" name="harga" min="0" placeholder="Harga tiket" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                <button class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-xl uppercase text-xs tracking-widest">Simpan Rute Railtick</button>
            </form>
        </section>
        <section class="xl:col-span-2 bg-white rounded-[2rem] border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-800">Daftar Rute RailTick</h2>
                <span class="text-[10px] font-black uppercase bg-blue-50 text-blue-600 px-4 py-1.5 rounded-full"><?php echo mysqli_num_rows($routes); ?> rute</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-400 text-[10px] uppercase tracking-widest">
                    <tr><th class="px-6 py-4">Kode</th><th class="px-6 py-4">Kereta</th><th class="px-6 py-4">Lintas</th><th class="px-6 py-4">Data</th><th class="px-6 py-4">Status</th><th class="px-6 py-4 text-center">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                    <?php while ($row = mysqli_fetch_assoc($routes)): ?>
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-6 py-4 font-black text-blue-600"><?php echo htmlspecialchars($row['kode_route']); ?></td>
                            <td class="px-6 py-4 font-bold text-gray-800"><?php echo htmlspecialchars($row['nama_kereta']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($row['asal']); ?> <i class="fas fa-arrow-right text-gray-300 mx-2"></i> <?php echo htmlspecialchars($row['tujuan']); ?></td>
                            <td class="px-6 py-4 text-xs text-gray-500"><?php echo (int)$row['total_stop']; ?> stop / <?php echo (int)$row['total_fare']; ?> tarif</td>
                            <td class="px-6 py-4"><span class="text-[10px] font-black uppercase px-3 py-1 rounded-full <?php echo $row['status_route']=='aktif'?'bg-green-50 text-green-600':'bg-gray-100 text-gray-500'; ?>"><?php echo htmlspecialchars($row['status_route']); ?></span></td>
                            <td class="px-6 py-4 text-center"><a href="?toggle=<?php echo $row['id_route_kai']; ?>" class="text-xs font-bold text-red-500 hover:underline">Ubah Status</a></td>
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
