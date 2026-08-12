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
        <x-stat-card color="rose" icon="⚖️" label="Poin Aktif" :value="$ringkasan['poin_aktif']" />
        <x-stat-card color="violet" icon="🧭" label="Tahap Saat Ini" :value="$tahapLabel($ringkasan['tahap_saat_ini'])" />
        <x-stat-card color="amber" icon="📋" label="Rekomendasi" :value="$ringkasan['rekomendasi_tahap'] ? 'Tahap '.$ringkasan['rekomendasi_tahap'] : 'Normal'" />
        <x-stat-card color="sky" icon="📁" label="Jumlah Kasus" :value="$ringkasan['jumlah_kasus']" />
        <x-stat-card color="emerald" icon="🤝" label="Pembinaan" :value="$ringkasan['jumlah_pembinaan']" />
    </div>

    <div class="rounded-xl px-4 py-3 text-sm
        {{ $ringkasan['status'] === 'Normal' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : ($ringkasan['status'] === 'Dalam Pembinaan' ? 'bg-amber-50 text-amber-700 border border-amber-100' : 'bg-sky-50 text-sky-700 border border-sky-100') }}">
        Status: <b>{{ $ringkasan['status'] }}</b>
        <span class="text-xs opacity-70">
            (Total pelanggaran historis: +{{ $ringkasan['total_pelanggaran'] }}, total pengurangan: -{{ $ringkasan['total_pengurangan'] }})
        </span>
    </div>

    {{-- Timeline --}}
    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">Riwayat Perkembangan</p>
        @if($timeline->isEmpty())
            <p class="text-sm text-slate-400 py-8 text-center">Belum ada riwayat.</p>
        @else
        <div class="space-y-3">
            @foreach($timeline as $item)
            @php $d = $item['data']; @endphp
            <div class="flex gap-3 border-l-2 pl-4 pb-3
                {{ $item['jenis'] === 'kasus' ? 'border-rose-200' : ($item['jenis'] === 'pengurangan' ? 'border-emerald-200' : ($item['jenis'] === 'pembinaan' ? 'border-violet-200' : 'border-sky-200')) }}">
                <div class="flex-1">
                    <p class="text-xs text-slate-400">{{ $item['tanggal']->translatedFormat('d F Y') }}</p>

                    @if($item['jenis'] === 'kasus')
                        <p class="font-semibold text-slate-800">
                            {{ $d->nama_pelanggaran }}
                            <span class="badge bg-rose-50 text-rose-700 ml-1">+{{ $d->poin }} poin</span>
                            @if($d->dibatalkan_at)<span class="badge bg-slate-100 text-slate-400 ml-1">Dibatalkan</span>@endif
                        </p>
                        <p class="text-sm text-slate-500">Kategori {{ $d->kategori }} &middot; Dilaporkan {{ $d->guruPelapor->name ?? '-' }} &middot; Status: {{ $d->status }}</p>
                        @if($d->dibatalkan_at)<p class="text-xs text-slate-400 italic">Alasan batal: {{ $d->alasan_pembatalan }}</p>@endif

                    @elseif($item['jenis'] === 'pembinaan')
                        <p class="font-semibold text-slate-800">
                            {{ $d->jenis_pembinaan }}
                            <span class="badge bg-violet-50 text-violet-700 ml-1">Tahap {{ $d->tahap }}</span>
                            <span class="badge bg-slate-100 text-slate-600 ml-1">{{ $d->status }}</span>
                        </p>
                        <p class="text-sm text-slate-500">{{ $d->catatan_bk }}</p>
                        @if($d->hasil_pembinaan)<p class="text-sm text-slate-500 italic">Hasil: {{ $d->hasil_pembinaan }}</p>@endif
                        <p class="text-xs text-slate-400">Petugas: {{ $d->petugas->name ?? '-' }}</p>

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
                       } }">
            <p class="font-bold text-lg text-slate-800 mb-4">Catat Pelanggaran — {{ $siswa->nama }}</p>
            <form method="POST" action="{{ route('bk.kasus.store') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="siswa_id" value="{{ $siswa->id }}">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Kejadian</label>
                    <input type="date" name="tanggal_kejadian" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required class="input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Jenis Pelanggaran (dari master, opsional)</label>
                    <select x-model="jenisId" @change="pilihJenis(jenisId)" name="jenis_pelanggaran_id" class="input">
                        <option value="">-- Pilih dari master / isi manual di bawah --</option>
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
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Kategori</label>
                        <select name="kategori" x-model="kategori" required class="input">
                            <option value="">Pilih</option>
                            @foreach($rentangKategori as $kat => [$min,$max])
                                <option value="{{ $kat }}">{{ $kat }} ({{ $min }}-{{ $max }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Poin</label>
                        <input type="number" name="poin" x-model="poin" required min="1" max="100" class="input">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Kronologi</label>
                    <textarea name="kronologi" required rows="3" class="input" placeholder="Ceritakan kejadiannya..."></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Bukti/Catatan Pendukung (opsional)</label>
                    <textarea name="bukti_catatan" rows="2" class="input"></textarea>
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
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto" @click.outside="modal=null" x-data="{ status: 'Direncanakan' }">
            <p class="font-bold text-lg text-slate-800 mb-1">Catat Pembinaan — {{ $siswa->nama }}</p>
            <p class="text-xs text-slate-400 mb-4">Rekomendasi sistem berdasarkan poin aktif: <b>{{ $ringkasan['rekomendasi_tahap'] ? 'Tahap '.$ringkasan['rekomendasi_tahap'] : '-' }}</b> (keputusan akhir tetap milik BK)</p>
            <form method="POST" action="{{ route('bk.pembinaan.store') }}" class="space-y-3">
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
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Tahap</label>
                        <select name="tahap" required class="input">
                            @for($t = 1; $t <= 7; $t++)
                                <option value="{{ $t }}" {{ $t == ($ringkasan['rekomendasi_tahap'] ?? 1) ? 'selected' : '' }}>Tahap {{ $t }}</option>
                            @endfor
                        </select>
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
                        <option value="Direncanakan">Direncanakan</option>
                        <option value="Berlangsung">Berlangsung</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Tidak Berhasil">Tidak Berhasil</option>
                    </select>
                </div>
                <div x-show="status === 'Selesai' || status === 'Tidak Berhasil'">
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Hasil Pembinaan</label>
                    <textarea name="hasil_pembinaan" rows="2" class="input"></textarea>
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
            <form method="POST" action="{{ route('bk.pemanggilan.store') }}" class="space-y-3">
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
