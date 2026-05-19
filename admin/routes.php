<?php
session_start();
include "../config/koneksi.php";

// Proteksi Halaman Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";

// Ambil Data Stasiun untuk Dropdown
$stations = [];
$q_stations = mysqli_query($conn, "SELECT id_station, nama_station FROM stations WHERE status_station = 'aktif' ORDER BY nama_station ASC");
while ($s = mysqli_fetch_assoc($q_stations)) {
    $stations[] = $s;
}

// Logika Tambah Rute (Sekarang menggunakan ID Stasiun)
if (isset($_POST['tambah'])) {
    $asal_id   = mysqli_real_escape_string($conn, $_POST['asal_id']);
    $tujuan_id = mysqli_real_escape_string($conn, $_POST['tujuan_id']);
    $harga     = mysqli_real_escape_string($conn, $_POST['harga']);

    if ($asal_id == $tujuan_id) {
        $message = "error_sama";
    } else {
        // Ambil nama stasiun berdasarkan ID untuk dimasukkan ke tabel routes
        $res_asal = mysqli_query($conn, "SELECT nama_station FROM stations WHERE id_station = '$asal_id'");
        $nama_asal = mysqli_fetch_assoc($res_asal)['nama_station'];

        $res_tujuan = mysqli_query($conn, "SELECT nama_station FROM stations WHERE id_station = '$tujuan_id'");
        $nama_tujuan = mysqli_fetch_assoc($res_tujuan)['nama_station'];

        $insert = mysqli_query($conn, "INSERT INTO routes (asal, tujuan, harga, status_route) VALUES ('$nama_asal', '$nama_tujuan', '$harga', 'aktif')");

        if ($insert) {
            $message = "success_tambah";
        } else {
            $message = "error_" . mysqli_error($conn);
        }
    }
}

// Logika Hapus Rute
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);
    // Opsional: Daripada dihapus permanen, kamu bisa ubah jadi UPDATE routes SET status_route = 'nonaktif'
    $delete = mysqli_query($conn, "DELETE FROM routes WHERE id_route='$id'");

    if ($delete) {
        header("Location: routes.php?status=deleted");
        exit();
    }
}

// PERBAIKAN: Ambil Data Rute untuk Tabel (Hanya menampilkan semua yang AKTIF)
$query_routes = mysqli_query($conn, "SELECT * FROM routes WHERE status_route = 'aktif' ORDER BY id_route DESC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Rute - Admin RailTick</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
        }

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

        .modal-overlay {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
        }

        .modal-card {
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .modal-active .modal-card {
            transform: scale(1);
            opacity: 1;
        }
    </style>
</head>

<body class="bg-gray-50 flex">

    <?php include "layout/sidebar.php"; ?>

    <main class="flex-1 flex flex-col min-h-screen">
        <header class="h-20 bg-white border-b border-slate-800/10 flex items-center justify-between px-8 sticky top-0 z-30">
            <div class="flex items-center gap-4">
                <h1 class="text-xl font-bold text-gray-800 tracking-tight">Manajemen Rute Perjalanan</h1>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'Admin'); ?></p>
                    <p class="text-[10px] text-green-500 font-bold uppercase tracking-widest">Administrator Online</p>
                </div>
                <div class="w-10 h-10 bg-red-50 text-red-500 rounded-full flex items-center justify-center border border-red-100 shadow-sm">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
        </header>

        <div class="p-8 flex-1">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

                <div class="lg:col-span-1">
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 sticky top-28">
                        <h2 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
                            <i class="fas fa-plus-circle text-red-500"></i> Tambah Rute Baru
                        </h2>

                        <?php if ($message == "success_tambah"): ?>
                            <div class="mb-6 p-4 bg-green-50 text-green-700 text-sm rounded-xl flex items-center gap-3 border-l-4 border-green-500">
                                <i class="fas fa-check-circle"></i> Rute berhasil disinkronkan!
                            </div>
                        <?php elseif ($message == "error_sama"): ?>
                            <div class="mb-6 p-4 bg-yellow-50 text-yellow-700 text-sm rounded-xl flex items-center gap-3 border-l-4 border-yellow-500">
                                <i class="fas fa-exclamation-triangle"></i> Asal & Tujuan tidak boleh sama!
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-5" id="formTambah">
                            <input type="hidden" name="tambah" value="1">
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase mb-2 ml-1">Stasiun Asal</label>
                                <select name="asal_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 outline-none transition font-medium text-gray-700" required>
                                    <option value="">Pilih Stasiun Asal</option>
                                    <?php foreach ($stations as $st): ?>
                                        <option value="<?= $st['id_station'] ?>"><?= htmlspecialchars($st['nama_station']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase mb-2 ml-1">Stasiun Tujuan</label>
                                <select name="tujuan_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 outline-none transition font-medium text-gray-700" required>
                                    <option value="">Pilih Stasiun Tujuan</option>
                                    <?php foreach ($stations as $st): ?>
                                        <option value="<?= $st['id_station'] ?>"><?= htmlspecialchars($st['nama_station']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase mb-2 ml-1">Harga Tiket (Rp)</label>
                                <input type="number" name="harga" placeholder="Contoh: 150000" required
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 outline-none transition font-medium">
                            </div>
                            <button type="button" onclick="toggleTambahModal(true)"
                                class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-red-200 transition transform active:scale-95 uppercase tracking-widest text-xs">
                                SIMPAN RUTE RELASIONAL
                            </button>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                        <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                            <h3 class="font-bold text-gray-800">Daftar Rute Aktif</h3>
                            <span class="bg-blue-100 text-blue-600 text-[10px] font-black px-4 py-1.5 rounded-full uppercase tracking-widest">
                                <?php echo mysqli_num_rows($query_routes); ?> Rute Terdaftar
                            </span>
                        </div>

                        <div class="overflow-y-auto h-[calc(100vh-320px)] custom-scrollbar">
                            <table class="w-full text-left border-separate border-spacing-0">
                                <thead class="bg-gray-50 text-gray-400 text-[10px] uppercase tracking-widest font-bold sticky top-0 z-20 shadow-sm">
                                    <tr>
                                        <th class="px-8 py-5 bg-gray-50">ID</th>
                                        <th class="px-8 py-5 bg-gray-50">Rute Perjalanan</th>
                                        <th class="px-8 py-5 bg-gray-50 text-right">Harga</th>
                                        <th class="px-8 py-5 bg-gray-50 text-center">Opsi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <?php while ($r = mysqli_fetch_assoc($query_routes)) { ?>
                                        <tr class="hover:bg-gray-50/30 transition group">
                                            <td class="px-8 py-6 text-sm text-gray-400 font-medium italic">#<?php echo $r['id_route']; ?></td>
                                            <td class="px-8 py-6">
                                                <div class="flex items-center gap-3">
                                                    <span class="font-bold text-slate-800 uppercase text-xs"><?php echo htmlspecialchars($r['asal']); ?></span>
                                                    <i class="fas fa-arrow-right text-[10px] text-red-300"></i>
                                                    <span class="font-bold text-slate-800 uppercase text-xs"><?php echo htmlspecialchars($r['tujuan']); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-8 py-6 text-right font-black text-blue-600 text-sm">
                                                Rp <?php echo number_format($r['harga'], 0, ',', '.'); ?>
                                            </td>
                                            <td class="px-8 py-6">
                                                <div class="flex justify-center">
                                                    <a href="javascript:void(0)"
                                                        onclick="openDeleteModal(<?php echo $r['id_route']; ?>)"
                                                        class="w-10 h-10 flex items-center justify-center bg-red-50 text-red-500 rounded-2xl hover:bg-red-500 hover:text-white transition-all shadow-sm group-hover:scale-110">
                                                        <i class="fas fa-trash-alt text-xs"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <?php if (mysqli_num_rows($query_routes) == 0): ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-24 text-center">
                                                <div class="flex flex-col items-center opacity-20">
                                                    <i class="fas fa-route text-6xl mb-4"></i>
                                                    <p class="text-sm italic font-bold">Belum ada rute operasional.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <footer class="p-8 text-center text-gray-300 text-[10px] font-bold uppercase tracking-[0.2em] mt-auto">
            &copy; 2026 RailTick Control Center. Build 1.0.8-relational
        </footer>
    </main>
    <!-- MODAL TAMBAH -->
    <div id="tambahModal" class="modal-overlay fixed inset-0 z-[100] hidden items-center justify-center p-4">
        <div class="modal-card bg-white w-full max-w-sm rounded-[2.5rem] shadow-2xl border border-gray-100">
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                    <i class="fas fa-save"></i>
                </div>
                <h3 class="text-xl font-black text-gray-800 mb-2 uppercase">Konfirmasi</h3>
                <p class="text-gray-400 text-sm mb-8">Yakin tambah rute ini?</p>

                <div class="grid grid-cols-2 gap-4">
                    <button onclick="toggleTambahModal(false)" class="bg-gray-100 py-4 rounded-2xl text-xs font-bold">
                        Batal
                    </button>
                    <button onclick="submitForm()" class="bg-red-500 text-white py-4 rounded-2xl text-xs font-bold">
                        Iya
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DELETE -->
    <div id="deleteModal" class="modal-overlay fixed inset-0 z-[100] hidden items-center justify-center p-4">
        <div class="modal-card bg-white w-full max-w-sm rounded-[2.5rem] shadow-2xl border border-gray-100">
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <h3 class="text-xl font-black text-gray-800 mb-2 uppercase">Hapus Data</h3>
                <p class="text-gray-400 text-sm mb-8">Yakin hapus rute ini?</p>

                <div class="grid grid-cols-2 gap-4">
                    <button onclick="toggleDeleteModal(false)" class="bg-gray-100 py-4 rounded-2xl text-xs font-bold">
                        Batal
                    </button>
                    <a id="confirmDeleteBtn" class="bg-red-500 text-white py-4 rounded-2xl text-xs font-bold flex items-center justify-center">
                        Iya
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleTambahModal(show) {
            const modal = document.getElementById('tambahModal');

            if (show) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                setTimeout(() => modal.classList.add('modal-active'), 10);
            } else {
                modal.classList.remove('modal-active');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }, 300);
            }
        }

        function submitForm() {
            document.querySelector('#formTambah').submit();
        }

        function openDeleteModal(id) {
            const modal = document.getElementById('deleteModal');
            const btn = document.getElementById('confirmDeleteBtn');

            btn.href = "?hapus=" + id;

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            setTimeout(() => modal.classList.add('modal-active'), 10);
        }

        function toggleDeleteModal(show) {
            const modal = document.getElementById('deleteModal');

            modal.classList.remove('modal-active');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }

        window.onclick = function(e) {
            const tambah = document.getElementById('tambahModal');
            const hapus = document.getElementById('deleteModal');

            if (e.target === tambah) toggleTambahModal(false);
            if (e.target === hapus) toggleDeleteModal(false);
        }
    </script>
</body>

</html>