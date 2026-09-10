import 'package:flutter/material.dart';

import '../../features/auth/session_controller.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/status_banner.dart';

/// Sign-in screen, mirroring the Laravel `Login` Livewire component.
///
/// Credentials go to `POST /api/login`; on success the SessionController
/// flips to `online` and the root ListenableBuilder swaps this screen out.
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.session});

  final SessionController session;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController _email = TextEditingController();
  final TextEditingController _password = TextEditingController();

  bool get _busy => widget.session.phase == SessionPhase.signingIn;

  void _submit() {
    if (_busy) return;
    widget.session.login(
      email: _email.text,
      password: _password.text,
    );
  }

  @override
  Widget build(BuildContext context) {
    // Spec §9 auth: light bg, centered brand header, white card form,
    // filled primary CTA + text links.
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(gradient: AppGradients.page),
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(AppSpacing.xl),
            child: Container(
              width: 420,
              padding: const EdgeInsets.all(AppSpacing.xl),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(AppRadius.xl),
                border: Border.all(color: AppColors.border),
                boxShadow: AppShadows.card,
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const AuthBrandHeader(
                      title: 'BizBite POS',
                      subtitle: 'Sign in to start billing'),
                  const SizedBox(height: AppSpacing.xl),
                  StatusBanner(
                    text: widget.session.errorMessage ?? '',
                    error: true,
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  TextField(
                    controller: _email,
                    keyboardType: TextInputType.emailAddress,
                    decoration: const InputDecoration(
                      labelText: 'Email',
                      prefixIcon: Icon(Icons.mail_outline_rounded),
                    ),
                  ),
                  const SizedBox(height: AppSpacing.md),
                  TextField(
                    controller: _password,
                    obscureText: true,
                    onSubmitted: (_) => _submit(),
                    decoration: const InputDecoration(
                      labelText: 'Password',
                      prefixIcon: Icon(Icons.lock_outline_rounded),
                    ),
                  ),
                  const SizedBox(height: AppSpacing.xl),
                  FilledButton.icon(
                    icon: const Icon(Icons.login_rounded, size: 18),
                    label: Text(_busy ? 'Signing in…' : 'Sign in'),
                    onPressed: _busy ? null : _submit,
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}