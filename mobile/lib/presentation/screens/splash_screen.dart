import 'package:flutter/material.dart';

import '../../features/auth/session_controller.dart';
import '../theme/bizbite_theme.dart';

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

    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(gradient: AppGradients.page),
        child: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                height: 72,
                width: 72,
                decoration: const BoxDecoration(
                    gradient: AppGradients.brandIndigo,
                    shape: BoxShape.circle),
                child: const Icon(Icons.storefront_rounded,
                    size: 36, color: Colors.white),
              ),
              const SizedBox(height: AppSpacing.lg),
              Text('BizBite', style: theme.textTheme.headlineMedium),
              const SizedBox(height: AppSpacing.sm),
              Text('Loading your outlet…',
                  style: theme.textTheme.bodyLarge
                      ?.copyWith(color: AppColors.muted)),
              const SizedBox(height: AppSpacing.lg),
              const SizedBox(
                width: 28,
                height: 28,
                child: CircularProgressIndicator(strokeWidth: 3),
              ),
            ],
          ),
        ),
      ),
    );
  }
}