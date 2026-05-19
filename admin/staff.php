<?php
session_start();
include "../config/koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";

if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_staff']);
    $nip = mysqli_real_escape_string($conn, $_POST['nip']);
    $jabatan = mysqli_real_escape_string($conn, $_POST['jabatan']);
    $telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $cek = mysqli_query($conn, "SELECT id_staff FROM staff WHERE nip='$nip' LIMIT 1");
    if (mysqli_num_rows($cek) > 0) {
        $message = "NIP petugas sudah terdaftar.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            $id_user = "NULL";
            if ($email !== "" && $username !== "" && $password !== "") {
                $cek_user = mysqli_query($conn, "SELECT id_user FROM users WHERE email='$email' OR username='$username' OR nik='$nip' LIMIT 1");
                if (mysqli_num_rows($cek_user) > 0) {
                    throw new Exception("Email, username, atau NIP akun sudah dipakai.");
                }

                $insert_user = mysqli_query($conn, "
                    INSERT INTO users (nama_lengkap, email, username, password, no_telp, nik, role)
                    VALUES ('$nama', '$email', '$username', '$password', '$telp', '$nip', '$jabatan')
                ");
                if (!$insert_user) {
                    throw new Exception(mysqli_error($conn));
                }
                $id_user = (string) mysqli_insert_id($conn);
            }

            $insert_staff = mysqli_query($conn, "
                INSERT INTO staff (id_user, nama_staff, nip, jabatan, no_telp, status_staff)
                VALUES ($id_user, '$nama', '$nip', '$jabatan', '$telp', 'aktif')
            ");
            if (!$insert_staff) {
                throw new Exception(mysqli_error($conn));
            }

            mysqli_commit($conn);
            header("Location: staff.php?status=added");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = $e->getMessage();
        }
    }
}

if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    mysqli_query($conn, "UPDATE staff SET status_staff = IF(status_staff='aktif','nonaktif','aktif') WHERE id_staff='$id'");
    header("Location: staff.php");
    exit();
}

$staff = mysqli_query($conn, "
    SELECT st.*, u.username
    FROM staff st
    LEFT JOIN users u ON u.id_user = st.id_user
    ORDER BY st.status_staff ASC, st.jabatan ASC, st.nama_staff ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Petugas - RailTick Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap'); body{font-family:'Poppins',sans-serif}</style>
</head>
<body class="bg-gray-50 flex">
<?php include "layout/sidebar.php"; ?>
<main class="flex-1 min-h-screen">
    <header class="h-20 bg-white border-b border-slate-800/10 flex items-center justify-between px-8 sticky top-0 z-30">
        <h1 class="text-xl font-bold text-gray-800">Master Petugas Operasional</h1>
        <div class="text-right hidden sm:block">
            <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'Administrator'); ?></p>
            <p class="text-[10px] text-green-500 font-black uppercase tracking-widest">Admin Online</p>
        </div>
    </header>
    <div class="p-8 grid grid-cols-1 xl:grid-cols-3 gap-8">
        <section class="bg-white p-8 rounded-[2rem] border border-gray-100 shadow-sm h-fit">
            <h2 class="font-bold text-gray-800 mb-2">Tambah Petugas</h2>
            <p class="text-xs text-gray-400 mb-6">Isi akun login kalau petugas perlu masuk ke dashboard tugasnya.</p>
            <?php if ($message): ?>
                <div class="mb-5 bg-red-50 text-red-600 p-4 rounded-xl text-sm font-semibold"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="tambah" value="1">
                <input name="nama_staff" placeholder="Nama petugas" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                <input name="nip" placeholder="NIP / ID petugas" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                <select name="jabatan" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                    <option value="masinis">Masinis</option>
                    <option value="kondektur">Kondektur</option>
                    <option value="pramuniaga">Pramuniaga</option>
                </select>
                <input name="no_telp" placeholder="No. telepon" class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                <div class="pt-4 border-t border-dashed border-gray-100 space-y-4">
                    <input type="email" name="email" placeholder="Email akun login, opsional" class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                    <input name="username" placeholder="Username akun, opsional" class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                    <input type="password" name="password" placeholder="Password akun, opsional" class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                </div>
                <button class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-xl uppercase text-xs tracking-widest">Simpan Petugas</button>
            </form>
        </section>
        <section class="xl:col-span-2 bg-white rounded-[2rem] border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-800">Daftar Petugas</h2>
                <span class="text-[10px] font-black uppercase bg-blue-50 text-blue-600 px-4 py-1.5 rounded-full"><?php echo mysqli_num_rows($staff); ?> data</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-400 text-[10px] uppercase tracking-widest">
                    <tr><th class="px-6 py-4">Nama</th><th class="px-6 py-4">Jabatan</th><th class="px-6 py-4">Akun</th><th class="px-6 py-4">Status</th><th class="px-6 py-4 text-center">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                    <?php while ($row = mysqli_fetch_assoc($staff)): ?>
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-800"><?php echo htmlspecialchars($row['nama_staff']); ?></p>
                                <p class="text-[10px] text-gray-400 font-bold uppercase"><?php echo htmlspecialchars($row['nip']); ?></p>
                            </td>
                            <td class="px-6 py-4 capitalize font-black text-blue-600"><?php echo htmlspecialchars($row['jabatan']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo $row['username'] ? htmlspecialchars($row['username']) : '-'; ?></td>
                            <td class="px-6 py-4"><span class="text-[10px] font-black uppercase px-3 py-1 rounded-full <?php echo $row['status_staff']=='aktif'?'bg-green-50 text-green-600':'bg-gray-100 text-gray-500'; ?>"><?php echo htmlspecialchars($row['status_staff']); ?></span></td>
                            <td class="px-6 py-4 text-center"><a href="?toggle=<?php echo $row['id_staff']; ?>" class="text-xs font-bold text-red-500 hover:underline">Ubah Status</a></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
</body>
</html>
