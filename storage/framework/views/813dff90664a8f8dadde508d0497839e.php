<?php $__env->startSection('title', 'Kelola Pengguna'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6" x-data="{ showForm: false }">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Cari nama/NIP..." class="input max-w-xs">
            <select name="role" class="input" onchange="this.form.submit()">
                <option value="">Semua Role</option>
                <option value="admin" <?php echo e(request('role')=='admin'?'selected':''); ?>>Admin</option>
                <option value="kepala_sekolah" <?php echo e(request('role')=='kepala_sekolah'?'selected':''); ?>>Kepala Sekolah</option>
                <option value="kurikulum" <?php echo e(request('role')=='kurikulum'?'selected':''); ?>>Kurikulum</option>
                <option value="guru" <?php echo e(request('role')=='guru'?'selected':''); ?>>Guru</option>
            </select>
            <button class="btn-outline">Cari</button>
        </form>
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Pengguna</button>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Pengguna</p>
        <form method="POST" action="<?php echo e(route('users.store')); ?>" class="grid sm:grid-cols-3 gap-3 items-end">
            <?php echo csrf_field(); ?>
            <input type="text" name="name" placeholder="Nama Lengkap" required class="input">
            <input type="text" name="nip" placeholder="NIP (opsional)" class="input">
            <input type="email" name="email" placeholder="Email" required class="input">
            <input type="password" name="password" placeholder="Password" required class="input">
            <select name="role" required class="input">
                <option value="guru">Guru</option>
                <option value="kurikulum">Kurikulum</option>
                <option value="kepala_sekolah">Kepala Sekolah</option>
                <option value="admin">Admin</option>
            </select>
            <input type="text" name="no_hp" placeholder="No. HP (opsional)" class="input">
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Nama</th><th>NIP</th><th>Email</th><th>Role</th><th>Status</th><th class="th-aksi">Aksi</th></tr></thead>
                <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tbody x-data="{ editing: false }">
                    <tr x-show="!editing">
                        <td class="font-medium"><?php echo e($u->name); ?></td>
                        <td><?php echo e($u->nip ?? '-'); ?></td>
                        <td><?php echo e($u->email); ?></td>
                        <td><span class="badge bg-brand-50 text-brand-700"><?php echo e($u->roleLabel()); ?></span></td>
                        <td>
                            <?php if($u->is_active): ?><span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                            <?php else: ?><span class="badge bg-slate-100 text-slate-500">Nonaktif</span><?php endif; ?>
                        </td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit">✏️ Edit</button>
                                <form method="POST" action="<?php echo e(route('users.destroy', $u)); ?>" onsubmit="return confirm('Hapus pengguna ini?')">
                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <button class="btn-chip btn-chip-delete">🗑️ Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="6" class="bg-brand-50/40">
                            <form method="POST" action="<?php echo e(route('users.update', $u)); ?>" class="grid sm:grid-cols-3 gap-3 items-end py-2">
                                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                                <input type="text" name="name" value="<?php echo e($u->name); ?>" placeholder="Nama Lengkap" required class="input">
                                <input type="text" name="nip" value="<?php echo e($u->nip); ?>" placeholder="NIP (opsional)" class="input">
                                <input type="email" name="email" value="<?php echo e($u->email); ?>" placeholder="Email" required class="input">
                                <input type="password" name="password" placeholder="Kosongkan jika tidak diubah" class="input">
                                <select name="role" required class="input">
                                    <option value="guru" <?php echo e($u->role === 'guru' ? 'selected' : ''); ?>>Guru</option>
                                    <option value="kurikulum" <?php echo e($u->role === 'kurikulum' ? 'selected' : ''); ?>>Kurikulum</option>
                                    <option value="kepala_sekolah" <?php echo e($u->role === 'kepala_sekolah' ? 'selected' : ''); ?>>Kepala Sekolah</option>
                                    <option value="admin" <?php echo e($u->role === 'admin' ? 'selected' : ''); ?>>Admin</option>
                                </select>
                                <input type="text" name="no_hp" value="<?php echo e($u->no_hp); ?>" placeholder="No. HP (opsional)" class="input">
                                <label class="flex items-center gap-1.5 text-xs text-slate-600 font-semibold">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" <?php echo e($u->is_active ? 'checked' : ''); ?> class="rounded">
                                    Aktif
                                </label>
                                <div class="flex gap-2 sm:col-span-3">
                                    <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                                    <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                </tbody>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tbody>
                    <tr><td colspan="6" class="text-center text-slate-400 py-8">Belum ada data.</td></tr>
                </tbody>
                <?php endif; ?>
            </table>
        </div>
        <div class="mt-4"><?php echo e($users->links()); ?></div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/users/index.blade.php ENDPATH**/ ?>