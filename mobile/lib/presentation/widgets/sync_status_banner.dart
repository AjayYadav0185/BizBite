import 'package:flutter/material.dart';

import '../../core/sync/sync_controller.dart';
import '../theme/bizbite_theme.dart';

/// Connectivity + outbox status strip for the POS shell.
///
/// States: 🟢 Online | 🟡 Offline (N queued) | 🔵 Syncing | 🔴 Failed.
/// Tapping retry re-kicks the orchestrator via [onRetry].
class SyncStatusBanner extends StatelessWidget {
  const SyncStatusBanner({
    super.key,
    required this.sync,
    this.onRetry,
  });

  final SyncController sync;
  final Future<void> Function()? onRetry;

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: sync,
      builder: (context, _) {
        final state = sync.banner;
        final (bg, fg, icon) = switch (state) {
          SyncBannerState.online =>
            (AppColors.successBg, AppColors.successDeep, Icons.check_circle_rounded),
          SyncBannerState.offline =>
            (AppColors.warningBg, AppColors.warningDeep, Icons.wifi_off_rounded),
          SyncBannerState.syncing =>
            (AppColors.surfaceMuted, AppColors.skyTeal, Icons.sync_rounded),
          SyncBannerState.failed =>
            (AppColors.errorBg, AppColors.error, Icons.error_outline_rounded),
        };
        return Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            color: bg,
            border: Border(bottom: BorderSide(color: fg.withValues(alpha: 0.2))),
          ),
          child: Row(
            children: [
              Icon(icon, size: 16, color: fg),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  sync.bannerText,
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: fg,
                  ),
                ),
              ),
              if (state == SyncBannerState.failed ||
                  (state == SyncBannerState.offline && sync.pendingCount > 0))
                TextButton(
                  onPressed: onRetry == null
                      ? null
                      : () => onRetry!(),
                  style: TextButton.styleFrom(
                    foregroundColor: fg,
                    padding: const EdgeInsets.symmetric(horizontal: 8),
                    minimumSize: const Size(0, 30),
                  ),
                  child: const Text(
                    'Retry',
                    style: TextStyle(fontWeight: FontWeight.w800),
                  ),
                ),
            ],
          ),
        );
      },
    );
  }
}
