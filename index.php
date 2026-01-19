<!-- username = admin -->
<!-- password = admin123 -->
<?php
session_start();
// Kalau sudah login, lempar langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="image/gajah_tunggal.png" type="image/png">
    <title>Login - Automation & Management System</title>

    <script src="assets/vendor/tailwind.js"></script>
    <script src="assets/vendor/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/components/button.css">
    <link rel="stylesheet" href="assets/css/main.css">

    <style>
        /* Animasi halus untuk background */
        .animate-blob {
            animation: blob 7s infinite;
        }

        .animation-delay-2000 {
            animation-delay: 2s;
        }

        @keyframes blob {
            0% {
                transform: translate(0px, 0px) scale(1);
            }

            33% {
                transform: translate(30px, -50px) scale(1.1);
            }

            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }

            100% {
                transform: translate(0px, 0px) scale(1);
            }
        }
    </style>

    <link rel="manifest" href="manifest.json">
        
        <meta name="theme-color" content="#03142c">
        <link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/2920/2920249.png">

        <script>
        if ("serviceWorker" in navigator) {
            window.addEventListener("load", function() {
            navigator.serviceWorker
                .register("sw.js")
                .then(res => console.log("Service Worker berhasil didaftarkan!"))
                .catch(err => console.log("Gagal mendaftarkan Service Worker", err));
            });
        }
        </script>
</head>

<body class="bg-slate-950 text-slate-200 font-sans antialiased h-screen flex items-center justify-center relative overflow-hidden">

    <div class="absolute top-0 -left-4 w-72 h-72 bg-emerald-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
    <div class="absolute top-0 -right-4 w-72 h-72 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>

    <div class="relative z-10 w-full max-w-md p-8 bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-2xl">

        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-800 mb-4 border border-slate-700 shadow-lg shadow-emerald-500/10 overflow-hidden p-1.5">
                <img src="image/gajah_tunggal.png" alt="Logo Dept" class="w-full h-full object-cover rounded-full">
            </div>
            <h1 class="text-2xl font-bold text-white tracking-wide">JIS <span class="text-emerald-400">PORTAL.</span></h1>
            <p class="text-sm text-slate-500 mt-1">Automation & Management System</p>
        </div>

        <!-- FORM LOGIN ADA DISINI -->
        <form action="auth.php" method="POST" class="space-y-6" id="loginForm">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">ID Number</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user text-slate-500"></i>
                    </div>
                    <input type="text" name="username" class="block w-full pl-10 pr-3 py-3 border border-slate-700 rounded-lg leading-5 bg-slate-950 text-slate-300 placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 sm:text-sm transition" placeholder="Enter your ID Number">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-slate-500"></i>
                    </div>
                    <input type="password" name="password" class="block w-full pl-10 pr-3 py-3 border border-slate-700 rounded-lg leading-5 bg-slate-950 text-slate-300 placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 sm:text-sm transition" placeholder="••••••••">
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <!-- <input id="remember_me" type="checkbox" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-slate-700 rounded bg-slate-800"> -->
                    <!-- <label for="remember_me" class="ml-2 block text-sm text-slate-400">Ingat Saya</label> -->
                </div>
                <div class="text-sm">
                    <!-- <a href="#" class="font-medium text-emerald-500 hover:text-emerald-400">Lupa Password?</a> -->
                    <!-- <a href="javascript:void(0)" onclick="forgotPassword()" class="font-medium text-emerald-500 hover:text-emerald-400 transition">Forgot Password?</a> -->
                    <a href="reset_password.php" class="font-medium text-emerald-500 hover:text-emerald-400 transition">Forgot Password?</a>
                </div>
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-emerald-500 transition shadow-lg shadow-emerald-600/20">
                    LOGIN<i class="fas fa-arrow-right ml-2 mt-1"></i>
                </button>
            </div>
        </form>

        <!-- FOOTER FORM LOGIN -->
        <div class="mt-6 text-center">
            <p class="text-xs text-slate-600">
                &copy; 2025 JIS Automation Dept. Internal Use Only.
            </p>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {

                const btn = this.querySelector('button[type="submit"]');
                const originalText = btn.innerHTML;

                // Ubah tampilan tombol biar seolah-olah mikir
                btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Verifying...';
                btn.classList.add('opacity-75', 'cursor-not-allowed');
            });
        }
    </script>

    <?php
    if (isset($_GET['error'])) {
        echo "
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Akses Ditolak!',
                text: 'ID (NIP) atau Password yang Anda masukkan salah.',
                background: '#1e293b', 
                color: '#fff', 
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Coba Lagi'
            }).then(() => {
                // Hapus parameter ?error=1 dari URL agar bersih
                window.history.replaceState(null, null, window.location.pathname);
            });
        </script>
        ";
    }
    ?>

    <!-- INSTAL POP UP DISINI -->
<div id="install-popup" class="hidden fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-96 bg-white p-4 rounded-xl shadow-2xl border border-slate-100 z-[9999] flex flex-col gap-4 animate-bounce-in">
    
    <div class="flex items-start gap-4">
        <div class="bg-blue-50 p-2 rounded-lg shrink-0">
            <img src="image/gajah_tunggal_biru.png" alt="App Logo" class="w-10 h-10 object-contain">
        </div>
        <div>
            <h3 class="text-sm font-bold text-slate-800">Install Automation Dashboard App</h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                Pasang aplikasi ini di layar utama agar akses lebih cepat & bisa jalan offline! 🚀
            </p>
        </div>
    </div>

    <div class="flex gap-2">
        <button id="btn-batal" class="flex-1 py-2 px-3 text-xs font-semibold text-slate-500 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
            Nanti Saja
        </button>
        <button id="btn-install-app" class="flex-1 py-2 px-3 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-md shadow-blue-200 transition flex items-center justify-center gap-2">
            <i class="fas fa-download"></i> Install Sekarang
        </button>
    </div>
</div>

<script>
    let deferredPrompt; // Wadah untuk menyimpan event install bawaan browser
    const popup = document.getElementById('install-popup');
    const btnInstall = document.getElementById('btn-install-app');
    const btnBatal = document.getElementById('btn-batal');

    // 1. Dengar Event "beforeinstallprompt"
    // Browser berteriak: "Hei, web ini bisa diinstall lho!"
    window.addEventListener('beforeinstallprompt', (e) => {
        // Tahan dulu, jangan biarkan browser kasih notifikasi default yang kecil
        e.preventDefault();
        
        // Simpan event-nya ke variabel, biar bisa kita panggil nanti saat tombol diklik
        deferredPrompt = e;
        
        // Munculkan Pop-up buatan kita
        popup.classList.remove('hidden');
        popup.classList.add('flex'); // Pakai flex biar rapi
    });

    // 2. Jika Tombol "Install Sekarang" Diklik
    btnInstall.addEventListener('click', async () => {
        if (deferredPrompt) {
            // Panggil prompt asli browser lewat tombol kita
            deferredPrompt.prompt();
            
            // Tunggu user klik "Accept" atau "Cancel"
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`User memilih: ${outcome}`);
            
            // Hapus event karena sudah dipakai (cuma bisa sekali pakai)
            deferredPrompt = null;
            
            // Sembunyikan pop-up
            popup.classList.add('hidden');
        }
    });

    // 3. Jika Tombol "Nanti Saja" Diklik
    btnBatal.addEventListener('click', () => {
        popup.classList.add('hidden');
    });

    // 4. Cek kalau user sudah install, sembunyikan popup (Backup Logic)
    window.addEventListener('appinstalled', () => {
        popup.classList.add('hidden');
        deferredPrompt = null;
        console.log('Aplikasi berhasil diinstall!');
    });
</script>
</body>

</html>
