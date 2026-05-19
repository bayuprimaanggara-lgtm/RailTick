<?php
/**
 * RailTick - Riwayat Pemesanan Tiket
 * Menampilkan daftar transaksi yang pernah dilakukan oleh user
 */
session_start();
include "../config/koneksi.php";

// Proteksi Halaman User
if (!isset($_SESSION['role']) || $_SESSION['role'] != "user") {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['user_id'];

/**
 * Ambil data user terbaru langsung dari database
 * Agar nama di header otomatis berubah setelah edit profil tanpa harus re-login
 */
$query_user_db = mysqli_query($conn, "SELECT nama_lengkap, username, nik FROM users WHERE id_user = '$id_user'");
$user_db = mysqli_fetch_assoc($query_user_db);
$nama_tampil = !empty($user_db['nama_lengkap']) ? $user_db['nama_lengkap'] : $user_db['username'];

/**
 * Query riwayat:
 * - support booking lama (routes.asal / routes.tujuan)
 * - support booking baru antar stasiun (asal_station_id / tujuan_station_id)
 */
$query = mysqli_query($conn, "
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
    JOIN schedules s ON s.id_schedule = b.id_schedule
    LEFT JOIN routes r ON r.id_route = s.id_route
    JOIN users u ON u.id_user = b.id_user
    LEFT JOIN stations sa ON sa.id_station = b.asal_station_id
    LEFT JOIN stations st ON st.id_station = b.tujuan_station_id
    WHERE b.id_user = '$id_user'
    ORDER BY b.created_at DESC
");

if (!$query) {
    die("Query Error: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - RailTick</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Libre+Barcode+128&display=swap');
        body { font-family: 'Poppins', sans-serif; }
        
        @media print {
            body * { visibility: hidden; }
            #ticket-print-area, #ticket-print-area * { visibility: visible; }
            #ticket-print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print { display: none !important; }
        }

        .barcode {
            font-family: 'Libre Barcode 128', cursive;
            font-size: 40px;
        }

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
            <a href="jadwal.php" class="flex items-center gap-3 text-gray-500 hover:bg-gray-50 hover:text-red-500 px-4 py-3 rounded-2xl transition font-medium">
                <i class="fas fa-search"></i> Cari Jadwal
            </a>
            <a href="riwayat.php" class="flex items-center gap-3 bg-red-50 text-red-600 px-4 py-3 rounded-2xl font-bold transition">
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
                <h1 class="text-sm text-gray-400 font-medium tracking-wide uppercase">Layanan Pelanggan / Riwayat Perjalanan</h1>
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
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Riwayat Pemesanan</h2>
                <p class="text-gray-400 text-sm">Daftar seluruh transaksi tiket yang telah Anda lakukan secara digital.</p>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-400 text-[10px] uppercase font-black tracking-[0.2em] border-b border-gray-100">
                            <tr>
                                <th class="px-8 py-5">Kode / Tgl</th>
                                <th class="px-8 py-5">Detail Rute</th>
                                <th class="px-8 py-5 text-center">Tiket / Pembayaran</th>
                                <th class="px-8 py-5">Status</th>
                                <th class="px-8 py-5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php while($data = mysqli_fetch_array($query)): ?>
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-8 py-6">
                                    <span class="block font-black text-blue-600 text-sm">#<?php echo htmlspecialchars($data['kode_booking']); ?></span>
                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">
                                        <?php echo date('d M Y', strtotime($data['tanggal_berangkat'])); ?>
                                    </span>
                                </td>

                                <td class="px-8 py-6">
                                    <div class="text-sm">
                                        <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">
                                            <?php echo htmlspecialchars($data['nama_kereta'] ?? 'Kereta'); ?>
                                        </p>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-bold text-gray-800 uppercase text-xs"><?php echo htmlspecialchars($data['asal']); ?></span>
                                            <i class="fas fa-long-arrow-alt-right text-[10px] text-gray-300"></i>
                                            <span class="font-bold text-gray-800 uppercase text-xs"><?php echo htmlspecialchars($data['tujuan']); ?></span>
                                        </div>
                                        <div class="inline-flex items-center gap-2 text-[10px] font-black text-slate-500 bg-slate-100 px-2 py-1 rounded">
                                            <i class="far fa-clock"></i>
                                            <span><?php echo date('H:i', strtotime($data['jam_berangkat'])); ?> WIB</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-8 py-6 text-center">
    <div class="flex flex-col items-center gap-2">
        <span class="bg-slate-100 text-slate-600 px-3 py-1 rounded-lg text-xs font-black">
            <?php echo (int)$data['jumlah_tiket']; ?> Psg
        </span>
        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">
            Rp <?php echo number_format($data['harga_satuan'], 0, ',', '.'); ?> / tiket
        </div>
        <div class="text-sm font-black text-red-500">
            Total: Rp <?php echo number_format($data['total_harga'], 0, ',', '.'); ?>
        </div>
    </div>
</td>

                                <td class="px-8 py-6">
                                    <?php 
                                        $status = $data['status_booking'];
                                        $color = "bg-yellow-100 text-yellow-600";
                                        if($status == 'berhasil') $color = "bg-green-100 text-green-600 border border-green-200";
                                        if($status == 'dibatalkan') $color = "bg-red-100 text-red-600 border border-red-200";
                                    ?>
                                    <span class="<?php echo $color; ?> px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest shadow-sm">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>

                                <td class="px-8 py-6 text-center">
                                    <?php if($status == 'berhasil'): ?>
                                    <button 
                                        onclick='preparePrint(<?php echo json_encode([
                                            "kode_booking"   => $data["kode_booking"],
                                            "nama_lengkap"   => $data["nama_lengkap"],
                                            "nik"            => $data["nik"],
                                            "nama_kereta"    => $data["nama_kereta"],
                                            "asal"           => $data["asal"],
                                            "tujuan"         => $data["tujuan"],
                                            "jam_berangkat"  => $data["jam_berangkat"],
                                            "jam_tiba"       => $data["jam_tiba"],
                                            "tanggal_berangkat" => $data["tanggal_berangkat"],
                                            "jumlah_tiket"   => $data["jumlah_tiket"],
                                            "harga_satuan"   => $data["harga_satuan"],
                                            "total_harga"    => $data["total_harga"]   
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                                        class="bg-slate-900 hover:bg-red-600 text-white p-2.5 rounded-xl transition shadow-lg shadow-slate-200 flex items-center justify-center mx-auto group">
                                        <i class="fas fa-print group-hover:scale-110 transition-transform"></i>
                                    </button>
                                    <?php else: ?>
                                    <span class="text-gray-300 text-[10px] font-bold italic">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>

                            <?php if(mysqli_num_rows($query) == 0): ?>
                            <tr>
                                <td colspan="5" class="px-8 py-20 text-center">
                                    <div class="opacity-10 mb-4">
                                        <i class="fas fa-receipt text-6xl"></i>
                                    </div>
                                    <p class="text-gray-400 font-medium">Belum ada riwayat transaksi tiket.</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <footer class="p-16 text-center text-gray-400 text-[10px] font-bold uppercase tracking-[0.3em] mt-auto">
            &copy; 2026 RailTick Indonesia. Experience The Best Way To Travel.
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
    <div id="ticket-print-area" class="hidden print:block p-10 bg-white">
        <div class="max-w-4xl mx-auto border-2 border-slate-900 rounded-lg overflow-hidden flex flex-col md:flex-row bg-white">
            <!-- Left Side -->
            <div class="flex-1 p-8 border-r-2 border-dashed border-slate-300 relative">
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 italic uppercase">RailTick</h1>
                        <p class="text-[10px] font-bold text-slate-500 tracking-widest uppercase">Boarding Pass / E-Ticket</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-slate-400">Kode Booking</p>
                        <h2 id="print-kode" class="text-xl font-black text-red-600"></h2>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8 mb-10">
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase font-bold mb-1">Nama Penumpang</p>
                        <p id="print-nama" class="text-sm font-black text-slate-800 uppercase"></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase font-bold mb-1">Identitas (NIK)</p>
                        <p id="print-nik" class="text-sm font-black text-slate-800"></p>
                    </div>
                </div>

                <div class="mb-6">
                    <p class="text-[10px] text-slate-400 uppercase font-bold mb-1">Nama Kereta</p>
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
                            <div class="absolute -top-1 left-1/2 -translate-x-1/2 bg-white px-2 text-[8px] font-bold text-slate-400 uppercase">Executive Class</div>
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

            <!-- Right Side -->
            <div class="w-full md:w-64 bg-slate-900 text-white p-8 flex flex-col items-center justify-center text-center">
                <div class="bg-white p-3 rounded-xl mb-4">
                    <img id="print-qr" src="" alt="QR" class="w-24 h-24">
                </div>
                <p class="text-[8px] font-bold tracking-widest uppercase opacity-60 mb-6">Scan QR saat Boarding</p>
                <div class="barcode leading-none opacity-80 mb-2">1234567890</div>
                <p class="text-[10px] font-black uppercase">PT RailTick Indonesia</p>
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

        function preparePrint(data) {
            document.getElementById('print-kode').innerText = data.kode_booking;
            document.getElementById('print-nama').innerText = data.nama_lengkap;
            document.getElementById('print-nik').innerText = data.nik;
            document.getElementById('print-kereta').innerText = data.nama_kereta || '-';
            document.getElementById('print-asal').innerText = data.asal;
            document.getElementById('print-tujuan').innerText = data.tujuan;
            document.getElementById('print-jam-berangkat').innerText = data.jam_berangkat.substring(0, 5) + ' WIB';
            document.getElementById('print-jam-tiba').innerText = (data.jam_tiba ? data.jam_tiba.substring(0, 5) : '--:--') + ' WIB';
            document.getElementById('print-jumlah').innerText = data.jumlah_tiket + ' Orang';
            document.getElementById('print-harga').innerText =
    'Rp ' + Number(data.harga_satuan).toLocaleString('id-ID');

document.getElementById('print-total').innerText =
    'Rp ' + Number(data.total_harga).toLocaleString('id-ID');

            const date = new Date(data.tanggal_berangkat);
            document.getElementById('print-tanggal').innerText = date.toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            document.getElementById('print-qr').src =
                "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" + encodeURIComponent(data.kode_booking);

            setTimeout(() => { window.print(); }, 500);
        }
    </script>

</body>
</html>