<?php
session_start();
include "../config/koneksi.php";
include "../config/operational_status.php";
sync_operational_status($conn);

// Proteksi Halaman Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil data statistik lengkap
$total_rute = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM routes"));
$total_jadwal = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM schedules"));
$total_booking = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM bookings"));
$total_kereta = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM trains"));
$total_petugas = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM staff WHERE status_staff='aktif'"));
$total_run_hari_ini = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM train_runs WHERE tanggal_berangkat = CURDATE() AND status_run <> 'batal'"));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RailTick</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 flex">

    <?php include "layout/sidebar.php"; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col">
        <!-- Topbar -->
        <header class="h-20 bg-white border-b border-slate-800/10 flex items-center justify-between px-8 sticky top-0 z-30">
            <div class="flex items-center gap-4">
                <h1 class="text-xl font-bold text-gray-800 tracking-tight">Ringkasan Sistem</h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'Administrator'); ?></p>
                    <p class="text-[10px] text-green-500 font-black uppercase tracking-widest">Admin Online</p>
                </div>
                <div class="w-10 h-10 bg-red-50 text-red-500 rounded-full flex items-center justify-center border border-red-100 shadow-sm">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="p-8">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-6 mb-12">
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 flex items-center gap-6 group hover:shadow-xl transition duration-500">
                    <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-3xl flex items-center justify-center text-2xl group-hover:rotate-12 transition">
                        <i class="fas fa-route"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-1">Total Rute</p>
                        <h4 class="text-3xl font-black text-slate-800 tracking-tighter"><?php echo $total_rute; ?></h4>
                    </div>
                </div>
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 flex items-center gap-6 group hover:shadow-xl transition duration-500">
                    <div class="w-16 h-16 bg-purple-50 text-purple-600 rounded-3xl flex items-center justify-center text-2xl group-hover:rotate-12 transition">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-1">Total Jadwal</p>
                        <h4 class="text-3xl font-black text-slate-800 tracking-tighter"><?php echo $total_jadwal; ?></h4>
                    </div>
                </div>
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 flex items-center gap-6 group hover:shadow-xl transition duration-500">
                    <div class="w-16 h-16 bg-red-50 text-red-500 rounded-3xl flex items-center justify-center text-2xl group-hover:rotate-12 transition">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-1">Total Pesanan</p>
                        <h4 class="text-3xl font-black text-slate-800 tracking-tighter"><?php echo $total_booking; ?></h4>
                    </div>
                </div>
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 flex items-center gap-6 group hover:shadow-xl transition duration-500">
                    <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-3xl flex items-center justify-center text-2xl group-hover:rotate-12 transition">
                        <i class="fas fa-train"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-1">Kereta</p>
                        <h4 class="text-3xl font-black text-slate-800 tracking-tighter"><?php echo $total_kereta; ?></h4>
                    </div>
                </div>
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 flex items-center gap-6 group hover:shadow-xl transition duration-500">
                    <div class="w-16 h-16 bg-yellow-50 text-yellow-600 rounded-3xl flex items-center justify-center text-2xl group-hover:rotate-12 transition">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-1">Petugas</p>
                        <h4 class="text-3xl font-black text-slate-800 tracking-tighter"><?php echo $total_petugas; ?></h4>
                    </div>
                </div>
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 flex items-center gap-6 group hover:shadow-xl transition duration-500">
                    <div class="w-16 h-16 bg-cyan-50 text-cyan-600 rounded-3xl flex items-center justify-center text-2xl group-hover:rotate-12 transition">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-1">Run Hari Ini</p>
                        <h4 class="text-3xl font-black text-slate-800 tracking-tighter"><?php echo $total_run_hari_ini; ?></h4>
                    </div>
                </div>
            </div>

            <!-- Management Menu Grid -->
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-[0.3em] mb-8 ml-2">Utama</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Card Rute -->
                <a href="routes.php" class="group bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 hover:shadow-2xl hover:border-red-500 transition-all duration-500">
                    <div class="w-12 h-12 bg-gray-50 text-gray-400 group-hover:bg-red-500 group-hover:text-white rounded-2xl flex items-center justify-center text-xl transition mb-4">
                        <i class="fas fa-map-signs"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-1 text-gray-800 tracking-tight">Kelola Rute</h3>
                    <p class="text-gray-400 text-[11px] mb-4 leading-relaxed line-clamp-2">Tambah rute operasional kereta baru.</p>
                    <span class="text-red-500 font-bold text-[10px] uppercase tracking-widest inline-flex items-center gap-2">
                        Buka Menu <i class="fas fa-chevron-right text-[8px]"></i>
                    </span>
                </a>

                <!-- Card Jadwal -->
                <a href="schedules.php" class="group bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 hover:shadow-2xl hover:border-red-500 transition-all duration-500">
                    <div class="w-12 h-12 bg-gray-50 text-gray-400 group-hover:bg-red-500 group-hover:text-white rounded-2xl flex items-center justify-center text-xl transition mb-4">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-1 text-gray-800 tracking-tight">Kelola Jadwal</h3>
                    <p class="text-gray-400 text-[11px] mb-4 leading-relaxed line-clamp-2">Atur jam keberangkatan & kedatangan.</p>
                    <span class="text-red-500 font-bold text-[10px] uppercase tracking-widest inline-flex items-center gap-2">
                        Buka Menu <i class="fas fa-chevron-right text-[8px]"></i>
                    </span>
                </a>

                <!-- Card Kursi -->
                <a href="seats.php" class="group bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 hover:shadow-2xl hover:border-red-500 transition-all duration-500">
                    <div class="w-12 h-12 bg-gray-50 text-gray-400 group-hover:bg-red-500 group-hover:text-white rounded-2xl flex items-center justify-center text-xl transition mb-4">
                        <i class="fas fa-couch"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-1 text-gray-800 tracking-tight">Kelola Kursi</h3>
                    <p class="text-gray-400 text-[11px] mb-4 leading-relaxed line-clamp-2">Manajemen gerbong dan nomor kursi.</p>
                    <span class="text-red-500 font-bold text-[10px] uppercase tracking-widest inline-flex items-center gap-2">
                        Buka Menu <i class="fas fa-chevron-right text-[8px]"></i>
                    </span>
                </a>

                <!-- Card Pesanan -->
                <a href="bookings.php" class="group bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 hover:shadow-2xl hover:border-red-500 transition-all duration-500">
                    <div class="w-12 h-12 bg-gray-50 text-gray-400 group-hover:bg-red-500 group-hover:text-white rounded-2xl flex items-center justify-center text-xl transition mb-4">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-1 text-gray-800 tracking-tight">Pesanan Tiket</h3>
                    <p class="text-gray-400 text-[11px] mb-4 leading-relaxed line-clamp-2">Pantau data pesanan para penumpang.</p>
                    <span class="text-red-500 font-bold text-[10px] uppercase tracking-widest inline-flex items-center gap-2">
                        Buka Menu <i class="fas fa-chevron-right text-[8px]"></i>
                    </span>
                </a>
                <a href="trains.php" class="group bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 hover:shadow-2xl hover:border-red-500 transition-all duration-500">
                    <div class="w-12 h-12 bg-gray-50 text-gray-400 group-hover:bg-red-500 group-hover:text-white rounded-2xl flex items-center justify-center text-xl transition mb-4">
                        <i class="fas fa-train"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-1 text-gray-800 tracking-tight">Master Kereta</h3>
                    <p class="text-gray-400 text-[11px] mb-4 leading-relaxed line-clamp-2">Kelola data armada kereta.</p>
                    <span class="text-red-500 font-bold text-[10px] uppercase tracking-widest inline-flex items-center gap-2">Buka Menu <i class="fas fa-chevron-right text-[8px]"></i></span>
                </a>
                <a href="train_runs.php" class="group bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 hover:shadow-2xl hover:border-red-500 transition-all duration-500">
                    <div class="w-12 h-12 bg-gray-50 text-gray-400 group-hover:bg-red-500 group-hover:text-white rounded-2xl flex items-center justify-center text-xl transition mb-4">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-1 text-gray-800 tracking-tight">Perjalanan Harian</h3>
                    <p class="text-gray-400 text-[11px] mb-4 leading-relaxed line-clamp-2">Atur kereta yang jalan per tanggal.</p>
                    <span class="text-red-500 font-bold text-[10px] uppercase tracking-widest inline-flex items-center gap-2">Buka Menu <i class="fas fa-chevron-right text-[8px]"></i></span>
                </a>
                <a href="staff.php" class="group bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 hover:shadow-2xl hover:border-red-500 transition-all duration-500">
                    <div class="w-12 h-12 bg-gray-50 text-gray-400 group-hover:bg-red-500 group-hover:text-white rounded-2xl flex items-center justify-center text-xl transition mb-4">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-1 text-gray-800 tracking-tight">Petugas</h3>
                    <p class="text-gray-400 text-[11px] mb-4 leading-relaxed line-clamp-2">Masinis, kondektur, dan pramuniaga.</p>
                    <span class="text-red-500 font-bold text-[10px] uppercase tracking-widest inline-flex items-center gap-2">Buka Menu <i class="fas fa-chevron-right text-[8px]"></i></span>
                </a>
                <a href="assignments.php" class="group bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 hover:shadow-2xl hover:border-red-500 transition-all duration-500">
                    <div class="w-12 h-12 bg-gray-50 text-gray-400 group-hover:bg-red-500 group-hover:text-white rounded-2xl flex items-center justify-center text-xl transition mb-4">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-1 text-gray-800 tracking-tight">Penugasan Kru</h3>
                    <p class="text-gray-400 text-[11px] mb-4 leading-relaxed line-clamp-2">Atur petugas per keberangkatan.</p>
                    <span class="text-red-500 font-bold text-[10px] uppercase tracking-widest inline-flex items-center gap-2">Buka Menu <i class="fas fa-chevron-right text-[8px]"></i></span>
                </a>
            </div>

            <!-- Footer -->
            <footer class="mt-16 text-center text-gray-300 text-[10px] font-bold uppercase tracking-widest">
                <p>&copy; 2026 RailTick Control Center. Build 1.0.7-stable</p>
        </div>
        </div>
    </main>

</body>

</html>
