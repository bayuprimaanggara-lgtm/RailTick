<!-- no use sementara -->

<aside class="w-64 bg-white border-r border-gray-100 hidden md:flex flex-col sticky top-0 h-screen shadow-sm no-print">
    <div class="p-8 border-b border-gray-50 flex items-center gap-3">
        <div class="bg-red-500 p-2 rounded-lg text-white">
            <i class="fas fa-train"></i>
        </div>
        <span class="text-xl font-bold text-gray-800 italic">RailTick</span>
    </div>
    
    <nav class="flex-1 p-6 space-y-3 mt-4">
        <a href="dashboard.php" class="flex items-center gap-3 bg-red-50 text-red-600 px-4 py-3 rounded-2xl font-semibold transition">
            <i class="fas fa-home"></i> Beranda
        </a>
        <a href="jadwal.php" class="flex items-center gap-3 text-gray-500 hover:bg-gray-50 hover:text-red-500 px-4 py-3 rounded-2xl transition">
            <i class="fas fa-search"></i> Cari Jadwal
        </a>
        <a href="riwayat.php" class="flex items-center gap-3 text-gray-500 hover:bg-gray-50 hover:text-red-500 px-4 py-3 rounded-2xl transition">
            <i class="fas fa-history"></i> Riwayat Pesanan
        </a>
    </nav>

    <div class="p-6 border-t border-gray-50">
        <a href="../auth/logout.php" class="flex items-center gap-3 text-gray-400 hover:text-red-500 px-4 py-3 transition text-sm">
            <i class="fas fa-sign-out-alt"></i> Keluar Akun
        </a>
    </div>
</aside>