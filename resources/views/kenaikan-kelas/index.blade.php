@extends('layouts.app')
@section('title', 'Kenaikan Kelas')

@section('content')
<div class="space-y-6" x-data="{ showPreview: false }">

    {{-- STEP 4 Bagian 2/19 — Info Tahun Ajaran Tujuan (otomatis, bukan dropdown bebas) --}}
    <div class="card p-5">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Kenaikan Kelas</p>
        @if(!$periodeAktif)
            <p class="text-sm text-amber-600">Belum ada Tahun Ajaran aktif. Aktifkan salah satu di menu Tahun Ajaran terlebih dahulu.</p>
        @elseif(!$tahunAjaranTujuan)
            <p class="text-sm text-amber-600">
                Tahun Ajaran {{ $namaTahunTujuan ?? 'berikutnya' }} belum tersedia.
                Silakan buat Tahun Ajaran Baru terlebih dahulu di menu
                <a href="{{ route('tahun-ajaran.index') }}" class="underline font-semibold">Tahun Ajaran</a>.
            </p>
        @else
            <p class="text-sm text-slate-600">
                Kenaikan Kelas: <span class="font-bold">{{ $periodeAktif->nama }}</span>
                <span class="text-slate-400">&rarr;</span>
                <span class="font-bold">{{ $tahunAjaranTujuan->nama }}</span>
            </p>
        @endif
    </div>

    {{-- Langkah 1: pilih kelas asal --}}
    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">1. Pilih Kelas Asal</p>
        <form method="GET" action="{{ route('kenaikan-kelas.index') }}" class="flex flex-wrap gap-3 items-end">
            <div class="min-w-[220px]">
                <label class="block text-xs font-semibold text-slate-500 mb-1">Kelas Asal</label>
                <select name="kelas_asal_id" required class="input" onchange="this.form.submit()">
                    <option value="">Pilih kelas...</option>
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id }}" {{ $kelasAsal && $kelasAsal->id === $k->id ? 'selected' : '' }}>
                            {{ $k->nama_kelas }} (Tingkat {{ $k->tingkat }})
                        </option>
                    @endforeach
                </select>
            </div>
            <noscript><button class="btn-outline">Tampilkan</button></noscript>
        </form>
    </div>

    @if($kelasAsal && $tahunAjaranTujuan)
    @php
        $siswaNamaJson = $siswas->pluck('nama')->values()->toJson();
        $kelasMapJson = $kelasList->mapWithKeys(fn($k) => [(string) $k->id => $k->nama_kelas.' (Tingkat '.$k->tingkat.')'])->toJson();
    @endphp
    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-1">2. Proses Kenaikan Kelas — {{ $kelasAsal->nama_kelas }}</p>
        <p class="text-sm text-slate-500 mb-4">
            Centang siswa yang akan diproses ke Tahun Ajaran {{ $tahunAjaranTujuan->nama }}. Siswa yang tidak
            dicentang tidak diproses sama sekali pada sesi ini (bisa diproses belakangan). Untuk siswa yang
            <span class="font-semibold">TINGGAL KELAS</span> (tidak naik), pilih Kelas Tujuan yang SAMA dengan
            Kelas Asal — sistem akan tetap mencatatnya di Riwayat Kelas untuk Tahun Ajaran {{ $tahunAjaranTujuan->nama }}.
        </p>

        <form method="POST" action="{{ route('kenaikan-kelas.store') }}"
              x-data="{
                  checkedCount: {{ $siswas->count() }},
                  checkedNames: {{ $siswaNamaJson }},
                  kelasTujuanId: '',
                  kelasTujuanLabel: '',
                  kelasMap: {{ $kelasMapJson }},
                  toggleSiswa(nama, checked) {
                      if (checked) { if (!this.checkedNames.includes(nama)) this.checkedNames.push(nama) }
                      else { this.checkedNames = this.checkedNames.filter(n => n !== nama) }
                      this.checkedCount = this.checkedNames.length
                  },
                  confirmed: false
              }"
              @submit="if (! confirmed) { $event.preventDefault(); kelasTujuanLabel = kelasMap[kelasTujuanId] || ''; showPreview = true }">
            @csrf
            <input type="hidden" name="kelas_asal_id" value="{{ $kelasAsal->id }}">

            <div class="grid sm:grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Kelas Tujuan</label>
                    <select name="kelas_tujuan_id" required class="input" x-model="kelasTujuanId">
                        <option value="">Pilih kelas tujuan...</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id }}">
                                {{ $k->nama_kelas }} (Tingkat {{ $k->tingkat }}){{ $k->id === $kelasAsal->id ? ' — sama dengan asal (tinggal kelas)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Keterangan (opsional)</label>
                    <input type="text" name="keterangan" placeholder="Contoh: Kenaikan kelas TA {{ $tahunAjaranTujuan->nama }}" class="input">
                </div>
            </div>

            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead>
                        <tr>
                            <th class="w-10">
                                <input type="checkbox" checked
                                       @change="document.querySelectorAll('.chk-siswa').forEach(c => { c.checked = $event.target.checked; c.dispatchEvent(new Event('change')) })">
                            </th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>L/P</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($siswas as $s)
                        <tr>
                            <td>
                                <input type="checkbox" class="chk-siswa" name="siswa_ids[]" value="{{ $s->id }}" checked
                                       @change="toggleSiswa({{ json_encode($s->nama) }}, $event.target.checked)">
                            </td>
                            <td>{{ $s->nis }}</td>
                            <td class="font-medium">{{ $s->nama }}</td>
                            <td>{{ $s->jenis_kelamin }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-slate-400 py-8">Tidak ada siswa aktif di kelas ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($siswas->isNotEmpty())
            <div class="mt-4 flex justify-end">
                <button type="submit" class="btn-primary" :disabled="checkedCount < 1 || !kelasTujuanId">
                    Proses Kenaikan Kelas (<span x-text="checkedCount"></span> siswa)
                </button>
            </div>
            @endif

            {{-- STEP 4 Bagian 10 — Preview WAJIB sebelum disimpan: tahun asal,
                 kelas asal, tahun tujuan, kelas tujuan, DAN daftar nama siswa. --}}
            <div x-show="showPreview" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                 @keydown.escape.window="showPreview = false">
                <div class="card w-full max-w-md p-6 max-h-[85vh] overflow-y-auto" @click.outside="showPreview = false">
                    <p class="font-bold text-slate-800 text-lg mb-4">Preview Kenaikan Kelas</p>

                    <dl class="text-sm space-y-2 mb-4">
                        <div class="flex justify-between"><dt class="text-slate-500">Tahun Asal</dt><dd class="font-semibold">{{ $periodeAktif->nama }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Kelas Asal</dt><dd class="font-semibold">{{ $kelasAsal->nama_kelas }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Tahun Tujuan</dt><dd class="font-semibold">{{ $tahunAjaranTujuan->nama }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Kelas Tujuan</dt><dd class="font-semibold" x-text="kelasTujuanLabel"></dd></div>
                    </dl>

                    <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Siswa (<span x-text="checkedCount"></span>)</p>
                    <ul class="text-sm text-slate-700 list-decimal list-inside space-y-0.5 mb-4 max-h-40 overflow-y-auto">
                        <template x-for="nama in checkedNames" :key="nama">
                            <li x-text="nama"></li>
                        </template>
                    </ul>

                    <p class="text-xs text-slate-400 mb-4">
                        Aksi ini akan tercatat di Riwayat Kelas masing-masing siswa dan tidak dapat dibatalkan lewat aplikasi.
                    </p>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-outline" @click="showPreview = false">Batal</button>
                        <button type="submit" class="btn-primary" @click="confirmed = true; showPreview = false">Proses Kenaikan</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
