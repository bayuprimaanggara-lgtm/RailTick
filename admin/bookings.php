<?php
session_start();
include "../config/koneksi.php";
include "../config/operational_status.php";
sync_operational_status($conn);

// 1. Proteksi Halaman Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: ../auth/login.php");
    exit();
}

// 2. Logika Hapus Pesanan
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);
    mysqli_query($conn, "DELETE FROM bookings WHERE id_booking='$id'");
    header("Location: bookings.php");
    exit();
}

// 3. Filter Rute
$route_filter = isset($_GET['route']) ? mysqli_real_escape_string($conn, $_GET['route']) : "";

// Ambil data rute untuk dropdown
$routes_master = mysqli_query($conn, "
    SELECT DISTINCT tr.id_route_kai AS id_route, sa.nama_station AS asal, st.nama_station AS tujuan
    FROM train_routes tr
    JOIN schedules s ON s.id_route_kai = tr.id_route_kai
    JOIN stations sa ON sa.id_station = tr.asal_awal_station_id
    JOIN stations st ON st.id_station = tr.tujuan_akhir_station_id
    ORDER BY sa.nama_station ASC
");

// 4. Query Utama yang Disinkronkan
$query_sql = "
    SELECT 
        b.*, 
        u.nama_lengkap, 
        u.email,
        s.jam_berangkat,
        s.jam_tiba,
        s.nama_kereta,
        COALESCE(sa.nama_station, r.asal) AS asal_display, 
        COALESCE(st.nama_station, r.tujuan) AS tujuan_display
    FROM bookings b
    JOIN users u ON b.id_user = u.id_user
    JOIN schedules s ON b.id_schedule = s.id_schedule
    LEFT JOIN routes r ON s.id_route = r.id_route
    LEFT JOIN stations sa ON sa.id_station = b.asal_station_id
    LEFT JOIN stations st ON st.id_station = b.tujuan_station_id
";

if (!empty($route_filter)) {
    $query_sql .= " WHERE s.id_route_kai = '$route_filter'";
}

$query_sql .= " ORDER BY b.created_at DESC";
$query_bookings = mysqli_query($conn, $query_sql);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Pesanan - RailTick Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
        }

        /* Custom Scrollbar untuk Tabel */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }

        /* Memastikan Sidebar tetap di tempat dan tombol logout naik */
        aside {
            height: 100vh;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        /* Menaikkan posisi tombol terakhir di sidebar agar tidak tertutup */
        aside nav a:last-child {
            margin-top: auto;
            margin-bottom: 20px !important;
        }
    </style>
</head>

<body class="bg-gray-50 flex">

    <?php include "layout/sidebar.php"; ?>

    <main class="flex-1 flex flex-col min-w-0 min-h-screen">
        <header class="h-20 bg-white border-b border-slate-800/10 flex items-center justify-between px-8 sticky top-0 z-30">
            <div class="flex items-center gap-4">
                <a href="index.php" class="md:hidden text-gray-600"><i class="fas fa-bars text-xl"></i></a>
                <h1 class="text-xl font-bold text-gray-800 tracking-tight">Monitoring Pesanan Tiket</h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'Administrator'); ?></p>
                    <p class="text-[10px] text-green-500 font-black uppercase tracking-widest">Admin Online</p>
                </div>
                <div class="w-10 h-10 bg-red-50 text-red-500 rounded-full flex items-center justify-center border border-red-100 shadow-sm">
                    <i class="fas fa-user-shield text-sm"></i>
                </div>
            </div>
        </header>

        <div class="p-8">
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/30">
                    <div class="flex items-center gap-4">
                        <h3 class="font-bold text-gray-800">Log Transaksi Penumpang</h3>

                        <form method="GET">
                            <select name="route" onchange="this.form.submit()"
                                class="text-xs font-bold bg-white border border-gray-200 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Semua Rute Terdaftar</option>
                                <?php while ($rt = mysqli_fetch_assoc($routes_master)): ?>
                                    <option value="<?= $rt['id_route']; ?>" <?= ($route_filter == $rt['id_route']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($rt['asal'] . " → " . $rt['tujuan']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </form>
                    </div>
                    <span class="bg-blue-50 text-blue-600 text-[10px] font-black px-4 py-1.5 rounded-full uppercase tracking-widest border border-blue-100">
                        <?php echo mysqli_num_rows($query_bookings); ?> Tiket Terdaftar
                    </span>
                </div>

                <div class="overflow-x-auto overflow-y-auto max-h-[calc(100vh-280px)] custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50/90 backdrop-blur-sm text-gray-400 text-[10px] uppercase tracking-[0.2em] font-black border-b border-gray-100 sticky top-0 z-10">
                            <tr>
                                <th class="px-8 py-5">Kode / Penumpang</th>
                                <th class="px-8 py-5">Detail Rute & Kereta</th>
                                <th class="px-8 py-5 text-center">Jumlah</th>
                                <th class="px-8 py-5">Total Bayar</th>
                                <th class="px-8 py-5 text-center">Status</th>
                                <th class="px-8 py-5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php if (mysqli_num_rows($query_bookings) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($query_bookings)): ?>
                                    <tr class="hover:bg-gray-50/40 transition group">
                                        <td class="px-8 py-6">
                                            <span class="font-black text-blue-600 block mb-1">#<?php echo $row['kode_booking']; ?></span>
                                            <span class="text-slate-900 font-bold uppercase tracking-tight text-sm"><?php echo htmlspecialchars($row['nama_lengkap']); ?></span>
                                            <p class="text-[10px] text-gray-400 font-medium italic mt-1 opacity-70"><?php echo htmlspecialchars($row['email']); ?></p>
                                        </td>
                                        <td class="px-8 py-6">
                                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">
                                                <?php echo htmlspecialchars($row['nama_kereta'] ?? 'Kereta Api'); ?>
                                            </p>
                                            <div class="flex items-center gap-3 mb-2">
                                                <span class="text-slate-800 font-bold text-xs uppercase"><?php echo htmlspecialchars($row['asal_display']); ?></span>
                                                <i class="fas fa-long-arrow-alt-right text-gray-300"></i>
                                                <span class="text-slate-800 font-bold text-xs uppercase"><?php echo htmlspecialchars($row['tujuan_display']); ?></span>
                                            </div>
                                            <div class="inline-flex items-center gap-2 text-[11px] font-black text-slate-700 bg-slate-100 border border-slate-200 px-3 py-1.5 rounded-lg">
                                                <i class="far fa-clock text-slate-400"></i>
                                                <span><?php echo date('H:i', strtotime($row['jam_berangkat'])); ?> - <?php echo date('H:i', strtotime($row['jam_tiba'])); ?> WIB</span>
                                            </div>
                                            <p class="text-[10px] text-red-500 font-black mt-2 uppercase tracking-widest">
                                                <?php echo date('d M Y', strtotime($row['tanggal_berangkat'])); ?>
                                            </p>
                                        </td>
                                        <td class="px-8 py-6 text-center">
                                            <span class="bg-slate-100 text-slate-700 px-3 py-1.5 rounded-lg text-xs font-black border border-slate-200">
                                                <?php echo $row['jumlah_tiket']; ?> Psg
                                            </span>
                                        </td>
                                        <td class="px-8 py-6 font-black text-slate-900 text-sm whitespace-nowrap">
                                            Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?>
                                        </td>
                                        <td class="px-8 py-6 text-center">
                                            <?php
                                            $status = $row['status_booking'];
                                            $status_class = "bg-green-100 text-green-600 border-green-200";
                                            if ($status == 'dibatalkan') $status_class = "bg-red-100 text-red-600 border-red-200";
                                            if ($status == 'menunggu') $status_class = "bg-yellow-100 text-yellow-600 border-yellow-200";
                                            ?>
                                            <span class="px-4 py-1.5 <?php echo $status_class; ?> text-[9px] font-black rounded-full uppercase tracking-widest border shadow-sm">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                        <td class="px-8 py-6 text-center">
                                            <a href="?hapus=<?php echo $row['id_booking']; ?>"
                                                onclick="return confirm('Hapus catatan pesanan ini?')"
                                                class="w-10 h-10 flex items-center justify-center bg-red-50 text-red-500 rounded-2xl hover:bg-red-500 hover:text-white transition-all shadow-sm mx-auto group-hover:scale-110">
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-8 py-20 text-center text-gray-400 italic text-sm">Tidak ada data pesanan untuk rute ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <footer class="mt-auto p-10 text-center text-gray-300 text-[10px] font-black uppercase tracking-[0.4em]">
            &copy; 2026 RailTick Administrator Control.
        </footer>
    </main>

</body>

</html>
