<?php
// 1. Panggil Satpam & Koneksi Global
include 'layouts/auth_and_config.php';

// 2. PROTEKSI HALAMAN (HANYA ADMIN)
if ($role_user != 'admin') {
    header("Location: dashboard.php");
    exit();
}

// 3. KONFIGURASI LAYOUT
$pageTitle = "System User Management";

// [SLOT HEADER] Tombol Tambah User
$extraMenu = '
    <button onclick="openModal(\'modalAddUser\')" class="bg-cyan-600 hover:bg-cyan-500 text-white px-4 py-1.5 rounded-full text-sm font-medium transition shadow-lg shadow-cyan-600/20 flex items-center gap-2">
        <i class="fas fa-plus"></i> <span class="hidden sm:inline">Tambah User</span>
    </button>';

// [SLOT HEAD] CSS Khusus Halaman Ini
$extraHead = '
    <style>
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #475569; border-radius: 10px; }
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
';

// 4. FUNGSI HELPER (Time Ago)
function timeAgo($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);
    $timeData = ['y' => $diff->y, 'm' => $diff->m, 'w' => $weeks, 'd' => $days, 'h' => $diff->h, 'i' => $diff->i, 's' => $diff->s];
    $labels = ['y' => 'tahun', 'm' => 'bulan', 'w' => 'minggu', 'd' => 'hari', 'h' => 'jam', 'i' => 'menit', 's' => 'detik'];
    $string = [];
    foreach ($labels as $k => $label) { if ($timeData[$k]) { $string[] = $timeData[$k] . ' ' . $label; } }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' yg lalu' : 'Baru saja';
}
?>

<head>
    <meta name="turbo-cache-control" content="no-preview">
</head>

<!DOCTYPE html>
<html lang="id">

<!-- HEAD ADA DISINI -->
<?php include 'layouts/head.php'; ?>

<body class="bg-slate-900 text-slate-200 font-sans antialiased">

    <div class="flex h-screen overflow-hidden">

        <!-- SIDEBAR ADA DISINI -->
         <?php include 'layouts/sidebar.php'; ?>

        <main class="flex-1 flex flex-col overflow-y-auto relative pb-24" id="main-content">
            
        <!-- HEADER ADA DISINI -->
            <?php include 'layouts/header.php'; ?>

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

    <?php include 'layouts/mobile_nav.php'; ?>
    <?php include 'layouts/scripts.php'; ?>

    <script>
        // 1. FUNGSI EDIT USER (Khusus Manajemen User)
        function editUser(id, username, fullname, shortname, role) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_fullname').value = fullname;
            document.getElementById('edit_shortname').value = shortname;
            document.getElementById('edit_role').value = role;
            openModal('modalEditUser'); // Memanggil fungsi global
        }

        // 2. KONFIRMASI HAPUS (Khusus Manajemen User)
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

        // 3. RESET PASSWORD (Khusus Manajemen User)
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

        // 4. NOTIFIKASI KHUSUS (Optional: Jika ingin teks pesan berbeda dari standar)
        var urlParams = new URLSearchParams(window.location.search);
        var status = urlParams.get('status');
        if (status === 'deleted') {
            Swal.fire({
                icon: 'success',
                title: 'Terhapus!',
                text: 'User telah berhasil dihapus dari sistem.',
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#059669'
            }).then(() => {
                window.history.replaceState(null, null, window.location.pathname);
            });
        }
    </script>

</body>
</html>