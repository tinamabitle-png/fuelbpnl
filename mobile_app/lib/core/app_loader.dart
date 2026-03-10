import 'package:flutter/material.dart';

class AppLoader extends StatefulWidget {
  const AppLoader({super.key, this.size = 160, this.showText = true});

  final double size;
  final bool showText;

  @override
  State<AppLoader> createState() => _AppLoaderState();
}

class _AppLoaderState extends State<AppLoader>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1200),
  )..repeat();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final shell = widget.size.clamp(16, 160).toDouble();
    final outer = (shell * 0.6).clamp(10, 96).toDouble();
    final sweep = (shell * 0.4).clamp(8, 64).toDouble();
    final dot = (shell * 0.13).clamp(4, 20).toDouble();

    return SizedBox(
      width: widget.size,
      height: widget.showText ? widget.size + 8 : shell,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          SizedBox(
            width: outer,
            height: outer,
            child: Stack(
              alignment: Alignment.center,
              children: [
                Container(
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(color: const Color(0xFF35526F)),
                    color: const Color(0xFF0B1E33),
                  ),
                ),
                RotationTransition(
                  turns: _controller,
                  child: Container(
                    width: sweep,
                    height: sweep,
                    decoration: const BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: SweepGradient(
                        colors: [
                          Color(0x00020DFF),
                          Color(0xFF7C3AED),
                          Color(0xFF020DFF),
                          Color(0x00020DFF),
                        ],
                      ),
                    ),
                  ),
                ),
                Container(
                  width: dot,
                  height: dot,
                  decoration: BoxDecoration(
                    color: const Color(0xFF0F172A),
                    shape: BoxShape.circle,
                    border: Border.all(color: const Color(0xFF334155)),
                  ),
                ),
              ],
            ),
          ),
          if (widget.showText) ...[
            const SizedBox(height: 10),
            const Text(
              'Loading...',
              style: TextStyle(color: Color(0xFF94A3B8), fontSize: 20),
            ),
          ],
        ],
      ),
    );
  }
}
