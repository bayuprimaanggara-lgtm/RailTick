<?php
/**
 * RailTick Login System
 * Pastikan file ini disimpan dengan ekstensi .php
 * Jalankan melalui localhost (XAMPP/Laragon), bukan klik kanan > open file
 */

session_start();

// 1. Koneksi Database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sistem_transportasi_kereta";

// Mencoba menyambung ke database
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek jika koneksi gagal
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$error = "";

// 2. Logika Login
if (isset($_POST['login'])) {
    $identifier = mysqli_real_escape_string($conn, $_POST['identifier']);
    $password   = mysqli_real_escape_string($conn, $_POST['password']);

    // Query untuk cek email atau username
    $sql = "SELECT * FROM users WHERE (email='$identifier' OR username='$identifier') AND password='$password' LIMIT 1";
    $query = mysqli_query($conn, $sql);

    if ($query) {
        if (mysqli_num_rows($query) > 0) {
            $data = mysqli_fetch_assoc($query);

            // Set Session
            $_SESSION['user_id']   = $data['id_user'];
            $_SESSION['role']      = $data['role'];
            $_SESSION['nama']      = $data['username'];
            $_SESSION['full_name'] = $data['nama_lengkap'];

            // Redirect berdasarkan role
            if ($data['role'] == "admin") {
                header("Location: ../admin/index.php");
            } elseif ($data['role'] == "user") {
                header("Location: ../user/dashboard.php");
            } else {
                header("Location: ../staff/dashboard.php");
            }
            exit();
        } else {
            $error = "Email/Username atau Password salah!";
        }
    } else {
        $error = "Kesalahan Query: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RailTick Indonesia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }
       .login-bg {
    background: linear-gradient(rgba(15, 23, 42, 0.75), rgba(15, 23, 42, 0.75)), 
                url('../pictures/kereta.png');
    background-size: cover;
    background-position: center;
}
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3 bg-white/10 backdrop-blur-md px-6 py-3 rounded-2xl border border-white/20 mb-4">
                <div class="bg-red-500 p-2 rounded-lg text-white">
                    <i class="fas fa-train text-xl"></i>
                </div>
                <span class="text-2xl font-bold tracking-tight text-white italic">RailTick</span>
            </div>
            <h2 class="text-white text-lg font-light opacity-80 tracking-wide">Selamat Datang Kembali</h2>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="p-8 md:p-10">
                <h3 class="text-2xl font-bold text-gray-800 mb-2 text-center">Login Akun</h3>
                <p class="text-gray-500 text-center mb-8 text-sm">Masukkan kredensial Anda untuk akses tiket</p>

                <?php if ($error != ""): ?>
                    <div class="mb-6 flex items-start gap-3 bg-red-50 border-l-4 border-red-500 p-4 text-red-700 text-sm rounded-r-lg">
                        <i class="fas fa-exclamation-circle text-lg"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="space-y-5">
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-400 mb-2 ml-1">Email atau Username</label>
                            <div class="relative">
                                <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="identifier" placeholder="Username atau Email" required
                                    class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 outline-none transition-all font-medium">
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between mb-2 ml-1">
                                <label class="block text-xs font-bold uppercase text-gray-400">Kata Sandi</label>
                                <a href="#" class="text-xs font-bold text-red-500 hover:underline">Lupa Sandi?</a>
                            </div>
                            <div class="relative">
                                <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="password" name="password" placeholder="••••••••" required
                                    class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 outline-none transition-all font-medium">
                            </div>
                        </div>

                        <button type="submit" name="login" 
                            class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-red-200 mt-4">
                            MASUK SEKARANG
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-gray-50 p-6 text-center border-t border-gray-100 font-medium">
                <p class="text-gray-500 text-sm">
                    Belum punya akun? <a href="register.php" class="text-red-500 font-bold hover:underline">Daftar Gratis</a>
                </p>
            </div>
        </div>

        <div class="text-center mt-8 text-white/60 hover:text-white transition cursor-pointer">
            <a href="../index.php" class="text-sm italic"><i class="fas fa-home"></i> Kembali ke Beranda</a>
        </div>
    </div>

</body>
</html>
