import 'dart:math' as math;

import 'package:flutter/material.dart';

class FxButton extends StatefulWidget {
  const FxButton({
    super.key,
    required this.label,
    this.icon,
    this.onPressed,
    this.fullWidth = false,
    this.height = 54,
  });

  final String label;
  final IconData? icon;
  final VoidCallback? onPressed;
  final bool fullWidth;
  final double height;

  @override
  State<FxButton> createState() => _FxButtonState();
}

class _FxButtonState extends State<FxButton>
    with SingleTickerProviderStateMixin {
  late final AnimationController _spin = AnimationController(
    vsync: this,
    duration: const Duration(seconds: 8),
  )..repeat();

  bool _hovered = false;
  bool _pressed = false;

  @override
  void dispose() {
    _spin.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final enabled = widget.onPressed != null;
    final active = _hovered || _pressed;
    final radius = BorderRadius.circular(14);

    return GestureDetector(
      onTapDown: enabled ? (_) => setState(() => _pressed = true) : null,
      onTapCancel: enabled ? () => setState(() => _pressed = false) : null,
      onTapUp: enabled ? (_) => setState(() => _pressed = false) : null,
      child: AnimatedScale(
        duration: const Duration(milliseconds: 180),
        scale: _pressed ? 0.98 : (_hovered ? 1.01 : 1),
        child: Opacity(
          opacity: enabled ? 1 : 0.5,
          child: Stack(
            children: [
              Positioned.fill(
                child: IgnorePointer(
                  child: AnimatedOpacity(
                    duration: const Duration(milliseconds: 220),
                    opacity: active ? 0.3 : 0.18,
                    child: DecoratedBox(
                      decoration: BoxDecoration(
                        borderRadius: radius,
                        gradient: const LinearGradient(
                          colors: [
                            Color(0xFF4F46E5),
                            Color(0xFF7C3AED),
                            Color(0xFFEC4899),
                          ],
                        ),
                        boxShadow: const [
                          BoxShadow(
                            color: Color(0x554F46E5),
                            blurRadius: 22,
                            spreadRadius: 2,
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
              ClipRRect(
                borderRadius: radius,
                child: SizedBox(
                  width: widget.fullWidth ? double.infinity : null,
                  height: widget.height,
                  child: Material(
                    color: const Color(0xFF0B1220),
                    child: InkWell(
                      onHover: (hovered) {
                        if (_hovered != hovered) {
                          setState(() => _hovered = hovered);
                        }
                      },
                      onTap: widget.onPressed,
                      child: Stack(
                        fit: StackFit.expand,
                        children: [
                          AnimatedBuilder(
                            animation: _spin,
                            builder: (context, child) {
                              return Transform.rotate(
                                angle: _spin.value * 2 * math.pi,
                                child: child,
                              );
                            },
                            child: Center(
                              child: Container(
                                width: widget.fullWidth ? 320 : 180,
                                height: widget.height * 2.2,
                                decoration: const BoxDecoration(
                                  gradient: SweepGradient(
                                    colors: [
                                      Color(0x004F46E5),
                                      Color(0xAA4F46E5),
                                      Color(0x007C3AED),
                                      Color(0xAAEC4899),
                                      Color(0x004F46E5),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          ),
                          Positioned.fill(
                            child: Padding(
                              padding: const EdgeInsets.all(1.5),
                              child: DecoratedBox(
                                decoration: BoxDecoration(
                                  color: const Color(0xFF0B1220),
                                  borderRadius: BorderRadius.circular(12.5),
                                  border: Border.all(
                                    color: active
                                        ? const Color(0x887C3AED)
                                        : const Color(0xFF334155),
                                  ),
                                ),
                              ),
                            ),
                          ),
                          Center(
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                if (widget.icon != null) ...[
                                  Icon(
                                    widget.icon,
                                    size: 18,
                                    color: Colors.white,
                                  ),
                                  const SizedBox(width: 8),
                                ],
                                Text(
                                  widget.label,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.w700,
                                    fontSize: 14,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
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
}
