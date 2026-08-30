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
    expect(find.text('Email atau NIP'), findsOneWidget);
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

    await tester.tap(find.widgetWithText(FilledButton, 'Masuk'));
    await tester.pump();

    expect(find.text('Email atau NIP belum diisi.'), findsOneWidget);
    expect(find.text('Kata sandi belum diisi.'), findsOneWidget);
  });

  testWidgets('kata sandi tersembunyi sampai ikon mata ditekan',
      (WidgetTester tester) async {
    await tester.pumpWidget(const MaterialApp(home: LayarMasuk()));
    await tester.pumpAndSettle();

    expect(find.byIcon(Icons.visibility), findsOneWidget);
    await tester.tap(find.byIcon(Icons.visibility));
    await tester.pump();
    expect(find.byIcon(Icons.visibility_off), findsOneWidget);
  });
}
