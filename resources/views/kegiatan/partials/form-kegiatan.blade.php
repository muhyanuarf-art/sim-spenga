{{--
    Isi form kegiatan — dipakai bersama oleh form TAMBAH dan form UBAH
    supaya keduanya tidak pernah berbeda field/aturannya.

    Wajib dibungkus <form> yang punya x-data="{ cakupan: '...' }".
    Variabel: $kegiatan (null saat tambah), $kelasList, $tingkatList, $hariList.
--}}
@php
    $nilai = fn ($field, $default = null) => old($field, $kegiatan->{$field} ?? $default);
    $hariTerpilih = old('hari_aktif', $kegiatan->hari_aktif ?? []) ?: [];
    $kelasTerpilih = old('kelas_ids', $kegiatan?->kelasTerpilih->pluck('id')->all() ?? []) ?: [];
@endphp

<div class="grid sm:grid-cols-2 gap-4">
    <div class="sm:col-span-2">
        <label class="label">Nama Kegiatan</label>
        <input type="text" name="nama" value="{{ $nilai('nama') }}" required class="input"
               placeholder="Contoh: Lomba HUT RI ke-81, Asesmen Sumatif Tengah Semester, Pesantren Ramadan">
    </div>

    <div>
        <label class="label">Jenis Kegiatan</label>
        <select name="jenis" class="input" required>
            @foreach(\App\Models\KegiatanSekolah::JENIS as $key => $label)
                <option value="{{ $key }}" @selected($nilai('jenis', 'lainnya') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="label">Cakupan Kelas</label>
        <select name="cakupan" class="input" x-model="cakupan" required>
            @foreach(\App\Models\KegiatanSekolah::CAKUPAN as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="label">Tanggal Mulai</label>
        <input type="date" name="tanggal_mulai" required class="input"
               value="{{ old('tanggal_mulai', $kegiatan?->tanggal_mulai?->toDateString() ?? now()->toDateString()) }}">
    </div>

    <div>
        <label class="label">Tanggal Selesai</label>
        <input type="date" name="tanggal_selesai" required class="input"
               value="{{ old('tanggal_selesai', $kegiatan?->tanggal_selesai?->toDateString() ?? now()->toDateString()) }}">
    </div>

    <div x-show="cakupan === 'tingkat'" x-cloak>
        <label class="label">Tingkat</label>
        <select name="tingkat" class="input">
            <option value="">— pilih tingkat —</option>
            @foreach($tingkatList as $t)
                <option value="{{ $t }}" @selected($nilai('tingkat') == $t)>Tingkat {{ $t }}</option>
            @endforeach
        </select>
    </div>

    <div class="sm:col-span-2" x-show="cakupan === 'kelas'" x-cloak>
        <label class="label">Pilih Kelas</label>
        <div class="flex flex-wrap gap-2">
            @foreach($kelasList as $kls)
                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm cursor-pointer hover:bg-slate-50">
                    <input type="checkbox" name="kelas_ids[]" value="{{ $kls->id }}"
                           class="rounded border-slate-300 text-brand-600" @checked(in_array($kls->id, $kelasTerpilih))>
                    {{ $kls->nama_kelas }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="sm:col-span-2">
        <label class="label">Hari Berlangsung (opsional)</label>
        <div class="flex flex-wrap gap-2">
            @foreach($hariList as $hari)
                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm cursor-pointer hover:bg-slate-50">
                    <input type="checkbox" name="hari_aktif[]" value="{{ $hari }}"
                           class="rounded border-slate-300 text-brand-600" @checked(in_array($hari, $hariTerpilih))>
                    {{ $hari }}
                </label>
            @endforeach
        </div>
        <p class="text-xs text-slate-400 mt-1">
            Biarkan kosong kalau kegiatan berlangsung setiap hari dalam rentang tanggal.
            Centang hari tertentu untuk kegiatan berkala, misalnya lomba yang hanya digelar tiap Sabtu selama Agustus.
        </p>
    </div>

    <div class="sm:col-span-2">
        <label class="label">Keterangan (opsional)</label>
        <textarea name="keterangan" rows="2" class="input"
                  placeholder="Contoh: Seluruh siswa berkumpul di lapangan pukul 07.00, seragam olahraga.">{{ $nilai('keterangan') }}</textarea>
    </div>

    <div class="sm:col-span-2 flex flex-wrap gap-5 pt-1">
        <label class="inline-flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
            <input type="checkbox" name="kirim_wa_alfa" value="1" class="rounded border-slate-300 text-brand-600"
                   @checked(old('kirim_wa_alfa', $kegiatan->kirim_wa_alfa ?? true))>
            Kirim notifikasi WhatsApp untuk siswa Alfa
        </label>

        @if($kegiatan)
            <label class="inline-flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                <input type="checkbox" name="is_aktif" value="1" class="rounded border-slate-300 text-brand-600"
                       @checked(old('is_aktif', $kegiatan->is_aktif))>
                Kegiatan aktif (tampil untuk wali kelas)
            </label>
        @endif
    </div>
</div>
