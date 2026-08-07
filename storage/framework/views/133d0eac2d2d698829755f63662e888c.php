<?php $__env->startSection('title', 'Tahun Ajaran'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6" x-data="{ showForm: false }">
    <div class="flex justify-end">
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Tahun Ajaran</button>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Tahun Ajaran</p>
        <form method="POST" action="<?php echo e(route('tahun-ajaran.store')); ?>" class="grid sm:grid-cols-3 gap-3 items-end">
            <?php echo csrf_field(); ?>
            <input type="text" name="nama" placeholder="Contoh: 2026/2027" required class="input">
            <select name="semester" required class="input">
                <option value="Ganjil">Ganjil</option>
                <option value="Genap">Genap</option>
            </select>
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Tahun Ajaran</th><th>Semester</th><th>Status</th><th class="th-aksi">Aksi</th></tr></thead>
                <?php $__empty_1 = true; $__currentLoopData = $tahunAjaran; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tbody x-data="{ editing: false }">
                    <tr x-show="!editing">
                        <td class="font-semibold"><?php echo e($t->nama); ?></td>
                        <td><?php echo e($t->semester); ?></td>
                        <td>
                            <?php if($t->is_active): ?><span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                            <?php else: ?><span class="badge bg-slate-100 text-slate-500">Nonaktif</span><?php endif; ?>
                        </td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit">✏️ Edit</button>
                                <?php if (! ($t->is_active)): ?>
                                <form method="POST" action="<?php echo e(route('tahun-ajaran.aktifkan', $t)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <button class="btn-chip btn-chip-success">✅ Aktifkan</button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" action="<?php echo e(route('tahun-ajaran.destroy', $t)); ?>" onsubmit="return confirm('Hapus tahun ajaran ini?')">
                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <button class="btn-chip btn-chip-delete">🗑️ Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="4" class="bg-brand-50/40">
                            <form method="POST" action="<?php echo e(route('tahun-ajaran.update', $t)); ?>" class="grid sm:grid-cols-3 gap-3 items-end py-2">
                                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                                <input type="text" name="nama" value="<?php echo e($t->nama); ?>" required class="input">
                                <select name="semester" required class="input">
                                    <option value="Ganjil" <?php echo e($t->semester === 'Ganjil' ? 'selected' : ''); ?>>Ganjil</option>
                                    <option value="Genap" <?php echo e($t->semester === 'Genap' ? 'selected' : ''); ?>>Genap</option>
                                </select>
                                <div class="flex gap-2">
                                    <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                                    <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                </tbody>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tbody>
                    <tr><td colspan="4" class="text-center text-slate-400 py-8">Belum ada data.</td></tr>
                </tbody>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/tahun-ajaran/index.blade.php ENDPATH**/ ?>