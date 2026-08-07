<?php $__env->startSection('title', 'Data Siswa'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6" x-data="{ showForm: false }">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Cari nama/NIS..." class="input max-w-xs">
            <select name="kelas_id" class="input max-w-[160px]" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                <?php $__currentLoopData = $kelasList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k->id); ?>" <?php echo e(request('kelas_id') == $k->id ? 'selected' : ''); ?>><?php echo e($k->nama_kelas); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button class="btn-outline">Cari</button>
        </form>
        <div class="flex gap-2">
            <a href="<?php echo e(route('siswa.import.form')); ?>" class="btn-outline">📥 Import Excel</a>
            <button @click="showForm = !showForm" class="btn-primary">+ Tambah Siswa</button>
        </div>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Siswa</p>
        <form method="POST" action="<?php echo e(route('siswa.store')); ?>" class="grid sm:grid-cols-5 gap-3 items-end">
            <?php echo csrf_field(); ?>
            <input type="text" name="nis" placeholder="NIS" required class="input">
            <input type="text" name="nisn" placeholder="NISN (opsional)" class="input">
            <input type="text" name="nama" placeholder="Nama Lengkap" required class="input sm:col-span-2">
            <select name="jenis_kelamin" required class="input">
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
            </select>
            <select name="kelas_id" required class="input">
                <option value="">Pilih Kelas</option>
                <?php $__currentLoopData = $kelasList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k->id); ?>"><?php echo e($k->nama_kelas); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>NIS</th><th>Nama</th><th>L/P</th><th>Kelas</th><th>Status</th><th class="th-aksi">Aksi</th></tr></thead>
                <?php $__empty_1 = true; $__currentLoopData = $siswas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tbody x-data="{ editing: false }">
                    <tr x-show="!editing">
                        <td><?php echo e($s->nis); ?></td>
                        <td class="font-medium"><?php echo e($s->nama); ?></td>
                        <td><?php echo e($s->jenis_kelamin); ?></td>
                        <td><?php echo e($s->kelas->nama_kelas); ?></td>
                        <td>
                            <?php if($s->is_active): ?><span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                            <?php else: ?><span class="badge bg-slate-100 text-slate-500">Nonaktif</span><?php endif; ?>
                        </td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit">✏️ Edit</button>
                                <form method="POST" action="<?php echo e(route('siswa.destroy', $s)); ?>" onsubmit="return confirm('Hapus siswa ini?')">
                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <button class="btn-chip btn-chip-delete">🗑️ Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="6" class="bg-brand-50/40">
                            <form method="POST" action="<?php echo e(route('siswa.update', $s)); ?>" class="grid sm:grid-cols-7 gap-3 items-end py-2">
                                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                                <input type="text" name="nis" value="<?php echo e($s->nis); ?>" placeholder="NIS" required class="input">
                                <input type="text" name="nisn" value="<?php echo e($s->nisn); ?>" placeholder="NISN" class="input">
                                <input type="text" name="nama" value="<?php echo e($s->nama); ?>" placeholder="Nama Lengkap" required class="input sm:col-span-2">
                                <select name="jenis_kelamin" required class="input">
                                    <option value="L" <?php echo e($s->jenis_kelamin === 'L' ? 'selected' : ''); ?>>Laki-laki</option>
                                    <option value="P" <?php echo e($s->jenis_kelamin === 'P' ? 'selected' : ''); ?>>Perempuan</option>
                                </select>
                                <select name="kelas_id" required class="input">
                                    <?php $__currentLoopData = $kelasList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($k->id); ?>" <?php echo e($s->kelas_id == $k->id ? 'selected' : ''); ?>><?php echo e($k->nama_kelas); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <label class="flex items-center gap-1.5 text-xs text-slate-600 font-semibold">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" <?php echo e($s->is_active ? 'checked' : ''); ?> class="rounded">
                                    Aktif
                                </label>
                                <div class="flex gap-2 sm:col-span-7">
                                    <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                                    <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                </tbody>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tbody>
                    <tr><td colspan="6" class="text-center text-slate-400 py-8">Belum ada data siswa.</td></tr>
                </tbody>
                <?php endif; ?>
            </table>
        </div>
        <div class="mt-4"><?php echo e($siswas->links()); ?></div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/siswa/index.blade.php ENDPATH**/ ?>