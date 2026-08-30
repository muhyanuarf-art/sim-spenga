@extends('layouts.app')
@section('title', 'Pengingat Guru (WhatsApp)')

@section('content')
<div class="space-y-6">

    {{-- ============ Keadaan sekarang ============
         Ditaruh paling atas dan ditulis sebagai kalimat, bukan lencana
         "Aktif/Nonaktif" saja, karena ada keadaan ketiga yang mudah luput:
         sudah dinyalakan tetapi tokennya belum diisi — dari sisi admin
         terlihat aktif padahal tidak ada satu pesan pun yang bisa keluar. --}}
    @php
        $siap = $pengaturan->siapKirim();
        $adaToken = $pengaturan->token() !== null;
    @endphp

    <div class="card p-5 flex items-start gap-4 flex-wrap
        {{ $siap ? 'border-emerald-200 bg-emerald-50/40' : ($pengaturan->aktif ? 'border-amber-200 bg-amber-50/40' : '') }}">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0
            {{ $siap ? 'bg-emerald-100 text-emerald-600' : ($pengaturan->aktif ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-slate-400') }}">
            <i class="fa-brands fa-whatsapp text-xl"></i>
        </div>
        <div class="min-w-0 flex-1">
            @if($siap)
                <p class="font-bold text-emerald-800">Pengingat aktif dan siap mengirim</p>
                <p class="text-sm text-emerald-700/80 mt-0.5">
                    Guru yang belum mengisi jurnal &amp; absensi akan dihubungi
                    <strong>{{ $pengaturan->jeda_menit }} menit</strong> setelah jam pelajarannya selesai,
                    dari perangkat WhatsApp kepala sekolah.
                </p>
            @elseif($pengaturan->aktif)
                <p class="font-bold text-amber-800">Dinyalakan, tetapi belum bisa mengirim</p>
                <p class="text-sm text-amber-700/90 mt-0.5">
                    Token perangkat WhatsApp kepala sekolah belum diisi. Selama itu kosong,
                    sesi yang terlambat tetap tercatat tetapi tidak ada pesan yang keluar.
                </p>
            @else
                <p class="font-bold text-slate-700">Pengingat sedang dimatikan</p>
                <p class="text-sm text-slate-500 mt-0.5">
                    Tidak ada pesan apa pun yang dikirim ke guru.
                </p>
            @endif
        </div>
    </div>

    {{-- ============ Penjelasan dua perangkat ============ --}}
    <x-panel judul="Dua nomor WhatsApp yang terpisah"
             deskripsi="Siapa mengirim apa kepada siapa"
             ikon="fa-diagram-project">
        <div class="grid md:grid-cols-2 gap-4">
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-2">Perangkat 1</p>
                <p class="font-semibold text-slate-800">Nomor sekolah</p>
                <p class="text-sm text-slate-500 mt-1 leading-relaxed">
                    Mengirim pemberitahuan siswa <strong>Alfa</strong> kepada <strong>orang tua</strong>.
                </p>
                <p class="text-xs text-slate-400 mt-3">
                    Tokennya diatur di berkas <code class="text-pink-600">.env</code> dan
                    <strong>tidak diubah dari halaman ini</strong>.
                </p>
            </div>
            <div class="rounded-xl border border-brand-200 bg-brand-50/40 p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-brand-500 mb-2">Perangkat 2</p>
                <p class="font-semibold text-slate-800">Nomor kepala sekolah</p>
                <p class="text-sm text-slate-600 mt-1 leading-relaxed">
                    Mengirim <strong>pengingat jurnal &amp; absensi</strong> kepada <strong>guru</strong>.
                </p>
                <p class="text-xs text-brand-700/70 mt-3">
                    Tokennya diisi di halaman ini dan disimpan terenkripsi.
                </p>
            </div>
        </div>
        <p class="text-xs text-slate-400 mt-4 leading-relaxed">
            Keduanya adalah <em>device</em> berbeda di akun Fonnte yang sama. Yang membedakannya adalah
            <strong>token</strong>-nya, bukan alamat servernya — jadi mengisi token di sini tidak mengubah
            apa pun pada notifikasi Alfa yang sudah berjalan.
        </p>
    </x-panel>

    {{-- ============ Form pengaturan ============ --}}
    <form method="POST" action="{{ route('pengaturan-notifikasi.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-panel judul="Pengaturan Pengingat" ikon="fa-sliders">
            <div class="space-y-5">

                <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 cursor-pointer hover:border-brand-300 transition">
                    {{-- Pasangan hidden+checkbox: checkbox yang tidak dicentang
                         tidak dikirim browser sama sekali, jadi hidden inilah
                         yang memberi tahu server bahwa pilihannya "mati". --}}
                    <input type="hidden" name="aktif" value="0">
                    <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $pengaturan->aktif)) class="mt-0.5 rounded">
                    <span>
                        <span class="font-semibold text-slate-800">Nyalakan pengingat otomatis</span>
                        <span class="block text-sm text-slate-500 mt-0.5">
                            Sistem memeriksa tiap 5 menit dan menghubungi guru yang jurnal &amp; absensinya belum terisi.
                        </span>
                    </span>
                </label>

                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <label class="label">Jeda setelah jam pelajaran selesai</label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="jeda_menit" min="5" max="240"
                                   value="{{ old('jeda_menit', $pengaturan->jeda_menit) }}" required class="input">
                            <span class="text-sm text-slate-500 shrink-0">menit</span>
                        </div>
                        @error('jeda_menit')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        <p class="text-xs text-slate-400 mt-1">
                            Dihitung dari jam <strong>terakhir</strong> sesi, bukan jam pertama.
                        </p>
                    </div>
                    <div>
                        <label class="label">Mulai mengirim pukul</label>
                        <input type="time" name="jam_mulai_kirim"
                               value="{{ old('jam_mulai_kirim', substr((string) $pengaturan->jam_mulai_kirim, 0, 5)) }}" required class="input">
                        @error('jam_mulai_kirim')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label">Berhenti mengirim pukul</label>
                        <input type="time" name="jam_akhir_kirim"
                               value="{{ old('jam_akhir_kirim', substr((string) $pengaturan->jam_akhir_kirim, 0, 5)) }}" required class="input">
                        @error('jam_akhir_kirim')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        <p class="text-xs text-slate-400 mt-1">Agar guru tidak dihubungi larut malam.</p>
                    </div>
                </div>

                <div>
                    <label class="label">
                        Token perangkat WhatsApp kepala sekolah
                        @if($adaToken)
                            <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">sudah terisi</span>
                        @else
                            <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-600">belum diisi</span>
                        @endif
                    </label>
                    <input type="password" name="fonnte_token" autocomplete="new-password" spellcheck="false"
                           placeholder="{{ $adaToken ? 'Biarkan kosong bila tidak ingin diganti' : 'Tempelkan token perangkat 2 dari dasbor Fonnte' }}"
                           class="input font-mono">
                    @error('fonnte_token')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                        Ambil di dasbor Fonnte → <strong>Device</strong> → pilih perangkat nomor kepala sekolah → salin <strong>Token</strong>.
                        Setelah disimpan, token tidak pernah ditampilkan lagi.
                        <strong>Mengosongkan kolom ini tidak menghapus token</strong> yang sudah tersimpan.
                    </p>
                </div>

                <div>
                    <label class="label">Naskah pesan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea name="template_pesan" rows="11" class="input font-mono text-sm"
                              placeholder="Kosongkan untuk memakai naskah bawaan">{{ old('template_pesan', $pengaturan->template_pesan) }}</textarea>
                    @error('template_pesan')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror

                    <p class="text-xs font-semibold text-slate-500 mt-3 mb-1.5">Kata kunci yang bisa dipakai:</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach(\App\Models\PengaturanNotifikasiGuru::KATA_KUNCI as $kunci => $arti)
                            <span class="px-2 py-1 rounded-lg bg-slate-100 text-xs" title="{{ $arti }}">
                                <code class="text-pink-600">{{ $kunci }}</code>
                                <span class="text-slate-400">— {{ $arti }}</span>
                            </span>
                        @endforeach
                    </div>

                    <details class="mt-3">
                        <summary class="text-xs font-semibold text-brand-600 cursor-pointer">Lihat naskah bawaan</summary>
                        <pre class="mt-2 p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs whitespace-pre-wrap text-slate-600">{{ \App\Models\PengaturanNotifikasiGuru::TEMPLATE_BAWAAN }}</pre>
                    </details>
                </div>

                <div class="flex gap-2 pt-1">
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Pengaturan
                    </button>
                </div>
            </div>
        </x-panel>
    </form>

    {{-- ============ Uji coba ============ --}}
    <x-panel judul="Uji Coba Pengiriman"
             deskripsi="Buktikan tokennya benar sebelum dinyalakan untuk semua guru"
             ikon="fa-paper-plane">
        <form method="POST" action="{{ route('pengaturan-notifikasi.uji') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="flex-1 min-w-[220px]">
                <label class="label">Nomor WhatsApp tujuan uji coba</label>
                <input type="text" name="nomor_uji" value="{{ old('nomor_uji') }}"
                       placeholder="081234567890" class="input">
                @error('nomor_uji')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-outline h-[42px]" @disabled(! $adaToken)>
                <i class="fa-solid fa-paper-plane mr-2"></i> Kirim Pesan Uji
            </button>
        </form>
        @unless($adaToken)
            <p class="text-xs text-amber-600 mt-2">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                Isi dan simpan tokennya lebih dulu.
            </p>
        @endunless

        @if($adaToken)
            <form method="POST" action="{{ route('pengaturan-notifikasi.hapus-token') }}" class="mt-5 pt-4 border-t border-slate-100"
                  onsubmit="return confirm('Hapus token perangkat yang tersimpan? Pengingat akan ikut dimatikan sampai token baru diisi.')">
                @csrf @method('DELETE')
                <button type="submit" class="text-xs font-semibold text-red-500 hover:text-red-700 cursor-pointer">
                    <i class="fa-solid fa-trash mr-1"></i> Hapus token perangkat yang tersimpan
                </button>
                <p class="text-xs text-slate-400 mt-1">Dipakai bila nomor kepala sekolah berganti perangkat.</p>
            </form>
        @endif
    </x-panel>

    {{-- ============ Riwayat ============ --}}
    <x-panel judul="Riwayat Pengingat" ikon="fa-clock-rotate-left" rapat>
        <div class="p-5 border-b border-slate-100">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Bulan</label>
                    <select name="bulan" class="input" onchange="this.form.submit()">
                        @foreach(range(1, 12) as $b)
                            <option value="{{ $b }}" {{ $b === $bulan ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($b)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Tahun</label>
                    <select name="tahun" class="input" onchange="this.form.submit()">
                        @foreach(range(now()->year - 1, now()->year + 1) as $y)
                            <option value="{{ $y }}" {{ $y === $tahun ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-wrap gap-2 ml-auto">
                    @foreach(['terkirim' => 'Terkirim', 'pending' => 'Menunggu', 'gagal' => 'Gagal', 'dilewati' => 'Dilewati'] as $k => $label)
                        <span class="px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs">
                            <span class="font-bold text-slate-700">{{ $ringkasan[$k] ?? 0 }}</span>
                            <span class="text-slate-400">{{ $label }}</span>
                        </span>
                    @endforeach
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-left px-4 py-2.5 font-semibold">Tanggal</th>
                        <th class="text-left px-4 py-2.5 font-semibold">Guru</th>
                        <th class="text-left px-4 py-2.5 font-semibold">Kelas</th>
                        <th class="text-left px-4 py-2.5 font-semibold">Mata Pelajaran</th>
                        <th class="text-left px-4 py-2.5 font-semibold">Jam</th>
                        <th class="text-left px-4 py-2.5 font-semibold">Status</th>
                        <th class="text-left px-4 py-2.5 font-semibold">Keterangan</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($riwayat as $r)
                        <tr>
                            <td class="px-4 py-2.5 whitespace-nowrap">{{ $r->tanggal->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-2.5">{{ $r->guru?->name ?? '-' }}</td>
                            <td class="px-4 py-2.5">{{ $r->kelas?->nama_kelas ?? '-' }}</td>
                            <td class="px-4 py-2.5">{{ $r->mapel?->nama_mapel ?? '-' }}</td>
                            <td class="px-4 py-2.5 whitespace-nowrap">{{ $r->labelJam() }}</td>
                            <td class="px-4 py-2.5">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $r->statusBadgeClass() }}">{{ $r->statusLabel() }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-slate-400 text-xs max-w-xs">{{ $r->keterangan_gagal ?: ($r->dikirim_at?->format('H:i') ? 'Terkirim pukul '.$r->dikirim_at->format('H:i') : '—') }}</td>
                            <td class="px-4 py-2.5 text-right">
                                @if(in_array($r->status_kirim, ['gagal', 'dilewati'], true))
                                    <form method="POST" action="{{ route('pengaturan-notifikasi.kirim-ulang', $r) }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-brand-600 hover:text-brand-800 cursor-pointer whitespace-nowrap">
                                            <i class="fa-solid fa-rotate-right mr-1"></i> Kirim ulang
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-400">
                                Belum ada pengingat pada bulan ini.
                                <span class="block text-xs mt-1">Bila pengingat sudah dinyalakan, ini justru kabar baik — berarti tidak ada jurnal yang terlambat diisi.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($riwayat->hasPages())
            <div class="p-4 border-t border-slate-100">{{ $riwayat->links() }}</div>
        @endif
    </x-panel>

    {{-- ============ Syarat teknis ============ --}}
    <x-panel judul="Agar Pengingat Benar-benar Berjalan" ikon="fa-server">
        <p class="text-sm text-slate-600 leading-relaxed mb-3">
            Pengingat dikerjakan di belakang layar, jadi di server harus ada dua proses yang hidup terus.
            Bila salah satunya mati, pengaturan di halaman ini tetap tersimpan tetapi tidak ada pesan yang keluar.
        </p>
        <div class="grid md:grid-cols-2 gap-4">
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="font-semibold text-slate-800 text-sm mb-1">1. Penjadwal</p>
                <pre class="text-xs bg-slate-50 border border-slate-200 rounded-lg p-2.5 overflow-x-auto">php artisan schedule:run</pre>
                <p class="text-xs text-slate-400 mt-2">Dipanggil tiap menit oleh Task Scheduler Windows atau cron.</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="font-semibold text-slate-800 text-sm mb-1">2. Pekerja antrian</p>
                <pre class="text-xs bg-slate-50 border border-slate-200 rounded-lg p-2.5 overflow-x-auto">php artisan queue:work</pre>
                <p class="text-xs text-slate-400 mt-2">Berjalan terus-menerus; yang benar-benar mengirim pesannya.</p>
            </div>
        </div>
        <p class="text-xs text-slate-400 mt-4">
            Untuk memeriksa temuan hari ini tanpa mengirim apa pun:
            <code class="text-pink-600">php artisan pengingat:jurnal --lihat</code>
        </p>
    </x-panel>

</div>
@endsection
