<?php
$currentPage = basename($_SERVER['PHP_SELF']);

// Daftar Menu
$menus = [
    ['url' => 'dashboard.php', 'icon' => 'fa-solid fa-house-chimney', 'label' => 'Home'],
    ['url' => 'database.php', 'icon' => 'fas fa-database', 'label' => 'Database'],
    ['url' => 'laporan.php', 'icon' => 'fas fa-clipboard-list', 'label' => 'Report'],
    ['url' => 'project.php', 'icon' => 'fas fa-project-diagram', 'label' => 'Projects'],
    ['url' => 'overtime.php', 'icon' => 'fas fa-clock', 'label' => 'Overtime'],
];

// Menu Admin
if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    $menus[] = ['url' => 'manage_users.php', 'icon' => 'fas fa-users-cog', 'label' => 'Users'];
}

// Menu Logout
$menus[] = ['url' => 'logout.php', 'icon' => 'fas fa-sign-out-alt', 'label' => 'Logout', 'is_logout' => true];
?>

<style>
  .no-scrollbar::-webkit-scrollbar { display: none !important; }
  .no-scrollbar { 
    -ms-overflow-style: none !important; 
    scrollbar-width: none !important; 
    /* WAJIB: Matikan animasi agar teleportasi menu tidak terlihat geser */
    scroll-behavior: auto !important; 
  }
</style>

<button id="mobileNavToggle" class="fixed bottom-20 right-4 z-[60] md:hidden w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300 focus:outline-none active:scale-95 bg-emerald-600 text-white shadow-lg shadow-emerald-900/50 border border-emerald-500">
    <i class="fas fa-times text-base transition-transform duration-300" id="mobileNavIcon"></i>
</button>

<div id="mobileNavContainer" class="fixed bottom-4 left-4 right-4 z-50 md:hidden transition-transform duration-300 ease-in-out translate-y-0 shadow-xl">
    
    <div class="bg-slate-900/95 backdrop-blur-xl border border-slate-700 rounded-[1.25rem] overflow-hidden">
        
        <div id="navScrollArea" class="flex items-center overflow-x-auto no-scrollbar px-1 py-1.5 gap-0">    

            <?php foreach ($menus as $menu): ?>
                <?php 
                    if ($menu['url'] == 'database.php') {
                        $isActive = ($currentPage == 'database.php' || $currentPage == 'master_items.php');
                    } elseif ($menu['url'] == 'laporan.php') {
                        $isActive = ($currentPage == 'laporan.php' || $currentPage == 'my_laporan.php');
                    } else {
                        $isActive = ($currentPage == $menu['url']);
                    }

                    $isLogout = isset($menu['is_logout']) && $menu['is_logout'];
                    $colorClass = $isActive ? 'text-emerald-400' : 'text-slate-400';
                    $hoverClass = $isLogout ? 'hover:text-red-400' : 'hover:text-emerald-300';
                ?>
                
                <a href="<?php echo $menu['url']; ?>" 
                   <?php echo $isActive ? 'id="activeMobileMenu"' : ''; ?>
                   class="relative flex flex-col items-center justify-center shrink-0 transition-all duration-300 rounded-xl basis-1/6 py-1.5 px-0 <?php echo "$colorClass $hoverClass"; ?>">
                    
                    <?php if ($isActive && !$isLogout): ?>
                        <div class="absolute inset-0 bg-emerald-900/40 border border-emerald-500/20 rounded-xl transition-colors duration-300 animate-expand-pill"></div>
                    <?php endif; ?>

                    <div class="relative z-10 text-[1.1rem] mb-0.5 group-active:scale-90 transition-transform <?php echo $isActive ? 'animate-blub' : ''; ?>">
                        <i class="<?php echo $menu['icon']; ?>"></i>
                    </div>
                    <span class="relative z-10 text-[8px] font-semibold uppercase tracking-wider">
                        <?php echo $menu['label']; ?>
                    </span>
                </a>
            <?php endforeach; ?>
            
        </div>
    </div>
</div>

<!-- <button id="btnNotifTest" class="fixed top-20 right-4 z-[60] w-12 h-12 bg-white rounded-full shadow-lg border border-slate-200 flex items-center justify-center text-emerald-700 active:scale-90 transition-transform">
    <div class="relative">
        <i class="fas fa-bell text-xl"></i>
        <div class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center border-2 border-white">6</div>
    </div>
</button>

<div id="notifBackdrop" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[70] opacity-0 pointer-events-none transition-opacity duration-300"></div>

<div id="notifModal" class="fixed bottom-0 left-0 right-0 z-[80] translate-y-full transition-transform duration-300 ease-in-out md:max-w-md md:mx-auto">
    <div class="flex justify-center pt-3 pb-1 bg-slate-50 rounded-t-[1.5rem]">
        <div class="w-12 h-1.5 bg-slate-300 rounded-full"></div>
    </div>

    <div class="bg-slate-50 h-[85vh] flex flex-col rounded-t-none pb-safe">
        
        <div class="px-5 pt-2 pb-4 flex justify-between items-start bg-slate-50 relative z-10 shadow-sm shrink-0">
            <div class="flex items-center gap-3">
                <div class="relative w-10 h-10 bg-emerald-900 rounded-full flex items-center justify-center text-white">
                    <i class="fas fa-bell"></i>
                    <div class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center border-2 border-slate-50">6</div>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-800">Notifikasi</h2>
                    <p class="text-[10px] text-slate-500">6 notifikasi • 6 belum dibaca</p>
                </div>
            </div>
            <button id="closeNotifBtn" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 text-slate-600 hover:bg-slate-300 active:scale-90 transition-transform">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="px-5 py-3 flex items-center gap-2 overflow-x-auto no-scrollbar bg-slate-50 shrink-0 border-b border-slate-200/60">
            <button class="px-4 py-1.5 bg-emerald-900 text-white text-[11px] font-semibold rounded-full whitespace-nowrap">Semua (6)</button>
            <button class="px-4 py-1.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 text-[11px] font-semibold rounded-full whitespace-nowrap transition">Belum Dibaca (6)</button>
            <button class="px-4 py-1.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 text-[11px] font-semibold rounded-full whitespace-nowrap transition">Sistem (2)</button>
        </div>

        <div class="px-5 py-3 bg-slate-50 shrink-0">
            <button class="text-emerald-700 text-[11px] font-semibold flex items-center gap-1.5 hover:opacity-80 transition">
                <i class="fas fa-check-double"></i> Tandai Semua Dibaca
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-5 py-2 pb-24 space-y-3">
            
            <div class="bg-emerald-900 rounded-2xl p-4 text-white relative shadow-md">
                <button class="absolute top-4 right-4 text-emerald-300/60 hover:text-white transition"><i class="fas fa-times text-xs"></i></button>
                <div class="flex gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-800/50 flex items-center justify-center shrink-0 border border-emerald-700">
                        <i class="fas fa-clock text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-bold text-sm">Approval Overtime</h3>
                            <span class="bg-emerald-700 text-[9px] px-1.5 py-0.5 rounded-md font-semibold text-emerald-100">Penting</span>
                        </div>
                        <p class="text-[11px] text-emerald-100/80 leading-relaxed mb-3">Pengajuan lembur Bapak Firman untuk hari ini belum di-approve. Segera cek detailnya.</p>
                        <div class="flex justify-between items-center text-[10px]">
                            <span class="text-emerald-300">00:34:27 lalu</span>
                            <a href="#" class="font-bold text-white flex items-center gap-1 active:scale-95 transition-transform">Buka Overtime <i class="fas fa-arrow-right text-[8px]"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 relative shadow-sm border border-slate-200">
                <div class="flex gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                        <i class="fas fa-project-diagram text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-1.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                                <h3 class="font-bold text-sm text-slate-800">Project Baru Ditambahkan</h3>
                            </div>
                            <span class="text-[9px] text-slate-400">Hari ini</span>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed mb-2">Project "Maintenance Server Induk" telah ditugaskan kepada Anda oleh Admin.</p>
                        <a href="#" class="font-bold text-emerald-700 text-[10px] flex items-center gap-1 active:scale-95 transition-transform">Lihat Detail <i class="fas fa-arrow-right text-[8px]"></i></a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div> -->

<script>
(function() {
    // 1. GEMBOK: Mencegah listener ganda agar tidak berat
    if (window.isMobileNavInitialized) return;

    document.addEventListener('turbo:load', function() {
        if (document.documentElement.hasAttribute("data-turbo-preview")) return;

        // 2. MATIKAN INGATAN BROWSER: Kunci agar tidak narik ke kanan dulu
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }

        const activeMenu = document.getElementById('activeMobileMenu');
        const scrollArea = document.getElementById('navScrollArea');

        if (activeMenu && scrollArea) {
            // JURUS TELEPORTASI INSTAN
            const teleport = () => {
                scrollArea.style.scrollBehavior = 'auto'; // Matikan animasi
                
                // Jika menu yang aktif adalah salah satu dari 6 menu pertama, 
                // paksa scroll ke paling kiri (Home) agar diam seperti Qareeb
                const menuIndex = Array.from(scrollArea.children).indexOf(activeMenu);
                
                if (menuIndex < 6) {
                    scrollArea.scrollLeft = 0;
                } else {
                    // Jika ke menu Logout (menu ke-7), baru geser ke ujung
                    activeMenu.scrollIntoView({ behavior: 'auto', inline: 'end' });
                }
            };

            // Tembak instan
            teleport();
            // Tembak lagi saat frame render siap untuk memastikan Turbo tidak menimpa
            requestAnimationFrame(teleport);

            // Aktifkan smooth hanya setelah navigasi benar-benar tenang (untuk geser manual)
            setTimeout(() => {
                scrollArea.style.scrollBehavior = 'smooth';
            }, 300);
        }

        // 5. LOGIKA TOGGLE BUKA/TUTUP (Versi Komplit Anti-Double)
        const toggleBtn = document.getElementById('mobileNavToggle');
        const navContainer = document.getElementById('mobileNavContainer');
        const navIcon = document.getElementById('mobileNavIcon'); // <-- Ini yang tadi kelewatan

        if (toggleBtn && navContainer && navIcon) {
            toggleBtn.onclick = function() {
                const isClosed = navContainer.classList.contains('translate-y-[150%]');
                
                // Class dasar tombol yang tidak berubah
                const baseBtnClasses = "fixed bottom-20 right-4 z-[60] md:hidden w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300 focus:outline-none active:scale-95";

                if (isClosed) {
                    // MUNCULKAN NAV
                    navContainer.classList.replace('translate-y-[150%]', 'translate-y-0');
                    
                    // UBAH JADI ICON X & TOMBOL HIJAU SOLID
                    navIcon.className = 'fas fa-times text-base transition-transform duration-300';
                    navIcon.style.transform = 'rotate(0deg)';
                    toggleBtn.className = baseBtnClasses + " bg-emerald-600 text-white shadow-lg shadow-emerald-900/50 border border-emerald-500";
                } else {
                    // SEMBUNYIKAN NAV
                    navContainer.classList.replace('translate-y-0', 'translate-y-[150%]');
                    
                    // UBAH JADI ICON HAMBURGER & TOMBOL KACA (GLASS EFFECT)
                    navIcon.className = 'fas fa-bars text-base transition-transform duration-300';
                    navIcon.style.transform = 'rotate(180deg)';
                    toggleBtn.className = baseBtnClasses + " bg-slate-800/40 backdrop-blur-md text-slate-400 border border-slate-700 shadow-none";
                }
            };
        }

        // --- LOGIKA MODAL NOTIFIKASI (BOTTOM SHEET) ---
        const notifModal = document.getElementById('notifModal');
        const notifBackdrop = document.getElementById('notifBackdrop');
        const closeNotifBtn = document.getElementById('closeNotifBtn');
        const openNotifBtn = document.getElementById('btnNotifTest'); // Tombol test kita

        if (notifModal && notifBackdrop && openNotifBtn) {
            // Gunakan onclick biar anti-latah & anti-double klik
            const toggleNotif = (show) => {
                if (show) {
                    notifBackdrop.classList.remove('opacity-0', 'pointer-events-none');
                    notifModal.classList.remove('translate-y-full');
                    document.body.style.overflow = 'hidden'; // Kunci scroll halaman belakang
                } else {
                    notifBackdrop.classList.add('opacity-0', 'pointer-events-none');
                    notifModal.classList.add('translate-y-full');
                    document.body.style.overflow = ''; // Buka kembali scroll halaman
                }
            };

            openNotifBtn.onclick = () => toggleNotif(true);
            closeNotifBtn.onclick = () => toggleNotif(false);
            notifBackdrop.onclick = () => toggleNotif(false); // Tutup kalau user klik area gelap
        }

        
    });

    window.isMobileNavInitialized = true;
})();
</script>