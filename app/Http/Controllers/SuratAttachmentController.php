<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use App\Models\SuratActivity;
use App\Models\SuratAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SuratAttachmentController extends Controller
{
    public function store(Request $request, Surat $surat)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:5120'], // maks 5MB
        ]);

        $file = $validated['file'];
        $path = $file->store('surat-lampiran', 'public');

        SuratAttachment::create([
            'surat_id' => $surat->id,
            'nama_file' => $file->getClientOriginalName(),
            'path' => $path,
            'tipe_file' => $file->getClientMimeType(),
            'ukuran' => $file->getSize(),
            'user_id' => $request->user()->id,
        ]);
        SuratActivity::catat($surat, 'Lampiran diunggah', $file->getClientOriginalName());

        return back()->with('success', 'Lampiran berhasil diunggah.');
    }

    public function destroy(SuratAttachment $lampiran)
    {
        $surat = $lampiran->surat;
        $nama = $lampiran->nama_file;

        Storage::disk('public')->delete($lampiran->path);
        $lampiran->delete();
        SuratActivity::catat($surat, 'Lampiran dihapus', $nama);

        return back()->with('success', 'Lampiran berhasil dihapus.');
    }
}
