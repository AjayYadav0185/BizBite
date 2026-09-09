import 'package:flutter/material.dart';

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
    final scheme = theme.colorScheme;

    return Container(
      padding: EdgeInsets.all(12),
      color: error ? scheme.errorContainer : scheme.primaryContainer,
      width: double.infinity,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        spacing: 8,
        children: [
          Icon(
            error ? Icons.error : Icons.check_circle,
            size: 18,
            color: error ? scheme.onErrorContainer : scheme.onPrimaryContainer,
          ),
          Expanded(
            child: Text(
              text,
              maxLines: 3,
              style: theme.textTheme.bodyMedium,
              selectionColor:
                  error ? scheme.onErrorContainer : scheme.onPrimaryContainer,
            ),
          ),
        ],
      ),
    );
  }
}