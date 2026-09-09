import 'package:flutter/material.dart';

import '../../features/auth/session_controller.dart';
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
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Scaffold(
      body: Center(
        child: Container(
          width: 420,
          padding: EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                spacing: 12,
                children: [
                  Icon(Icons.storefront, size: 44, color: scheme.primary),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('BizBite POS',
                            style: theme.textTheme.headlineSmall,
                            selectionColor: scheme.onSurface),
                        Text('Sign in to start billing',
                            style: theme.textTheme.bodyMedium,
                            selectionColor: scheme.onSurfaceVariant),
                      ],
                    ),
                  ),
                ],
              ),
              SizedBox(height: 24),
              StatusBanner(
                text: widget.session.errorMessage ?? '',
                error: true,
              ),
              SizedBox(height: 16),
              TextField(
                controller: _email,
                obscureText: false,
                decoration: InputDecoration(
                  labelText: 'Email',
                  icon: Icon(Icons.mail),
                ),
              ),
              SizedBox(height: 8),
              TextField(
                controller: _password,
                obscureText: true,
                decoration: InputDecoration(
                  labelText: 'Password',
                  icon: Icon(Icons.password),
                ),
              ),
              SizedBox(height: 24),
              FilledButton.icon(
                icon: Icon(Icons.login, size: 18),
                label: Text(_busy ? 'Signing in…' : 'Sign in'),
                onPressed: _submit,
              ),
            ],
          ),
        ),
      ),
    );
  }
}