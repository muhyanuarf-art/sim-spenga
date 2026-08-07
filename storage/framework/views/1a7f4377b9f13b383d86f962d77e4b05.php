<?php $__env->startSection('title', 'Jam Pelajaran'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6" x-data="{ hariAktif: '<?php echo e(old('hari', request('hari', $hariList[0]))); ?>', showForm: false }">
    <div class="rounded-xl bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 text-sm">
        ℹ️ Jam pelajaran sekarang diatur <b>per hari</b> (Senin - Sabtu), karena waktu setiap hari bisa berbeda-beda.
        Setiap perubahan otomatis terhubung ke Jadwal Pelajaran, Absensi, dan tampilan Guru/Wali Kelas pada hari yang bersangkutan.
    </div>

    
    <div class="card p-2 flex flex-wrap gap-1">
        <?php $__currentLoopData = $hariList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button type="button" @click="hariAktif = '<?php echo e($h); ?>'"
                    :class="hariAktif === '<?php echo e($h); ?>' ? 'bg-brand-600 text-white shadow-soft' : 'text-slate-500 hover:bg-slate-100'"
                    class="px-4 py-2.5 rounded-xl text-sm font-bold transition">
                <?php echo e($h); ?>

                <span :class="hariAktif === '<?php echo e($h); ?>' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-400'"
                      class="ml-1.5 px-1.5 py-0.5 rounded-full text-[11px]"><?php echo e(($jamPelajaranPerHari[$h] ?? collect())->count()); ?></span>
            </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="flex justify-end">
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Jam Ke</button>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Jam Pelajaran</p>
        <form method="POST" action="<?php echo e(route('jam-pelajaran.store')); ?>" class="grid sm:grid-cols-5 gap-3 items-end">
            <?php echo csrf_field(); ?>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Hari</label>
                <select name="hari" x-model="hariAktif" required class="input">
                    <?php $__currentLoopData = $hariList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($h); ?>"><?php echo e($h); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Jam Ke</label>
                <select name="jam_ke" required class="input">
                    <?php for($i = 1; $i <= 10; $i++): ?><option value="<?php echo e($i); ?>">Jam Ke-<?php echo e($i); ?></option><?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Mulai</label>
                <input type="time" name="jam_mulai" required class="input">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Selesai</label>
                <input type="time" name="jam_selesai" required class="input">
            </div>
            <button type="submit" class="btn-primary h-[42px]">Simpan</button>
        </form>
    </div>

    <?php $__currentLoopData = $hariList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="card p-5" x-show="hariAktif === '<?php echo e($h); ?>'" x-cloak>
        <p class="font-bold text-slate-800 mb-4 text-lg">Jam Pelajaran - <?php echo e($h); ?></p>
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Jam Ke</th><th>Mulai</th><th>Selesai</th><th>Status</th><th class="th-aksi">Aksi</th></tr></thead>
                <?php $__empty_1 = true; $__currentLoopData = ($jamPelajaranPerHari[$h] ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $j): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tbody x-data="{ editing: false }">
                    <tr x-show="!editing">
                        <td class="font-semibold">Ke-<?php echo e($j->jam_ke); ?></td>
                        <td><?php echo e(substr($j->jam_mulai, 0, 5)); ?></td>
                        <td><?php echo e(substr($j->jam_selesai, 0, 5)); ?></td>
                        <td>
                            <?php if($j->is_active): ?><span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                            <?php else: ?><span class="badge bg-slate-100 text-slate-500">Nonaktif</span><?php endif; ?>
                        </td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit">✏️ Edit</button>
                                <form method="POST" action="<?php echo e(route('jam-pelajaran.destroy', $j)); ?>" onsubmit="return confirm('Hapus jam pelajaran ini?')">
                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <button class="btn-chip btn-chip-delete">🗑️ Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="5" class="bg-brand-50/40">
                            <form method="POST" action="<?php echo e(route('jam-pelajaran.update', $j)); ?>" class="grid sm:grid-cols-6 gap-3 items-end py-2">
                                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                                <select name="hari" required class="input">
                                    <?php $__currentLoopData = $hariList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($hh); ?>" <?php echo e($j->hari === $hh ? 'selected' : ''); ?>><?php echo e($hh); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <select name="jam_ke" required class="input">
                                    <?php for($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?php echo e($i); ?>" <?php echo e($j->jam_ke == $i ? 'selected' : ''); ?>>Jam Ke-<?php echo e($i); ?></option>
                                    <?php endfor; ?>
                                </select>
                                <input type="time" name="jam_mulai" value="<?php echo e(substr($j->jam_mulai, 0, 5)); ?>" required class="input">
                                <input type="time" name="jam_selesai" value="<?php echo e(substr($j->jam_selesai, 0, 5)); ?>" required class="input">
                                <label class="flex items-center gap-1.5 text-xs text-slate-600 font-semibold">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" <?php echo e($j->is_active ? 'checked' : ''); ?> class="rounded">
                                    Aktif
                                </label>
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
                    <tr><td colspan="5" class="text-center text-slate-400 py-8">Belum ada jam pelajaran untuk hari <?php echo e($h); ?>.</td></tr>
                </tbody>
                <?php endif; ?>
            </table>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/jam-pelajaran/index.blade.php ENDPATH**/ ?>