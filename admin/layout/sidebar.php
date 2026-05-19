<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="w-64 bg-slate-900 min-h-screen text-slate-300 hidden md:flex flex-col sticky top-0">
    
    <!-- Logo -->
    <div class="h-20 border-b border-slate-800 flex items-center px-8">
        <a href="index.php" class="flex items-center gap-4 hover:opacity-80 transition-opacity">
            <div class="bg-red-500 w-10 h-10 rounded-xl text-white flex items-center justify-center shadow-lg shadow-red-500/20">
                <i class="fas fa-train text-lg"></i>
            </div>
            <span class="text-xl font-bold text-white uppercase tracking-tight">
                RailTick
            </span>
        </a>
    </div>

    <!-- Menu -->
    <nav class="flex-1 p-4 space-y-2 mt-4">
        <a href="index.php"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition 
           <?php echo ($current_page == 'index.php') ? 'bg-red-500 text-white shadow-lg shadow-red-500/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-chart-pie w-5 text-center"></i> Dashboard
        </a>

        <a href="routes.php"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition 
           <?php echo ($current_page == 'routes.php') ? 'bg-red-500 text-white shadow-lg shadow-red-500/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-map-marked-alt w-5 text-center"></i> Rute Lama
        </a>

        <a href="stations.php"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition 
           <?php echo ($current_page == 'stations.php') ? 'bg-red-500 text-white shadow-lg shadow-red-500/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-building w-5 text-center"></i> Stasiun
        </a>

        <a href="trains.php"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition 
           <?php echo ($current_page == 'trains.php') ? 'bg-red-500 text-white shadow-lg shadow-red-500/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-train w-5 text-center"></i> Kereta
        </a>

        <a href="train_routes.php"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition 
           <?php echo ($current_page == 'train_routes.php') ? 'bg-red-500 text-white shadow-lg shadow-red-500/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-route w-5 text-center"></i> Rute RailTick
        </a>

        <a href="schedules.php"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition 
           <?php echo ($current_page == 'schedules.php') ? 'bg-red-500 text-white shadow-lg shadow-red-500/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-calendar-alt w-5 text-center"></i> Kelola Jadwal
        </a>

        <a href="train_runs.php"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition 
           <?php echo ($current_page == 'train_runs.php') ? 'bg-red-500 text-white shadow-lg shadow-red-500/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-calendar-day w-5 text-center"></i> Perjalanan Harian
        </a>

        <a href="staff.php"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition 
           <?php echo ($current_page == 'staff.php') ? 'bg-red-500 text-white shadow-lg shadow-red-500/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-users-cog w-5 text-center"></i> Petugas
        </a>

        <a href="assignments.php"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition 
           <?php echo ($current_page == 'assignments.php') ? 'bg-red-500 text-white shadow-lg shadow-red-500/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-user-check w-5 text-center"></i> Penugasan
        </a>

        <a href="seats.php"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition 
           <?php echo ($current_page == 'seats.php') ? 'bg-red-500 text-white shadow-lg shadow-red-500/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-chair w-5 text-center"></i> Kelola Kursi
        </a>

        <a href="bookings.php"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition 
           <?php echo ($current_page == 'bookings.php') ? 'bg-red-500 text-white shadow-lg shadow-red-500/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-ticket-alt w-5 text-center"></i> Pesanan Tiket
        </a>

        <a href="report_export.php"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition 
           <?php echo ($current_page == 'report_export.php') ? 'bg-red-500 text-white shadow-lg shadow-red-500/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-file-excel w-5 text-center"></i> Laporan Penjualan
        </a>
    </nav>

    <!-- Logout Button -->
    <div class="p-4 border-t border-slate-800">
        <button onclick="toggleLogoutModal(true)"
            class="flex items-center gap-3 text-red-400 hover:bg-red-500 hover:text-white px-4 py-3 rounded-xl transition w-full text-left">
            <i class="fas fa-sign-out-alt w-5 text-center"></i>
            <span class="font-medium">Keluar Sistem</span>
        </button>
    </div>

</aside>

<!-- INCLUDE MODAL -->
<?php include 'modal_logout.php'; ?>
