import 'package:flutter/material.dart';

import '../../features/auth/session_controller.dart';

/// Full-screen boot phase shown while [SessionController.boot] runs.
///
/// The state's initState kicks off the boot sequence exactly once per app
/// launch; the session notifier then swaps this screen out via the root
/// ListenableBuilder.
class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key, required this.session});

  final SessionController session;

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  bool _booted = false;

  @override
  void initState() {
    super.initState();
    if (!_booted) {
      _booted = true;
      widget.session.boot();
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.storefront, size: 72, color: scheme.primary),
          SizedBox(height: 16),
          Text(
            'BizBite',
            style: theme.textTheme.headlineMedium,
            selectionColor: scheme.onSurface,
          ),
          SizedBox(height: 8),
          Text(
            'Loading your outlet…',
            style: theme.textTheme.bodyLarge,
            selectionColor: scheme.onSurfaceVariant,
          ),
        ],
      ),
    );
  }
}