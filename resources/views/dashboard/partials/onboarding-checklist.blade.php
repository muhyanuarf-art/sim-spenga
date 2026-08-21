@php
    $selesaiSemua = $checklistOnboarding['selesai_semua'];
@endphp

<div class="card p-5 {{ $selesaiSemua ? 'border-emerald-200' : '' }}">
    <div class="flex items-center justify-between gap-3 mb-4">
        <div>
            <p class="font-bold text-slate-800">Hal yang Perlu Dilakukan</p>
            <p class="text-sm text-slate-400 mt-0.5">Checklist setup tahun ajaran — Pengaturan Sekolah s.d. Import Akun Orang Tua.</p>
        </div>
        <span class="badge shrink-0 {{ $selesaiSemua ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
            {{ $checklistOnboarding['jumlah_selesai'] }} / {{ $checklistOnboarding['jumlah_total'] }} selesai
        </span>
    </div>

    <ol class="space-y-2">
        @foreach($checklistOnboarding['items'] as $i => $item)
            <li>
                <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 border transition
                    {{ $item['selesai'] ? 'bg-emerald-50 border-emerald-100' : 'bg-slate-50 border-slate-100' }}">

                    <span class="w-5 h-5 rounded-md flex items-center justify-center text-xs shrink-0
                        {{ $item['selesai'] ? 'bg-emerald-500 text-white' : 'bg-white border border-slate-300' }}">
                        @if($item['selesai'])
                            ✓
                        @endif
                    </span>

                    @if($item['route'])
                        <a href="{{ $item['route'] }}"
                           class="text-sm font-medium hover:underline {{ $item['selesai'] ? 'text-emerald-700' : 'text-slate-600' }}">
                            {{ $i + 1 }}. {{ $item['label'] }}
                        </a>
                    @else
                        <span class="text-sm font-medium {{ $item['selesai'] ? 'text-emerald-700' : 'text-slate-600' }}">
                            {{ $i + 1 }}. {{ $item['label'] }}
                        </span>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</div>
