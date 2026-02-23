<script src="assets/js/ui-sidebar.js"></script>
<script src="assets/js/ui-modal.js"></script>

<script>
    // --- 1. FUNGSI GLOBAL (Boleh di luar turbo:load) ---
    function toggleNotif() {
        const dropdown = document.getElementById('notifDropdown');
        if (dropdown) dropdown.classList.toggle('hidden');
    }

    // --- 2. LOGIKA YANG JALAN SETIAP PINDAH HALAMAN ---
    document.addEventListener('turbo:load', function() {
        // Abaikan jika hanya preview halaman bayangan
        if (document.documentElement.hasAttribute("data-turbo-preview")) return;

        // A. Cek Notifikasi SweetAlert
        // Kita pakai variabel lokal saja biar tidak bentrok dengan halaman lain
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const msg = urlParams.get('msg');

        if (status) {
            let icon = 'success';
            let title = 'Berhasil!';
            let btnColor = '#059669';

            if (status === 'error') {
                icon = 'error';
                title = 'Gagal!';
                btnColor = '#ef4444';
            }

            Swal.fire({
                icon: icon,
                title: title,
                text: msg || 'Transaksi berhasil diproses.',
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: btnColor,
                iconColor: icon === 'success' ? '#34d399' : '#f87171'
            }).then(() => {
                // Bersihkan URL tanpa refresh halaman (Sangat penting di Turbo)
                window.history.replaceState(null, null, window.location.pathname);
            });
        }
    });

    // --- 3. JURUS ANTI-DOUBLE CLICK (Hanya dipasang 1x seumur hidup) ---
    if (!window.isClickEventAttached) {
        window.addEventListener('click', function(e) {
            const btn = document.querySelector('button[onclick="toggleNotif()"]');
            const dropdown = document.getElementById('notifDropdown');
            
            if (btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
        // Tandai bahwa listener sudah terpasang
        window.isClickEventAttached = true;
    }
</script>