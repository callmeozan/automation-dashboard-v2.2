<?php
session_start();
include 'config.php';

$step = 1; // Tahap 1: Verifikasi, Tahap 2: Ganti Password
$error = "";
$success = "";
$nik_verified = "";

// LOGIKA RESET
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // TAHAP 1: CEK KECOCOKAN DATA
    if (isset($_POST['verify_user'])) {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);

        // Cek apakah NIK dan Nama Lengkap cocok?
        $check = mysqli_query($conn, "SELECT * FROM tb_users WHERE username='$username' AND full_name='$fullname'");
        
        if (mysqli_num_rows($check) > 0) {
            $step = 2; // Lanjut ke tahap ganti password
            $nik_verified = $username; // Simpan NIK untuk tahap selanjutnya
        } else {
            $error = "Data tidak ditemukan! Pastikan NIK dan Nama Lengkap sesuai database.";
        }
    }

    // TAHAP 2: SIMPAN PASSWORD BARU
    if (isset($_POST['save_password'])) {
        $username = mysqli_real_escape_string($conn, $_POST['username_hidden']);
        $new_pass = mysqli_real_escape_string($conn, $_POST['new_password']);
        $md5_pass = md5($new_pass); // Enkripsi MD5

        $update = mysqli_query($conn, "UPDATE tb_users SET password='$md5_pass' WHERE username='$username'");
        
        if ($update) {
            $success = "Password berhasil diubah! Silakan login.";
        } else {
            $error = "Gagal mengubah password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Automation Dept</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/vendor/tailwind.js"></script>
    <script src="assets/vendor/sweetalert2.all.min.js"></script>
</head>

<body class="bg-slate-950 text-slate-200 font-sans antialiased h-screen flex items-center justify-center">

    <div class="w-full max-w-md p-8 bg-slate-900/80 border border-slate-800 rounded-2xl shadow-2xl relative">
        
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold text-white">Reset Password</h1>
            <p class="text-xs text-slate-500 mt-1">Verifikasi identitas Anda untuk melanjutkan.</p>
        </div>

        <?php if ($step == 1 && empty($success)): ?>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs text-slate-400 mb-1">NIK (Username)</label>
                <input type="text" name="username" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 outline-none">
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Nama Lengkap (Sesuai Database)</label>
                <input type="text" name="fullname" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 outline-none" placeholder="Contoh: Budi Santoso">
            </div>
            <button type="submit" name="verify_user" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-sm font-medium transition shadow-lg shadow-emerald-600/20">
                Verifikasi Saya
            </button>
            <div class="text-center mt-4">
                <a href="index.php" class="text-xs text-slate-500 hover:text-white">Kembali ke Login</a>
            </div>
        </form>
        <?php endif; ?>

        <?php if ($step == 2 && empty($success)): ?>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="username_hidden" value="<?php echo $nik_verified; ?>">
            
            <div class="bg-emerald-500/10 border border-emerald-500/20 p-3 rounded text-center mb-4">
                <p class="text-xs text-emerald-400"><i class="fas fa-check-circle mr-1"></i> Identitas Terverifikasi!</p>
                <p class="text-sm font-bold text-white mt-1"><?php echo $nik_verified; ?></p>
            </div>

            <div>
                <label class="block text-xs text-slate-400 mb-1">Password Baru</label>
                <input type="password" name="new_password" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 outline-none" placeholder="Minimal 6 karakter">
            </div>
            <button type="submit" name="save_password" class="w-full py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-medium transition shadow-lg shadow-blue-600/20">
                Simpan Password Baru
            </button>
        </form>
        <?php endif; ?>

    </div>

    <script>
        <?php if(!empty($error)): ?>
            Swal.fire({ icon: 'error', title: 'Gagal!', text: '<?php echo $error; ?>', background: '#1e293b', color: '#fff', confirmButtonColor: '#ef4444' });
        <?php endif; ?>

        <?php if(!empty($success)): ?>
            Swal.fire({ 
                icon: 'success', title: 'Berhasil!', text: '<?php echo $success; ?>', 
                background: '#1e293b', color: '#fff', confirmButtonColor: '#059669', confirmButtonText: 'Login Sekarang' 
            }).then(() => { window.location.href = 'index.php'; });
        <?php endif; ?>
    </script>

</body>
</html>