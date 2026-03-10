import 'package:audioplayers/audioplayers.dart';

class AppSfx {
  AppSfx._();

  static final AudioPlayer _player = AudioPlayer();

  static Future<void> playWiserTone() async {
    try {
      await _player.stop();
      await _player.play(
        AssetSource('audio/wiser.wav'),
        mode: PlayerMode.lowLatency,
      );
    } catch (_) {
      // Avoid interrupting auth/redeem flows if audio fails.
    }
  }
}
