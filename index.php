<!-- username = admin -->
<!-- password = admin85296 -->
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

        html, body {
            overflow-x: hidden;
            max-width: 100vw;
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

<!-- <body class="bg-slate-950 text-slate-200 font-sans antialiased h-screen flex items-center justify-center relative overflow-hidden"> -->
<body class="bg-slate-950 text-slate-200 font-sans antialiased min-h-screen flex items-center justify-center relative overflow-x-hidden pb-32 md:pb-0 pt-4 md:pt-0">

    <div class="absolute top-0 -left-4 w-72 h-72 bg-cyan-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
    <div class="absolute top-0 -right-4 w-72 h-72 bg-blue-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-indigo-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>

    <div class="relative z-10 w-[88%] mx-auto sm:w-full max-w-md p-6 sm:p-8 bg-slate-900/80 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-2xl">
        <div class="text-center mb-8 flex flex-col items-center">
            
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-slate-950 mb-4 border-2 border-slate-700 shadow-[0_0_20px_rgba(6,182,212,0.2)] overflow-hidden p-3 transition-transform hover:scale-105 cursor-pointer">
                <img src="image/gajah_tunggal.png" alt="Logo Dept" class="w-full h-full object-contain">
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-wider mt-2">JIS <span class="text-cyan-400">PORTAL.</span></h1>
            <p class="text-sm text-slate-400 mt-1 font-medium">Automation & Management System</p>
        </div>

        <form action="auth.php" method="POST" class="space-y-5" id="loginForm">
            <div>
                <label class="block text-[11px] font-bold text-slate-400 mb-1.5 uppercase tracking-widest">ID Number</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none transition-colors group-focus-within:text-cyan-400">
                        <i class="fas fa-user text-slate-500 group-focus-within:text-cyan-400 transition-colors"></i>
                    </div>
                    <input type="text" name="username" class="block w-full pl-11 pr-3 py-3 border border-slate-700 rounded-xl leading-5 bg-slate-950/50 text-slate-200 placeholder-slate-600 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 sm:text-sm transition-all" placeholder="Enter your ID Number" autocomplete="off" required>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-400 mb-1.5 uppercase tracking-widest">Password</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none transition-colors group-focus-within:text-cyan-400">
                        <i class="fas fa-lock text-slate-500 group-focus-within:text-cyan-400 transition-colors"></i>
                    </div>
                    <input type="password" name="password" class="block w-full pl-11 pr-3 py-3 border border-slate-700 rounded-xl leading-5 bg-slate-950/50 text-slate-200 placeholder-slate-600 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 sm:text-sm transition-all" placeholder="••••••••" required>
                </div>
            </div>

            <div class="flex items-center justify-end mt-2">
                <div class="text-sm">
                    <a href="reset_password.php" class="font-semibold text-cyan-500 hover:text-cyan-400 transition-colors">Forgot Password?</a>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl text-sm font-bold text-white bg-cyan-600 hover:bg-cyan-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-cyan-500 transition-all shadow-lg shadow-cyan-600/25 active:scale-[0.98]">
                    LOGIN <i class="fas fa-sign-in-alt ml-2"></i>
                </button>
            </div>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-800 text-center">
            <p class="text-xs text-slate-500 font-medium">
                &copy; <?php echo date('Y'); ?> JIS Automation Dept.<br>Internal Use Only.
            </p>
            
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest bg-slate-800/40 border border-slate-700/50 rounded">
                <i class="fas fa-rocket text-cyan-500"></i>
                <span>V2.0 <span class="text-slate-500 font-medium capitalize">| New Server</span></span>
            </div>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                const btn = this.querySelector('button[type="submit"]');
                // Efek loading saat ditekan
                btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Authenticating...';
                btn.classList.add('opacity-80', 'cursor-not-allowed');
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
                background: '#0f172a', 
                color: '#f8fafc', 
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Coba Lagi',
                customClass: {
                    popup: 'border border-slate-700 rounded-2xl'
                }
            }).then(() => {
                window.history.replaceState(null, null, window.location.pathname);
            });
        </script>
        ";
    }
    ?>

    <div id="install-popup" class="hidden fixed bottom-6 left-0 right-0 mx-auto w-[92%] max-w-sm md:left-auto md:right-6 md:mx-0 md:max-w-none md:w-96 bg-white p-5 rounded-2xl shadow-2xl border border-slate-200 z-[9999] flex-col gap-4 animate-bounce-in">
        <div class="flex items-start gap-4">
            <div class="bg-blue-50 p-2.5 rounded-xl shrink-0">
                <img src="image/gajah_tunggal_biru.png" alt="App Logo" class="w-10 h-10 object-contain">
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-800">Install Automation Dashboard App</h3>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                    Pasang aplikasi ini di layar utama agar akses lebih cepat & bisa jalan offline! 🚀
                </p>
            </div>
        </div>

        <div class="flex gap-3 mt-1">
            <button id="btn-batal" class="flex-1 py-2.5 px-3 text-xs font-bold text-slate-500 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                Nanti Saja
            </button>
            <button id="btn-install-app" class="flex-1 py-2.5 px-3 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-lg shadow-blue-600/30 transition-all flex items-center justify-center gap-2">
                <i class="fas fa-download"></i> Install Sekarang
            </button>
        </div>
    </div>

    <script>
        let deferredPrompt; 
        const popup = document.getElementById('install-popup');
        const btnInstall = document.getElementById('btn-install-app');
        const btnBatal = document.getElementById('btn-batal');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            popup.classList.remove('hidden');
            popup.classList.add('flex'); 
        });

        btnInstall.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                deferredPrompt = null;
                popup.classList.add('hidden');
            }
        });

        btnBatal.addEventListener('click', () => {
            popup.classList.add('hidden');
        });

        window.addEventListener('appinstalled', () => {
            popup.classList.add('hidden');
            deferredPrompt = null;
        });
    </script>
</body>

</html>
