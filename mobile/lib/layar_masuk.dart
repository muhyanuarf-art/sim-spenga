import 'dart:async';

import 'package:flutter/material.dart';

import 'biometrik.dart';
import 'identitas_sekolah.dart';
import 'layanan.dart';
import 'layar_lupa_sandi.dart';
import 'layar_web.dart';

/// Layar masuk NATIVE.
///
/// Sengaja bukan halaman web: di sinilah pembatasan peran terasa jelas
/// bagi pengguna (Admin ditolak dengan kalimat yang menjelaskan alasannya,
/// bukan sekadar dilempar balik), di sinilah alamat server bisa diperbaiki
/// tanpa perlu masuk lebih dulu, dan di sinilah sidik jari bisa dipakai —
/// tiga hal yang tidak mungkin dikerjakan halaman web.
///
/// Yang MENEGAKKAN pembatasan itu tetap server — lihat
/// App\Http\Controllers\AplikasiMobileController. Layar ini hanya
/// menyampaikan jawabannya dengan enak dibaca.
///
/// TAMPILANNYA sengaja dibuat sama persis dengan halaman login web
/// (resources/views/auth/login.blade.php): gradien brand-900 → brand-600,
/// kartu putih melengkung, isian bergaya .input, tombol .btn-primary.
/// Pengguna berpindah antara ponsel dan komputer sepanjang hari; dua
/// wajah yang berbeda untuk pintu masuk yang sama hanya membingungkan.
class LayarMasuk extends StatefulWidget {
  const LayarMasuk({super.key});

  @override
  State<LayarMasuk> createState() => _LayarMasukState();
}

class _LayarMasukState extends State<LayarMasuk> {
  // Palet diambil dari tailwind.config.js supaya benar-benar sewarna.
  static const _brand900 = Color(0xFF193C8C);
  static const _brand800 = Color(0xFF1844B3);
  static const _brand600 = Color(0xFF1C68F2);
  static const _brand500 = Color(0xFF3388FD);
  static const _slate800 = Color(0xFF1E293B);
  static const _slate500 = Color(0xFF64748B);
  static const _slate400 = Color(0xFF94A3B8);
  static const _garis = Color(0xFFDBE2EE);

  final _emailC = TextEditingController();
  final _sandiC = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  bool _sedangMasuk = false;
  bool _ingatEmail = true;
  bool _sandiTerlihat = false;
  String? _pesanGalat;
  String _alamatServer = '';

  bool _biometrikTersedia = false;
  bool _biometrikAktif = false;

  IdentitasSekolah? _sekolah;

  @override
  void initState() {
    super.initState();
    _muatAwal();
  }

  Future<void> _muatAwal() async {
    final alamat = await Layanan.alamatServer();
    final email = await Layanan.emailTerakhir();
    final tersedia = await Biometrik.tersedia();
    final aktif = await Biometrik.aktif();
    final sekolah = await IdentitasSekolah.tersimpan();

    if (!mounted) return;
    setState(() {
      _alamatServer = alamat;
      if (email != null) _emailC.text = email;
      _ingatEmail = email != null;
      _biometrikTersedia = tersedia;
      _biometrikAktif = aktif;
      _sekolah = sekolah;
    });

    // Identitas sekolah diperbarui diam-diam di latar: layar masuk tidak
    // boleh menunggu jaringan hanya demi sebuah logo.
    unawaited(_segarkanIdentitas());

    // Kalau sudah dinyalakan, langsung tawarkan sidik jari begitu aplikasi
    // dibuka — itulah gunanya. Pengguna tetap bisa membatalkan dan
    // mengetik kata sandi seperti biasa.
    if (tersedia && aktif) {
      await _masukBiometrik();
    }
  }

  /// Tanya server siapa dirinya. Gagal pun tidak apa-apa: yang tersimpan
  /// tetap terpakai, dan layar masuk tidak menampilkan galat karenanya.
  Future<void> _segarkanIdentitas() async {
    final baru = await IdentitasSekolah.ambilDariServer();
    if (baru == null || !mounted) return;
    setState(() => _sekolah = baru);
  }

  @override
  void dispose() {
    _emailC.dispose();
    _sandiC.dispose();
    super.dispose();
  }

  /// Masuk memakai kredensial yang dijaga sidik jari.
  Future<void> _masukBiometrik() async {
    if (_sedangMasuk) return;

    final lolos = await Biometrik.minta('Buktikan diri Anda untuk masuk.');
    if (!lolos || !mounted) return;

    final kredensial = await Biometrik.ambil();
    if (kredensial == null) {
      // Brankas kosong padahal ditandai aktif — rapikan keadaannya.
      await Biometrik.lupakan();
      if (!mounted) return;
      setState(() => _biometrikAktif = false);
      return;
    }

    setState(() {
      _sedangMasuk = true;
      _pesanGalat = null;
    });

    final hasil = await Layanan.masuk(kredensial.email, kredensial.sandi);
    if (!mounted) return;

    if (!hasil.berhasil) {
      // Kata sandi yang tersimpan sudah basi (mis. diganti Admin).
      // Menyimpannya lebih lama hanya akan gagal berulang kali.
      await Biometrik.lupakan();
      if (!mounted) return;
      setState(() {
        _sedangMasuk = false;
        _biometrikAktif = false;
        _pesanGalat = '${hasil.pesan}\n\nMasuk dengan sidik jari dimatikan. '
            'Silakan masuk dengan kata sandi, lalu nyalakan lagi.';
      });
      return;
    }

    await _lanjutKeAplikasi(hasil);
  }

  Future<void> _masuk() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _sedangMasuk = true;
      _pesanGalat = null;
    });

    final email = _emailC.text.trim();
    final sandi = _sandiC.text;
    final hasil = await Layanan.masuk(email, sandi);

    if (!mounted) return;

    if (!hasil.berhasil) {
      setState(() {
        _sedangMasuk = false;
        _pesanGalat = hasil.pesan;
      });
      return;
    }

    await Layanan.simpanEmail(_ingatEmail ? email : null);
    if (!mounted) return;

    // Tawarkan sidik jari HANYA sesudah kata sandinya terbukti benar —
    // menyimpan kredensial yang salah tidak ada gunanya.
    if (_biometrikTersedia && !_biometrikAktif) {
      final mau = await _tanyaAktifkanBiometrik();
      if (mau) {
        await Biometrik.simpan(email, sandi);
        if (mounted) setState(() => _biometrikAktif = true);
      }
    }

    if (!mounted) return;
    await _lanjutKeAplikasi(hasil);
  }

  Future<void> _lanjutKeAplikasi(HasilMasuk hasil) async {
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

  Future<bool> _tanyaAktifkanBiometrik() async {
    final jawab = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        icon: const Icon(Icons.fingerprint, size: 40, color: _brand600),
        title: const Text('Masuk dengan sidik jari?'),
        content: const Text(
          'Lain kali Anda cukup menempelkan sidik jari, tanpa mengetik kata '
          'sandi.\n\nKata sandi disimpan terkunci di ponsel ini saja, dan '
          'hanya terbuka oleh sidik jari Anda.',
          style: TextStyle(fontSize: 14, height: 1.5),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Nanti saja'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Nyalakan'),
          ),
        ],
      ),
    );

    return jawab ?? false;
  }

  /// Buka alur lupa kata sandi. Sidik jari ikut dicabut di sana, karena
  /// kredensial tersimpannya menjadi basi begitu kata sandinya diganti —
  /// itulah sebabnya tombol "Lupakan sidik jari" yang berdiri sendiri
  /// tidak lagi diperlukan di layar ini.
  Future<void> _bukaLupaSandi() async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => LayarLupaSandi(emailAwal: _emailC.text.trim()),
      ),
    );

    if (!mounted) return;

    // Kembali dari sana, sidik jari mungkin sudah dicabut.
    final aktif = await Biometrik.aktif();
    if (!mounted) return;
    setState(() => _biometrikAktif = aktif);
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

    // Alamat baru bisa berarti sekolah yang berbeda.
    unawaited(_segarkanIdentitas());
  }

  /// Isian bergaya .input di app.css: tepi #dbe2ee, sudut membulat,
  /// dan cincin biru tipis saat difokus.
  InputDecoration _gayaIsian({required String petunjuk, Widget? akhiran}) {
    OutlineInputBorder tepi(Color warna, double tebal) => OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: warna, width: tebal),
        );

    return InputDecoration(
      hintText: petunjuk,
      hintStyle: const TextStyle(color: _slate400, fontSize: 14.5),
      suffixIcon: akhiran,
      filled: true,
      fillColor: Colors.white,
      isDense: true,
      contentPadding: const EdgeInsets.symmetric(horizontal: 13, vertical: 14),
      border: tepi(_garis, 1),
      enabledBorder: tepi(_garis, 1),
      focusedBorder: tepi(_brand500, 1.6),
      errorBorder: tepi(const Color(0xFFE0392F), 1),
      focusedErrorBorder: tepi(const Color(0xFFE0392F), 1.6),
      errorStyle: const TextStyle(fontSize: 12.5),
    );
  }

  Widget _label(String teks) => Padding(
        padding: const EdgeInsets.only(bottom: 5),
        child: Text(
          teks,
          style: const TextStyle(
            fontSize: 12.5,
            fontWeight: FontWeight.w600,
            color: _slate500,
          ),
        ),
      );

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        // Sepadan dengan `bg-gradient-to-br from-brand-900 via-brand-800
        // to-brand-600` di halaman login web.
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [_brand900, _brand800, _brand600],
          ),
        ),
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 28),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 448),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // Alamat server tidak lagi tertulis di layar, tapi tetap
                    // harus bisa diganti kalau server sekolah pindah alamat —
                    // tanpa jalan ini, satu-satunya cara adalah membangun
                    // ulang APK. Disembunyikan di balik tekan-lama pada logo
                    // supaya tidak terpencet guru secara tidak sengaja.
                    GestureDetector(
                      onLongPress: _sedangMasuk ? null : _gantiAlamatServer,
                      child: _Kepala(sekolah: _sekolah),
                    ),
                    const SizedBox(height: 28),
                    _kartuMasuk(),
                    const SizedBox(height: 24),
                    _kaki(),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _kartuMasuk() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.25),
            blurRadius: 40,
            offset: const Offset(0, 20),
          ),
        ],
      ),
      padding: const EdgeInsets.all(26),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text(
              'Masuk ke Akun Anda',
              style: TextStyle(fontSize: 17.5, fontWeight: FontWeight.bold, color: _slate800),
            ),
            const SizedBox(height: 3),
            const Text(
              'Gunakan email dan kata sandi yang diberikan Admin sekolah.',
              style: TextStyle(fontSize: 13.5, color: _slate400, height: 1.4),
            ),
            const SizedBox(height: 22),

            if (_pesanGalat != null) ...[
              _kotakGalat(),
              const SizedBox(height: 18),
            ],

            _label('Email'),
            TextFormField(
              controller: _emailC,
              enabled: !_sedangMasuk,
              keyboardType: TextInputType.emailAddress,
              textInputAction: TextInputAction.next,
              style: const TextStyle(fontSize: 14.5, color: _slate800),
              decoration: _gayaIsian(petunjuk: 'nama@sekolah.sch.id'),
              validator: (v) =>
                  (v == null || v.trim().isEmpty) ? 'Email belum diisi.' : null,
            ),
            const SizedBox(height: 16),

            _label('Kata Sandi'),
            TextFormField(
              controller: _sandiC,
              enabled: !_sedangMasuk,
              obscureText: !_sandiTerlihat,
              textInputAction: TextInputAction.done,
              onFieldSubmitted: (_) => _sedangMasuk ? null : _masuk(),
              style: const TextStyle(fontSize: 14.5, color: _slate800),
              decoration: _gayaIsian(
                petunjuk: '••••••••',
                akhiran: IconButton(
                  icon: Icon(
                    _sandiTerlihat ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                    size: 20,
                    color: _slate400,
                  ),
                  tooltip: _sandiTerlihat ? 'Sembunyikan' : 'Tampilkan',
                  onPressed: () => setState(() => _sandiTerlihat = !_sandiTerlihat),
                ),
              ),
              validator: (v) => (v == null || v.isEmpty) ? 'Kata sandi belum diisi.' : null,
            ),
            const SizedBox(height: 10),

            // Sepadan dengan "Ingat saya di perangkat ini" di halaman web.
            InkWell(
              onTap: _sedangMasuk ? null : () => setState(() => _ingatEmail = !_ingatEmail),
              borderRadius: BorderRadius.circular(8),
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 4),
                child: Row(
                  children: [
                    SizedBox(
                      width: 22,
                      height: 22,
                      child: Checkbox(
                        value: _ingatEmail,
                        onChanged: _sedangMasuk
                            ? null
                            : (v) => setState(() => _ingatEmail = v ?? false),
                        activeColor: _brand600,
                        materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                        visualDensity: VisualDensity.compact,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4)),
                      ),
                    ),
                    const SizedBox(width: 10),
                    const Expanded(
                      child: Text(
                        'Ingat saya di perangkat ini',
                        style: TextStyle(fontSize: 13.5, color: _slate500),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            SizedBox(
              height: 46,
              child: FilledButton.icon(
                onPressed: _sedangMasuk ? null : _masuk,
                style: FilledButton.styleFrom(
                  backgroundColor: _brand600,
                  disabledBackgroundColor: _brand600.withValues(alpha: 0.55),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                icon: _sedangMasuk
                    ? const SizedBox.shrink()
                    : const Icon(Icons.login_rounded, size: 18),
                label: _sedangMasuk
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                      )
                    : const Text(
                        'Masuk',
                        style: TextStyle(fontSize: 14.5, fontWeight: FontWeight.w700),
                      ),
              ),
            ),

            if (_biometrikTersedia && _biometrikAktif) ...[
              const SizedBox(height: 14),
              Row(
                children: [
                  const Expanded(child: Divider(color: Color(0xFFE6EAF2))),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 10),
                    child: Text(
                      'atau',
                      style: TextStyle(fontSize: 12, color: _slate400.withValues(alpha: 0.9)),
                    ),
                  ),
                  const Expanded(child: Divider(color: Color(0xFFE6EAF2))),
                ],
              ),
              const SizedBox(height: 14),
              SizedBox(
                height: 46,
                child: OutlinedButton.icon(
                  onPressed: _sedangMasuk ? null : _masukBiometrik,
                  style: OutlinedButton.styleFrom(
                    foregroundColor: _brand600,
                    side: const BorderSide(color: _garis),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  icon: const Icon(Icons.fingerprint, size: 22),
                  label: const Text(
                    'Masuk dengan sidik jari',
                    style: TextStyle(fontSize: 14.5, fontWeight: FontWeight.w600),
                  ),
                ),
              ),
            ],

            // Selalu tampil, bukan hanya saat sidik jari menyala: yang
            // lupa kata sandi justru paling sering yang belum pernah
            // menyalakannya.
            Align(
              alignment: Alignment.center,
              child: TextButton(
                onPressed: _sedangMasuk ? null : _bukaLupaSandi,
                child: const Text(
                  'Lupa kata sandi?',
                  style: TextStyle(fontSize: 13, color: _slate500),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _kotakGalat() {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFFEF2F2),
        border: Border.all(color: const Color(0xFFFECACA)),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.error_outline, color: Color(0xFFDC2626), size: 19),
          const SizedBox(width: 9),
          Expanded(
            child: Text(
              _pesanGalat!,
              style: const TextStyle(color: Color(0xFFB91C1C), fontSize: 13.5, height: 1.45),
            ),
          ),
        ],
      ),
    );
  }

  Widget _kaki() {
    return Column(
      children: [
        Text(
          'SIM-SPENGA · ${_sekolah?.nama ?? "SMP Negeri"}',
          textAlign: TextAlign.center,
          style: TextStyle(
            color: Colors.white.withValues(alpha: 0.4),
            fontSize: 11,
          ),
        ),
        const SizedBox(height: 3),
        // Tahun sengaja TETAP 2026, bukan tahun berjalan: ini tahun
        // pembuatan aplikasinya, dan hak cipta tidak ikut bergeser
        // hanya karena kalender berganti.
        Text(
          '© 2026 FF Production',
          textAlign: TextAlign.center,
          style: TextStyle(
            color: Colors.white.withValues(alpha: 0.45),
            fontSize: 11,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}

/// Kepala halaman: logo sekolah, nama aplikasi, nama sekolah, dan satu
/// baris keterangan — susunan yang sama persis dengan halaman login web.
///
/// Logo & nama diambil dari server (lihat IdentitasSekolah). Selama
/// belum terambil — pemasangan baru, atau server sedang tak terjangkau —
/// yang tampil adalah inisial sekolah, persis seperti halaman web ketika
/// Logo Aplikasi belum diunggah.
class _Kepala extends StatelessWidget {
  const _Kepala({required this.sekolah});

  final IdentitasSekolah? sekolah;

  @override
  Widget build(BuildContext context) {
    final logo = sekolah?.logoUrl;

    return Column(
      children: [
        Container(
          width: 64,
          height: 64,
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.15),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: Colors.white.withValues(alpha: 0.2)),
          ),
          clipBehavior: Clip.antiAlias,
          alignment: Alignment.center,
          child: logo == null
              ? _inisial()
              : Padding(
                  // Sepadan dengan `object-contain p-2` di halaman web.
                  padding: const EdgeInsets.all(8),
                  child: Image.network(
                    logo,
                    fit: BoxFit.contain,
                    // Selama logo masih diunduh, inisial dulu yang tampil —
                    // lebih baik daripada kotak kosong yang berkedip.
                    frameBuilder: (_, anak, frame, __) =>
                        frame == null ? _inisial() : anak,
                    // Server mati atau berkasnya terhapus tidak boleh
                    // menyisakan ikon gambar rusak di layar masuk.
                    errorBuilder: (_, __, ___) => _inisial(),
                  ),
                ),
        ),
        const SizedBox(height: 16),
        const Text(
          'SIM-SPENGA',
          style: TextStyle(
            color: Colors.white,
            fontSize: 24,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.4,
          ),
        ),
        const SizedBox(height: 5),
        Text(
          sekolah?.nama ?? 'Sistem Informasi Manajemen Sekolah',
          textAlign: TextAlign.center,
          style: TextStyle(
            color: Colors.white.withValues(alpha: 0.8),
            fontSize: 13.5,
          ),
        ),
        const SizedBox(height: 3),
        Text(
          'Monitoring & manajemen guru serta siswa',
          style: TextStyle(
            color: Colors.white.withValues(alpha: 0.6),
            fontSize: 11.5,
          ),
        ),
      ],
    );
  }

  Widget _inisial() => Center(
        child: Text(
          sekolah?.inisial ?? 'SIM',
          style: const TextStyle(
            color: Colors.white,
            fontSize: 21,
            fontWeight: FontWeight.w800,
          ),
        ),
      );
}
