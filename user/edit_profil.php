<?php
session_start();
include "../config/koneksi.php";

// Proteksi Halaman User
if (!isset($_SESSION['role']) || $_SESSION['role'] != "user") {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['user_id'];
$message = "";
$status = "";

// Ambil Data Profil Terbaru dari Database
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id_user = '$id_user'");
$user_data = mysqli_fetch_assoc($query_user);

// Proses Update Profil
if (isset($_POST['update'])) {
    $nama         = mysqli_real_escape_string($conn, $_POST['nama']);
    $username     = mysqli_real_escape_string($conn, $_POST['username']);
    $email        = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telp      = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $nik          = mysqli_real_escape_string($conn, $_POST['nik']);
    
    $old_password = mysqli_real_escape_string($conn, $_POST['old_password']);
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);

    // 1. Validasi apakah username sudah dipakai orang lain
    $cek_username = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username' AND id_user != '$id_user'");
    
    if (mysqli_num_rows($cek_username) > 0) {
        $message = "Username sudah digunakan oleh orang lain!";
        $status = "error";
    } else {
        $update_query = "";
        $error_password = false;

        // 2. Logika Perubahan Password
        if (!empty($new_password)) {
            // Jika ingin ganti password, verifikasi password lama
            if ($old_password === $user_data['password']) {
                $update_query = "UPDATE users SET 
                                nama_lengkap='$nama', 
                                username='$username', 
                                email='$email', 
                                no_telp='$no_telp', 
                                nik='$nik', 
                                password='$new_password' 
                                WHERE id_user='$id_user'";
            } else {
                $message = "Kata sandi lama salah! Perubahan gagal disimpan.";
                $status = "error";
                $error_password = true;
            }
        } else {
            // Jika hanya update profil biasa (tanpa ganti password)
            $update_query = "UPDATE users SET 
                            nama_lengkap='$nama', 
                            username='$username', 
                            email='$email', 
                            no_telp='$no_telp', 
                            nik='$nik' 
                            WHERE id_user='$id_user'";
        }

        // Eksekusi jika tidak ada error pada verifikasi password
        if (!$error_password) {
            if (mysqli_query($conn, $update_query)) {
                $message = "Profil berhasil diperbarui!";
                $status = "success";
                
                // Update Session agar nama di dashboard langsung sinkron
                $_SESSION['nama'] = $username;
                
                header("Refresh: 2; url=edit_profil.php");
            } else {
                $message = "Gagal memperbarui profil: " . mysqli_error($conn);
                $status = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - RailTick</title>
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

    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-100 hidden md:flex flex-col sticky top-0 h-screen shadow-sm">
        <div class="p-8 border-b border-gray-50 flex items-center gap-3">
            <div class="bg-red-500 p-2 rounded-lg text-white shadow-lg shadow-red-200">
                <i class="fas fa-train"></i>
            </div>
            <span class="text-xl font-bold text-gray-800 italic tracking-tight">RailTick</span>
        </div>
        
        <nav class="flex-1 p-6 space-y-3 mt-4">
            <a href="dashboard.php" class="flex items-center gap-3 text-gray-500 hover:bg-gray-50 hover:text-red-500 px-4 py-3 rounded-2xl transition">
                <i class="fas fa-home"></i> Beranda
            </a>
            <a href="jadwal.php" class="flex items-center gap-3 text-gray-500 hover:bg-gray-50 hover:text-red-500 px-4 py-3 rounded-2xl transition">
                <i class="fas fa-search"></i> Cari Jadwal
            </a>
            <a href="riwayat.php" class="flex items-center gap-3 text-gray-500 hover:bg-gray-50 hover:text-red-500 px-4 py-3 rounded-2xl transition">
                <i class="fas fa-history"></i> Riwayat Pesanan
            </a>
            <a href="edit_profil.php" class="flex items-center gap-3 bg-red-50 text-red-600 px-4 py-3 rounded-2xl font-semibold transition">
                <i class="fas fa-user-edit"></i> Profil Saya
            </a>
        </nav>

        <div class="p-6 border-t border-gray-50">
            <!-- Link Logout diganti menjadi pemicu modal -->
            <button onclick="toggleLogoutModal(true)" class="w-full flex items-center gap-3 text-gray-400 hover:text-red-500 px-4 py-3 transition text-sm font-medium outline-none">
                <i class="fas fa-sign-out-alt"></i> Keluar Akun
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1">
        <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 px-8 py-5 border-b border-gray-100 flex justify-between items-center">
            <div class="flex items-center gap-4 text-left">
                <a href="dashboard.php" class="text-gray-400 hover:text-red-500 transition"><i class="fas fa-arrow-left"></i></a>
                <h1 class="text-sm text-gray-400 font-medium tracking-wide uppercase">Layanan Akun / Manajemen Identitas</h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($user_data['nama_lengkap']); ?></p>
                    <p class="text-[10px] text-green-500 font-bold uppercase tracking-widest">Penumpang</p>
                </div>
                <div class="w-10 h-10 bg-red-50 text-red-500 rounded-full flex items-center justify-center border border-red-100 shadow-sm">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </header>

        <div class="p-8 max-w-5xl mx-auto">
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-10 border-b border-gray-50 bg-gray-50/30 flex items-center gap-6">
                    <div class="w-20 h-20 bg-white rounded-3xl shadow-md flex items-center justify-center text-3xl text-red-500 border border-gray-100">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Edit Profil Penumpang</h2>
                        <p class="text-gray-400 text-sm">Kelola data diri dan keamanan akun Anda dalam satu tempat.</p>
                    </div>
                </div>

                <div class="p-10">
                    <?php if ($message != ""): ?>
                        <div class="mb-8 p-4 <?php echo $status == 'success' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'; ?> border rounded-2xl flex items-center gap-3 text-sm font-medium animate-pulse">
                            <i class="fas <?php echo $status == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> text-lg"></i>
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Nama Lengkap -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-1 tracking-widest">Nama Lengkap (Sesuai KTP)</label>
                                <div class="relative">
                                    <i class="fas fa-id-badge absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                                    <input type="text" name="nama" value="<?php echo htmlspecialchars($user_data['nama_lengkap']); ?>" required
                                        class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-red-500 outline-none transition font-medium">
                                </div>
                            </div>

                            <!-- Username -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-1 tracking-widest">Username Akun</label>
                                <div class="relative">
                                    <i class="fas fa-at absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                                    <input type="text" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required
                                        class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-red-500 outline-none transition font-medium">
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-1 tracking-widest">Alamat Email</label>
                                <div class="relative">
                                    <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required
                                        class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-red-500 outline-none transition font-medium">
                                </div>
                            </div>

                            <!-- NIK -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-1 tracking-widest">NIK KTP (16 Digit)</label>
                                <div class="relative">
                                    <i class="fas fa-id-card absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                                    <input type="text" name="nik" value="<?php echo htmlspecialchars($user_data['nik']); ?>" required maxlength="16"
                                        class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-red-500 outline-none transition font-medium tracking-widest">
                                </div>
                            </div>

                            <!-- No Telp -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-1 tracking-widest">Nomor Telepon</label>
                                <div class="relative">
                                    <i class="fas fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                                    <input type="tel" name="no_telp" value="<?php echo htmlspecialchars($user_data['no_telp']); ?>" required
                                        class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-red-500 outline-none transition font-medium">
                                </div>
                            </div>
                        </div>

                        <!-- Keamanan / Password Section -->
                        <div class="pt-8 border-t border-dashed border-gray-100">
                            <h3 class="text-sm font-bold text-gray-800 mb-6 flex items-center gap-2">
                                <i class="fas fa-key text-red-500"></i> Perubahan Kata Sandi
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-red-500 uppercase ml-1 tracking-widest">Kata Sandi Lama</label>
                                    <div class="relative">
                                        <i class="fas fa-unlock absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                                        <input type="password" name="old_password" placeholder="Verifikasi sandi saat ini"
                                            class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-red-500 outline-none transition font-medium">
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase ml-1 tracking-widest">Kata Sandi Baru</label>
                                    <div class="relative">
                                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                                        <input type="password" name="new_password" placeholder="Biarkan kosong jika tidak diganti"
                                            class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-red-500 outline-none transition font-medium">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-6">
                            <button type="submit" name="update" class="w-full bg-slate-900 hover:bg-red-600 text-white font-bold py-5 rounded-2xl shadow-xl shadow-slate-200 transition-all transform active:scale-95 flex items-center justify-center gap-3 uppercase tracking-widest text-sm">
                                KONFIRMASI & SIMPAN PERUBAHAN <i class="fas fa-check-circle"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <footer class="p-12 text-center text-gray-300 text-[10px] font-bold uppercase tracking-[0.3em]">
            RAILTICK PRIVACY SYSTEM &copy; 2026
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