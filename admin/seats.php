<?php
session_start();
include "../config/koneksi.php";

// Proteksi Halaman Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";

// Logika Tambah Kursi
if (isset($_POST['tambah'])) {
    $gerbong     = mysqli_real_escape_string($conn, $_POST['gerbong']);
    $nomor_kursi = mysqli_real_escape_string($conn, $_POST['nomor_kursi']);

    $cek_kursi = mysqli_query($conn, "SELECT * FROM seats WHERE gerbong='$gerbong' AND nomor_kursi='$nomor_kursi'");
    if (mysqli_num_rows($cek_kursi) > 0) {
        $message = "error_duplicate";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO seats (gerbong, nomor_kursi) VALUES ('$gerbong', '$nomor_kursi')");
        if ($insert) {
            $message = "success_tambah";
        } else {
            $message = "error_" . mysqli_error($conn);
        }
    }
}

// Logika Hapus Kursi
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);
    $delete = mysqli_query($conn, "DELETE FROM seats WHERE id_seat='$id'");

    if ($delete) {
        header("Location: seats.php?status=deleted");
        exit();
    }
}

// Ambil Data Kursi
$query_seats = mysqli_query($conn, "SELECT * FROM seats ORDER BY gerbong ASC, nomor_kursi ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kursi - Admin RailTick</title>
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

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #cbd5e1;
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
                <a href="index.php" class="md:hidden text-gray-600"><i class="fas fa-bars text-xl"></i></a>
                <h1 class="text-xl font-bold text-gray-800 tracking-tight">Manajemen Kursi Kereta</h1>
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

        <div class="p-8 flex-1">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

                <div class="lg:col-span-1">
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 sticky top-28">
                        <h2 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
                            <i class="fas fa-plus-circle text-red-500"></i> Tambah Kursi
                        </h2>

                        <?php if ($message == "success_tambah"): ?>
                            <div class="mb-6 p-4 bg-green-50 text-green-700 text-sm rounded-xl flex items-center gap-3 border-l-4 border-green-500 font-medium">
                                <i class="fas fa-check-circle"></i> Kursi berhasil ditambahkan!
                            </div>
                        <?php elseif ($message == "error_duplicate"): ?>
                            <div class="mb-6 p-4 bg-orange-50 text-orange-700 text-sm rounded-xl flex items-center gap-3 border-l-4 border-orange-500 font-medium">
                                <i class="fas fa-exclamation-triangle"></i> Kursi di gerbong ini sudah ada.
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase mb-2 ml-1">Nomor Gerbong</label>
                                <input type="number" name="gerbong" placeholder="Contoh: 1" required
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 outline-none transition font-medium">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase mb-2 ml-1">Nomor Kursi</label>
                                <input type="text" name="nomor_kursi" placeholder="Contoh: A1" required
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 outline-none transition font-medium">
                            </div>
                            <button type="button" onclick="toggleTambahModal(true)"
                                class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-red-200 transition transform active:scale-95 flex items-center justify-center gap-2">
                                <i class="fas fa-save"></i> SIMPAN KURSI
                            </button>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                        <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                            <h3 class="font-bold text-gray-800">Daftar Kursi Aktif</h3>
                            <span class="bg-blue-100 text-blue-600 text-[10px] font-bold px-3 py-1 rounded-full uppercase">
                                <?php echo mysqli_num_rows($query_seats); ?> Kursi
                            </span>
                        </div>

                        <div class="overflow-y-auto h-[calc(100vh-320px)] custom-scrollbar">
                            <table class="w-full text-left border-separate border-spacing-0">
                                <thead class="bg-gray-50 text-gray-400 text-[10px] uppercase tracking-widest font-bold sticky top-0 z-20 shadow-sm">
                                    <tr>
                                        <th class="px-8 py-4 bg-gray-50">ID</th>
                                        <th class="px-8 py-4 bg-gray-50">Gerbong</th>
                                        <th class="px-8 py-4 bg-gray-50 text-center">No. Kursi</th>
                                        <th class="px-8 py-4 bg-gray-50 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <?php while ($d = mysqli_fetch_array($query_seats)): ?>
                                        <tr class="hover:bg-gray-50/30 transition group">
                                            <td class="px-8 py-5 text-sm text-gray-400 font-medium italic">#<?php echo $d['id_seat']; ?></td>
                                            <td class="px-8 py-5">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 bg-slate-100 text-slate-500 rounded-lg flex items-center justify-center text-[10px] font-black">G</div>
                                                    <span class="font-bold text-gray-800">Gerbong <?php echo $d['gerbong']; ?></span>
                                                </div>
                                            </td>
                                            <td class="px-8 py-5 text-center">
                                                <span class="bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg font-black text-xs border border-blue-100">
                                                    <?php echo $d['nomor_kursi']; ?>
                                                </span>
                                            </td>
                                            <td class="px-8 py-5 text-center">
                                                <div class="flex justify-center">
                                                    <a href="javascript:void(0)"
                                                        onclick="openDeleteModal(<?php echo $d['id_seat']; ?>)"
                                                        class="w-10 h-10 flex items-center justify-center bg-red-50 text-red-500 rounded-2xl hover:bg-red-500 hover:text-white transition-all shadow-sm">
                                                        <i class="fas fa-trash-alt text-sm"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <footer class="p-8 text-center text-gray-300 text-[10px] font-bold uppercase tracking-[0.2em] mt-auto">
            &copy; 2026 RailTick Control Center. Seat Management Module.
        </footer>
    </main>
    <div id="tambahModal" class="modal-overlay fixed inset-0 z-[100] hidden items-center justify-center p-4">
        <div class="modal-card bg-white w-full max-w-sm rounded-[2.5rem] shadow-2xl border border-gray-100">
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                    <i class="fas fa-save"></i>
                </div>
                <h3 class="text-xl font-black text-gray-800 mb-2 uppercase">Konfirmasi</h3>
                <p class="text-gray-400 text-sm mb-8">Yakin tambah kursi ini?</p>

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
    <div id="deleteModal" class="modal-overlay fixed inset-0 z-[100] hidden items-center justify-center p-4">
        <div class="modal-card bg-white w-full max-w-sm rounded-[2.5rem] shadow-2xl border border-gray-100">
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <h3 class="text-xl font-black text-gray-800 mb-2 uppercase">Hapus Data</h3>
                <p class="text-gray-400 text-sm mb-8">Yakin hapus kursi ini?</p>

                <div class="grid grid-cols-2 gap-4">
                    <button onclick="toggleDeleteModal(false)" class="bg-gray-100 py-4 rounded-2xl text-xs font-bold">
                        Batal
                    </button>
                    <a id="confirmDeleteBtn"
                        class="bg-red-500 text-white py-4 rounded-2xl text-xs font-bold flex items-center justify-center">
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
            const form = document.querySelector('form');

            // 🔥 FIX SUPAYA PHP KEBACA
            let input = document.createElement("input");
            input.type = "hidden";
            input.name = "tambah";
            input.value = "1";
            form.appendChild(input);

            form.submit();
        }

        function openDeleteModal(id) {
            const modal = document.getElementById('deleteModal');
            const btn = document.getElementById('confirmDeleteBtn');

            btn.href = "?hapus=" + id;

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            setTimeout(() => modal.classList.add('modal-active'), 10);
        }

        function toggleDeleteModal() {
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