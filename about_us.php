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
    <title>About Us - RailTick</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }
        
        .about-bg {
            background: linear-gradient(rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.9)), 
                        url('https://images.unsplash.com/photo-1551820059-45097b695191?auto=format&fit=crop&q=80&w=1920');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-card:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-10px);
            border-color: rgba(239, 68, 68, 0.4);
        }

        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navbar -->
    <nav class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center space-x-12">
                    <a href="index.php" class="flex items-center gap-2">
                        <div class="bg-red-500 p-2 rounded-lg text-white">
                            <i class="fas fa-train text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold tracking-tight text-gray-800">RailTick</span>
                    </a>
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="about_us.php" class="text-red-500 font-semibold transition border-b-2 border-red-500">About Us</a>
                        <a href="contact.php" class="text-gray-600 hover:text-red-500 font-semibold transition">Contact Us</a>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <?php if(!$is_logged_in): ?>
                        <a href="auth/login.php" class="text-gray-700 font-medium hover:text-red-500 transition">Masuk</a>
                        <a href="auth/register.php" class="bg-red-500 text-white font-semibold px-6 py-3 rounded-md hover:bg-red-600 transition shadow-lg shadow-red-200">Daftar</a>
                    <?php else: ?>
                        <div class="flex items-center gap-3 bg-gray-100 px-4 py-2 rounded-full">
                            <i class="fas fa-user-circle text-gray-400"></i>
                            <span class="text-sm font-bold text-gray-700"><?php echo $_SESSION['nama']; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Section 1: Template Awal (Glassmorphism Cards) -->
    <section class="about-bg py-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-20">
                <h2 class="text-sm font-bold text-red-500 uppercase tracking-[0.3em] mb-4">Discovery</h2>
                <h3 class="text-4xl md:text-6xl font-bold text-white mb-6">About RailTick</h3>
                <div class="h-1.5 w-24 bg-red-500 mx-auto rounded-full shadow-[0_0_15px_rgba(239,68,68,0.5)]"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="glass-card p-10 rounded-[2rem] text-white group">
                    <h4 class="text-2xl font-bold mb-6 group-hover:text-red-500 transition">Welcome</h4>
                    <p class="text-gray-300 leading-relaxed">
                        RailTick hadir sebagai solusi transportasi kereta api digital nomor satu di Indonesia, mengutamakan kemudahan navigasi dan kecepatan transaksi.
                    </p>
                </div>

                <div class="glass-card p-10 rounded-[2rem] text-white group">
                    <h4 class="text-2xl font-bold mb-6 group-hover:text-red-500 transition">Our Values</h4>
                    <p class="text-gray-300 leading-relaxed">
                        Integritas dan kenyamanan adalah jantung dari RailTick. Kami percaya setiap perjalanan adalah cerita yang berharga bagi setiap penumpang.
                    </p>
                </div>

                <div class="glass-card p-10 rounded-[2rem] text-white group">
                    <h4 class="text-2xl font-bold mb-6 group-hover:text-red-500 transition">What We Do</h4>
                    <p class="text-gray-300 leading-relaxed">
                        Mendigitalisasi sistem perkeretaapian dengan fitur cek jadwal real-time, manajemen kursi, hingga pencetakan e-tiket standar internasional.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 2: Z-Pattern Layout (Pesan Petinggi Perusahaan) -->
    <section class="py-24 bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-32">
            
            <!-- Row 1: CEO (Teks Kiri, Foto Kanan) -->
            <div class="flex flex-col md:flex-row items-center gap-16">
                <div class="md:w-1/2">
                    <div class="inline-block px-4 py-1.5 bg-red-50 text-red-500 rounded-full text-xs font-bold uppercase tracking-widest mb-6">Chief Executive Officer</div>
                    <h2 class="text-4xl font-bold text-gray-800 mb-6 tracking-tight">Visi <span class="text-red-500">Kepemimpinan</span></h2>
                    <p class="text-gray-600 leading-loose text-lg italic border-l-4 border-red-500 pl-6 mb-6">
                        "Membangun RailTick bukan sekadar tentang bisnis aplikasi, melainkan tentang membangun kepercayaan jutaan masyarakat Indonesia untuk bergerak lebih efisien melintasi pulau Jawa hingga Sumatera melalui kemudahan teknologi."
                    </p>
                    <p class="text-gray-500 font-bold">— Raden Ahmad, Founder & CEO</p>
                </div>
                <div class="md:w-1/2">
                    <div class="relative group">
                        <div class="absolute -inset-4 bg-red-500/10 rounded-[3rem] blur-xl group-hover:bg-red-500/20 transition duration-500"></div>
                        <img src="pictures/raden.jpg" alt="CEO RailTick" class="relative rounded-[2.5rem] shadow-2xl object-cover h-[450px] w-full transform group-hover:scale-[1.02] transition duration-500">
                    </div>
                </div>
            </div>

            <!-- Row 2: COO (Foto Kiri, Teks Kanan) -->
            <div class="flex flex-col md:flex-row-reverse items-center gap-16">
                <div class="md:w-1/2">
                    <div class="inline-block px-4 py-1.5 bg-blue-50 text-blue-600 rounded-full text-xs font-bold uppercase tracking-widest mb-6">Chief Operating Officer</div>
                    <h2 class="text-4xl font-bold text-gray-800 mb-6 tracking-tight">Dedikasi <span class="text-blue-600">Operasional</span></h2>
                    <p class="text-gray-600 leading-loose text-lg italic border-l-4 border-blue-600 pl-6 mb-6">
                        "Kami memastikan setiap gerbong, setiap kursi, dan setiap menit jadwal Anda terjaga kualitasnya. Operasional yang mulus adalah komitmen harian kami untuk kepuasan perjalanan Anda tanpa kompromi."
                    </p>
                    <p class="text-gray-500 font-bold">— Azril Bintang, COO</p>
                </div>
                <div class="md:w-1/2">
                    <div class="relative group">
                        <div class="absolute -inset-4 bg-blue-500/10 rounded-[3rem] blur-xl group-hover:bg-blue-500/20 transition duration-500"></div>
                        <img src="pictures/azril.jpg" alt="COO RailTick" class="relative rounded-[2.5rem] shadow-2xl object-cover h-[450px] w-full transform group-hover:scale-[1.02] transition duration-500">
                    </div>
                </div>
            </div>

            <!-- Row 3: CTO (Teks Kiri, Foto Kanan) -->
            <div class="flex flex-col md:flex-row items-center gap-16">
                <div class="md:w-1/2">
                    <div class="inline-block px-4 py-1.5 bg-slate-100 text-slate-600 rounded-full text-xs font-bold uppercase tracking-widest mb-6">Chief Technology Officer</div>
                    <h2 class="text-4xl font-bold text-gray-800 mb-6 tracking-tight">Inovasi <span class="text-slate-600">Digital</span></h2>
                    <p class="text-gray-600 leading-loose text-lg italic border-l-4 border-slate-600 pl-6 mb-6">
                        "Sistem RailTick dirancang dengan arsitektur masa depan. Kami menjamin keamanan enkripsi data Anda dan kecepatan proses booking tiket dalam hitungan detik, bahkan di jam sibuk sekalipun."
                    </p>
                    <p class="text-gray-500 font-bold">— Bayu Prima, CTO</p>
                </div>
                <div class="md:w-1/2">
                    <div class="relative group">
                        <div class="absolute -inset-4 bg-slate-500/10 rounded-[3rem] blur-xl group-hover:bg-slate-500/20 transition duration-500"></div>
                        <img src="pictures/ubay.jpg" alt="CTO RailTick" class="relative rounded-[2.5rem] shadow-2xl object-cover h-[450px] w-full transform group-hover:scale-[1.02] transition duration-500">
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-100 text-gray-400 py-16 border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <div class="flex justify-center items-center gap-2 mb-8">
                <div class="bg-red-500 p-1.5 rounded text-white shadow-md shadow-red-100">
                    <i class="fas fa-train"></i>
                </div>
                <span class="text-gray-800 font-bold text-xl uppercase tracking-widest">RailTick</span>
            </div>
            <p class="text-sm">© 2026 RailTick Indonesia. Perjalanan aman dan nyaman setiap hari.</p>
        </div>
    </footer>

</body>
</html>