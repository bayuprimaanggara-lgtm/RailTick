<?php
session_start();
include "../config/koneksi.php";
include "../config/operational_status.php";
sync_operational_status($conn);

// Proteksi Halaman User
if (!isset($_SESSION['role']) || $_SESSION['role'] != "user") {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil ID user dari session
$id_user = $_SESSION['user_id'];

/** * PERBAIKAN: Ambil data user terbaru langsung dari database
 * Agar nama di header otomatis berubah setelah edit profil tanpa harus re-login
 */
$query_user_db = mysqli_query($conn, "SELECT nama_lengkap, username FROM users WHERE id_user = '$id_user'");
$user_db = mysqli_fetch_assoc($query_user_db);
// Gunakan nama_lengkap jika ada, jika tidak gunakan username sebagai fallback
$nama_tampil = !empty($user_db['nama_lengkap']) ? $user_db['nama_lengkap'] : $user_db['username'];

// Ambil data stasiun untuk filter
$stations = [];
$qStations = mysqli_query($conn, "SELECT id_station, nama_station FROM stations WHERE status_station = 'aktif' ORDER BY nama_station ASC");
while ($row = mysqli_fetch_assoc($qStations)) {
    $stations[] = $row;
}

$asal_filter = isset($_GET['asal']) ? mysqli_real_escape_string($conn, $_GET['asal']) : '';
$tujuan_filter = isset($_GET['tujuan']) ? mysqli_real_escape_string($conn, $_GET['tujuan']) : '';
$tanggal_filter = isset($_GET['tanggal']) && $_GET['tanggal'] !== '' ? mysqli_real_escape_string($conn, $_GET['tanggal']) : date('Y-m-d');

$data_jadwal = [];
$message = "";

// Logika Pencarian Jadwal
if (!empty($asal_filter) && !empty($tujuan_filter)) {
    if ($asal_filter === $tujuan_filter) {
        $message = "Stasiun asal dan tujuan tidak boleh sama.";
    } else {
        $sql = "
            SELECT
                s.id_schedule,
                tr.id_route_kai,
                t.nama_kereta,
                sa.id_station AS asal_station_id,
                sa.nama_station AS stasiun_asal,
                st.id_station AS tujuan_station_id,
                st.nama_station AS stasiun_tujuan,
                rs_asal.jam_berangkat AS jam_berangkat,
                rs_tujuan.jam_tiba AS jam_tiba,
                TIMEDIFF(rs_tujuan.jam_tiba, rs_asal.jam_berangkat) AS durasi,
                rf.harga
            FROM train_routes tr
            JOIN trains t ON t.id_train = tr.id_train
            JOIN schedules s ON s.id_route_kai = tr.id_route_kai AND s.status_jadwal = 'aktif'
            JOIN route_stations rs_asal ON rs_asal.id_route_kai = tr.id_route_kai
            JOIN route_stations rs_tujuan ON rs_tujuan.id_route_kai = tr.id_route_kai
            JOIN stations sa ON sa.id_station = rs_asal.id_station
            JOIN stations st ON st.id_station = rs_tujuan.id_station
            JOIN route_fares rf 
                ON rf.id_route_kai = tr.id_route_kai
               AND rf.asal_station_id = rs_asal.id_station
               AND rf.tujuan_station_id = rs_tujuan.id_station
            WHERE sa.nama_station = '$asal_filter'
              AND st.nama_station = '$tujuan_filter'
              AND rs_asal.urutan < rs_tujuan.urutan
              AND rs_asal.is_stop = 1
              AND rs_tujuan.is_stop = 1
              AND EXISTS (
                    SELECT 1
                    FROM train_runs run
                    WHERE run.id_schedule = s.id_schedule
                      AND run.tanggal_berangkat = '$tanggal_filter'
                      AND run.status_run = 'terjadwal'
              )
            ORDER BY rs_asal.jam_berangkat ASC, t.nama_kereta ASC
        ";

        $query = mysqli_query($conn, $sql);

        if ($query) {
            while ($row = mysqli_fetch_assoc($query)) {
                $data_jadwal[] = $row;
            }
        } else {
            $message = "Terjadi kesalahan saat mengambil data jadwal.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Kereta - RailTick</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }

        /* Modal Logout Style */
        .modal-overlay {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            transition: all 0.3s ease;
        }
        .modal-card {
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .modal-active .modal-card {
            transform: scale(1);
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-50 flex min-h-screen">

    <!-- Sidebar Navigasi -->
    <aside class="w-64 bg-white border-r border-gray-100 hidden md:flex flex-col sticky top-0 h-screen shadow-sm no-print">
        <div class="p-8 border-b border-gray-50 flex items-center gap-3">
            <div class="bg-red-500 p-2 rounded-lg text-white shadow-lg shadow-red-200">
                <i class="fas fa-train"></i>
            </div>
            <span class="text-xl font-bold text-gray-800 italic">RailTick</span>
        </div>
        
        <nav class="flex-1 p-6 space-y-3 mt-4">
            <a href="dashboard.php" class="flex items-center gap-3 text-gray-500 hover:bg-gray-50 hover:text-red-500 px-4 py-3 rounded-2xl transition font-medium">
                <i class="fas fa-home"></i> Beranda
            </a>
            <a href="jadwal.php" class="flex items-center gap-3 bg-red-50 text-red-600 px-4 py-3 rounded-2xl font-bold transition">
                <i class="fas fa-search"></i> Cari Jadwal
            </a>
            <a href="riwayat.php" class="flex items-center gap-3 text-gray-500 hover:bg-gray-50 hover:text-red-500 px-4 py-3 rounded-2xl transition font-medium">
                <i class="fas fa-history"></i> Riwayat Pesanan
            </a>
            <a href="edit_profil.php" class="flex items-center gap-3 text-gray-500 hover:bg-gray-50 hover:text-red-500 px-4 py-3 rounded-2xl transition font-medium">
                <i class="fas fa-user-circle"></i> Profil Saya
            </a>
        </nav>

        <div class="p-6 border-t border-gray-50">
            <!-- Link Logout diganti menjadi trigger modal -->
            <button onclick="toggleLogoutModal(true)" class="w-full flex items-center gap-3 text-gray-400 hover:text-red-500 px-4 py-3 transition text-sm font-bold uppercase tracking-widest outline-none">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1">
        <!-- Topbar -->
        <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 px-8 py-5 border-b border-gray-100 flex justify-between items-center">
            <div>
                <h1 class="text-sm text-gray-400 font-medium tracking-wide uppercase">Layanan Tiket / Jadwal Perjalanan</h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($nama_tampil); ?></p>
                    <p class="text-[10px] text-red-500 font-bold uppercase tracking-widest">Penumpang</p>
                </div>
                <!-- Interaksi Klik Profil menuju Edit Profil -->
                <a href="edit_profil.php" class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 border border-gray-200 hover:border-red-500 hover:text-red-500 transition shadow-sm group">
                    <i class="fas fa-user group-hover:scale-110 transition-transform"></i>
                </a>
            </div>
        </header>

        <div class="p-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Cari Jadwal Antar Stasiun</h2>
                    <p class="text-gray-400 text-sm">Pilih stasiun keberangkatan dan tujuan Anda untuk melihat ketersediaan kereta.</p>
                </div>
                <div class="flex items-center bg-white px-5 py-2.5 rounded-2xl shadow-sm border border-gray-100">
                    <i class="fas fa-calendar-check text-red-500 mr-3"></i>
                    <span class="text-sm font-bold text-gray-700 uppercase tracking-tighter"><?php echo date('d F Y'); ?></span>
                </div>
            </div>

            <!-- Search Form -->
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 mb-10">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-6 items-end">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase ml-1 tracking-[0.2em]">Tanggal</label>
                        <input type="date" name="tanggal" min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($tanggal_filter); ?>" required
                            class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-red-500 outline-none font-medium text-gray-700 transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase ml-1 tracking-[0.2em]">Stasiun Asal</label>
                        <div class="relative">
                            <select name="asal" class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-red-500 outline-none font-medium text-gray-700 appearance-none transition-all" required>
                                <option value="">Pilih Stasiun Asal</option>
                                <?php foreach ($stations as $station): ?>
                                    <option value="<?php echo htmlspecialchars($station['nama_station']); ?>" <?php echo ($asal_filter == $station['nama_station']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($station['nama_station']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-map-marker-alt absolute right-5 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase ml-1 tracking-[0.2em]">Stasiun Tujuan</label>
                        <div class="relative">
                            <select name="tujuan" class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-red-500 outline-none font-medium text-gray-700 appearance-none transition-all" required>
                                <option value="">Pilih Stasiun Tujuan</option>
                                <?php foreach ($stations as $station): ?>
                                    <option value="<?php echo htmlspecialchars($station['nama_station']); ?>" <?php echo ($tujuan_filter == $station['nama_station']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($station['nama_station']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-circle-right absolute right-5 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"></i>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-black py-4 rounded-2xl transition-all shadow-xl shadow-red-200 uppercase tracking-widest text-xs flex items-center justify-center gap-2">
                        CARI JADWAL <i class="fas fa-search text-[10px]"></i>
                    </button>

                    <a href="jadwal.php" class="block w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-4 rounded-2xl transition-all uppercase tracking-widest text-xs">
                        Reset Filter
                    </a>
                </form>
            </div>

            <!-- Active Filters Info -->
            <?php if (!empty($asal_filter) || !empty($tujuan_filter)): ?>
                <div class="mb-8 flex flex-wrap gap-3">
                    <?php if (!empty($asal_filter)): ?>
                        <span class="bg-slate-100 text-slate-600 px-5 py-2.5 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm">
                            <i class="fas fa-calendar mr-2"></i> Tanggal: <?php echo date('d M Y', strtotime($tanggal_filter)); ?>
                        </span>
                        <span class="bg-red-50 text-red-600 px-5 py-2.5 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm">
                            <i class="fas fa-train mr-2"></i> Asal: <?php echo htmlspecialchars($asal_filter); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($tujuan_filter)): ?>
                        <span class="bg-blue-50 text-blue-600 px-5 py-2.5 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm">
                            <i class="fas fa-train mr-2"></i> Tujuan: <?php echo htmlspecialchars($tujuan_filter); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Message Notification -->
            <?php if ($message != ""): ?>
                <div class="mb-10 p-5 bg-red-50 text-red-700 text-sm rounded-2xl border-l-8 border-red-500 flex items-center gap-3 animate-pulse font-medium">
                    <i class="fas fa-info-circle text-lg"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Schedule List Area -->
            <div class="space-y-6">
                <?php if (!empty($asal_filter) && !empty($tujuan_filter) && empty($message)): ?>
                    <?php if (count($data_jadwal) > 0): ?>
                        <?php foreach ($data_jadwal as $data): ?>
                            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 hover:shadow-xl hover:border-red-100 transition-all duration-300 group">
                                <div class="flex flex-col lg:flex-row items-center justify-between gap-10">
                                    
                                    <!-- Info Kereta -->
                                    <div class="flex items-center gap-6 w-full lg:w-1/3">
                                        <div class="w-16 h-16 bg-red-50 text-red-500 rounded-[1.5rem] flex items-center justify-center text-2xl shrink-0 group-hover:rotate-6 transition-transform">
                                            <i class="fas fa-subway"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-slate-800 uppercase tracking-widest mb-1">
                                                <?php echo htmlspecialchars($data['nama_kereta']); ?>
                                            </p>
                                            <div class="flex items-center gap-3 mb-2">
                                                <span class="font-bold text-gray-700 text-sm"><?php echo htmlspecialchars($data['stasiun_asal']); ?></span>
                                                <i class="fas fa-arrow-right text-[10px] text-gray-300"></i>
                                                <span class="font-bold text-gray-700 text-sm"><?php echo htmlspecialchars($data['stasiun_tujuan']); ?></span>
                                            </div>
                                            <span class="px-3 py-1 bg-slate-100 text-slate-500 rounded-full text-[9px] font-black uppercase tracking-widest border border-slate-200">Eksekutif Class</span>
                                        </div>
                                    </div>

                                    <!-- Waktu & Durasi -->
                                    <div class="flex items-center justify-between flex-1 w-full border-x-0 lg:border-x border-gray-100 px-0 lg:px-12 py-4 lg:py-0">
                                        <div class="text-center">
                                            <p class="text-2xl font-black text-slate-800 tracking-tighter"><?php echo date('H:i', strtotime($data['jam_berangkat'])); ?></p>
                                            <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mt-1"><?php echo htmlspecialchars($data['stasiun_asal']); ?></p>
                                        </div>
                                        <div class="flex flex-col items-center px-6">
                                            <span class="text-[8px] font-black text-red-400 mb-2 uppercase tracking-widest italic">Est. Durasi</span>
                                            <div class="h-px w-24 bg-gray-200 relative mb-2">
                                                <div class="absolute -top-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-red-500 rounded-full border-2 border-white shadow-sm"></div>
                                            </div>
                                            <span class="text-[10px] font-black text-slate-500 bg-slate-50 px-3 py-1 rounded-full"><?php echo htmlspecialchars($data['durasi']); ?> Jam</span>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-2xl font-black text-slate-800 tracking-tighter"><?php echo date('H:i', strtotime($data['jam_tiba'])); ?></p>
                                            <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mt-1"><?php echo htmlspecialchars($data['stasiun_tujuan']); ?></p>
                                        </div>
                                    </div>

                                    <!-- Harga & Booking -->
                                    <div class="w-full lg:w-1/4 text-center lg:text-right flex flex-col justify-center">
                                        <p class="text-[10px] text-gray-400 mb-1 font-bold uppercase tracking-widest">Harga Tiket Per Orang</p>
                                        <h4 class="text-2xl font-black text-red-600 mb-5 tracking-tighter">Rp <?php echo number_format($data['harga'], 0, ',', '.'); ?></h4>
                                        <a href="pesan_tiket.php?id=<?php echo $data['id_schedule']; ?>&asal_station_id=<?php echo $data['asal_station_id']; ?>&tujuan_station_id=<?php echo $data['tujuan_station_id']; ?>&tanggal=<?php echo urlencode($tanggal_filter); ?>"
                                           class="block w-full bg-slate-900 hover:bg-red-600 text-white font-black py-4 rounded-2xl transition-all shadow-xl shadow-slate-100 transform active:scale-95 text-xs uppercase tracking-widest text-center">
                                            PESAN TIKET SEKARANG
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="bg-white p-24 rounded-[3rem] text-center border-2 border-dashed border-gray-100">
                            <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-train text-gray-200 text-4xl"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 mb-2">Kereta Tidak Ditemukan</h3>
                            <p class="text-gray-400 text-sm max-w-xs mx-auto">Maaf, tidak ada jadwal operasional yang tersedia untuk rute stasiun yang Anda pilih saat ini.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- State Awal Sebelum Search -->
                    <div class="bg-white p-24 rounded-[3rem] text-center border-2 border-dashed border-gray-100">
                        <div class="w-24 h-24 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-search-location text-red-200 text-4xl animate-bounce"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Mulai Perjalanan Anda</h3>
                        <p class="text-gray-400 text-sm max-w-xs mx-auto">Gunakan form pencarian di atas untuk melihat jadwal kereta api antar stasiun yang tersedia.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <footer class="p-16 text-center text-gray-400 text-[10px] font-bold uppercase tracking-[0.3em] mt-auto border-t border-gray-50">
            &copy; 2026 RailTick Indonesia. Sistem Informasi Jadwal Kereta Api Digital.
        </footer>
    </main>

    <!-- Modal Konfirmasi Logout -->
    <div id="logoutModal" class="modal-overlay fixed inset-0 z-[100] hidden items-center justify-center p-4">
        <div class="modal-card bg-white w-full max-w-sm rounded-[2.5rem] shadow-2xl overflow-hidden border border-gray-100">
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <h3 class="text-xl font-black text-gray-800 mb-2 uppercase tracking-tight">Konfirmasi Keluar</h3>
                <p class="text-gray-400 text-sm leading-relaxed mb-8">Apakah Anda yakin ingin mengakhiri sesi perjalanan Anda di RailTick?</p>
                
                <div class="grid grid-cols-2 gap-4">
                    <button onclick="toggleLogoutModal(false)" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-4 rounded-2xl transition uppercase tracking-widest text-xs">
                        Tidak
                    </button>
                    <a href="../auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-2xl transition shadow-lg shadow-red-200 uppercase tracking-widest text-xs flex items-center justify-center">
                        Iya, Keluar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Logika Modal Logout
        function toggleLogoutModal(show) {
            const modal = document.getElementById('logoutModal');
            if (show) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                setTimeout(() => {
                    modal.classList.add('modal-active');
                }, 10);
            } else {
                modal.classList.remove('modal-active');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }, 300);
            }
        }

        // Tutup modal jika klik di luar card
        window.onclick = function(event) {
            const modal = document.getElementById('logoutModal');
            if (event.target == modal) {
                toggleLogoutModal(false);
            }
        }
    </script>
</body>
</html>
