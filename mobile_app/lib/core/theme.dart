import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppTheme {
  static const Color primaryBlue = Color(0xFF020DFF);
  static const Color deepBlue = Color(0xFF312E81);
  static const Color softBlue = Color(0xFF6366F1);
  static const Color lilac = Color(0xFFA855F7);
  static const Color mist = Color(0xFF020617);
  static const Color slate = Color(0xFFE2E8F0);

  // Backward-compatible aliases used by existing screens.
  static const Color navy = deepBlue;
  static const Color cobalt = primaryBlue;
  static const Color emerald = softBlue;
  static const Color green = primaryBlue;
  static const Color greenDeep = deepBlue;
  static const Color gold = lilac;

  static const LinearGradient shellGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomCenter,
    colors: [Color(0xFF020617), Color(0xFF0B1120), Color(0xFF111827)],
  );

  static const LinearGradient actionGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF4F46E5), Color(0xFF7C3AED), Color(0xFFEC4899)],
  );

  static ThemeData get light {
    final scheme = ColorScheme.fromSeed(
      seedColor: primaryBlue,
      brightness: Brightness.dark,
      primary: primaryBlue,
      secondary: lilac,
      surface: const Color(0xFF0B1220),
    );

    final base = ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
      scaffoldBackgroundColor: mist,
    );

    final textTheme = GoogleFonts.orbitronTextTheme(
      base.textTheme,
    ).apply(
      bodyColor: slate,
      displayColor: slate,
    ).copyWith(
      headlineSmall: GoogleFonts.orbitron(
        fontWeight: FontWeight.w700,
        letterSpacing: -0.1,
      ),
      titleLarge: GoogleFonts.orbitron(
        fontWeight: FontWeight.w700,
        letterSpacing: -0.1,
      ),
      titleMedium: GoogleFonts.orbitron(fontWeight: FontWeight.w600),
      bodyLarge: GoogleFonts.orbitron(fontWeight: FontWeight.w500),
      bodyMedium: GoogleFonts.orbitron(fontWeight: FontWeight.w500),
      labelLarge: GoogleFonts.orbitron(fontWeight: FontWeight.w700),
    );

    return base.copyWith(
      textTheme: textTheme,
      appBarTheme: const AppBarTheme(
        centerTitle: false,
        elevation: 0,
        backgroundColor: Colors.transparent,
        foregroundColor: Color(0xFFE2E8F0),
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        margin: EdgeInsets.zero,
        color: const Color(0xFF0B1220),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(18),
          side: const BorderSide(color: Color(0xFF334155)),
        ),
      ),
      navigationBarTheme: NavigationBarThemeData(
        labelTextStyle: WidgetStateProperty.all(
          const TextStyle(fontWeight: FontWeight.w600, fontSize: 12),
        ),
        backgroundColor: const Color(0xFF0B1220),
        indicatorColor: const Color(0x334F46E5),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return const IconThemeData(color: Color(0xFFE2E8F0));
          }
          return const IconThemeData(color: Color(0xFF94A3B8));
        }),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          elevation: 0,
          minimumSize: const Size.fromHeight(52),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          backgroundColor: primaryBlue,
          foregroundColor: Colors.white,
          textStyle: const TextStyle(fontWeight: FontWeight.w700),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size.fromHeight(46),
          side: const BorderSide(color: Color(0xFF334155)),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          textStyle: const TextStyle(fontWeight: FontWeight.w600),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: const Color(0xFF0F172A),
        hintStyle: const TextStyle(color: Color(0xFF94A3B8)),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 14,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFF334155)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFF334155)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFF7C3AED), width: 1.2),
        ),
      ),
    );
  }
}

class AppSurface extends StatelessWidget {
  const AppSurface({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        Container(
          decoration: const BoxDecoration(gradient: AppTheme.shellGradient),
        ),
        Positioned.fill(
          child: IgnorePointer(
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: RadialGradient(
                  center: const Alignment(-0.6, -0.8),
                  radius: 1.2,
                  colors: [
                    const Color(0x334F46E5),
                    const Color(0x227C3AED),
                    Colors.transparent,
                  ],
                ),
              ),
            ),
          ),
        ),
        child,
      ],
    );
  }
}
