// ============================================================
// FUNGSI GLOBAL (WAJIB ADA DI LUAR DOMContentLoaded)
// Agar bisa dipanggil oleh tombol onclick="" dan toggleDetail
// ============================================================

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) {
        modal.classList.remove('hidden');
        // Efek animasi masuk
        const content = modal.querySelector('div[class*="transform"]');
        if(content) {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }
    } else {
        console.error("Modal ID tidak ditemukan: " + modalId);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) modal.classList.add('hidden');
}

// Fungsi untuk Expand Table Row (+)
function toggleDetail(rowId) {
    const detailRow = document.getElementById('detail-' + rowId);
    const icon = document.getElementById('icon-' + rowId);
    
    if (detailRow && icon) {
        if (detailRow.classList.contains('hidden')) {
            detailRow.classList.remove('hidden');
            detailRow.classList.add('table-row');
            icon.classList.remove('fa-plus');
            icon.classList.add('fa-minus');
            icon.style.transform = 'rotate(180deg)';
        } else {
            detailRow.classList.add('hidden');
            detailRow.classList.remove('table-row');
            icon.classList.remove('fa-minus');
            icon.classList.add('fa-plus');
            icon.style.transform = 'rotate(0deg)';
        }
    }
}

// ============================================================
// LOGIC UTAMA (Ditaruh di dalam DOMContentLoaded)
// ============================================================
document.addEventListener('DOMContentLoaded', function() {

    // --- 1. MODAL LAPORAN HARIAN (Dashboard & Laporan) ---
    // Menggunakan Class: btn-input-laporan
    const btnsInputLaporan = document.querySelectorAll('.btn-input-laporan');
    btnsInputLaporan.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal('modalLaporan');
        });
    });

    // Tutup Modal Laporan (Tombol X & Batal)
    document.querySelectorAll('.close-modal').forEach(btn => btn.addEventListener('click', () => closeModal('modalLaporan')));
    // Tutup via Backdrop
    const backdropLaporan = document.getElementById('backdropLaporan');
    if (backdropLaporan) {
        backdropLaporan.addEventListener('click', () => closeModal('modalLaporan'));
    }


    // --- 2. MODAL TAMBAH ASSET (Database Page) ---
    const btnAddItem = document.getElementById('btnAddItem');
    if (btnAddItem) { 
        btnAddItem.addEventListener('click', (e) => {
            e.preventDefault();
            openModal('modalAddPart');
        });
    }

    // Tutup Modal Asset
    document.querySelectorAll('.close-modal-add').forEach(btn => btn.addEventListener('click', () => closeModal('modalAddPart')));
    const backdropAddPart = document.getElementById('backdropAddPart');
    if (backdropAddPart) {
        backdropAddPart.addEventListener('click', () => closeModal('modalAddPart'));
    }


    // --- 3. MODAL NEW PROJECT (Projects Page) ---
    const btnProject = document.getElementById('btnNewProject');
    if (btnProject) { 
        btnProject.addEventListener('click', (e) => {
            e.preventDefault();
            openModal('modalProject');
        });
    }

    // Tutup Modal Project
    document.querySelectorAll('.close-modal-proj').forEach(btn => btn.addEventListener('click', () => closeModal('modalProject')));
    const backdropProject = document.getElementById('backdropProject');
    if (backdropProject) {
        backdropProject.addEventListener('click', () => closeModal('modalProject'));
    }


    // --- 4. MODAL AMBIL PART (Dashboard) ---
    // const btnAmbilPart = document.querySelector('.btn-secondary');
    // if (btnAmbilPart) {
    //     btnAmbilPart.addEventListener('click', (e) => {
    //         e.preventDefault();
    //         openModal('modalPart');
    //     });
    // }
    // Tutup Modal Ambil Part
    document.querySelectorAll('.close-modal-part').forEach(btn => btn.addEventListener('click', () => closeModal('modalPart')));
    const backdropPart = document.getElementById('backdropPart');
    if(backdropPart) backdropPart.addEventListener('click', () => closeModal('modalPart'));


    // --- 5. LOGIC UPLOAD FILE NAME DISPLAY ---
    
    // Laporan Harian
    const fileInput = document.getElementById('file_evidence');
    const fileNameDisplay = document.getElementById('file-name-display');
    if (fileInput && fileNameDisplay) {
        fileInput.addEventListener('change', function(e) {
            if (this.files.length > 0) {
                fileNameDisplay.classList.remove('hidden');
                fileNameDisplay.textContent = `📄 ${this.files[0].name}`;
            }
        });
    }

    // Database Asset
    const fileSpec = document.getElementById('file_spec');
    const fileNameSpec = document.getElementById('file-name-spec');
    if (fileSpec && fileNameSpec) {
        fileSpec.addEventListener('change', function(e) {
            if (this.files.length > 0) {
                fileNameSpec.classList.remove('hidden');
                fileNameSpec.textContent = `📄 ${this.files[0].name}`;
            }
        });
    }

    // --- 6. MODAL ADD USER ---
    // Tutup Modal User
    document.querySelectorAll('.close-modal-user').forEach(btn => {
        btn.addEventListener('click', () => closeModal('modalAddUser'));
    });

});

// ==========================================
// LOGIKA DARK/LIGHT MODE
// ==========================================

// 1. Cek LocalStorage saat loading
if (localStorage.getItem('theme') === 'light') {
    document.body.classList.add('light-mode');
    updateIcon(true);
}

// ==========================================
// LOGIKA DARK/LIGHT MODE (MANUAL SWITCH)
// ==========================================

// 1. Cek Memori Browser saat Loading
// Jika user pernah memilih 'light', maka aktifkan mode terang
if (localStorage.getItem('theme') === 'light') {
    document.body.classList.add('light-mode');
    // Tunggu elemen icon ada, lalu ubah jadi bulan
    setTimeout(() => updateIcon(true), 100); 
}

// 2. Fungsi Tombol Switch
function toggleTheme() {
    // Tambah/Hapus kelas 'light-mode' di body
    document.body.classList.toggle('light-mode');
    
    // Cek apakah sekarang jadi terang?
    const isLight = document.body.classList.contains('light-mode');
    
    // Simpan pilihan user ke memori browser (LocalStorage)
    localStorage.setItem('theme', isLight ? 'light' : 'dark');
    
    // Ubah Ikon
    updateIcon(isLight);
}

// 3. Fungsi Ubah Ikon Matahari <-> Bulan
function updateIcon(isLight) {
    const icon = document.getElementById('themeIcon');
    if(icon) {
        if (isLight) {
            // Kalau Terang, ikon jadi Bulan (Moon)
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
            icon.classList.add('text-slate-600'); 
        } else {
            // Kalau Gelap, ikon jadi Matahari (Sun)
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
            icon.classList.remove('text-slate-600');
        }
    }
}