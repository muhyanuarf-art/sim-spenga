import 'package:flutter/material.dart';

import 'layanan.dart';
import 'layar_web.dart';

/// Layar masuk NATIVE.
///
/// Sengaja bukan halaman web: di sinilah pembatasan peran terasa jelas
/// bagi pengguna (Admin ditolak dengan kalimat yang menjelaskan alasannya,
/// bukan sekadar dilempar balik), dan di sinilah alamat server bisa
/// diperbaiki tanpa perlu masuk ke aplikasi lebih dulu.
///
/// Yang MENEGAKKAN pembatasan itu tetap server — lihat
/// App\Http\Controllers\AplikasiMobileController. Layar ini hanya
/// menyampaikan jawabannya dengan enak dibaca.
class LayarMasuk extends StatefulWidget {
  const LayarMasuk({super.key});

  @override
  State<LayarMasuk> createState() => _LayarMasukState();
}

class _LayarMasukState extends State<LayarMasuk> {
  final _emailC = TextEditingController();
  final _sandiC = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  bool _sedangMasuk = false;
  bool _ingatEmail = true;
  bool _sandiTerlihat = false;
  String? _pesanGalat;
  String _alamatServer = '';

  @override
  void initState() {
    super.initState();
    _muatAwal();
  }

  Future<void> _muatAwal() async {
    final alamat = await Layanan.alamatServer();
    final email = await Layanan.emailTerakhir();
    if (!mounted) return;
    setState(() {
      _alamatServer = alamat;
      if (email != null) _emailC.text = email;
      _ingatEmail = email != null;
    });
  }

  @override
  void dispose() {
    _emailC.dispose();
    _sandiC.dispose();
    super.dispose();
  }

  Future<void> _masuk() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _sedangMasuk = true;
      _pesanGalat = null;
    });

    final hasil = await Layanan.masuk(_emailC.text.trim(), _sandiC.text);

    if (!mounted) return;

    if (!hasil.berhasil) {
      setState(() {
        _sedangMasuk = false;
        _pesanGalat = hasil.pesan;
      });
      return;
    }

    await Layanan.simpanEmail(_ingatEmail ? _emailC.text.trim() : null);

    if (!mounted) return;

    // Kata sandi dibuang dari ingatan begitu tidak diperlukan lagi.
    _sandiC.clear();

    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => LayarWeb(
          urlMasuk: hasil.urlMasuk!,
          nama: hasil.nama ?? '',
          peran: hasil.peran ?? '',
        ),
      ),
    );

    if (!mounted) return;
    setState(() => _sedangMasuk = false);
  }

  Future<void> _gantiAlamatServer() async {
    final c = TextEditingController(text: _alamatServer);

    final baru = await showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Alamat Server'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Alamat komputer server sekolah. Tanyakan kepada Admin bila '
              'Anda tidak yakin.',
              style: TextStyle(fontSize: 13),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: c,
              autofocus: true,
              keyboardType: TextInputType.url,
              decoration: const InputDecoration(
                hintText: 'contoh: 192.168.1.10',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, c.text),
            child: const Text('Simpan'),
          ),
        ],
      ),
    );

    if (baru == null || baru.trim().isEmpty) return;

    await Layanan.simpanAlamatServer(baru);
    final tersimpan = await Layanan.alamatServer();
    if (!mounted) return;
    setState(() {
      _alamatServer = tersimpan;
      _pesanGalat = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0F172A),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const _Kepala(),
                  const SizedBox(height: 24),
                  Card(
                    elevation: 0,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                    child: Padding(
                      padding: const EdgeInsets.all(22),
                      child: Form(
                        key: _formKey,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            const Text(
                              'Masuk',
                              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(height: 4),
                            const Text(
                              'Gunakan akun yang sama seperti di komputer.',
                              style: TextStyle(fontSize: 14, color: Color(0xFF64748B)),
                            ),
                            const SizedBox(height: 20),

                            TextFormField(
                              controller: _emailC,
                              enabled: !_sedangMasuk,
                              keyboardType: TextInputType.emailAddress,
                              textInputAction: TextInputAction.next,
                              style: const TextStyle(fontSize: 16),
                              decoration: const InputDecoration(
                                labelText: 'Email atau NIP',
                                prefixIcon: Icon(Icons.person_outline),
                                border: OutlineInputBorder(),
                              ),
                              validator: (v) =>
                                  (v == null || v.trim().isEmpty) ? 'Email atau NIP belum diisi.' : null,
                            ),
                            const SizedBox(height: 14),

                            TextFormField(
                              controller: _sandiC,
                              enabled: !_sedangMasuk,
                              obscureText: !_sandiTerlihat,
                              textInputAction: TextInputAction.done,
                              onFieldSubmitted: (_) => _sedangMasuk ? null : _masuk(),
                              style: const TextStyle(fontSize: 16),
                              decoration: InputDecoration(
                                labelText: 'Kata Sandi',
                                prefixIcon: const Icon(Icons.lock_outline),
                                border: const OutlineInputBorder(),
                                suffixIcon: IconButton(
                                  icon: Icon(_sandiTerlihat ? Icons.visibility_off : Icons.visibility),
                                  tooltip: _sandiTerlihat ? 'Sembunyikan' : 'Tampilkan',
                                  onPressed: () => setState(() => _sandiTerlihat = !_sandiTerlihat),
                                ),
                              ),
                              validator: (v) =>
                                  (v == null || v.isEmpty) ? 'Kata sandi belum diisi.' : null,
                            ),

                            CheckboxListTile(
                              value: _ingatEmail,
                              onChanged: _sedangMasuk ? null : (v) => setState(() => _ingatEmail = v ?? false),
                              title: const Text('Ingat email saya', style: TextStyle(fontSize: 14)),
                              subtitle: const Text(
                                'Kata sandi tidak pernah disimpan.',
                                style: TextStyle(fontSize: 12),
                              ),
                              controlAffinity: ListTileControlAffinity.leading,
                              contentPadding: EdgeInsets.zero,
                              dense: true,
                            ),

                            if (_pesanGalat != null) ...[
                              const SizedBox(height: 4),
                              Container(
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFFEF2F2),
                                  border: Border.all(color: const Color(0xFFFECACA)),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const Icon(Icons.error_outline, color: Color(0xFFDC2626), size: 20),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      child: Text(
                                        _pesanGalat!,
                                        style: const TextStyle(color: Color(0xFFB91C1C), fontSize: 14, height: 1.4),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],

                            const SizedBox(height: 16),
                            SizedBox(
                              height: 52,
                              child: FilledButton(
                                onPressed: _sedangMasuk ? null : _masuk,
                                child: _sedangMasuk
                                    ? const SizedBox(
                                        width: 22, height: 22,
                                        child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                                      )
                                    : const Text('Masuk', style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold)),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),

                  const SizedBox(height: 16),
                  TextButton.icon(
                    onPressed: _sedangMasuk ? null : _gantiAlamatServer,
                    icon: const Icon(Icons.dns_outlined, size: 18, color: Colors.white70),
                    label: Text(
                      'Server: $_alamatServer',
                      style: const TextStyle(color: Colors.white70, fontSize: 13),
                    ),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'Akun Admin dan portal orang tua tidak dapat masuk lewat aplikasi ini.',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Colors.white38, fontSize: 12, height: 1.5),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _Kepala extends StatelessWidget {
  const _Kepala();

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Container(
          width: 64, height: 64,
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(20),
          ),
          child: const Icon(Icons.school_outlined, color: Colors.white, size: 32),
        ),
        const SizedBox(height: 14),
        const Text(
          'SIM-SPENGA',
          style: TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.w800, letterSpacing: 0.5),
        ),
        const SizedBox(height: 2),
        const Text(
          'SMP Negeri 3 Bumiayu',
          style: TextStyle(color: Colors.white70, fontSize: 14),
        ),
      ],
    );
  }
}
