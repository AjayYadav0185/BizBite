import 'package:flutter/material.dart';

import '../theme/bizbite_theme.dart';

/// Single-line error / success / info banner used across POS flows.
///
/// Renders as a full-width tinted strip; an empty [text] renders nothing.
class StatusBanner extends StatelessWidget {
  const StatusBanner({
    super.key,
    required this.text,
    this.error = false,
  });

  final String text;
  final bool error;

  @override
  Widget build(BuildContext context) {
    if (text.isEmpty) return SizedBox.shrink();

    final theme = Theme.of(context);

    final bg = error ? AppColors.errorBg : AppColors.successBg;
    final fg = error ? AppColors.error : AppColors.successDeep;

    return Container(
      margin: const EdgeInsets.only(top: AppSpacing.sm),
      padding: const EdgeInsets.all(AppSpacing.md),
      width: double.infinity,
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: fg.withValues(alpha: 0.25)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        spacing: 8,
        children: [
          Icon(
            error ? Icons.error_outline_rounded : Icons.check_circle_rounded,
            size: 18,
            color: fg,
          ),
          Expanded(
            child: Text(
              text,
              maxLines: 3,
              style: theme.textTheme.bodyMedium?.copyWith(color: fg),
            ),
          ),
        ],
      ),
    );
  }
}