<?php
session_start();
include "../config/koneksi.php";

// Proteksi Halaman User
if (!isset($_SESSION['role']) || $_SESSION['role'] != "user") {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil ID user dari session
$id_user = $_SESSION['user_id'];

/**
 * Ambil data user terbaru langsung dari database
 * Agar nama di dashboard otomatis berubah setelah edit profil tanpa harus re-login
 */
$query_user_db = mysqli_query($conn, "SELECT nama_lengkap, username, nik FROM users WHERE id_user = '$id_user'");
$user_db = mysqli_fetch_assoc($query_user_db);
$nama_tampil = !empty($user_db['nama_lengkap']) ? $user_db['nama_lengkap'] : $user_db['username'];

// 1. Menghitung Tiket Aktif (Status Berhasil)
$query_aktif = mysqli_query($conn, "SELECT * FROM bookings WHERE id_user = '$id_user' AND status_booking = 'berhasil'");
$total_aktif = mysqli_num_rows($query_aktif);

// 2. Menghitung Total Pengeluaran
$query_total = mysqli_query($conn, "SELECT SUM(total_harga) as total FROM bookings WHERE id_user = '$id_user' AND status_booking = 'berhasil'");
$data_total = mysqli_fetch_assoc($query_total);
$total_pengeluaran = $data_total['total'] ?? 0;

// 3. Mengambil data pesanan terakhir untuk tabel
$query_recent = mysqli_query($conn, "
    SELECT 
        b.*,
        s.jam_berangkat,
        s.jam_tiba,
        s.nama_kereta,
        u.nama_lengkap,
        u.nik,
        COALESCE(sa.nama_station, r.asal) AS asal,
        COALESCE(st.nama_station, r.tujuan) AS tujuan
    FROM bookings b
    JOIN schedules s ON b.id_schedule = s.id_schedule
    LEFT JOIN routes r ON r.id_route = s.id_route
    JOIN users u ON b.id_user = u.id_user
    LEFT JOIN stations sa ON sa.id_station = b.asal_station_id
    LEFT JOIN stations st ON st.id_station = b.tujuan_station_id
    WHERE b.id_user = '$id_user' 
    ORDER BY b.created_at DESC
    LIMIT 5
");

if (!$query_recent) {
    die("Query Error: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Saya - RailTick</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Libre+Barcode+128&display=swap');
        body { font-family: 'Poppins', sans-serif; }

        .user-gradient {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        }

        @media print {
            body * { visibility: hidden; }
            #ticket-print-area, #ticket-print-area * { visibility: visible; }
            #ticket-print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 0;
                margin: 0;
            }
            .no-print { display: none !important; }
        }

        .barcode {
            font-family: 'Libre Barcode 128', cursive;
            font-size: 40px;
        }

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

    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-100 hidden md:flex flex-col sticky top-0 h-screen shadow-sm no-print">
        <div class="p-8 border-b border-gray-50 flex items-center gap-3">
            <div class="bg-red-500 p-2 rounded-lg text-white">
                <i class="fas fa-train"></i>
            </div>
            <span class="text-xl font-bold text-gray-800 italic">RailTick</span>
        </div>
        
        <nav class="flex-1 p-6 space-y-3 mt-4">
            <a href="dashboard.php" class="flex items-center gap-3 bg-red-50 text-red-600 px-4 py-3 rounded-2xl font-bold transition">
                <i class="fas fa-home"></i> Beranda
            </a>
            <a href="jadwal.php" class="flex items-center gap-3 text-gray-500 hover:bg-gray-50 hover:text-red-500 px-4 py-3 rounded-2xl transition font-medium">
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
            <button onclick="toggleLogoutModal(true)" class="w-full flex items-center gap-3 text-gray-400 hover:text-red-500 px-4 py-3 transition text-sm font-bold uppercase tracking-widest outline-none">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 no-print">
        <!-- Topbar -->
        <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 px-8 py-5 border-b border-gray-100 flex justify-between items-center">
            <div>
                <h1 class="text-sm text-gray-400 font-medium tracking-wide uppercase">Dashboard / Ringkasan Akun</h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($nama_tampil); ?></p>
                    <p class="text-[10px] text-red-500 font-bold uppercase tracking-widest">Penumpang</p>
                </div>
                <a href="edit_profil.php" class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 border border-gray-200 hover:border-red-500 hover:text-red-500 transition shadow-sm group">
                    <i class="fas fa-user group-hover:scale-110 transition-transform"></i>
                </a>
            </div>
        </header>

        <div class="p-8">
            <!-- Welcome Section -->
            <div class="user-gradient p-10 rounded-[2.5rem] text-white mb-10 relative overflow-hidden shadow-2xl shadow-slate-200">
                <div class="relative z-10">
                    <h2 class="text-3xl font-bold mb-2">Halo, <?php echo htmlspecialchars($nama_tampil); ?>! 👋</h2>
                    <p class="text-slate-300 opacity-90 max-w-md mb-8 font-light">
                        Siap untuk petualangan baru? Cek jadwal kereta favoritmu dan pesan tiket sekarang.
                    </p>
                    <a href="jadwal.php" class="bg-red-500 hover:bg-red-600 text-white px-8 py-3.5 rounded-xl font-bold transition inline-flex items-center gap-3 shadow-lg shadow-red-500/20 uppercase tracking-widest text-xs">
                        Cari Tiket Sekarang <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                </div>
                <i class="fas fa-train absolute -right-10 -bottom-10 text-[15rem] opacity-5 transform -rotate-12"></i>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-between group hover:shadow-md transition">
                    <div>
                        <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-blue-500 group-hover:text-white transition-colors duration-500">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mb-1">Tiket Aktif</p>
                        <h4 class="text-3xl font-black text-slate-800 tracking-tighter">
                            <?php echo $total_aktif; ?> 
                            <span class="text-sm font-normal text-gray-400 tracking-normal">Tiket</span>
                        </h4>
                    </div>
                </div>
                
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-between group hover:shadow-md transition">
                    <div>
                        <div class="w-12 h-12 bg-green-50 text-green-500 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-green-500 group-hover:text-white transition-colors duration-500">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mb-1">Total Pengeluaran</p>
                        <h4 class="text-3xl font-black text-slate-800 tracking-tighter">
                            Rp <?php echo number_format($total_pengeluaran, 0, ',', '.'); ?>
                        </h4>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/30">
                    <h3 class="font-bold text-gray-800 text-lg tracking-tight">Aktivitas Terakhir</h3>
                    <a href="riwayat.php" class="text-red-500 font-bold text-xs uppercase tracking-widest hover:underline">Lihat Semua</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-black tracking-widest border-b">
                            <tr>
                                <th class="px-8 py-4">Kode Booking</th>
                                <th class="px-8 py-4">Detail Perjalanan</th>
                                <th class="px-8 py-4 text-center">Tiket / Total</th>
                                <th class="px-8 py-4 text-center">Opsi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php while($row = mysqli_fetch_assoc($query_recent)): ?>
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-8 py-6 font-black text-sm text-blue-600 italic">
                                    #<?php echo htmlspecialchars($row['kode_booking']); ?>
                                </td>

                                <td class="px-8 py-6">
                                    <div class="text-sm">
                                        <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">
                                            <?php echo htmlspecialchars($row['nama_kereta'] ?? 'Kereta'); ?>
                                        </p>
                                        <p class="font-bold text-gray-800 uppercase text-xs">
                                            <?php echo htmlspecialchars($row['asal']) . " → " . htmlspecialchars($row['tujuan']); ?>
                                        </p>
                                        <p class="text-[10px] text-red-500 font-bold mt-1 uppercase tracking-tighter">
                                            <?php echo date('d M Y', strtotime($row['tanggal_berangkat'])); ?> • <?php echo date('H:i', strtotime($row['jam_berangkat'])); ?> WIB
                                        </p>
                                    </div>
                                </td>

                                <td class="px-8 py-6 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="bg-slate-100 text-slate-700 px-3 py-1 rounded-lg text-xs font-black">
                                            <?php echo (int)$row['jumlah_tiket']; ?> Psg
                                        </span>
                                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                                            Rp <?php echo number_format($row['harga_satuan'], 0, ',', '.'); ?> / tiket
                                        </div>
                                        <div class="text-sm font-black text-red-500">
                                            Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-8 py-6 text-center">
                                    <button 
                                        onclick='preparePrint(<?php echo json_encode([
                                            "kode_booking"       => $row["kode_booking"],
                                            "nama_lengkap"       => $row["nama_lengkap"],
                                            "nik"                => $row["nik"],
                                            "nama_kereta"        => $row["nama_kereta"],
                                            "asal"               => $row["asal"],
                                            "tujuan"             => $row["tujuan"],
                                            "jam_berangkat"      => $row["jam_berangkat"],
                                            "jam_tiba"           => $row["jam_tiba"],
                                            "tanggal_berangkat"  => $row["tanggal_berangkat"],
                                            "jumlah_tiket"       => $row["jumlah_tiket"],
                                            "harga_satuan"       => $row["harga_satuan"],
                                            "total_harga"        => $row["total_harga"]
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                                        class="bg-slate-900 hover:bg-red-600 text-white text-[10px] font-bold py-2.5 px-5 rounded-xl uppercase transition flex items-center gap-2 mx-auto shadow-sm group">
                                        <i class="fas fa-print group-hover:scale-110 transition-transform"></i> Cetak
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if(mysqli_num_rows($query_recent) == 0): ?>
                            <tr>
                                <td colspan="4" class="px-8 py-16 text-center text-gray-400 italic text-sm">
                                    <i class="fas fa-folder-open text-4xl mb-4 opacity-10 block"></i>
                                    Belum ada riwayat pemesanan tiket.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <footer class="p-16 text-center text-gray-400 text-[10px] font-bold uppercase tracking-[0.4em] border-t border-gray-50">
            &copy; 2026 RailTick Indonesia. Sistem Informasi Tiket Terintegrasi.
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

    <!-- Area Cetak Tiket -->
    <div id="ticket-print-area" class="hidden print:block p-10 bg-white min-h-screen">
        <div class="max-w-4xl mx-auto border-2 border-slate-900 rounded-lg overflow-hidden flex flex-col md:flex-row bg-white">
            <div class="flex-1 p-8 border-r-2 border-dashed border-slate-300 relative">
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 italic uppercase">RailTick</h1>
                        <p class="text-[10px] font-bold text-slate-500 tracking-widest uppercase">E-Ticket / Boarding Pass</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-slate-400">Kode Booking</p>
                        <h2 id="print-kode" class="text-xl font-black text-red-600"></h2>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8 mb-10">
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase font-bold mb-1 tracking-tighter">Nama Penumpang</p>
                        <p id="print-nama" class="text-sm font-black text-slate-800 uppercase"></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase font-bold mb-1 tracking-tighter">Nomor Identitas (NIK)</p>
                        <p id="print-nik" class="text-sm font-black text-slate-800"></p>
                    </div>
                </div>

                <div class="mb-6">
                    <p class="text-[10px] text-slate-400 uppercase font-bold mb-1 tracking-tighter">Nama Kereta</p>
                    <p id="print-kereta" class="text-sm font-black text-slate-800 uppercase"></p>
                </div>

                <div class="flex items-center justify-between bg-slate-50 p-6 rounded-xl border border-slate-100 mb-10">
                    <div class="text-center">
                        <p id="print-asal" class="text-lg font-black text-slate-900 uppercase"></p>
                        <p id="print-jam-berangkat" class="text-2xl font-black text-blue-700"></p>
                    </div>
                    <div class="flex-1 px-4 flex flex-col items-center">
                        <i class="fas fa-train text-slate-300 text-xl mb-1"></i>
                        <div class="w-full h-px bg-slate-200 relative">
                            <div class="absolute -top-1 left-1/2 -translate-x-1/2 bg-white px-2 text-[8px] font-bold text-slate-400 tracking-widest uppercase">EXECUTIVE</div>
                        </div>
                    </div>
                    <div class="text-center">
                        <p id="print-tujuan" class="text-lg font-black text-slate-900 uppercase"></p>
                        <p id="print-jam-tiba" class="text-2xl font-black text-slate-400"></p>
                    </div>
                </div>

                <div class="grid grid-cols-5 gap-4">
                    <div class="border-r border-slate-100">
                        <p class="text-[9px] text-slate-400 uppercase font-bold">Tanggal</p>
                        <p id="print-tanggal" class="text-xs font-black text-slate-800"></p>
                    </div>

                    <div class="border-r border-slate-100 pl-4">
                        <p class="text-[9px] text-slate-400 uppercase font-bold">Tiket</p>
                        <p id="print-jumlah" class="text-xs font-black text-slate-800 uppercase"></p>
                    </div>

                    <div class="border-r border-slate-100 pl-4">
                        <p class="text-[9px] text-slate-400 uppercase font-bold">Harga / Tiket</p>
                        <p id="print-harga" class="text-xs font-black text-slate-800"></p>
                    </div>

                    <div class="border-r border-slate-100 pl-4">
                        <p class="text-[9px] text-slate-400 uppercase font-bold">Total</p>
                        <p id="print-total" class="text-xs font-black text-red-600"></p>
                    </div>

                    <div class="pl-4">
                        <p class="text-[9px] text-slate-400 uppercase font-bold">Status</p>
                        <p class="text-xs font-black text-green-600 uppercase">VALID</p>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-64 bg-slate-900 text-white p-8 flex flex-col items-center justify-center text-center">
                <div class="bg-white p-3 rounded-xl mb-4">
                    <img id="qr-image" src="" alt="QR" class="w-24 h-24">
                </div>
                <p class="text-[8px] font-bold tracking-widest uppercase opacity-60 mb-6">Scan QR Code saat Boarding</p>
                <div class="barcode leading-none opacity-80 mb-2">1234567890</div>
                <p class="text-[10px] font-black uppercase">PT RailTick Indonesia</p>
                <p class="text-[8px] opacity-40">Tunjukkan Identitas Asli</p>
            </div>
        </div>
    </div>

    <script>
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

        window.onclick = function(event) {
            const modal = document.getElementById('logoutModal');
            if (event.target == modal) {
                toggleLogoutModal(false);
            }
        }

        function preparePrint(data) {
            document.getElementById('print-kode').innerText = data.kode_booking;
            document.getElementById('print-nama').innerText = data.nama_lengkap;
            document.getElementById('print-nik').innerText = data.nik;
            document.getElementById('print-kereta').innerText = data.nama_kereta || '-';
            document.getElementById('print-asal').innerText = data.asal;
            document.getElementById('print-tujuan').innerText = data.tujuan;

            document.getElementById('print-jam-berangkat').innerText =
                (data.jam_berangkat ? data.jam_berangkat.substring(0, 5) : '--:--') + ' WIB';

            document.getElementById('print-jam-tiba').innerText =
                (data.jam_tiba ? data.jam_tiba.substring(0, 5) : '--:--') + ' WIB';

            document.getElementById('print-jumlah').innerText = data.jumlah_tiket + ' Orang';
            document.getElementById('print-harga').innerText =
                'Rp ' + Number(data.harga_satuan).toLocaleString('id-ID');
            document.getElementById('print-total').innerText =
                'Rp ' + Number(data.total_harga).toLocaleString('id-ID');

            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            const date = new Date(data.tanggal_berangkat);
            document.getElementById('print-tanggal').innerText = date.toLocaleDateString('id-ID', options);

            document.getElementById('qr-image').src =
                `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(data.kode_booking)}`;

            setTimeout(() => { window.print(); }, 500);
        }
    </script>

</body>
</html>