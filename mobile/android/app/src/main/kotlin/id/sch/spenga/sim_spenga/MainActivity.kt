package id.sch.spenga.sim_spenga

import io.flutter.embedding.android.FlutterFragmentActivity

// FlutterFragmentActivity, bukan FlutterActivity: local_auth memunculkan
// dialog sidik jari lewat BiometricPrompt, yang menuntut FragmentActivity.
class MainActivity : FlutterFragmentActivity()
