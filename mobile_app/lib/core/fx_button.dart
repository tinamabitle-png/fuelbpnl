import 'package:flutter/material.dart';

class FxButton extends StatelessWidget {
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
  Widget build(BuildContext context) {
    final enabled = onPressed != null;
    final outerRadius = BorderRadius.circular(14);
    final innerRadius = BorderRadius.circular(12.5);

    return LayoutBuilder(
      builder: (context, constraints) {
        final finiteWidth = constraints.maxWidth.isFinite
            ? constraints.maxWidth
            : null;
        final resolvedWidth = fullWidth ? (finiteWidth ?? 280.0) : null;

        return Opacity(
          opacity: enabled ? 1 : 0.5,
          child: SizedBox(
            width: resolvedWidth,
            height: height,
            child: DecoratedBox(
              decoration: BoxDecoration(
                borderRadius: outerRadius,
                gradient: const LinearGradient(
                  begin: Alignment.centerLeft,
                  end: Alignment.centerRight,
                  colors: [Color(0xFF03A9F4), Color(0xFFF441A5)],
                ),
                boxShadow: const [
                  BoxShadow(
                    color: Color(0x33000000),
                    blurRadius: 3,
                    offset: Offset(2, 2),
                  ),
                ],
              ),
              child: Padding(
                padding: const EdgeInsets.all(3),
                child: FilledButton(
                  onPressed: onPressed,
                  style: FilledButton.styleFrom(
                    backgroundColor: const Color(0xFF000000),
                    foregroundColor: Colors.white,
                    disabledBackgroundColor: const Color(0xFF000000),
                    disabledForegroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: innerRadius),
                    padding: const EdgeInsets.symmetric(horizontal: 14),
                    textStyle: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                    ),
                    minimumSize: Size(
                      fullWidth ? (resolvedWidth ?? 0) : 0,
                      height - 6,
                    ),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (icon != null) ...[
                        Icon(icon, size: 18),
                        const SizedBox(width: 8),
                      ],
                      Text(label, maxLines: 1, overflow: TextOverflow.ellipsis),
                    ],
                  ),
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}
