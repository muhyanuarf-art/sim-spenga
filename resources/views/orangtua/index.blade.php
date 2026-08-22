@extends('layouts.app')
@section('title', 'Data Orang Tua')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama anak/NIS..." class="input max-w-xs">
            <select name="kelas_id" class="input max-w-[160px]" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($kelasList as $k)<option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>@endforeach
            </select>
            <button class="btn-outline">Cari</button>
        </form>
        <div class="flex gap-2">
            @if($jumlahSiswaBelumPunyaAkun > 0)
                <form method="POST" action="{{ route('orangtua-akun.generate') }}" onsubmit="return confirm('Buat akun orang tua untuk {{ $jumlahSiswaBelumPunyaAkun }} siswa yang belum punya akun? Password default: password.');">
                    @csrf
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-wand-magic-sparkles mr-1.5"></i> Buat Akun Otomatis ({{ $jumlahSiswaBelumPunyaAkun }} Siswa)</button>
                </form>
            @endif
        </div>
    </div>

    @if($jumlahSiswaBelumPunyaAkun > 0)
        <div class="card p-4 bg-amber-50 border border-amber-200 text-amber-800 text-sm">
            <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Ada <strong>{{ $jumlahSiswaBelumPunyaAkun }}</strong> siswa aktif yang belum punya akun orang tua.
            Klik <strong>"Buat Akun Otomatis"</strong> di atas — akunnya dibuat langsung dari data siswa yang sudah diinput di menu Data Siswa (tidak perlu upload file).
        </div>
    @endif

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>NIS</th><th>Nama Siswa</th><th>Kelas</th><th>Password Diganti?</th><th>Dibuat</th><th class="th-aksi">Aksi</th></tr></thead>
                <tbody>
                @forelse($akunOrtu as $akun)
                    <tr>
                        <td>{{ $akun->nis }}</td>
                        <td class="font-medium">{{ $akun->siswa->nama ?? '-' }}</td>
                        <td>{{ $akun->siswa->kelas->nama_kelas ?? '-' }}</td>
                        <td>
                            @if($akun->password_diubah_at)
                                <span class="badge bg-emerald-50 text-emerald-700"><i class="fa-solid fa-check mr-1.5"></i> Sudah diganti</span>
                            @else
                                <span class="badge bg-slate-100 text-slate-400">Masih default</span>
                            @endif
                        </td>
                        <td class="text-slate-400 text-sm">{{ $akun->created_at->format('d/m/Y') }}</td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <form method="POST" action="{{ route('orangtua-akun.reset-password', $akun) }}" onsubmit="return confirm('Reset password akun ini ke default (password)?');">
                                    @csrf
                                    <button class="btn-chip btn-chip-edit"><i class="fa-solid fa-key mr-1.5"></i> Reset Password</button>
                                </form>
                                <form method="POST" action="{{ route('orangtua-akun.destroy', $akun) }}" onsubmit="return confirm('Hapus akun orang tua ini?');">
                                    @csrf @method('DELETE')
                                    <button class="btn-chip btn-chip-delete"><i class="fa-solid fa-trash mr-1.5"></i> Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-400 py-6">Belum ada akun orang tua. Klik "Buat Akun Otomatis" untuk membuatnya dari data siswa.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $akunOrtu->links() }}</div>
    </div>
</div>
@endsection
