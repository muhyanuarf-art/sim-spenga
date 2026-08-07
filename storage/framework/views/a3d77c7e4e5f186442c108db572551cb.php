<?php $__env->startSection('title', 'Mata Pelajaran'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6" x-data="{ showForm: false }">
    <div class="flex justify-end">
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Mapel</button>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Mata Pelajaran</p>
        <form method="POST" action="<?php echo e(route('mapel.store')); ?>" class="grid sm:grid-cols-3 gap-3 items-end">
            <?php echo csrf_field(); ?>
            <input type="text" name="kode" placeholder="Kode, contoh: MTK" required class="input">
            <input type="text" name="nama_mapel" placeholder="Nama Mata Pelajaran" required class="input">
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Kode</th><th>Nama Mata Pelajaran</th><th class="th-aksi">Aksi</th></tr></thead>
                <?php $__empty_1 = true; $__currentLoopData = $mapel; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tbody x-data="{ editing: false }">
                    <tr x-show="!editing">
                        <td class="font-semibold"><?php echo e($m->kode); ?></td>
                        <td><?php echo e($m->nama_mapel); ?></td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit">✏️ Edit</button>
                                <form method="POST" action="<?php echo e(route('mapel.destroy', $m)); ?>" onsubmit="return confirm('Hapus mata pelajaran ini?')">
                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <button class="btn-chip btn-chip-delete">🗑️ Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="3" class="bg-brand-50/40">
                            <form method="POST" action="<?php echo e(route('mapel.update', $m)); ?>" class="grid sm:grid-cols-3 gap-3 items-end py-2">
                                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                                <input type="text" name="kode" value="<?php echo e($m->kode); ?>" required class="input">
                                <input type="text" name="nama_mapel" value="<?php echo e($m->nama_mapel); ?>" required class="input">
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
                    <tr><td colspan="3" class="text-center text-slate-400 py-8">Belum ada data.</td></tr>
                </tbody>
                <?php endif; ?>
            </table>
        </div>
        <div class="mt-4"><?php echo e($mapel->links()); ?></div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/mapel/index.blade.php ENDPATH**/ ?>