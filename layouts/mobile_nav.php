<?php
$currentPage = basename($_SERVER['PHP_SELF']);

// Daftar Menu
$menus = [
    ['url' => 'dashboard.php', 'icon' => 'fa-solid fa-house-chimney', 'label' => 'Home'],
    ['url' => 'database.php', 'icon' => 'fas fa-database', 'label' => 'Database'],
    ['url' => 'laporan.php', 'icon' => 'fas fa-clipboard-list', 'label' => 'Report'],
    ['url' => 'project.php', 'icon' => 'fas fa-project-diagram', 'label' => 'Projects'],
    ['url' => 'overtime.php', 'icon' => 'fas fa-clock', 'label' => 'Overtime'],
    ['url' => 'temperature.php', 'icon' => 'fas fa-chart-pie', 'label' => 'Monitoring'],
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

<button id="mobileNavToggle" class="fixed bottom-20 right-4 z-[60] md:hidden w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300 focus:outline-none active:scale-95 bg-cyan-600 text-white shadow-lg shadow-cyan-900/50 border border-cyan-500">
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
                    } elseif ($menu['url'] == 'monitoring.php' || $menu['url'] == 'temperature.php') {
                        $isActive = ($currentPage == 'temperature.php' || $currentPage == 'vibration.php' || $currentPage == $menu['url']);
                    } else {
                        $isActive = ($currentPage == $menu['url']);
                    }

                    $isLogout = isset($menu['is_logout']) && $menu['is_logout'];
                    $colorClass = $isActive ? 'text-cyan-400' : 'text-slate-400';
                    $hoverClass = $isLogout ? 'hover:text-red-400' : 'hover:text-cyan-300';
                ?>
                
                <a href="<?php echo $menu['url']; ?>" 
                   <?php echo $isActive ? 'id="activeMobileMenu"' : ''; ?>
                   class="relative flex flex-col items-center justify-center shrink-0 transition-all duration-300 rounded-xl basis-1/6 py-1.5 px-0 <?php echo "$colorClass $hoverClass"; ?>">
                    
                    <?php if ($isActive && !$isLogout): ?>
                        <div class="absolute inset-0 bg-cyan-900/40 border border-cyan-500/20 rounded-xl transition-colors duration-300 animate-expand-pill"></div>
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
                    toggleBtn.className = baseBtnClasses + " bg-cyan-600 text-white shadow-lg shadow-cyan-900/50 border border-cyan-500";
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