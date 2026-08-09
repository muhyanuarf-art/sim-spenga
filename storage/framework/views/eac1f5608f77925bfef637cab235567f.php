<?php $__env->startSection('title', 'Status WhatsApp Ortu'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <div class="card p-5">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Bulan</label>
                <select name="bulan" class="input" onchange="this.form.submit()">
                    <?php $__currentLoopData = range(1,12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($b); ?>" <?php echo e($b === $bulan ? 'selected' : ''); ?>><?php echo e(\Carbon\Carbon::create()->month($b)->translatedFormat('F')); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tahun</label>
                <select name="tahun" class="input" onchange="this.form.submit()">
                    <?php $__currentLoopData = range(now()->year - 1, now()->year + 1); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($y); ?>" <?php echo e($y === $tahun ? 'selected' : ''); ?>><?php echo e($y); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <?php if($bisaFilterKelas): ?>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Kelas</label>
                <select name="kelas_id" class="input" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    <?php $__currentLoopData = $kelasList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($k->id); ?>" <?php echo e(request('kelas_id') == $k->id ? 'selected' : ''); ?>><?php echo e($k->nama_kelas); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <?php endif; ?>
        </form>
        <p class="text-xs text-slate-400 mt-3">
            📅 Hari ini: <b class="text-slate-500"><?php echo e(now()->translatedFormat('l, d F Y')); ?></b>
            &middot; bulan &amp; tahun di atas otomatis mengikuti tanggal server saat halaman ini dibuka.
        </p>
    </div>

    <?php if($tanpaAksesData): ?>
        <div class="rounded-xl bg-slate-50 border border-slate-200 text-slate-500 px-5 py-6 text-sm text-center">
            📲 Menu ini menampilkan histori notifikasi WA per KELAS (bukan per mapel), jadi hanya relevan untuk
            <b>Wali Kelas</b>. Anda saat ini belum ditetapkan sebagai wali kelas manapun.
        </div>
    <?php else: ?>
        <div class="grid grid-cols-3 gap-4">
            <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['color' => 'emerald','icon' => '✅','label' => 'Terkirim','value' => $ringkasan['terkirim']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'emerald','icon' => '✅','label' => 'Terkirim','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($ringkasan['terkirim'])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['color' => 'amber','icon' => '⏳','label' => 'Menunggu Diproses','value' => $ringkasan['pending']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'amber','icon' => '⏳','label' => 'Menunggu Diproses','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($ringkasan['pending'])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['color' => 'rose','icon' => '⚠️','label' => 'Gagal Terkirim','value' => $ringkasan['gagal']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'rose','icon' => '⚠️','label' => 'Gagal Terkirim','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($ringkasan['gagal'])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
        </div>

        <?php if($ringkasan['pending'] > 0): ?>
            <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
                ⏳ Ada <?php echo e($ringkasan['pending']); ?> notifikasi yang masih menunggu diproses queue worker.
                Pastikan <code class="bg-amber-100 px-1 rounded">php artisan queue:work</code> sedang berjalan di server.
            </div>
        <?php endif; ?>
        <?php if($ringkasan['gagal'] > 0): ?>
            <div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">
                ⚠️ Ada <?php echo e($ringkasan['gagal']); ?> notifikasi gagal terkirim — kemungkinan nomor WA ortu kosong/salah format,
                atau gateway WA sedang bermasalah.
            </div>
        <?php endif; ?>

        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-1">Histori Notifikasi WA — <?php echo e(\Carbon\Carbon::create()->month($bulan)->translatedFormat('F')); ?> <?php echo e($tahun); ?></p>
            <?php if($kelasWali): ?>
                <p class="text-sm text-slate-400 mb-4">Menampilkan siswa kelas <?php echo e($kelasWali->nama_kelas); ?> (kelas wali Anda).</p>
            <?php else: ?>
                <p class="text-sm text-slate-400 mb-4">Diurutkan dari tanggal terbaru.</p>
            <?php endif; ?>

            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Menurut Mapel</th>
                            <th>Status</th>
                            <th>Waktu Terkirim</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="text-slate-500 whitespace-nowrap"><?php echo e($n->tanggal->translatedFormat('d M Y')); ?></td>
                            <td class="font-medium">
                                <div class="flex items-center gap-2">
                                    <?php if (isset($component)) { $__componentOriginal7863338fc871141cde043e16f24c9728 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7863338fc871141cde043e16f24c9728 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.initial-avatar','data' => ['nama' => $n->siswa->nama ?? '-']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('initial-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($n->siswa->nama ?? '-')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7863338fc871141cde043e16f24c9728)): ?>
<?php $attributes = $__attributesOriginal7863338fc871141cde043e16f24c9728; ?>
<?php unset($__attributesOriginal7863338fc871141cde043e16f24c9728); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7863338fc871141cde043e16f24c9728)): ?>
<?php $component = $__componentOriginal7863338fc871141cde043e16f24c9728; ?>
<?php unset($__componentOriginal7863338fc871141cde043e16f24c9728); ?>
<?php endif; ?>
                                    <?php echo e($n->siswa->nama ?? '(siswa dihapus)'); ?>

                                </div>
                            </td>
                            <td><?php if (isset($component)) { $__componentOriginal431ed0c73bd88bcebb6e4c05e61ec935 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal431ed0c73bd88bcebb6e4c05e61ec935 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.kelas-badge','data' => ['nama' => $n->siswa->kelas->nama_kelas ?? '-']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('kelas-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($n->siswa->kelas->nama_kelas ?? '-')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal431ed0c73bd88bcebb6e4c05e61ec935)): ?>
<?php $attributes = $__attributesOriginal431ed0c73bd88bcebb6e4c05e61ec935; ?>
<?php unset($__attributesOriginal431ed0c73bd88bcebb6e4c05e61ec935); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal431ed0c73bd88bcebb6e4c05e61ec935)): ?>
<?php $component = $__componentOriginal431ed0c73bd88bcebb6e4c05e61ec935; ?>
<?php unset($__componentOriginal431ed0c73bd88bcebb6e4c05e61ec935); ?>
<?php endif; ?></td>
                            <td class="text-slate-500">
                                <?php echo e($n->mapel->nama_mapel ?? '-'); ?>

                                <?php if($n->jam_ke): ?> <span class="text-slate-400">(jam ke-<?php echo e($n->jam_ke); ?>)</span> <?php endif; ?>
                            </td>
                            <td>
                                <?php if($n->status_kirim === 'terkirim'): ?>
                                    <span class="badge bg-emerald-50 text-emerald-700">✅ Terkirim</span>
                                <?php elseif($n->status_kirim === 'gagal'): ?>
                                    <span class="badge bg-rose-50 text-rose-700">⚠️ Gagal</span>
                                <?php else: ?>
                                    <span class="badge bg-amber-50 text-amber-700">⏳ Menunggu</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-slate-500 whitespace-nowrap">
                                <?php echo e($n->dikirim_at ? $n->dikirim_at->translatedFormat('d M Y, H:i') : '-'); ?>

                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="6" class="text-center text-emerald-600 py-8">🎉 Tidak ada notifikasi Alfa bulan ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/notifikasi-wa/index.blade.php ENDPATH**/ ?>