import 'package:flutter/material.dart';

import 'biometrik.dart';
import 'layanan.dart';

/// LUPA KATA SANDI — dua langkah dalam satu layar.
///
/// =====================================================================
/// KENAPA BEGINI
/// =====================================================================
/// Guru yang lupa kata sandinya sedang panik dan sering sedang buru-buru.
/// Karena itu alurnya sependek mungkin: ketik email, buka WhatsApp, ketik
/// 6 angka, selesai. Tidak ada langkah "buat kata sandi baru" di ponsel —
/// kata sandinya dikembalikan ke setelan awal oleh server, dan itu yang
/// ditampilkan besar-besar supaya bisa langsung dipakai masuk.
///
/// Email diisi DI SINI, bukan diambil diam-diam dari layar masuk: yang
/// membuka layar ini belum tentu sudah mengetik apa pun di sana, dan
/// melihat alamatnya sendiri sebelum kode dikirim membuat jelas ke akun
/// mana kode itu akan pergi.
///
/// =====================================================================
/// SIDIK JARI IKUT DICABUT
/// =====================================================================
/// Kata sandi yang tersimpan untuk sidik jari menjadi basi begitu server
/// menggantinya. Membiarkannya hanya membuat percobaan berikutnya gagal
/// dengan pesan yang membingungkan, jadi di sini langsung dihapus.
/// Inilah pengganti tombol "Lupakan sidik jari" yang dulu berdiri
/// sendiri: mencabutnya kini menjadi bagian dari alur yang memang punya
/// alasan untuk mencabutnya.
class LayarLupaSandi extends StatefulWidget {
  /// Email/NIP yang sudah diketik di layar masuk, supaya tidak perlu
  /// diketik dua kali. Boleh kosong.
  final String emailAwal;

  const LayarLupaSandi({super.key, this.emailAwal = ''});

  @override
  State<LayarLupaSandi> createState() => _LayarLupaSandiState();
}

enum _Langkah { email, kode, selesai }

class _LayarLupaSandiState extends State<LayarLupaSandi> {
  static const _brand900 = Color(0xFF193C8C);
  static const _brand800 = Color(0xFF1844B3);
  static const _brand600 = Color(0xFF1C68F2);
  static const _brand500 = Color(0xFF3388FD);
  static const _slate800 = Color(0xFF1E293B);
  static const _slate500 = Color(0xFF64748B);
  static const _slate400 = Color(0xFF94A3B8);
  static const _garis = Color(0xFFDBE2EE);

  final _emailC = TextEditingController();
  final _kodeC = TextEditingController();

  _Langkah _langkah = _Langkah.email;
  bool _sibuk = false;
  String? _pesanGalat;
  String? _pesanBaik;
  String? _sandiBaru;

  @override
  void initState() {
    super.initState();
    _emailC.text = widget.emailAwal;
  }

  @override
  void dispose() {
    _emailC.dispose();
    _kodeC.dispose();
    super.dispose();
  }

  Future<void> _mintaKode() async {
    final email = _emailC.text.trim();
    if (email.isEmpty) {
      setState(() => _pesanGalat = 'Email atau NIP belum diisi.');
      return;
    }

    setState(() {
      _sibuk = true;
      _pesanGalat = null;
      _pesanBaik = null;
    });

    final hasil = await Layanan.mintaKodeReset(email);
    if (!mounted) return;

    setState(() {
      _sibuk = false;
      if (hasil.berhasil) {
        _langkah = _Langkah.kode;
        _pesanBaik = hasil.pesan;
      } else {
        _pesanGalat = hasil.pesan;
      }
    });
  }

  Future<void> _verifikasi() async {
    final kode = _kodeC.text.trim();
    if (kode.isEmpty) {
      setState(() => _pesanGalat = 'Kode belum diisi.');
      return;
    }

    setState(() {
      _sibuk = true;
      _pesanGalat = null;
    });

    final hasil = await Layanan.verifikasiKodeReset(_emailC.text.trim(), kode);
    if (!mounted) return;

    if (!hasil.berhasil) {
      setState(() {
        _sibuk = false;
        _pesanGalat = hasil.pesan;
      });
      return;
    }

    // Kredensial sidik jari sudah tidak sah — kata sandinya baru saja
    // diganti server. Dicabut di sini, bukan dibiarkan gagal nanti.
    await Biometrik.lupakan();
    if (!mounted) return;

    setState(() {
      _sibuk = false;
      _langkah = _Langkah.selesai;
      _sandiBaru = hasil.sandiBaru;
      _pesanBaik = hasil.pesan;
    });
  }

  InputDecoration _gayaIsian({required String petunjuk}) {
    OutlineInputBorder tepi(Color warna, double tebal) => OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: warna, width: tebal),
        );

    return InputDecoration(
      hintText: petunjuk,
      hintStyle: const TextStyle(color: _slate400, fontSize: 14.5),
      filled: true,
      fillColor: Colors.white,
      isDense: true,
      contentPadding: const EdgeInsets.symmetric(horizontal: 13, vertical: 14),
      border: tepi(_garis, 1),
      enabledBorder: tepi(_garis, 1),
      focusedBorder: tepi(_brand500, 1.6),
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
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [_brand900, _brand800, _brand600],
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              Align(
                alignment: Alignment.centerLeft,
                child: IconButton(
                  icon: const Icon(Icons.arrow_back, color: Colors.white),
                  tooltip: 'Kembali',
                  onPressed: _sibuk ? null : () => Navigator.of(context).pop(),
                ),
              ),
              Expanded(
                child: Center(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                    child: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 448),
                      child: _kartu(),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _kartu() {
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
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: switch (_langkah) {
          _Langkah.email => _isiLangkahEmail(),
          _Langkah.kode => _isiLangkahKode(),
          _Langkah.selesai => _isiLangkahSelesai(),
        },
      ),
    );
  }

  List<Widget> _isiLangkahEmail() {
    return [
      const Icon(Icons.lock_reset, size: 38, color: _brand600),
      const SizedBox(height: 12),
      const Text(
        'Lupa Kata Sandi',
        textAlign: TextAlign.center,
        style: TextStyle(fontSize: 17.5, fontWeight: FontWeight.bold, color: _slate800),
      ),
      const SizedBox(height: 3),
      const Text(
        'Kode berisi 6 angka akan dikirim lewat WhatsApp ke nomor yang '
        'terdaftar pada akun Anda.',
        textAlign: TextAlign.center,
        style: TextStyle(fontSize: 13.5, color: _slate400, height: 1.4),
      ),
      const SizedBox(height: 22),
      if (_pesanGalat != null) ...[_kotakGalat(), const SizedBox(height: 18)],
      _label('Email atau NIP'),
      TextField(
        controller: _emailC,
        enabled: !_sibuk,
        keyboardType: TextInputType.emailAddress,
        style: const TextStyle(fontSize: 14.5, color: _slate800),
        decoration: _gayaIsian(petunjuk: 'nama@sekolah.sch.id'),
      ),
      const SizedBox(height: 18),
      _tombolUtama(
        teks: 'Kirim Kode',
        ikon: Icons.send_rounded,
        onTekan: _mintaKode,
      ),
      const SizedBox(height: 12),
      const Text(
        'Nomor tujuan tidak bisa diketik di sini — kode selalu dikirim ke '
        'nomor milik akun tersebut. Belum punya nomor WhatsApp di sistem? '
        'Hubungi Admin sekolah.',
        textAlign: TextAlign.center,
        style: TextStyle(fontSize: 11.5, color: _slate400, height: 1.5),
      ),
    ];
  }

  List<Widget> _isiLangkahKode() {
    return [
      const Icon(Icons.sms_outlined, size: 38, color: _brand600),
      const SizedBox(height: 12),
      const Text(
        'Masukkan Kode',
        textAlign: TextAlign.center,
        style: TextStyle(fontSize: 17.5, fontWeight: FontWeight.bold, color: _slate800),
      ),
      const SizedBox(height: 6),
      if (_pesanBaik != null)
        Text(
          _pesanBaik!,
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 13, color: _slate400, height: 1.45),
        ),
      const SizedBox(height: 22),
      if (_pesanGalat != null) ...[_kotakGalat(), const SizedBox(height: 18)],
      _label('Kode dari WhatsApp'),
      TextField(
        controller: _kodeC,
        enabled: !_sibuk,
        keyboardType: TextInputType.number,
        textAlign: TextAlign.center,
        maxLength: 6,
        style: const TextStyle(
          fontSize: 26,
          fontWeight: FontWeight.bold,
          letterSpacing: 8,
          color: _slate800,
        ),
        decoration: _gayaIsian(petunjuk: '••••••').copyWith(counterText: ''),
      ),
      const SizedBox(height: 18),
      _tombolUtama(
        teks: 'Atur Ulang Kata Sandi',
        ikon: Icons.check_rounded,
        onTekan: _verifikasi,
      ),
      TextButton(
        onPressed: _sibuk ? null : _mintaKode,
        child: const Text(
          'Kirim ulang kode',
          style: TextStyle(fontSize: 13, color: _slate500),
        ),
      ),
    ];
  }

  List<Widget> _isiLangkahSelesai() {
    return [
      const Icon(Icons.check_circle_outline, size: 44, color: Color(0xFF0FA968)),
      const SizedBox(height: 12),
      const Text(
        'Kata Sandi Diatur Ulang',
        textAlign: TextAlign.center,
        style: TextStyle(fontSize: 17.5, fontWeight: FontWeight.bold, color: _slate800),
      ),
      const SizedBox(height: 6),
      Text(
        _pesanBaik ?? 'Berhasil.',
        textAlign: TextAlign.center,
        style: const TextStyle(fontSize: 13.5, color: _slate400, height: 1.4),
      ),
      const SizedBox(height: 20),
      if (_sandiBaru != null) ...[
        Container(
          padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 12),
          decoration: BoxDecoration(
            color: const Color(0xFFEEF7FF),
            border: Border.all(color: const Color(0xFFBCDFFF)),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Column(
            children: [
              const Text(
                'Kata sandi Anda sekarang',
                style: TextStyle(fontSize: 12, color: _slate500),
              ),
              const SizedBox(height: 6),
              SelectableText(
                _sandiBaru!,
                style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                  letterSpacing: 1.5,
                  color: _brand600,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 14),
      ],
      const Text(
        'Segera ganti kata sandi ini setelah masuk, lewat menu Profil. '
        'Masuk dengan sidik jari juga sudah dimatikan dan bisa dinyalakan '
        'lagi setelah Anda masuk.',
        textAlign: TextAlign.center,
        style: TextStyle(fontSize: 12, color: _slate400, height: 1.5),
      ),
      const SizedBox(height: 20),
      _tombolUtama(
        teks: 'Kembali ke Halaman Masuk',
        ikon: Icons.login_rounded,
        onTekan: () async => Navigator.of(context).pop(),
      ),
    ];
  }

  Widget _tombolUtama({
    required String teks,
    required IconData ikon,
    required Future<void> Function() onTekan,
  }) {
    return SizedBox(
      height: 46,
      child: FilledButton.icon(
        onPressed: _sibuk ? null : () => onTekan(),
        style: FilledButton.styleFrom(
          backgroundColor: _brand600,
          disabledBackgroundColor: _brand600.withValues(alpha: 0.55),
          foregroundColor: Colors.white,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
        icon: _sibuk ? const SizedBox.shrink() : Icon(ikon, size: 18),
        label: _sibuk
            ? const SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
              )
            : Text(teks, style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w700)),
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
}
