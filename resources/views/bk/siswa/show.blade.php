@extends('layouts.app')
@section('title', 'Profil Perilaku Siswa')

@section('content')
@php
    $user = auth()->user();
    $bisaKelolaPoin = in_array($user->role, ['guru_bk', 'admin']);
    $bisaLaporKasus = in_array($user->role, ['guru', 'guru_bk', 'admin']);
    $tahapLabel = fn ($t) => $t ? "Tahap {$t}" : 'Belum ada';
@endphp
<div class="space-y-6" x-data="{ modal: null }">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <a href="{{ url()->previous() }}" class="text-sm text-slate-400 hover:text-slate-600">&larr; Kembali</a>
            <div class="flex items-center gap-3 mt-1">
                <x-initial-avatar :nama="$siswa->nama" size="w-12 h-12 text-lg" />
                <div>
                    <p class="text-xl font-extrabold text-slate-800">{{ $siswa->nama }}</p>
                    <div class="flex items-center gap-2 text-sm text-slate-400">
                        <span>{{ $siswa->nis }}</span> &middot; <x-kelas-badge :nama="$siswa->kelas->nama_kelas ?? '-'" />
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($bisaLaporKasus)
                <button @click="modal = 'kasus'" class="btn-primary bg-rose-600 hover:bg-rose-700">+ Catat Pelanggaran</button>
            @endif
            @if($bisaKelolaPoin)
                <button @click="modal = 'pembinaan'" class="btn-outline">+ Catat Pembinaan</button>
                <button @click="modal = 'pengurangan'" class="btn-outline">+ Kurangi Poin</button>
                <button @click="modal = 'pemanggilan'" class="btn-outline">+ Panggil Ortu</button>
            @endif
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <x-stat-card color="rose" icon="fa-scale-balanced" label="Poin Aktif" :value="$ringkasan['poin_aktif']" />
        <x-stat-card color="violet" icon="fa-compass" label="Tahap Saat Ini" :value="$tahapLabel($ringkasan['tahap_saat_ini'])" />
        <x-stat-card color="amber" icon="fa-clipboard-list" label="Rekomendasi" :value="$ringkasan['rekomendasi_tahap'] ? 'Tahap '.$ringkasan['rekomendasi_tahap'] : 'Normal'" />
        <x-stat-card color="sky" icon="fa-folder-open" label="Jumlah Kasus" :value="$ringkasan['jumlah_kasus']" />
        <x-stat-card color="emerald" icon="fa-handshake" label="Pembinaan" :value="$ringkasan['jumlah_pembinaan']" />
    </div>

    @php
        $statusBoxClass = match ($ringkasan['status']) {
            'Normal' => 'bg-emerald-50 text-emerald-700 border border-emerald-100',
            'Dalam Pembinaan' => 'bg-amber-50 text-amber-700 border border-amber-100',
            'Selesai' => 'bg-teal-50 text-teal-700 border border-teal-100',
            default => 'bg-sky-50 text-sky-700 border border-sky-100', // Menunggu Pembinaan
        };
    @endphp
    <div class="rounded-xl px-4 py-3 text-sm {{ $statusBoxClass }}">
        Status: <b>{{ $ringkasan['status'] }}</b>
        <span class="text-xs opacity-70">
            (Total pelanggaran historis: +{{ $ringkasan['total_pelanggaran'] }}, total pengurangan: -{{ $ringkasan['total_pengurangan'] }})
        </span>
    </div>

    {{-- Timeline --}}
    <div class="card p-5" @if($timeline->isNotEmpty()) x-data="{ filter: 'semua' }" @endif>
        <div class="flex items-start justify-between flex-wrap gap-3 mb-4">
            <div>
                <p class="font-bold text-slate-800 mb-1">Riwayat Perkembangan</p>
                <p class="text-xs text-slate-400">Diurutkan dari catatan paling awal ke paling baru &middot; {{ $timeline->count() }} catatan.</p>
            </div>
            @if($timeline->isNotEmpty())
            <div class="flex flex-wrap gap-1.5">
                <button type="button" @click="filter = 'semua'"
                    :class="filter === 'semua' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                    class="text-xs font-semibold px-3 py-1.5 rounded-full transition">
                    Semua ({{ $timeline->count() }})
                </button>
                @foreach([
                    'kasus' => ['fa-folder-open', 'Kasus'],
                    'pembinaan' => ['fa-handshake', 'Pembinaan'],
                    'pengurangan' => ['fa-circle-check', 'Pengurangan'],
                    'pemanggilan' => ['fa-phone', 'Panggil Ortu'],
                ] as $jenisKey => [$icon, $label])
                    @php $jumlahJenis = $timeline->where('jenis', $jenisKey)->count(); @endphp
                    @if($jumlahJenis > 0)
                    <button type="button" @click="filter = '{{ $jenisKey }}'"
                        :class="filter === '{{ $jenisKey }}' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                        class="text-xs font-semibold px-3 py-1.5 rounded-full transition">
                        <i class="fa-solid {{ $icon }}"></i> {{ $label }} ({{ $jumlahJenis }})
                    </button>
                    @endif
                @endforeach
            </div>
            @endif
        </div>

        @if($timeline->isEmpty())
            <p class="text-sm text-slate-400 py-8 text-center">Belum ada riwayat.</p>
        @else
        <div class="space-y-0">
            @foreach($timeline as $item)
            @php
                $d = $item['data'];
                $tampilan = match ($item['jenis']) {
                    'kasus' => ['fa-folder-open', 'bg-rose-100 text-rose-600', 'Kasus/Pelanggaran'],
                    'pembinaan' => ['fa-handshake', 'bg-violet-100 text-violet-600', 'Pembinaan'],
                    'pengurangan' => ['fa-circle-check', 'bg-emerald-100 text-emerald-600', 'Pengurangan Poin'],
                    default => ['fa-phone', 'bg-sky-100 text-sky-600', 'Pemanggilan Orang Tua'],
                };
                [$ikon, $kelasIkon, $labelJenis] = $tampilan;
            @endphp
            <div x-show="filter === 'semua' || filter === '{{ $item['jenis'] }}'" x-cloak class="flex gap-3">
                {{-- Node ikon + garis penghubung --}}
                <div class="flex flex-col items-center shrink-0">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-base shrink-0 {{ $kelasIkon }}"><i class="fa-solid {{ $ikon }}"></i></div>
                    @if(!$loop->last)<div class="w-px flex-1 bg-slate-200 my-1" style="min-height: 0.5rem;"></div>@endif
                </div>

                {{-- Kartu isi --}}
                <div class="flex-1 rounded-xl bg-slate-50 border border-slate-100 p-3.5 mb-3">
                    <div class="flex items-center justify-between flex-wrap gap-x-3 gap-y-0.5 mb-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400">#{{ $loop->iteration }} &middot; {{ $labelJenis }}</span>
                        <span class="text-xs text-slate-400 whitespace-nowrap">
                            {{ $item['tanggal']->translatedFormat('d F Y') }}
                            <span class="text-slate-300">&middot; {{ $item['tanggal']->diffForHumans() }}</span>
                        </span>
                    </div>

                    @if($item['jenis'] === 'kasus')
                        <p class="font-semibold text-slate-800">
                            {{ $d->nama_pelanggaran }}
                            <span class="badge bg-rose-50 text-rose-700 ml-1">+{{ $d->poin }} poin</span>
                            @if($d->dibatalkan_at)<span class="badge bg-slate-100 text-slate-400 ml-1">Dibatalkan</span>@endif
                        </p>
                        <p class="text-sm text-slate-500">Kategori {{ $d->kategori }} &middot; Dilaporkan {{ $d->guruPelapor->name ?? '-' }}</p>
                        @if($d->bukti_catatan)<p class="text-xs text-slate-400 italic">Catatan: {{ $d->bukti_catatan }}</p>@endif
                        @if($d->bukti_file_url)
                            <a href="{{ $d->bukti_file_url }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline mt-1">
                                <i class="fa-solid fa-paperclip mr-1.5"></i> Lihat Bukti ({{ strtoupper(pathinfo($d->bukti_file, PATHINFO_EXTENSION)) }})
                            </a>
                        @endif
                        @if($d->dibatalkan_at)
                            <p class="text-xs text-slate-400 italic">Alasan batal: {{ $d->alasan_pembatalan }}</p>
                        @elseif($bisaKelolaPoin)
                            <div class="mt-1 flex items-center gap-2">
                                <span class="text-xs text-slate-400">Status:</span>
                                <form method="POST" action="{{ route('bk.kasus.update-status', $d) }}">
                                    @csrf @method('PATCH')
                                    <select name="status" onchange="this.form.submit()" class="text-xs rounded-lg border border-slate-200 px-2 py-1 bg-white">
                                        @foreach(['Baru','Diproses','Dalam Pembinaan','Selesai'] as $s)
                                            <option value="{{ $s }}" {{ $d->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-0.5">Menandai "Selesai" otomatis menyelesaikan pembinaan terkait juga.</p>
                        @else
                            <p class="text-sm text-slate-500">Status: {{ $d->status }}</p>
                        @endif

                    @elseif($item['jenis'] === 'pembinaan')
                        <p class="font-semibold text-slate-800">
                            {{ $d->jenis_pembinaan }}
                            <span class="badge bg-violet-50 text-violet-700 ml-1">Tahap {{ $d->tahap }}</span>
                        </p>
                        <p class="text-sm text-slate-500">{{ $d->catatan_bk }}</p>
                        @if($d->hasil_pembinaan)<p class="text-sm text-slate-500 italic">Hasil: {{ $d->hasil_pembinaan }}</p>@endif
                        @if($d->bukti_file_url)
                            <a href="{{ $d->bukti_file_url }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline mt-1">
                                <i class="fa-solid fa-paperclip mr-1.5"></i> Lihat Bukti ({{ strtoupper(pathinfo($d->bukti_file, PATHINFO_EXTENSION)) }})
                            </a>
                        @endif
                        <p class="text-xs text-slate-400 mt-1">Petugas: {{ $d->petugas->name ?? '-' }}</p>
                        @if($bisaKelolaPoin)
                            <div class="mt-1 flex items-center gap-2">
                                <span class="text-xs text-slate-400">Status:</span>
                                <form method="POST" action="{{ route('bk.pembinaan.update', $d) }}">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="hasil_pembinaan" value="{{ $d->hasil_pembinaan }}">
                                    <select name="status" onchange="this.form.submit()" class="text-xs rounded-lg border border-slate-200 px-2 py-1 bg-white">
                                        <option value="Pembinaan" {{ $d->status === 'Pembinaan' ? 'selected' : '' }}>Pembinaan</option>
                                        <option value="Selesai" {{ $d->status === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                                    </select>
                                </form>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-0.5">Menandai "Selesai" otomatis menyelesaikan kasus terkait juga.</p>
                        @else
                            <span class="badge bg-slate-100 text-slate-600 mt-1 inline-block">{{ $d->status }}</span>
                        @endif

                    @elseif($item['jenis'] === 'pengurangan')
                        <p class="font-semibold text-slate-800">
                            Perubahan Perilaku
                            <span class="badge bg-emerald-50 text-emerald-700 ml-1">-{{ $d->jumlah }} poin</span>
                            @if($d->dibatalkan_at)<span class="badge bg-slate-100 text-slate-400 ml-1">Dibatalkan</span>@endif
                        </p>
                        <p class="text-sm text-slate-500">{{ $d->alasan }}</p>
                        <p class="text-xs text-slate-400">Petugas: {{ $d->petugas->name ?? '-' }}</p>

                    @else
                        <p class="font-semibold text-slate-800">
                            Pemanggilan Orang Tua
                            <span class="badge {{ $d->ortu_hadir ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} ml-1">
                                {{ $d->ortu_hadir ? 'Hadir' : 'Tidak Hadir' }}
                            </span>
                        </p>
                        <p class="text-sm text-slate-500">{{ $d->alasan }}</p>
                        @if($d->hasil_pertemuan)<p class="text-sm text-slate-500 italic">Hasil: {{ $d->hasil_pertemuan }}</p>@endif
                        @if($d->bukti_file_url)
                            <a href="{{ $d->bukti_file_url }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline mt-1">
                                <i class="fa-solid fa-paperclip mr-1.5"></i> Lihat Bukti ({{ strtoupper(pathinfo($d->bukti_file, PATHINFO_EXTENSION)) }})
                            </a>
                        @endif
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ===== MODAL: Catat Pelanggaran ===== --}}
    @if($bisaLaporKasus)
    <div x-show="modal === 'kasus'" x-cloak class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" @keydown.escape.window="modal=null">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto" @click.outside="modal=null"
             x-data="{ jenisData: {{ $jenisList->map(fn($j) => ['id'=>$j->id,'nama'=>$j->nama,'kategori'=>$j->kategori,'poin'=>$j->poin_default])->values() }},
                       jenisId: '', nama: '', kategori: '', poin: '',
                       pilihJenis(id) {
                           const j = this.jenisData.find(x => x.id == id);
                           if (j) { this.nama = j.nama; this.kategori = j.kategori; this.poin = j.poin; }
                           else { this.nama = ''; this.kategori = ''; this.poin = ''; }
                       } }">
            <p class="font-bold text-lg text-slate-800 mb-4">Catat Pelanggaran — {{ $siswa->nama }}</p>
            <form method="POST" action="{{ route('bk.kasus.store') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="hidden" name="siswa_id" value="{{ $siswa->id }}">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Kejadian</label>
                    <input type="date" name="tanggal_kejadian" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required class="input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Jenis Pelanggaran</label>
                    <select x-model="jenisId" @change="pilihJenis(jenisId)" name="jenis_pelanggaran_id" required class="input">
                        <option value="">-- Pilih Jenis Pelanggaran --</option>
                        @foreach($jenisList as $j)
                            <option value="{{ $j->id }}">{{ $j->nama }} ({{ $j->kategori }}, {{ $j->poin_default }} poin)</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Nama Pelanggaran</label>
                    <input type="text" name="nama_pelanggaran" x-model="nama" required class="input" placeholder="Mis. Terlambat masuk kelas">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Kategori <span class="text-slate-300 font-normal">(otomatis)</span></label>
                        <div class="input bg-slate-50 text-slate-500 flex items-center" x-text="kategori || '-'"></div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Poin <span class="text-slate-300 font-normal">(otomatis)</span></label>
                        <div class="input bg-slate-50 text-slate-500 flex items-center font-bold" x-text="poin ? poin + ' poin' : '-'"></div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Kronologi <span class="text-rose-500">*</span></label>
                    <textarea name="kronologi" required minlength="10" rows="3" class="input" placeholder="Ceritakan kejadiannya... (wajib diisi)"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Catatan Pendukung (opsional)</label>
                    <textarea name="bukti_catatan" rows="2" class="input" placeholder="Boleh dikosongkan"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Upload Bukti — Foto/PDF (opsional)</label>
                    <input type="file" name="bukti_file" accept=".jpg,.jpeg,.png,.pdf" class="input">
                    <p class="text-xs text-slate-400 mt-1">Format JPG/PNG/PDF, maksimal 5MB. Boleh dikosongkan.</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="modal=null" class="btn-outline">Batal</button>
                    <button type="submit" class="btn-primary bg-rose-600 hover:bg-rose-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ===== MODAL: Catat Pembinaan ===== --}}
    @if($bisaKelolaPoin)
    <div x-show="modal === 'pembinaan'" x-cloak class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" @keydown.escape.window="modal=null">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto" @click.outside="modal=null" x-data="{ status: 'Pembinaan' }">
            <p class="font-bold text-lg text-slate-800 mb-1">Catat Pembinaan — {{ $siswa->nama }}</p>
            <p class="text-xs text-slate-400 mb-4">
                Tahap ditentukan otomatis oleh sistem dari poin aktif saat ini:
                <b class="text-violet-600">{{ $ringkasan['rekomendasi_tahap'] ? 'Tahap '.$ringkasan['rekomendasi_tahap'] : 'Tahap 1' }}</b>
            </p>
            <form method="POST" action="{{ route('bk.pembinaan.store') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="hidden" name="siswa_id" value="{{ $siswa->id }}">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Terkait Kasus (opsional)</label>
                    <select name="kasus_siswa_id" class="input">
                        <option value="">-- Tidak terkait kasus tertentu --</option>
                        @foreach($kasusAktifTerbuka as $k)
                            <option value="{{ $k->id }}">{{ $k->tanggal_kejadian->format('d/m/Y') }} — {{ $k->nama_pelanggaran }} (+{{ $k->poin }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal</label>
                        <input type="date" name="tanggal" value="{{ now()->toDateString() }}" required class="input">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Tahap <span class="text-slate-300 font-normal">(otomatis)</span></label>
                        <div class="input bg-slate-50 text-slate-500 flex items-center font-bold">
                            Tahap {{ $ringkasan['rekomendasi_tahap'] ?? 1 }}
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Jenis Pembinaan</label>
                    <select name="jenis_pembinaan" required class="input">
                        @foreach(['Teguran lisan','Teguran tertulis','Penugasan edukatif','Konseling individu','Kontrak perilaku','Pemanggilan orang tua','Pembinaan khusus','Ruang refleksi','Skorsing edukatif','Pembinaan lanjutan'] as $jp)
                            <option value="{{ $jp }}">{{ $jp }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Catatan BK</label>
                    <textarea name="catatan_bk" required rows="2" class="input"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Status</label>
                    <select name="status" x-model="status" required class="input">
                        <option value="Pembinaan">Pembinaan</option>
                        <option value="Selesai">Selesai</option>
                    </select>
                </div>
                <div x-show="status === 'Selesai'">
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Hasil Pembinaan</label>
                    <textarea name="hasil_pembinaan" rows="2" class="input"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Upload Bukti — Foto/PDF (opsional)</label>
                    <input type="file" name="bukti_file" accept=".jpg,.jpeg,.png,.pdf" class="input">
                    <p class="text-xs text-slate-400 mt-1">Format JPG/PNG/PDF, maksimal 5MB. Boleh dikosongkan.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Evaluasi Berikutnya (opsional)</label>
                    <input type="date" name="tanggal_evaluasi_berikutnya" class="input">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="modal=null" class="btn-outline">Batal</button>
                    <button type="submit" class="btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== MODAL: Kurangi Poin ===== --}}
    <div x-show="modal === 'pengurangan'" x-cloak class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" @keydown.escape.window="modal=null">
        <div class="bg-white rounded-2xl max-w-md w-full p-6" @click.outside="modal=null">
            <p class="font-bold text-lg text-slate-800 mb-1">Kurangi Poin — {{ $siswa->nama }}</p>
            <p class="text-xs text-slate-400 mb-4">Poin aktif saat ini: <b>{{ $ringkasan['poin_aktif'] }}</b> (maksimal pengurangan)</p>
            <form method="POST" action="{{ route('bk.pengurangan.store') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="siswa_id" value="{{ $siswa->id }}">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal</label>
                        <input type="date" name="tanggal" value="{{ now()->toDateString() }}" required class="input">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Jumlah Pengurangan</label>
                        <input type="number" name="jumlah" required min="1" max="{{ $ringkasan['poin_aktif'] }}" class="input">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Alasan</label>
                    <textarea name="alasan" required rows="2" class="input" placeholder="Mis. Menunjukkan perubahan perilaku konsisten selama 2 minggu"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Dasar/Rekomendasi (opsional)</label>
                    <textarea name="dasar_rekomendasi" rows="2" class="input"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Catatan (opsional)</label>
                    <textarea name="catatan" rows="2" class="input"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="modal=null" class="btn-outline">Batal</button>
                    <button type="submit" class="btn-primary bg-emerald-600 hover:bg-emerald-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== MODAL: Pemanggilan Orang Tua ===== --}}
    <div x-show="modal === 'pemanggilan'" x-cloak class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" @keydown.escape.window="modal=null">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto" @click.outside="modal=null" x-data="{ hadir: '1' }">
            <p class="font-bold text-lg text-slate-800 mb-4">Catat Pemanggilan Orang Tua — {{ $siswa->nama }}</p>
            <form method="POST" action="{{ route('bk.pemanggilan.store') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="hidden" name="siswa_id" value="{{ $siswa->id }}">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Terkait Kasus (opsional)</label>
                    <select name="kasus_siswa_id" class="input">
                        <option value="">-- Tidak terkait kasus tertentu --</option>
                        @foreach($kasusAktifTerbuka as $k)
                            <option value="{{ $k->id }}">{{ $k->tanggal_kejadian->format('d/m/Y') }} — {{ $k->nama_pelanggaran }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ now()->toDateString() }}" required class="input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Alasan Pemanggilan</label>
                    <textarea name="alasan" required rows="2" class="input"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Orang Tua/Wali Hadir?</label>
                    <select name="ortu_hadir" x-model="hadir" required class="input">
                        <option value="1">Ya, hadir</option>
                        <option value="0">Tidak hadir</option>
                    </select>
                </div>
                <div x-show="hadir === '1'">
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Hasil Pertemuan</label>
                    <textarea name="hasil_pertemuan" rows="2" class="input"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Upload Bukti — Foto/PDF (opsional)</label>
                    <input type="file" name="bukti_file" accept=".jpg,.jpeg,.png,.pdf" class="input">
                    <p class="text-xs text-slate-400 mt-1">Format JPG/PNG/PDF, maksimal 5MB. Boleh dikosongkan.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Kesepakatan (opsional)</label>
                    <textarea name="kesepakatan" rows="2" class="input"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="modal=null" class="btn-outline">Batal</button>
                    <button type="submit" class="btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
