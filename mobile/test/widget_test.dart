import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:sim_spenga/layar_masuk.dart';
import 'package:sim_spenga/main.dart';

void main() {
  setUp(() {
    // Layar masuk membaca alamat server & email terakhir saat dibuka.
    SharedPreferences.setMockInitialValues({});
  });

  testWidgets('layar masuk menampilkan isian dan tombol yang diperlukan',
      (WidgetTester tester) async {
    await tester.pumpWidget(const AplikasiSimSpenga());
    await tester.pumpAndSettle();

    expect(find.text('SIM-SPENGA'), findsOneWidget);
    expect(find.text('Email'), findsOneWidget);
    expect(find.text('Kata Sandi'), findsOneWidget);
    expect(find.widgetWithText(FilledButton, 'Masuk'), findsOneWidget);
  });

  testWidgets('memberi tahu bahwa Admin & orang tua tidak bisa masuk',
      (WidgetTester tester) async {
    await tester.pumpWidget(const AplikasiSimSpenga());
    await tester.pumpAndSettle();

    expect(
      find.textContaining('Admin dan portal orang tua tidak dapat masuk'),
      findsOneWidget,
    );
  });

  testWidgets('isian kosong ditolak sebelum menghubungi server',
      (WidgetTester tester) async {
    await tester.pumpWidget(const MaterialApp(home: LayarMasuk()));
    await tester.pumpAndSettle();

    // Tata letaknya kini lebih tinggi dari layar uji bawaan (800x600),
    // jadi tombolnya digulir ke tampilan dulu sebelum ditekan.
    final tombol = find.widgetWithText(FilledButton, 'Masuk');
    await tester.ensureVisible(tombol);
    await tester.pumpAndSettle();

    await tester.tap(tombol);
    await tester.pump();

    expect(find.text('Email belum diisi.'), findsOneWidget);
    expect(find.text('Kata sandi belum diisi.'), findsOneWidget);
  });

  testWidgets('kata sandi tersembunyi sampai ikon mata ditekan',
      (WidgetTester tester) async {
    await tester.pumpWidget(const MaterialApp(home: LayarMasuk()));
    await tester.pumpAndSettle();

    expect(find.byIcon(Icons.visibility_outlined), findsOneWidget);
    await tester.tap(find.byIcon(Icons.visibility_outlined));
    await tester.pump();
    expect(find.byIcon(Icons.visibility_off_outlined), findsOneWidget);
  });
}
