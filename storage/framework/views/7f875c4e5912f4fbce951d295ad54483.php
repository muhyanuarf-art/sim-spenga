<?php $__env->startSection('title', 'Mapping Guru Mengajar Kelas'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6" x-data="{ showForm: false }">

    <?php if(!$tahunAjaran): ?>
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Aktifkan Tahun Ajaran terlebih dahulu sebelum menambah mapping.
        </div>
    <?php endif; ?>

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex gap-2">
            <a href="<?php echo e(route('kurikulum.guru-mengajar.import.form')); ?>" class="btn-outline">📥 Import Excel</a>
        </div>
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Mapping</button>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Mapping Guru Mengajar</p>
        <form method="POST" action="<?php echo e(route('kurikulum.guru-mengajar.store')); ?>" class="grid sm:grid-cols-4 gap-3 items-end">
            <?php echo csrf_field(); ?>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Guru</label>
                <select name="guru_id" required class="input">
                    <option value="">Pilih Guru</option>
                    <?php $__currentLoopData = $guruList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($g->id); ?>"><?php echo e($g->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Kelas</label>
                <select name="kelas_id" required class="input">
                    <option value="">Pilih Kelas</option>
                    <?php $__currentLoopData = $kelasList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k->id); ?>"><?php echo e($k->nama_kelas); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Mata Pelajaran</label>
                <select name="mata_pelajaran_id" required class="input">
                    <option value="">Pilih Mapel</option>
                    <?php $__currentLoopData = $mapelList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($m->id); ?>"><?php echo e($m->nama_mapel); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <form method="GET" class="flex flex-wrap gap-3 mb-4">
            <select name="kelas_id" class="input max-w-[180px]" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                <?php $__currentLoopData = $kelasList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k->id); ?>" <?php echo e(request('kelas_id') == $k->id ? 'selected' : ''); ?>><?php echo e($k->nama_kelas); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <select name="guru_id" class="input max-w-[220px]" onchange="this.form.submit()">
                <option value="">Semua Guru</option>
                <?php $__currentLoopData = $guruList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($g->id); ?>" <?php echo e(request('guru_id') == $g->id ? 'selected' : ''); ?>><?php echo e($g->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </form>

        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Kelas</th><th>Mata Pelajaran</th><th>Guru</th><th class="th-aksi">Aksi</th></tr></thead>
                <?php $__empty_1 = true; $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tbody x-data="{ editing: false }">
                    <tr x-show="!editing">
                        <td class="font-semibold"><?php echo e($d->kelas->nama_kelas); ?></td>
                        <td><?php echo e($d->mapel->nama_mapel); ?></td>
                        <td><?php echo e($d->guru->name); ?></td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit">✏️ Edit</button>
                                <form method="POST" action="<?php echo e(route('kurikulum.guru-mengajar.destroy', $d)); ?>" onsubmit="return confirm('Hapus mapping ini?')">
                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <button class="btn-chip btn-chip-delete">🗑️ Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="4" class="bg-brand-50/40">
                            <form method="POST" action="<?php echo e(route('kurikulum.guru-mengajar.update', $d)); ?>" class="grid sm:grid-cols-4 gap-3 items-end py-2">
                                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                                <select name="kelas_id" required class="input">
                                    <?php $__currentLoopData = $kelasList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($k->id); ?>" <?php echo e($d->kelas_id == $k->id ? 'selected' : ''); ?>><?php echo e($k->nama_kelas); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <select name="mata_pelajaran_id" required class="input">
                                    <?php $__currentLoopData = $mapelList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($m->id); ?>" <?php echo e($d->mata_pelajaran_id == $m->id ? 'selected' : ''); ?>><?php echo e($m->nama_mapel); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <select name="guru_id" required class="input">
                                    <?php $__currentLoopData = $guruList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($g->id); ?>" <?php echo e($d->guru_id == $g->id ? 'selected' : ''); ?>><?php echo e($g->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                    <tr><td colspan="4" class="text-center text-slate-400 py-8">Belum ada data mapping.</td></tr>
                </tbody>
                <?php endif; ?>
            </table>
        </div>
        <div class="mt-4"><?php echo e($data->links()); ?></div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/kurikulum/guru-mengajar/index.blade.php ENDPATH**/ ?>