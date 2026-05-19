<?php
include "config/koneksi.php";
include "config/operational_status.php";
sync_operational_status($conn);

$stations = [];
$qStations = mysqli_query($conn, "SELECT nama_station FROM stations WHERE status_station = 'aktif' ORDER BY nama_station ASC");
while ($row = mysqli_fetch_assoc($qStations)) {
    $stations[] = $row['nama_station'];
}

$asal = isset($_GET['asal']) ? mysqli_real_escape_string($conn, $_GET['asal']) : '';
$tujuan = isset($_GET['tujuan']) ? mysqli_real_escape_string($conn, $_GET['tujuan']) : '';
$tanggal = isset($_GET['tanggal']) && $_GET['tanggal'] !== '' ? mysqli_real_escape_string($conn, $_GET['tanggal']) : date('Y-m-d');
$showResults = false;
$message = '';
$data_jadwal = [];

if (!empty($asal) && !empty($tujuan)) {
    $showResults = true;

    if ($asal === $tujuan) {
        $message = "Stasiun asal dan tujuan tidak boleh sama.";
    } else {
        $sql = "
            SELECT
                s.id_schedule,
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
            WHERE sa.nama_station = '$asal'
              AND st.nama_station = '$tujuan'
              AND rs_asal.urutan < rs_tujuan.urutan
              AND rs_asal.is_stop = 1
              AND rs_tujuan.is_stop = 1
              AND EXISTS (
                    SELECT 1
                    FROM train_runs run
                    WHERE run.id_schedule = s.id_schedule
                      AND run.tanggal_berangkat = '$tanggal'
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
    <title>RailTick - Jelajahi Kota Menakjubkan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }

        .hero-bg {
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)),
                        url('pictures/kereta.png');
            background-size: cover;
            background-position: center;
        }

        .search-container {
            margin-top: -60px;
        }

        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">

    <nav class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center space-x-12">
                    <div class="flex items-center gap-2 cursor-pointer" onclick="window.location.href='index.php'">
                        <div class="bg-red-500 p-2 rounded-lg text-white">
                            <i class="fas fa-train text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold tracking-tight text-gray-800">RailTick</span>
                    </div>

                    <div class="hidden md:flex items-center space-x-8">
                        <a href="about_us.php" class="text-gray-600 hover:text-red-500 font-semibold transition">
                            About Us
                        </a>
                        <a href="contact.php" class="text-gray-600 hover:text-red-500 font-semibold transition">
                            Contact Us
                        </a>
                    </div>
                </div>

                <div class="flex items-center space-x-6">
                    <a href="auth/login.php" class="text-gray-700 font-medium hover:text-red-500 transition">
                        Masuk
                    </a>
                    <a href="auth/register.php" class="bg-red-500 text-white font-semibold px-6 py-3 rounded-md hover:bg-red-600 transition shadow-lg shadow-red-200">
                        Daftar
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <header class="hero-bg min-h-[70vh] flex items-center relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="max-w-2xl text-white">
                <h1 class="text-5xl md:text-7xl font-bold mb-4 leading-tight">
                    Explore <br><span class="text-red-500">your amazing</span> city
                </h1>
                <p class="text-xl md:text-2xl opacity-90 font-light">
                    Temukan kenyamanan perjalanan kereta api ke seluruh penjuru negeri bersama RailTick.
                </p>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20 search-container">
        <div class="bg-white p-8 rounded-xl shadow-2xl border border-gray-100">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-500 mb-2">Stasiun Asal</label>
                    <div class="relative">
                        <select name="asal" required class="w-full pl-4 pr-10 py-4 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 outline-none appearance-none font-medium text-gray-700">
                            <option value="" disabled <?php echo empty($asal) ? 'selected' : ''; ?>>Pilih Asal</option>
                            <?php foreach ($stations as $station): ?>
                                <option value="<?php echo htmlspecialchars($station); ?>" <?php echo ($asal === $station) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($station); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-5 text-gray-400 pointer-events-none"></i>
                    </div>
                </div>

                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-500 mb-2">Stasiun Tujuan</label>
                    <div class="relative">
                        <select name="tujuan" required class="w-full pl-4 pr-10 py-4 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 outline-none appearance-none font-medium text-gray-700">
                            <option value="" disabled <?php echo empty($tujuan) ? 'selected' : ''; ?>>Pilih Tujuan</option>
                            <?php foreach ($stations as $station): ?>
                                <option value="<?php echo htmlspecialchars($station); ?>" <?php echo ($tujuan === $station) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($station); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-5 text-gray-400 pointer-events-none"></i>
                    </div>
                </div>

                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-500 mb-2">Tanggal Pergi</label>
                    <input type="date" name="tanggal" min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($tanggal); ?>" class="w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 outline-none font-medium text-gray-700">
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-lg transition transform active:scale-95 shadow-xl shadow-red-200 uppercase tracking-widest text-sm">
                        CARI JADWAL
                    </button>
                </div>
            </form>
        </div>
    </div>

    <section id="resultsSection" class="<?php echo $showResults ? 'py-16 bg-gray-50' : 'hidden py-16 bg-gray-50'; ?>">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-10 border-b border-gray-200 pb-4">
                <h2 class="text-3xl font-bold text-gray-800">Jadwal Perjalanan Antar Stasiun</h2>
                <a href="index.php" class="text-red-500 font-bold hover:bg-red-50 px-4 py-2 rounded-lg transition">Tutup</a>
            </div>

            <?php if ($message !== ''): ?>
                <div class="mb-6 p-4 bg-red-50 text-red-700 text-sm rounded-xl border-l-4 border-red-500">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div id="resultsList" class="grid grid-cols-1 gap-6">
                <?php if ($showResults && $message === ''): ?>
                    <?php if (count($data_jadwal) > 0): ?>
                        <?php foreach ($data_jadwal as $item): ?>
                            <div class="bg-white p-8 rounded-[2rem] shadow-sm flex flex-col md:flex-row items-center justify-between border-l-8 border-red-500 group hover:shadow-2xl transition-all duration-500">
                                <div class="flex items-center gap-6 w-full md:w-1/3">
                                    <div class="w-16 h-16 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center transform group-hover:rotate-12 transition">
                                        <i class="fas fa-train text-2xl"></i>
                                    </div>
                                    <div>
                                        <h5 class="font-bold text-xl text-gray-800 tracking-tight"><?php echo htmlspecialchars($item['nama_kereta']); ?></h5>
                                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Executive Class</p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-around flex-1 w-full my-10 md:my-0 border-y md:border-y-0 md:border-x border-gray-100 py-6 md:py-0">
                                    <div class="text-center">
                                        <span class="block text-2xl font-black text-slate-800"><?php echo date('H:i', strtotime($item['jam_berangkat'])); ?></span>
                                        <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest"><?php echo htmlspecialchars($item['stasiun_asal']); ?></span>
                                    </div>

                                    <div class="px-8 flex flex-col items-center">
                                        <i class="fas fa-long-arrow-alt-right text-gray-200 text-2xl"></i>
                                        <span class="text-[10px] text-gray-400 font-bold mt-2"><?php echo htmlspecialchars($item['durasi']); ?></span>
                                    </div>

                                    <div class="text-center">
                                        <span class="block text-2xl font-black text-slate-800"><?php echo date('H:i', strtotime($item['jam_tiba'])); ?></span>
                                        <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest"><?php echo htmlspecialchars($item['stasiun_tujuan']); ?></span>
                                    </div>
                                </div>

                                <div class="w-full md:w-1/4 text-center md:text-right">
                                    <p class="text-[10px] text-gray-400 mb-1 font-black uppercase tracking-widest">Tiket Mulai</p>
                                    <p class="text-2xl font-black text-red-600 mb-4 tracking-tighter">IDR <?php echo number_format($item['harga'], 0, ',', '.'); ?></p>
                                    <a href="auth/login.php" class="block bg-slate-900 text-white px-8 py-3.5 rounded-xl font-bold hover:bg-red-500 transition-all w-full uppercase tracking-widest text-xs">
                                        Pesan Sekarang
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center p-10 bg-white rounded-3xl border-2 border-dashed text-gray-400">
                            Jadwal tidak ditemukan.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
                <div>
                    <h2 class="text-4xl font-bold mb-6">Perjalanan Nyaman, <br><span class="text-red-500">Tanpa Ribet.</span></h2>
                    <p class="text-gray-600 text-lg mb-8 leading-relaxed">
                        Nikmati kemudahan memesan tiket kereta api secara digital. Dengan RailTick, Anda bisa memantau jadwal secara real-time dan mendapatkan harga terbaik setiap harinya.
                    </p>
                    <div class="flex gap-4">
                        <a href="about_us.php" class="bg-gray-900 text-white px-8 py-3 rounded-lg font-bold hover:bg-gray-800 transition">Pelajari Lebih Lanjut</a>
                        <a href="contact.php" class="border border-gray-200 px-8 py-3 rounded-lg font-bold hover:bg-gray-50 transition">Contact Us</a>
                    </div>
                </div>
                <div class="bg-red-50 p-12 rounded-[3rem] relative">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center text-white"><i class="fas fa-check"></i></div>
                        <p class="font-bold text-xl">Pembayaran Instan</p>
                    </div>
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center text-white"><i class="fas fa-check"></i></div>
                        <p class="font-bold text-xl">E-Boarding Pass</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center text-white"><i class="fas fa-check"></i></div>
                        <p class="font-bold text-xl">Refund Mudah</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-gray-100 text-gray-400 py-16 border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <div class="flex justify-center items-center gap-2 mb-8">
                <div class="bg-red-500 p-1.5 rounded text-white">
                    <i class="fas fa-train"></i>
                </div>
                <span class="text-gray-800 font-bold text-xl uppercase tracking-widest">RailTick</span>
            </div>
            <p class="text-sm">© 2026 RailTick Indonesia. Experience the best way to travel.</p>
        </div>
    </footer>

    <?php if ($showResults): ?>
    <script>
        window.addEventListener('load', function () {
            const resultsSection = document.getElementById('resultsSection');
            if (resultsSection) {
                resultsSection.scrollIntoView({ behavior: 'smooth' });
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
