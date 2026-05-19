<?php
session_start();
// Mengecek apakah user sudah login untuk menentukan link di Navbar
$is_logged_in = isset($_SESSION['role']);
$dashboard_link = "";

if($is_logged_in) {
    $dashboard_link = ($_SESSION['role'] == 'admin') ? 'admin/index.php' : 'user/dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - RailTick</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
        }

        .contact-bg {
            background: linear-gradient(rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.9)),
                        url('https://images.unsplash.com/photo-1474487548417-781cb71495f3?auto=format&fit=crop&q=80&w=1920');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .glass-card:hover {
            border-color: rgba(239, 68, 68, 0.4);
            transform: scale(1.01);
        }
    </style>
</head>

<body class="bg-gray-50">

    <!-- Navbar -->
    <nav class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center space-x-12">
                    <a href="index.php" class="flex items-center gap-2">
                        <div class="bg-red-500 p-2 rounded-lg text-white shadow-lg shadow-red-200">
                            <i class="fas fa-train text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold tracking-tight text-gray-800">RailTick</span>
                    </a>
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="about_us.php" class="text-gray-600 hover:text-red-500 font-semibold transition">About Us</a>
                        <a href="contact.php" class="text-red-500 font-semibold transition border-b-2 border-red-500">Contact Us</a>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <?php if(!$is_logged_in): ?>
                        <a href="auth/login.php" class="text-gray-700 font-medium hover:text-red-500 transition">Masuk</a>
                        <a href="auth/register.php" class="bg-red-500 text-white font-semibold px-6 py-3 rounded-md hover:bg-red-600 transition shadow-lg shadow-red-200">Daftar</a>
                    <?php else: ?>
                        <div class="flex items-center gap-3 bg-gray-100 px-4 py-2 rounded-full">
                            <i class="fas fa-user-circle text-gray-400 text-lg"></i>
                            <span class="text-sm font-bold text-gray-700"><?php echo htmlspecialchars($_SESSION['nama']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Section -->
    <section class="contact-bg py-24 flex items-center justify-center">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="glass-card rounded-[3rem] p-12 md:p-20 flex flex-col items-center text-center shadow-2xl overflow-hidden relative border-none">

                <div class="relative z-10 w-full max-w-2xl">
                    <h2 class="text-sm font-bold text-red-500 uppercase tracking-[0.3em] mb-4">Get In Touch</h2>
                    <h2 class="text-4xl md:text-5xl font-bold text-white mb-6 uppercase tracking-tight">Butuh Bantuan?</h2>
                    <p class="text-slate-300 mb-12 text-lg leading-relaxed font-light">
                        Tim dukungan pelanggan kami siap membantu Anda 24/7 untuk menjawab pertanyaan seputar pemesanan tiket atau jadwal perjalanan.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 justify-items-center">
                        <a href="https://www.instagram.com/ril.bntangg" target="_blank" class="flex flex-col items-center gap-3 text-white hover:text-red-400 transition group">
                            <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center group-hover:bg-red-500 transition shadow-lg border border-white/10 group-hover:border-red-500">
                                <i class="fab fa-instagram text-2xl"></i>
                            </div>
                            <span class="font-bold text-xs tracking-widest uppercase">@ril.bntangg</span>
                        </a>

                        <a href="https://www.instagram.com/b.yuxd" target="_blank" class="flex flex-col items-center gap-3 text-white hover:text-red-400 transition group">
                            <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center group-hover:bg-red-500 transition shadow-lg border border-white/10 group-hover:border-red-500">
                                <i class="fab fa-instagram text-2xl"></i>
                            </div>
                            <span class="font-bold text-xs tracking-widest uppercase">@b.yuxd</span>
                        </a>

                        <a href="https://www.instagram.com/radnumr_" target="_blank" class="flex flex-col items-center gap-3 text-white hover:text-red-400 transition group">
                            <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center group-hover:bg-red-500 transition shadow-lg border border-white/10 group-hover:border-red-500">
                                <i class="fab fa-instagram text-2xl"></i>
                            </div>
                            <span class="font-bold text-xs tracking-widest uppercase">@radnumr_</span>
                        </a>
                    </div>
                </div>

                <!-- Dekorasi Background Icon -->
                <i class="fas fa-headset absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-[25rem] text-white opacity-[0.02] pointer-events-none"></i>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-100 text-gray-400 py-12 border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <div class="flex justify-center items-center gap-2 mb-4">
                <div class="bg-red-500 p-1.5 rounded text-white shadow-md shadow-red-100">
                    <i class="fas fa-train text-xs"></i>
                </div>
                <span class="text-gray-800 font-bold text-lg uppercase tracking-widest">RailTick</span>
            </div>
            <p class="text-sm">© 2026 RailTick Indonesia. Layanan Pelanggan Digital 24 Jam.</p>
        </div>
    </footer>
</body>
</html>