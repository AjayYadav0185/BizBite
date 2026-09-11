import 'package:flutter/material.dart';

import '../../core/network/api_exception.dart';
import '../../features/auth/session_controller.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/status_banner.dart';

/// My Profile — self-service account screen for signed-in staff.
///
/// Edits own display name / phone (`PUT /profile`) and password
/// (`PUT /profile/password`) via [SessionController] so the vaulted +
/// in-memory profile stays in sync automatically.
class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key, required this.session});

  final SessionController session;

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  late final TextEditingController _name;
  late final TextEditingController _phone;
  final TextEditingController _currentPassword = TextEditingController();
  final TextEditingController _newPassword = TextEditingController();
  final TextEditingController _confirmPassword = TextEditingController();

  bool _savingProfile = false;
  bool _changingPassword = false;
  String _profileMessage = '';
  bool _profileError = false;
  String _passwordMessage = '';
  bool _passwordError = false;

  SessionController get _session => widget.session;

  @override
  void initState() {
    super.initState();
    _name = TextEditingController(text: _session.user?.name ?? '');
    _phone = TextEditingController(text: _session.user?.phone ?? '');
  }

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _currentPassword.dispose();
    _newPassword.dispose();
    _confirmPassword.dispose();
    super.dispose();
  }

  Future<void> _saveProfile() async {
    if (_savingProfile) return;
    final name = _name.text.trim();
    if (name.isEmpty) {
      setState(() {
        _profileMessage = 'Please enter your name.';
        _profileError = true;
      });
      return;
    }
    setState(() {
      _savingProfile = true;
      _profileMessage = '';
      _profileError = false;
    });
    try {
      final fresh = await _session.updateProfile(
        name: name,
        phone: _phone.text.trim(),
      );
      if (!mounted) return;
      setState(() {
        _name.text = fresh.name;
        _phone.text = fresh.phone;
        _profileMessage = 'Profile updated.';
        _profileError = false;
      });
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _profileMessage =
            error.errorFor('name') ?? error.errorFor('phone') ?? error.message;
        _profileError = true;
      });
    } finally {
      if (mounted) setState(() => _savingProfile = false);
    }
  }

  Future<void> _changePassword() async {
    if (_changingPassword) return;
    final current = _currentPassword.text;
    final next = _newPassword.text;
    if (current.isEmpty || next.isEmpty) {
      setState(() {
        _passwordMessage = 'Enter your current and new password.';
        _passwordError = true;
      });
      return;
    }
    if (next.length < 8) {
      setState(() {
        _passwordMessage = 'New password must be at least 8 characters.';
        _passwordError = true;
      });
      return;
    }
    if (next != _confirmPassword.text) {
      setState(() {
        _passwordMessage = 'New passwords do not match.';
        _passwordError = true;
      });
      return;
    }
    setState(() {
      _changingPassword = true;
      _passwordMessage = '';
      _passwordError = false;
    });
    try {
      await _session.changePassword(
        currentPassword: current,
        newPassword: next,
      );
      if (!mounted) return;
      _currentPassword.clear();
      _newPassword.clear();
      _confirmPassword.clear();
      setState(() {
        _passwordMessage = 'Password changed.';
        _passwordError = false;
      });
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _passwordMessage =
            error.errorFor('current_password') ??
            error.errorFor('password') ??
            error.message;
        _passwordError = true;
      });
    } finally {
      if (mounted) setState(() => _changingPassword = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: BizAppBar(
        title: 'My Profile',
        subtitle: _session.user?.email ?? 'Signed-in staff',
      ),
      body: Container(
        decoration: const BoxDecoration(gradient: AppGradients.page),
        child: ListenableBuilder(
          listenable: _session,
          builder: (context, _) => ListView(
            padding: const EdgeInsets.fromLTRB(12, 12, 12, 32),
            children: [
              _identityCard(),
              const SizedBox(height: 12),
              _profileCard(),
              const SizedBox(height: 12),
              _passwordCard(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _identityCard() {
    final user = _session.user;
    final name = (user?.name ?? '').trim();
    final initial = name.isEmpty ? 'B' : name[0].toUpperCase();
    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppColors.border),
        boxShadow: AppShadows.card,
      ),
      child: Row(
        children: [
          Container(
            width: 52,
            height: 52,
            alignment: Alignment.center,
            decoration: const BoxDecoration(
              gradient: AppGradients.brandMain,
              shape: BoxShape.circle,
            ),
            child: Text(
              initial,
              style: const TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.w800,
                color: Colors.white,
              ),
            ),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name.isEmpty ? 'BizBite staff' : name,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                    color: AppColors.ink,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  user?.email ?? 'Signed-in staff',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w500,
                    color: AppColors.muted,
                  ),
                ),
                const SizedBox(height: 6),
                StatusPill(
                  label: _session.isAdmin ? 'ADMIN' : 'CASHIER',
                  color: _session.isAdmin
                      ? AppColors.successDeep
                      : AppColors.slate500,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _profileCard() {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppColors.border),
        boxShadow: AppShadows.card,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text(
            'Personal details',
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w800,
              color: AppColors.ink,
            ),
          ),
          const SizedBox(height: 2),
          const Text(
            'Name and phone sync to the server instantly.',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w500,
              color: AppColors.muted,
            ),
          ),
          StatusBanner(text: _profileMessage, error: _profileError),
          const SizedBox(height: AppSpacing.md),
          TextField(
            controller: _name,
            textCapitalization: TextCapitalization.words,
            decoration: const InputDecoration(
              labelText: 'Full name',
              prefixIcon: Icon(Icons.person_outline_rounded),
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          TextField(
            controller: _phone,
            keyboardType: TextInputType.phone,
            decoration: const InputDecoration(
              labelText: 'Phone (optional)',
              prefixIcon: Icon(Icons.phone_outlined),
            ),
          ),
          const SizedBox(height: AppSpacing.lg),
          FilledButton.icon(
            onPressed: _savingProfile ? null : _saveProfile,
            icon: _savingProfile
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2.2),
                  )
                : const Icon(Icons.save_outlined, size: 18),
            label: Text(_savingProfile ? 'Saving…' : 'Save changes'),
          ),
        ],
      ),
    );
  }

  Widget _passwordCard() {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppColors.border),
        boxShadow: AppShadows.card,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text(
            'Change password',
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w800,
              color: AppColors.ink,
            ),
          ),
          const SizedBox(height: 2),
          const Text(
            'The server verifies your current password first.',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w500,
              color: AppColors.muted,
            ),
          ),
          StatusBanner(text: _passwordMessage, error: _passwordError),
          const SizedBox(height: AppSpacing.md),
          TextField(
            controller: _currentPassword,
            obscureText: true,
            decoration: const InputDecoration(
              labelText: 'Current password',
              prefixIcon: Icon(Icons.lock_outline_rounded),
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          TextField(
            controller: _newPassword,
            obscureText: true,
            decoration: const InputDecoration(
              labelText: 'New password (min 8 characters)',
              prefixIcon: Icon(Icons.lock_reset_rounded),
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          TextField(
            controller: _confirmPassword,
            obscureText: true,
            onSubmitted: (_) => _changePassword(),
            decoration: const InputDecoration(
              labelText: 'Confirm new password',
              prefixIcon: Icon(Icons.verified_user_outlined),
            ),
          ),
          const SizedBox(height: AppSpacing.lg),
          FilledButton.tonalIcon(
            onPressed: _changingPassword ? null : _changePassword,
            icon: _changingPassword
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2.2),
                  )
                : const Icon(Icons.key_rounded, size: 18),
            label: Text(_changingPassword ? 'Updating…' : 'Update password'),
          ),
        ],
      ),
    );
  }
}
