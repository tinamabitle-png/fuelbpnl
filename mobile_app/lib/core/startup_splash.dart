import 'dart:math' as math;

import 'package:flutter/material.dart';

class StartupSplash extends StatefulWidget {
  const StartupSplash({super.key});

  @override
  State<StartupSplash> createState() => _StartupSplashState();
}

class _StartupSplashState extends State<StartupSplash>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(seconds: 2),
  )..repeat();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF020617),
      body: Center(
        child: AnimatedBuilder(
          animation: _controller,
          builder: (context, _) {
            final t = _controller.value;
            return Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    _DashGlyph(
                      progress: t,
                      gradient: const LinearGradient(
                        colors: [Color(0xFF00E0ED), Color(0xFF00DA72)],
                      ),
                      pathBuilder: (s) {
                        final p = Path();
                        final w = s.width;
                        final h = s.height;
                        p.moveTo(w * 0.15, h * 0.12);
                        p.lineTo(w * 0.27, h * 0.12);
                        p.lineTo(w * 0.27, h * 0.60);
                        p.cubicTo(
                          w * 0.27,
                          h * 0.83,
                          w * 0.74,
                          h * 0.83,
                          w * 0.74,
                          h * 0.60,
                        );
                        p.lineTo(w * 0.74, h * 0.12);
                        p.lineTo(w * 0.86, h * 0.12);
                        p.lineTo(w * 0.86, h * 0.60);
                        p.cubicTo(
                          w * 0.86,
                          h * 0.98,
                          w * 0.14,
                          h * 0.98,
                          w * 0.14,
                          h * 0.60,
                        );
                        p.lineTo(w * 0.15, h * 0.12);
                        return p;
                      },
                    ),
                    const SizedBox(width: 10),
                    _CenterRing(progress: t),
                    const SizedBox(width: 10),
                    _DashGlyph(
                      progress: t,
                      gradient: const LinearGradient(
                        colors: [Color(0xFF973BED), Color(0xFF007CFF)],
                      ),
                      pathBuilder: (s) {
                        final p = Path();
                        final w = s.width;
                        final h = s.height;
                        p.moveTo(w * 0.15, h * 0.14);
                        p.lineTo(w * 0.35, h * 0.14);
                        p.lineTo(w * 0.50, h * 0.30);
                        p.lineTo(w * 0.65, h * 0.14);
                        p.lineTo(w * 0.85, h * 0.14);
                        p.lineTo(w * 0.85, h * 0.84);
                        p.lineTo(w * 0.68, h * 0.84);
                        p.lineTo(w * 0.68, h * 0.42);
                        p.lineTo(w * 0.50, h * 0.58);
                        p.lineTo(w * 0.32, h * 0.42);
                        p.lineTo(w * 0.32, h * 0.84);
                        p.lineTo(w * 0.15, h * 0.84);
                        p.close();
                        return p;
                      },
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                const Text(
                  'Starting Bwiser...',
                  style: TextStyle(
                    color: Color(0xFFE2E8F0),
                    fontWeight: FontWeight.w600,
                    fontSize: 16,
                    letterSpacing: 0.2,
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _CenterRing extends StatelessWidget {
  const _CenterRing({required this.progress});

  final double progress;

  @override
  Widget build(BuildContext context) {
    final outerTurn = progress * 6 * math.pi;
    return SizedBox(
      width: 64,
      height: 64,
      child: Transform.rotate(
        angle: outerTurn,
        child: CustomPaint(
          painter: _RingPainter(progress: progress),
        ),
      ),
    );
  }
}

class _RingPainter extends CustomPainter {
  const _RingPainter({required this.progress});

  final double progress;

  @override
  void paint(Canvas canvas, Size size) {
    final rect = Offset.zero & size;
    final center = rect.center;
    const stroke = 7.0;
    final ringPaint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = stroke
      ..strokeCap = StrokeCap.round
      ..shader = const SweepGradient(
        colors: [
          Color(0xFFFFC800),
          Color(0xFFFF00FF),
          Color(0xFF7C3AED),
          Color(0xFF007CFF),
          Color(0xFFFFC800),
        ],
      ).createShader(rect);

    final start = progress * math.pi * 2;
    final sweep = (0.25 + 0.35 * math.sin(progress * math.pi * 2).abs()) *
        math.pi *
        2;
    canvas.drawArc(
      Rect.fromCircle(center: center, radius: size.width * 0.42),
      start,
      sweep,
      false,
      ringPaint,
    );
  }

  @override
  bool shouldRepaint(covariant _RingPainter oldDelegate) {
    return oldDelegate.progress != progress;
  }
}

class _DashGlyph extends StatelessWidget {
  const _DashGlyph({
    required this.progress,
    required this.gradient,
    required this.pathBuilder,
  });

  final double progress;
  final Gradient gradient;
  final Path Function(Size size) pathBuilder;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 64,
      height: 64,
      child: CustomPaint(
        painter: _DashPathPainter(
          progress: progress,
          gradient: gradient,
          pathBuilder: pathBuilder,
        ),
      ),
    );
  }
}

class _DashPathPainter extends CustomPainter {
  const _DashPathPainter({
    required this.progress,
    required this.gradient,
    required this.pathBuilder,
  });

  final double progress;
  final Gradient gradient;
  final Path Function(Size size) pathBuilder;

  @override
  void paint(Canvas canvas, Size size) {
    final path = pathBuilder(size);
    final bounds = Offset.zero & size;
    final paint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 6
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round
      ..shader = gradient.createShader(bounds);

    for (final metric in path.computeMetrics()) {
      final total = metric.length;
      if (total <= 0) continue;
      const dash = 22.0;
      const gap = 12.0;
      final shift = total * progress;
      double distance = -shift;

      while (distance < total) {
        final start = distance.clamp(0, total).toDouble();
        final end = (distance + dash).clamp(0, total).toDouble();
        if (end > start) {
          canvas.drawPath(metric.extractPath(start, end), paint);
        }
        distance += dash + gap;
      }
    }
  }

  @override
  bool shouldRepaint(covariant _DashPathPainter oldDelegate) {
    return oldDelegate.progress != progress ||
        oldDelegate.gradient != gradient ||
        oldDelegate.pathBuilder != pathBuilder;
  }
}
