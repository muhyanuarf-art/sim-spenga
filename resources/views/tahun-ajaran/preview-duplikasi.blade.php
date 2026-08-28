@extends('layouts.app')
@section('title', 'Preview Salin Data')

@section('content')
@php
    use App\Support\SalinDataPeriode;

    $tahunSama = $sumber->nama === $tujuan->nama;
    $totalDisalin = collect($rencana)->sum(fn ($r) => count($r['disalin']));
@endphp

<div class="max-w-3xl mx-auto space-y-6">
    <div class="card p-6">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Preview Salin Data</p>
        <p class="text-lg font-bold text-slate-800 mb-1">
            {{ $sumber->labelPeriode() }} <span class="text-slate-400">&rarr;</span> {{ $tujuan->labelPeriode() }}
        </p>
        <p class="text-sm text-slate-500">
            Belum ada perubahan yang tersimpan. Periksa daftar di bawah, lalu klik
            "Salin Sekarang" untuk benar-benar menyimpan, atau "Batal" untuk kembali.
        </p>

        @if($totalDisalin > 0)
        <p class="mt-3 rounded-lg bg-emerald-50 text-emerald-700 px-3 py-2 text-sm">
            <i class="fa-solid fa-circle-check mr-1.5"></i>
            Total <span class="font-bold">{{ $totalDisalin }}</span> baris baru akan dibuat di {{ $tujuan->labelPeriode() }}.
        </p>
        @else
        <p class="mt-3 rounded-lg bg-slate-100 text-slate-500 px-3 py-2 text-sm">
            Tidak ada yang perlu disalin — semuanya sudah ada di {{ $tujuan->labelPeriode() }}.
        </p>
        @endif
    </div>

    @foreach(SalinDataPeriode::KATEGORI as $kunci => $judul)
        @php
            $disalin = $rencana[$kunci]['disalin'] ?? [];
            $sudahAda = $rencana[$kunci]['sudah_ada'] ?? [];
            // Master data & kelas melekat pada TAHUN ajaran, bukan semester:
            // menyalin antar semester dalam tahun yang sama tidak ada gunanya.
            $tidakPerlu = $tahunSama && in_array($kunci, SalinDataPeriode::KATEGORI_PER_TAHUN, true);
        @endphp

        <div class="card p-6">
            <p class="font-bold text-slate-800 mb-3">{{ $judul }}</p>

            @if($tidakPerlu)
                <p class="text-sm text-slate-500">
                    {{ $sumber->nama }} sama dengan {{ $tujuan->nama }} (hanya beda semester) — {{ mb_strtolower($judul) }}
                    berlaku untuk satu tahun ajaran penuh, jadi memang sudah sama dan tidak perlu disalin.
                </p>
            @else
                <div class="grid sm:grid-cols-2 gap-3 mb-4 text-sm">
                    <div class="rounded-lg bg-emerald-50 text-emerald-700 px-3 py-2">
                        <span class="font-bold">{{ count($disalin) }}</span> akan disalin
                    </div>
                    <div class="rounded-lg bg-slate-100 text-slate-500 px-3 py-2">
                        <span class="font-bold">{{ count($sudahAda) }}</span> sudah ada di tujuan (dilewati)
                    </div>
                </div>

                @if(count($disalin) > 0)
                    <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Akan disalin:</p>
                    <ol class="text-sm text-slate-700 list-decimal list-inside space-y-0.5 mb-2 max-h-48 overflow-y-auto">
                        @foreach($disalin as $item)
                            <li>
                                {{ $item['label'] }}
                                @if($item['catatan'])
                                    <span class="text-xs text-amber-600">({{ $item['catatan'] }})</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif

                @if(count($disalin) === 0 && count($sudahAda) === 0)
                    <p class="text-sm text-slate-400">Tidak ada data {{ mb_strtolower($judul) }} di {{ $sumber->labelPeriode() }} untuk disalin.</p>
                @endif

                @if($kunci === 'kelas' && count($disalin) > 0)
                    <p class="text-xs text-amber-600">
                        <i class="fa-solid fa-triangle-exclamation mr-1.5"></i>
                        Wali kelas disalin sebagai titik awal — sesuaikan lagi di menu Data Kelas kalau ada pergantian.
                    </p>
                @endif

                @if($kunci === 'ekskul' && count($disalin) > 0)
                    <p class="text-xs text-amber-600">
                        <i class="fa-solid fa-triangle-exclamation mr-1.5"></i>
                        Pembina ikut disalin, <strong>daftar anggotanya tidak</strong> — saat ini siswa {{ $tujuan->nama }}
                        biasanya belum diimpor. Isi anggotanya lewat menu Ekstrakurikuler setelah Data Siswa lengkap.
                    </p>
                @endif
            @endif
        </div>
    @endforeach

    <div class="card p-6 bg-slate-50">
        <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Yang tidak ikut disalin</p>
        <ul class="text-sm text-slate-600 list-disc list-inside space-y-1">
            <li><strong>Data Siswa</strong> — dipindahkan lewat Import Excel Data Siswa dengan kode kelas barunya, supaya riwayat kelas tiap siswa ikut tercatat.</li>
            <li><strong>Anggota ekstrakurikuler</strong> — diisi ulang setelah data siswa periode baru lengkap.</li>
            <li><strong>Seluruh data transaksi</strong> (jurnal, absensi, nilai, kasus BK, surat) — itu catatan kejadian milik periodenya sendiri dan tetap tersimpan di sana.</li>
            <li><strong>Pengguna &amp; Pengaturan Sekolah</strong> — berlaku untuk seluruh periode, tidak pernah perlu disalin.</li>
        </ul>
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ route('tahun-ajaran.index') }}" class="btn-outline">Batal</a>
        <form method="POST" action="{{ route('tahun-ajaran.duplikasi', $tujuan) }}">
            @csrf
            <input type="hidden" name="dari_tahun_ajaran_id" value="{{ $sumber->id }}">
            <button type="submit" class="btn-primary" {{ $totalDisalin === 0 ? 'disabled' : '' }}>
                Salin Sekarang
            </button>
        </form>
    </div>
</div>
@endsection
