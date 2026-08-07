<?php $__env->startSection('title', 'Data Kelas'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6" x-data="{ showForm: false }">
    <div class="flex justify-end">
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Kelas</button>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Kelas</p>
        <form method="POST" action="<?php echo e(route('kelas.store')); ?>" class="grid sm:grid-cols-4 gap-3 items-end">
            <?php echo csrf_field(); ?>
            <input type="text" name="nama_kelas" placeholder="Contoh: 7A" required class="input">
            <select name="tingkat" required class="input">
                <option value="7">Tingkat 7</option>
                <option value="8">Tingkat 8</option>
                <option value="9">Tingkat 9</option>
            </select>
            <select name="wali_kelas_id" class="input">
                <option value="">Wali Kelas (opsional)</option>
                <?php $__currentLoopData = $guruList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($g->id); ?>"><?php echo e($g->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Kelas</th><th>Tingkat</th><th>Wali Kelas</th><th>Jumlah Siswa</th><th class="th-aksi">Aksi</th></tr></thead>
                <?php $__empty_1 = true; $__currentLoopData = $kelas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tbody x-data="{ editing: false }">
                    <tr x-show="!editing">
                        <td class="font-semibold"><?php echo e($k->nama_kelas); ?></td>
                        <td><?php echo e($k->tingkat); ?></td>
                        <td><?php echo e($k->waliKelas->name ?? '-'); ?></td>
                        <td><?php echo e($k->siswas_count); ?></td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit">✏️ Edit</button>
                                <form method="POST" action="<?php echo e(route('kelas.destroy', $k)); ?>" onsubmit="return confirm('Hapus kelas ini?')">
                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <button class="btn-chip btn-chip-delete">🗑️ Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="5" class="bg-brand-50/40">
                            <form method="POST" action="<?php echo e(route('kelas.update', $k)); ?>" class="grid sm:grid-cols-5 gap-3 items-end py-2">
                                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                                <input type="text" name="nama_kelas" value="<?php echo e($k->nama_kelas); ?>" required class="input">
                                <select name="tingkat" required class="input">
                                    <?php $__currentLoopData = [7,8,9]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($t); ?>" <?php echo e($k->tingkat == $t ? 'selected' : ''); ?>>Tingkat <?php echo e($t); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <select name="wali_kelas_id" class="input">
                                    <option value="">Wali Kelas (opsional)</option>
                                    <?php $__currentLoopData = $guruList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($g->id); ?>" <?php echo e($k->wali_kelas_id == $g->id ? 'selected' : ''); ?>><?php echo e($g->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                                <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                            </form>
                        </td>
                    </tr>
                </tbody>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tbody>
                    <tr><td colspan="5" class="text-center text-slate-400 py-8">Belum ada data kelas.</td></tr>
                </tbody>
                <?php endif; ?>
            </table>
        </div>
        <div class="mt-4"><?php echo e($kelas->links()); ?></div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/kelas/index.blade.php ENDPATH**/ ?>