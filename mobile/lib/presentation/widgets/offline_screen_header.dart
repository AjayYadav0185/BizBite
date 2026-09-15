import 'package:flutter/material.dart';

import '../../core/sync/offline_sources.dart';
import '../../core/sync/sync_controller.dart';
import '../theme/bizbite_theme.dart';
import 'sync_status_banner.dart';

/// Offline strip for the PUSHED screens (Orders, Shifts, Reports, Console,
/// Wallet) — the ones that cover the POS shell and therefore hide its banner.
///
/// Always shows the live sync state; adds an amber "Cached" strip whenever
/// ANY of the screen's [sources] came from SQLite because the server was
/// unreachable/slow, so the cashier can tell fresh numbers from last-good
/// numbers. [sources] is a set because the console paints three caches at
/// once (tables + campaigns + staff).
class OfflineScreenHeader extends StatelessWidget {
  const OfflineScreenHeader({
    super.key,
    required this.sync,
    required this.source,
    this.sources = const {},
    this.onRetry,
    this.bannerHeight = 34,
  });

  final SyncController sync;

  /// Primary cache this screen paints from (one of [OfflineSources]).
  final String source;

  /// Extra caches the screen paints from without a tab switch (console).
  final Set<String> sources;

  final Future<void> Function()? onRetry;

  /// Extra height reserved below the banner for the strip.
  final double bannerHeight;

  /// Height for `AppBar.bottom`'s `PreferredSize`: the strip grows by one line
  /// when any cached hint is visible.
  static double heightFor(
    SyncController sync,
    String source, {
    Set<String> sources = const {},
  }) {
    final stale = sync.isServedFromCache(source) ||
        sources.any(sync.isServedFromCache);
    return stale ? 64 : 34;
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: sync,
      builder: (context, _) {
        final stale = sync.isServedFromCache(source) ||
            sources.any(sync.isServedFromCache);
        return Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            SyncStatusBanner(sync: sync, onRetry: onRetry),
            if (stale)
              Container(
                width: double.infinity,
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                color: AppColors.warningBg,
                child: Row(
                  children: [
                    const Icon(Icons.cloud_off_rounded,
                        size: 14, color: AppColors.warningDeep),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        'Showing saved data — it refreshes automatically when '
                        'the server responds.',
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          color: AppColors.warningDeep,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
          ],
        );
      },
    );
  }
}
