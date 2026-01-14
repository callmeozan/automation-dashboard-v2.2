document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const presentationBtn = document.getElementById('presentationModeBtn');
    const presentationBanner = document.getElementById('presentationBanner');
    const operationalPanel = document.getElementById('operational-panel');
    const kpiContainer = document.getElementById('kpi-container');
    
    let isPresentationMode = false;

    if(sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            
            // Cek ukuran layar saat ini
            if (window.innerWidth >= 768) {
                // LOGIC DESKTOP (Laptop/PC)
                // Kita harus toggle class 'md:flex' agar sidebar mau hilang di layar besar
                sidebar.classList.toggle('md:flex');
                sidebar.classList.toggle('hidden');
            } else {
                // LOGIC MOBILE (HP)
                // Cukup toggle class 'hidden' seperti biasa
                sidebar.classList.toggle('hidden');
            }

        });
    }

    if(presentationBtn) {
        presentationBtn.addEventListener('click', () => {
            isPresentationMode = !isPresentationMode;
            
            if (isPresentationMode) {
                sidebar.classList.add('hidden');
                sidebar.classList.remove('flex');
                
                operationalPanel.style.opacity = '0';
                setTimeout(() => operationalPanel.classList.add('hidden'), 300);

                presentationBanner.classList.remove('hidden');

                kpiContainer.classList.remove('md:grid-cols-4');
                kpiContainer.classList.add('md:grid-cols-2', 'lg:grid-cols-4', 'gap-8');
                
                presentationBtn.innerHTML = '<i class="fas fa-times"></i> <span>Exit Presentation</span>';
                presentationBtn.classList.replace('bg-indigo-600', 'bg-red-600');
                presentationBtn.classList.replace('hover:bg-indigo-500', 'hover:bg-red-500');
            } else {
                if (window.innerWidth >= 768) {
                    sidebar.classList.remove('hidden');
                    sidebar.classList.add('flex');
                }

                operationalPanel.classList.remove('hidden');
                setTimeout(() => operationalPanel.style.opacity = '1', 50);

                presentationBanner.classList.add('hidden');

                kpiContainer.classList.add('md:grid-cols-4');
                kpiContainer.classList.remove('md:grid-cols-2', 'lg:grid-cols-4', 'gap-8');

                presentationBtn.innerHTML = '<i class="fas fa-tv"></i> <span>Presentation Mode</span>';
                presentationBtn.classList.replace('bg-red-600', 'bg-indigo-600');
                presentationBtn.classList.replace('hover:bg-red-500', 'hover:bg-indigo-500');
            }
        });
    }
});

// --- FUNGSI TOGGLE SUBMENU DATABASE (SIDEBAR) ---
function toggleDbMenu() {
    const menu = document.getElementById('dbSubmenu');
    const arrow = document.getElementById('arrowDb');
    
    // Cek apakah elemen ada (biar gak error di halaman login)
    if (menu && arrow) {
        if (menu.classList.contains('hidden')) {
            // Buka Menu
            menu.classList.remove('hidden');
            arrow.style.transform = 'rotate(180deg)'; // Putar panah ke atas
        } else {
            // Tutup Menu
            menu.classList.add('hidden');
            arrow.style.transform = 'rotate(0deg)'; // Balikin panah ke bawah
        }
    }
}