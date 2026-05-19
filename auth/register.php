<?php
/**
 * RailTick Registration System
 * Pastikan path koneksi benar sesuai struktur folder Anda
 */
include "../config/koneksi.php";

$error = "";
$success = "";

if (isset($_POST['register'])) {
    // Mengamankan input
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm  = mysqli_real_escape_string($conn, $_POST['confirm']);
    $telp     = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $nik      = mysqli_real_escape_string($conn, $_POST['nik']);

    // Validasi dasar
    if ($password != $confirm) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        // Cek apakah email atau username sudah ada
        $cek_user = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' OR username='$username' OR nik='$nik'");
        if (mysqli_num_rows($cek_user) > 0) {
            $error = "Email, Username, atau NIK sudah terdaftar!";
        } else {
            // Proses Simpan (Menggunakan role 'user' secara default)
            // Catatan: Gunakan password_hash() untuk keamanan lebih baik di produksi
            $query = mysqli_query($conn, "
                INSERT INTO users (nama_lengkap, email, username, password, no_telp, nik, role)
                VALUES ('$nama', '$email', '$username', '$password', '$telp', '$nik', 'user')
            ");

            if ($query) {
                $success = "Akun berhasil dibuat! Silakan login.";
            } else {
                $error = "Gagal mendaftar: " . mysqli_error($conn);
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
    <title>Daftar Akun - RailTick Indonesia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }
        
        .register-bg {
                background: linear-gradient(rgba(15, 23, 42, 0.75), rgba(15, 23, 42, 0.75)), 
                url('../pictures/kereta.png');
    background-size: cover;
    background-position: center;
}
    </style>
</head>
<body class="register-bg min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-2xl py-8">
        <!-- Logo & Branding -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3 bg-white/10 backdrop-blur-md px-6 py-3 rounded-2xl border border-white/20 mb-4 cursor-pointer" onclick="window.location.href='../index.php'">
                <div class="bg-red-500 p-2 rounded-lg text-white">
                    <i class="fas fa-train text-xl"></i>
                </div>
                <span class="text-2xl font-bold tracking-tight text-white italic">RailTick</span>
            </div>
            <h2 class="text-white text-lg font-light opacity-80 tracking-wide">Mulai Perjalanan Anda Bersama Kami</h2>
        </div>

        <!-- Register Card -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100">
            <div class="p-8 md:p-12">
                <h3 class="text-2xl font-bold text-gray-800 mb-2 text-center">Buat Akun Baru</h3>
                <p class="text-gray-500 text-center mb-10 text-sm font-medium">Lengkapi data diri Anda sesuai identitas resmi (KTP)</p>

                <!-- Feedback Messages -->
                <?php if ($error != ""): ?>
                    <div class="mb-8 flex items-start gap-3 bg-red-50 border-l-4 border-red-500 p-4 text-red-700 text-sm rounded-r-lg">
                        <i class="fas fa-exclamation-circle text-lg mt-0.5"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success != ""): ?>
                    <div class="mb-8 flex items-start gap-3 bg-green-50 border-l-4 border-green-500 p-4 text-green-700 text-sm rounded-r-lg">
                        <i class="fas fa-check-circle text-lg mt-0.5"></i>
                        <div>
                            <p class="font-bold">Berhasil!</p>
                            <p><?php echo $success; ?> <a href="login.php" class="underline font-bold">Klik di sini untuk Login</a></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nama Lengkap -->
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-400 mb-2 ml-1">Nama Lengkap</label>
                            <div class="relative group">
                                <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-red-500 transition-colors"></i>
                                <input type="text" name="nama" placeholder="Sesuai KTP" required
                                    class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 focus:bg-white outline-none transition-all font-medium text-gray-700">
                            </div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-400 mb-2 ml-1">Alamat Email</label>
                            <div class="relative group">
                                <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-red-500 transition-colors"></i>
                                <input type="email" name="email" placeholder="email@domain.com" required
                                    class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 focus:bg-white outline-none transition-all font-medium text-gray-700">
                            </div>
                        </div>

                        <!-- Username -->
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-400 mb-2 ml-1">Username</label>
                            <div class="relative group">
                                <i class="fas fa-at absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-red-500 transition-colors"></i>
                                <input type="text" name="username" placeholder="Username unik" required
                                    class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 focus:bg-white outline-none transition-all font-medium text-gray-700">
                            </div>
                        </div>

                        <!-- Nomor Telepon -->
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-400 mb-2 ml-1">No. Telepon</label>
                            <div class="relative group">
                                <i class="fas fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-red-500 transition-colors"></i>
                                <input type="tel" name="no_telp" placeholder="08xxxxxxxx" required
                                    class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 focus:bg-white outline-none transition-all font-medium text-gray-700">
                            </div>
                        </div>

                        <!-- NIK -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold uppercase text-gray-400 mb-2 ml-1">Nomor Induk Kependudukan (NIK)</label>
                            <div class="relative group">
                                <i class="fas fa-id-card absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-red-500 transition-colors"></i>
                                <input type="text" name="nik" maxlength="16" placeholder="16 Digit NIK KTP" required
                                    class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 focus:bg-white outline-none transition-all font-medium text-gray-700 tracking-widest">
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-400 mb-2 ml-1">Kata Sandi</label>
                            <div class="relative group">
                                <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-red-500 transition-colors"></i>
                                <input type="password" name="password" placeholder="••••••••" required
                                    class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 focus:bg-white outline-none transition-all font-medium text-gray-700">
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-400 mb-2 ml-1">Verifikasi Sandi</label>
                            <div class="relative group">
                                <i class="fas fa-shield-alt absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-red-500 transition-colors"></i>
                                <input type="password" name="confirm" placeholder="••••••••" required
                                    class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 focus:bg-white outline-none transition-all font-medium text-gray-700">
                            </div>
                        </div>
                    </div>

                    <!-- Register Button -->
                    <button type="submit" name="register" 
                        class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-xl transition-all transform active:scale-95 shadow-lg shadow-red-200 mt-6 flex items-center justify-center gap-2 tracking-widest uppercase">
                        DAFTAR SEKARANG <i class="fas fa-user-plus text-xs"></i>
                    </button>
                </form>
            </div>

            <!-- Footer Card -->
            <div class="bg-gray-50 p-6 text-center border-t border-gray-100">
                <p class="text-gray-500 text-sm font-medium">
                    Sudah memiliki akun RailTick? 
                    <a href="login.php" class="text-red-500 font-bold hover:underline transition">Masuk ke Akun</a>
                </p>
            </div>
        </div>

        <!-- Back to Home -->
        <div class="text-center mt-8">
            <a href="../index.php" class="text-white/60 hover:text-white text-sm transition flex items-center justify-center gap-2 group">
                <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform"></i> Kembali ke Beranda
            </a>
        </div>
    </div>

</body>
</html>