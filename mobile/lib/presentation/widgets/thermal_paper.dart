import 'package:flutter/material.dart';

/// Jagged "torn paper" edge rendered above and below the on-screen thermal
/// receipt preview, so the digital roll reads like real 58mm/80mm paper.
class TearEdge extends StatelessWidget {
  const TearEdge({
    super.key,
    required this.color,
    this.isTop = true,
    this.height = 10,
  });

  final Color color;
  final bool isTop;
  final double height;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: height,
      width: double.infinity,
      child: CustomPaint(
        painter: _TearEdgePainter(color: color, isTop: isTop),
      ),
    );
  }
}

class _TearEdgePainter extends CustomPainter {
  _TearEdgePainter({required this.color, required this.isTop});

  final Color color;
  final bool isTop;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.fill;

    const teeth = 18;
    final step = size.width / teeth;
    final path = Path();

    if (isTop) {
      // Flat bottom edge, irregular zig-zag top (freshly torn roll start).
      path.moveTo(0, size.height);
      for (var i = 0; i < teeth; i++) {
        final x = i * step;
        path.lineTo(x + step * 0.5, i.isEven ? 0 : size.height * 0.55);
        path.lineTo(x + step, size.height);
      }
    } else {
      // Flat top edge, irregular zig-zag bottom (torn-off roll end).
      path.moveTo(0, 0);
      for (var i = 0; i < teeth; i++) {
        final x = i * step;
        path.lineTo(x + step * 0.5, i.isEven ? size.height : size.height * 0.45);
        path.lineTo(x + step, 0);
      }
    }
    path.close();
    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant _TearEdgePainter old) =>
      old.color != color || old.isTop != isTop;
}

/// Dashed divider mimicking the perforated separators printed on a
/// thermal bill (`- - - - - - - - - -`).
class DashedDivider extends StatelessWidget {
  const DashedDivider({
    super.key,
    this.color = const Color(0xFF8E8A86),
    this.height = 12,
    this.thickness = 1.1,
    this.dashWidth = 4,
    this.gap = 3,
  });

  final Color color;
  final double height;
  final double thickness;
  final double dashWidth;
  final double gap;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: height,
      width: double.infinity,
      child: CustomPaint(
        painter: _DashedLinePainter(
          color: color,
          thickness: thickness,
          dashWidth: dashWidth,
          gap: gap,
        ),
      ),
    );
  }
}

class _DashedLinePainter extends CustomPainter {
  _DashedLinePainter({
    required this.color,
    required this.thickness,
    required this.dashWidth,
    required this.gap,
  });

  final Color color;
  final double thickness;
  final double dashWidth;
  final double gap;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..strokeWidth = thickness
      ..style = PaintingStyle.stroke;

    final y = size.height / 2;
    var x = 0.0;
    while (x < size.width) {
      final end = (x + dashWidth).clamp(0.0, size.width);
      canvas.drawLine(Offset(x, y), Offset(end, y), paint);
      x += dashWidth + gap;
    }
  }

  @override
  bool shouldRepaint(covariant _DashedLinePainter old) =>
      old.color != color || old.dashWidth != dashWidth || old.gap != gap;
}