<?php
session_start();
// --- PROTEKSI HALAMAN (HANYA ADMIN) ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}
include 'config.php';

// Notifikasi dari URL (untuk SweetAlert)
$status = isset($_GET['status']) ? $_GET['status'] : '';
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// Fungsi mengubah tanggal jadi "Sekian waktu yang lalu"
function timeAgo($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Hitung minggu secara manual agar tidak error di PHP 8.2
    // Kita pisahkan sisa hari menjadi minggu dan hari
    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);

    // Kita masukkan ke array biasa (bukan ke object diff)
    $timeData = [
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $weeks,
        'd' => $days,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s,
    ];

    $labels = [
        'y' => 'tahun',
        'm' => 'bulan',
        'w' => 'minggu',
        'd' => 'hari',
        'h' => 'jam',
        'i' => 'menit',
        's' => 'detik',
    ];

    $string = [];
    foreach ($labels as $k => $label) {
        if ($timeData[$k]) {
            $string[] = $timeData[$k] . ' ' . $label;
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' yg lalu' : 'Baru saja';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="theme-color" content="#03142c">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Admin Panel</title>

    <link rel="icon" href="image/gajah_tunggal.png" type="image/png">
    <link rel="stylesheet" href="assets/css/layouts/sidebar.css">
    <link rel="stylesheet" href="assets/css/layouts/header.css">
    <link rel="stylesheet" href="assets/css/components/button.css">
    <link rel="stylesheet" href="assets/css/components/card.css">
    <link rel="stylesheet" href="assets/css/components/modal.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/vendor/tailwind.js"></script>
    <script src="assets/vendor/sweetalert2.all.min.js"></script>

    <style>
        .custom-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scroll::-webkit-scrollbar-track {
            background: #1e293b;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 10px;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-slate-900 text-slate-200 font-sans antialiased">

    <div class="flex h-screen overflow-hidden">

        <aside id="sidebar" class="w-64 bg-slate-950 border-r border-slate-800 flex flex-col transition-all duration-300 hidden md:flex">
            <div class="h-16 flex items-center justify-center border-b border-slate-800">
                <h1 class="text-xl font-bold text-white tracking-wide">JIS <span class="text-emerald-400">PORTAL.</span></h1>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span class="font-medium">Dashboard</span>
                </a>

                <div class="relative">
                    <button onclick="toggleDbMenu()" class="nav-item w-full flex justify-between items-center focus:outline-none group">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-database w-6 group-hover:text-emerald-400 transition"></i>
                            <span class="group-hover:text-white transition">Database</span>
                        </div>
                        <i id="arrowDb" class="fas fa-chevron-down text-xs text-slate-500 transition-transform duration-200"></i>
                    </button>

                    <div id="dbSubmenu" class="hidden pl-10 space-y-1 mt-1 bg-slate-900/50 py-2 border-l border-slate-800 ml-3">
                        <a href="database.php" class="block text-sm text-slate-400 hover:text-emerald-400 transition py-1">
                            • Machine / Assets
                        </a>
                        <a href="master_items.php" class="block text-sm text-slate-400 hover:text-emerald-400 transition py-1">
                            • Master Items
                        </a>
                    </div>
                </div>

                <a href="laporan.php" class="nav-item">
                    <i class="fas fa-clipboard-list w-6"></i>
                    <span>Daily Report</span>
                </a>

                <a href="project.php" class="nav-item">
                    <i class="fas fa-project-diagram w-6"></i>
                    <span>Projects</span>
                </a>

                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
                    <a href="javascript:void(0)" onclick="openModal('modalAddUser')" class="nav-item hover:text-emerald-400 transition">
                        <i class="fa-solid fa-user-plus w-6"></i>
                        <span>Add User</span>
                    </a>
                <?php endif; ?>

                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <div class="px-4 py-2 text-xs font-bold text-slate-500 uppercase tracking-wider mt-4">Admin Menu</div>
                        <a href="manage_users.php" class="nav-item active">
                        <i class="fas fa-users-cog w-6"></i> <span class="font-medium">User Management</span>
                    </a>
                <?php endif; ?>

                <a href="logout.php" class="nav-item">
                    <i class="fas fa-solid fa-right-from-bracket w-6"></i>
                    <span>Logout</span>
                </a>
            </nav>

            <div class="p-4 border-t border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-slate-700 border border-slate-500 overflow-hidden flex items-center justify-center">
                        <img src="image/default_profile.png"
                            alt="User Profile"
                            class="w-full h-full object-cover scale-125 transition-transform hover:scale-150">
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">
                            <?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Guest'; ?>
                        </p>
                        <p class="text-xs text-emerald-500">Online</p>
                    </div>
                </div>
            </div>

        </aside>

        <main class="flex-1 flex flex-col overflow-y-auto relative pb-24" id="main-content">
            <header class="h-16 shrink-0 bg-slate-900/80 backdrop-blur-md border-b border-slate-800 sticky top-0 z-10 px-8 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button id="sidebarToggle" class="text-slate-400 hover:text-white mr-4 transition-transform active:scale-95">
                    </button>
                    <h1 class="text-lg font-medium text-white">System User Management</h1>
                </div>
                <button onclick="openModal('modalAddUser')" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-lg shadow-emerald-600/20 flex items-center gap-2">
                    <i class="fas fa-plus"></i> Tambah User
                </button>
            </header>

            <div class="p-8 fade-in">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <?php
                    $qTotal = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_users");
                    $totalUser = mysqli_fetch_assoc($qTotal)['total'];

                    $qAdmin = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_users WHERE role='admin'");
                    $adminCount = mysqli_fetch_assoc($qAdmin)['total'];
                    ?>

                    <div class="bg-slate-800 p-5 rounded-xl border border-slate-700 flex items-center gap-4 shadow-lg">
                        <div class="w-12 h-12 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center text-xl"><i class="fas fa-users"></i></div>
                        <div>
                            <h3 class="text-2xl font-bold text-white"><?php echo $totalUser - 1; ?></h3>
                            <p class="text-xs text-slate-400 uppercase tracking-wider">Total Users</p>
                        </div>
                    </div>

                    <div class="bg-slate-800 p-5 rounded-xl border border-slate-700 flex items-center gap-4 shadow-lg">
                        <div class="w-12 h-12 rounded-full bg-purple-500/20 text-purple-400 flex items-center justify-center text-xl"><i class="fas fa-user-shield"></i></div>
                        <div>
                            <h3 class="text-2xl font-bold text-white"><?php echo $adminCount - 1; ?></h3>
                            <p class="text-xs text-slate-400 uppercase tracking-wider">Administrators</p>
                        </div>
                    </div>

                    <div class="bg-slate-800 p-5 rounded-xl border border-slate-700 flex items-center gap-4 shadow-lg">
                        <div class="w-12 h-12 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <h3 class="text-2xl font-bold text-white">Active</h3>
                            <p class="text-xs text-slate-400 uppercase tracking-wider">System Status</p>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden shadow-2xl">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-400">
                            <thead class="bg-slate-900/50 text-xs uppercase font-semibold text-slate-300 border-b border-slate-700">
                                <tr>
                                    <th class="px-6 py-4">User Profile</th>
                                    <th class="px-6 py-4">Username (NIK)</th>
                                    <th class="px-6 py-4">Role</th>
                                    <th class="px-6 py-4">Last Login</th>
                                    <th class="px-6 py-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50">
                                <?php
                                $q = mysqli_query($conn, "SELECT * FROM tb_users WHERE username != 'admin' ORDER BY role ASC, full_name ASC");
                                while ($row = mysqli_fetch_assoc($q)) {
                                    // Warna Badge Role
                                    $roleBadge = 'bg-slate-700 text-slate-300';
                                    if ($row['role'] == 'admin') $roleBadge = 'bg-purple-500/10 text-purple-400 border border-purple-500/20';
                                    if ($row['role'] == 'section') $roleBadge = 'bg-blue-500/10 text-blue-400 border border-blue-500/20';
                                    if ($row['role'] == 'officer') $roleBadge = 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';

                                    // Avatar
                                    $avatarFile = !empty($row['avatar']) ? $row['avatar'] : 'default_profile.png';
                                    $avatarPath = "image/" . $avatarFile;
                                ?>
                                    <tr class="hover:bg-slate-700/30 transition group">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-slate-700 overflow-hidden border border-slate-600">
                                                    <img src="<?php echo $avatarPath; ?>" alt="Avatar" class="w-full h-full object-cover">
                                                </div>
                                                <div>
                                                    <div class="text-white font-medium group-hover:text-emerald-400 transition"><?php echo $row['full_name']; ?></div>
                                                    <div class="text-xs text-slate-500">Call: <?php echo $row['short_name']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-slate-300">
                                            <?php echo $row['username']; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="<?php echo $roleBadge; ?> px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide">
                                                <?php echo $row['role']; ?>
                                            </span>
                                        </td>
                                        <!--<td class="px-6 py-4">-->
                                        <!--    <span class="<?php echo $roleBadge; ?> px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide">-->
                                        <!--        <?php echo $row['last_login']; ?>-->
                                        <!--    </span>-->
                                        <!--</td>-->
                                        
                                       <td class="px-6 py-4 whitespace-nowrap">
    <?php if ($row['last_login'] == NULL): ?>
        
        <span class="bg-slate-800 border border-slate-700 text-slate-400 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide">
            NEVER
        </span>

    <?php else: ?>
        
        <?php 
            $loginDate = date('Y-m-d', strtotime($row['last_login']));
            $today = date('Y-m-d'); 
            $isToday = ($loginDate == $today);
        ?>

        <div class="flex flex-col">
            <span class="text-sm font-bold <?php echo $isToday ? 'text-emerald-400' : 'text-slate-300'; ?>">
                <?php 
                    if($isToday) {
                        echo "Today, " . date('d M Y', strtotime($row['last_login'])); 
                    } else {
                        echo date('d M Y', strtotime($row['last_login'])); 
                    }
                ?>
            </span>
            
            <span class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <?php echo date('H:i', strtotime($row['last_login'])); ?> WIB
            </span>
        </div>

    <?php endif; ?>
</td>

                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button onclick="editUser('<?php echo $row['user_id']; ?>', '<?php echo $row['username']; ?>', '<?php echo $row['full_name']; ?>', '<?php echo $row['short_name']; ?>', '<?php echo $row['role']; ?>')"
                                                    class="w-8 h-8 rounded bg-slate-700 hover:bg-blue-600 text-slate-300 hover:text-white transition flex items-center justify-center" title="Edit Data">
                                                    <i class="fas fa-pen text-xs"></i>
                                                </button>

                                                <button onclick="resetPassword('<?php echo $row['user_id']; ?>', '<?php echo $row['full_name']; ?>')"
                                                    class="w-8 h-8 rounded bg-slate-700 hover:bg-yellow-500 text-slate-300 hover:text-white transition flex items-center justify-center" title="Reset Password ke 123456">
                                                    <i class="fas fa-key text-xs"></i>
                                                </button>

                                                <?php if ($row['username'] != 'admin' ): // Admin utama tidak boleh dihapus 
                                                ?>
                                                    <button onclick="deleteUser('<?php echo $row['user_id']; ?>', '<?php echo $row['full_name']; ?>')"
                                                        class="w-8 h-8 rounded bg-slate-700 hover:bg-red-600 text-slate-300 hover:text-white transition flex items-center justify-center" title="Hapus User">
                                                        <i class="fas fa-trash text-xs"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL ADD USER -->
    <div id="modalAddUser" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeModal('modalAddUser')"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-md rounded-xl shadow-2xl p-6 relative animate-popup">
                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-user-plus text-emerald-400"></i> Add New User
                    </h3>
                    <button onclick="closeModal('modalAddUser')" class="text-slate-400 hover:text-red-400"><i class="fas fa-times text-xl"></i></button>
                </div>

                <form action="process/process_add_user.php" method="POST" class="space-y-4">
                    <input type="hidden" name="redirect_to" value="../manage_users.php">

                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">Username (NIK)</label>
                        <input type="text" name="username" placeholder="Cth: 23-4567" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">Password</label>
                        <input type="password" name="password" placeholder="******" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">Full Name</label>
                        <input type="text" name="full_name" placeholder="Contoh : Faozan Nur Amanulloh" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">Short Name (Panggilan)</label>
                            <input type="text" name="short_name" placeholder="Contoh : Faozan" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required>
                        <p class="text-[10px] text-slate-500 mt-1">*Digunakan untuk dropdown list team.</p>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">Role / Jabatan</label>
                            <select name="role" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                                <option value="-">--Pilih--</option>
                                <option value="worker">Worker</option>
                                <option value="officer">Officer</option>
                                <option value="section">Section</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-medium shadow-lg mt-2">Simpan User</button>
                </form>
            </div>
        </div>
    </div>

    <div id="modalEditUser" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeModal('modalEditUser')"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-md rounded-xl shadow-2xl p-6 relative animate-popup">
                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-user-edit text-blue-400"></i> Edit User Data
                    </h3>
                    <button onclick="closeModal('modalEditUser')" class="text-slate-400 hover:text-red-400"><i class="fas fa-times text-xl"></i></button>
                </div>

                <form action="process/process_edit_user.php" method="POST" class="space-y-4">
                    <input type="hidden" name="user_id" id="edit_user_id">

                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">Username (NIK) - <span class="text-red-400 text-[10px]">Read Only</span></label>
                        <input type="text" name="username" id="edit_username" class="w-full bg-slate-950 border border-slate-800 text-slate-500 cursor-not-allowed rounded px-3 py-2 text-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">Full Name</label>
                        <input type="text" name="full_name" id="edit_fullname" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">Short Name</label>
                            <input type="text" name="short_name" id="edit_shortname" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">Role</label>
                            <select name="role" id="edit_role" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                                <option value="worker">Worker</option>
                                <option value="officer">Officer</option>
                                <option value="section">Section</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg font-medium shadow-lg mt-2">Update Data</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        // FUNGSI ISI MODAL EDIT
        function editUser(id, username, fullname, shortname, role) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_fullname').value = fullname;
            document.getElementById('edit_shortname').value = shortname;
            document.getElementById('edit_role').value = role;
            openModal('modalEditUser');
        }

        // FUNGSI KONFIRMASI DELETE
        function deleteUser(id, name) {
            Swal.fire({
                title: 'Hapus User?',
                text: "User '" + name + "' akan dihapus permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                background: '#1e293b',
                color: '#fff',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'process/process_delete_user.php?id=' + id;
                }
            })
        }

        // FUNGSI RESET PASSWORD
        function resetPassword(id, name) {
            Swal.fire({
                title: 'Reset Password?',
                text: "Password untuk '" + name + "' akan dikembalikan ke '123456'.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#64748b',
                background: '#1e293b',
                color: '#fff',
                confirmButtonText: 'Ya, Reset!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'process/process_reset_password.php?id=' + id;
                }
            })
        }

        // HANDLING NOTIFIKASI DARI URL
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const msg = urlParams.get('msg');

        if (status) {
            let title = 'Berhasil!';
            let icon = 'success';

            if (status === 'error') {
                title = 'Gagal!';
                icon = 'error';
            }
            if (status === 'deleted') {
                title = 'Terhapus!';
            }

            Swal.fire({
                icon: icon,
                title: title,
                text: msg || 'Proses berhasil.',
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#059669'
            }).then(() => {
                // Bersihkan URL biar bersih
                window.history.replaceState(null, null, window.location.pathname);
            });
        }
    </script>
        <script src="assets/js/ui-sidebar.js"></script>
    <script src="assets/js/ui-modal.js"></script>

</body>

</html>