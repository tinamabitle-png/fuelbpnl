import 'package:flutter/material.dart';

class LogoMark extends StatelessWidget {
  const LogoMark({
    super.key,
    this.size = 28,
    this.color,
  });

  final double size;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    Widget logo = Image.asset(
      'assets/images/app_logo.png',
      width: size,
      height: size,
      fit: BoxFit.contain,
    );

    if (color != null) {
      logo = ColorFiltered(
        colorFilter: ColorFilter.mode(color!, BlendMode.srcIn),
        child: logo,
      );
    }

    return SizedBox(width: size, height: size, child: logo);
  }
}

