import 'package:flutter/foundation.dart'
    show TargetPlatform, defaultTargetPlatform, kIsWeb;
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/network/api_exception.dart';
import '../../features/auth/data/models/store_profile_model.dart';
import '../../features/auth/session_controller.dart';
import '../services/print_settings.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/status_banner.dart';

/// My Profile — self-service account screen for signed-in staff.
///
/// Layout:
///   1. Identity      — own avatar / name / email / role
///   2. About shop    — read-only store branding for EVERY staff member
///                      (logo, name, address, phones, tax ids, UPI VPA,
///                      receipt template) with a one-tap refresh
///   3. Manage shop   — owner (admin) only: edit the SAME store fields plus
///                      upload / replace / remove the shop logo
///   4. Printing      — till-level [PrintSettings] toggle
///   5. Personal details — own display name / phone (`PUT /profile`)
///   6. Change password  — `PUT /profile/password`
///
/// Every write funnels through [SessionController], so the vaulted +
/// in-memory profile/store stay in sync and every screen reacts instantly.
class ProfileScreen extends StatefulWidget {
  const ProfileScreen({
    super.key,
    required this.session,
    required this.printSettings,
  });

  final SessionController session;
  final PrintSettings printSettings;

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

  // --- Shop (store profile) state ------------------------------------------
  final ImagePicker _imagePicker = ImagePicker();

  late final TextEditingController _shopName;
  late final TextEditingController _shopPhone;
  late final TextEditingController _shopAlternatePhone;
  late final TextEditingController _shopAddress;
  late final TextEditingController _shopCity;
  late final TextEditingController _shopState;
  late final TextEditingController _shopPincode;
  late final TextEditingController _shopGstin;
  late final TextEditingController _shopFssai;
  late final TextEditingController _shopUpi;
  late final TextEditingController _shopCurrency;
  late final TextEditingController _shopGstRate;
  late final TextEditingController _shopPrintHeader;
  late final TextEditingController _shopPrintFooter;

  bool _shopGstEnabled = false;

  /// The shop form is populated on the FIRST build where a store payload is
  /// available — a cold restore can enter the portal from the vaulted profile
  /// before the silent `GET /api/store` refresh lands.
  bool _storeFormReady = false;

  bool _savingStore = false;
  bool _savingLogo = false;
  String _storeMessage = '';
  bool _storeError = false;

  SessionController get _session => widget.session;

  /// Camera capture is only offered where `image_picker` supports it
  /// (Android / iOS). Gallery works everywhere, web included.
  bool get _cameraSupported =>
      !kIsWeb &&
      (defaultTargetPlatform == TargetPlatform.android ||
          defaultTargetPlatform == TargetPlatform.iOS);

  @override
  void initState() {
    super.initState();
    _name = TextEditingController(text: _session.user?.name ?? '');
    _phone = TextEditingController(text: _session.user?.phone ?? '');

    _shopName = TextEditingController();
    _shopPhone = TextEditingController();
    _shopAlternatePhone = TextEditingController();
    _shopAddress = TextEditingController();
    _shopCity = TextEditingController();
    _shopState = TextEditingController();
    _shopPincode = TextEditingController();
    _shopGstin = TextEditingController();
    _shopFssai = TextEditingController();
    _shopUpi = TextEditingController();
    _shopCurrency = TextEditingController();
    _shopGstRate = TextEditingController();
    _shopPrintHeader = TextEditingController();
    _shopPrintFooter = TextEditingController();

    final store = _session.store;
    if (store != null) {
      _storeFormReady = true;
      _fillStoreForm(store);
    }
  }

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _currentPassword.dispose();
    _newPassword.dispose();
    _confirmPassword.dispose();
    _shopName.dispose();
    _shopPhone.dispose();
    _shopAlternatePhone.dispose();
    _shopAddress.dispose();
    _shopCity.dispose();
    _shopState.dispose();
    _shopPincode.dispose();
    _shopGstin.dispose();
    _shopFssai.dispose();
    _shopUpi.dispose();
    _shopCurrency.dispose();
    _shopGstRate.dispose();
    _shopPrintHeader.dispose();
    _shopPrintFooter.dispose();
    super.dispose();
  }

  /// Copy a store payload into the editable controllers (once on first paint
  /// and again after every successful server round-trip).
  void _fillStoreForm(StoreProfileModel store) {
    _shopName.text = store.name;
    _shopPhone.text = store.phone;
    _shopAlternatePhone.text = store.alternatePhone;
    _shopAddress.text = store.address;
    _shopCity.text = store.city;
    _shopState.text = store.state;
    _shopPincode.text = store.pincode;
    _shopGstin.text = store.gstin;
    _shopFssai.text = store.fssaiLicense;
    _shopUpi.text = store.upiVpa;
    _shopCurrency.text = store.currency;
    _shopGstRate.text = store.defaultGstRate == 0
        ? '0'
        : store.defaultGstRate
            .toStringAsFixed(2)
            .replaceAll(RegExp(r'\.?0+$'), '');
    _shopPrintHeader.text = store.printHeader;
    _shopPrintFooter.text = store.printFooter;
    _shopGstEnabled = store.isGstEnabled;
  }

  /// First-paint population guard for the shop form (see [_storeFormReady]).
  void _maybeFillStoreForm() {
    if (_storeFormReady) return;
    final store = _session.store;
    if (store == null) return;
    _storeFormReady = true;
    _fillStoreForm(store);
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
          listenable: Listenable.merge([_session, widget.printSettings]),
          builder: (context, _) {
            // The store payload can land after the first paint (vaulted
            // profile → silent GET /api/store), so populate the form here.
            _maybeFillStoreForm();
            return ListView(
              padding: const EdgeInsets.fromLTRB(12, 12, 12, 32),
              children: [
                _identityCard(),
                const SizedBox(height: 12),
                _shopCard(),
                if (_session.isAdmin) ...[
                  const SizedBox(height: 12),
                  _manageShopCard(),
                ],
                const SizedBox(height: 12),
                _printingCard(),
                const SizedBox(height: 12),
                _profileCard(),
                const SizedBox(height: 12),
                _passwordCard(),
              ],
            );
          },
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

  Widget _printingCard() {
    final preview = widget.printSettings.previewBeforePrint;
    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppColors.border),
        boxShadow: AppShadows.card,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  color: AppColors.infoBg,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.receipt_long_rounded,
                  size: 20,
                  color: AppColors.primaryDeep,
                ),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Receipt printing',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                        color: AppColors.ink,
                      ),
                    ),
                    SizedBox(height: 2),
                    Text(
                      'Preview stage between order and print',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                        color: AppColors.muted,
                      ),
                    ),
                  ],
                ),
              ),
              Switch.adaptive(
                value: preview,
                activeThumbColor: AppColors.primary,
                onChanged: (value) =>
                    widget.printSettings.setPreviewBeforePrint(value),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(AppSpacing.md),
            decoration: BoxDecoration(
              color: preview ? AppColors.infoBg : AppColors.surfaceMuted,
              borderRadius: BorderRadius.circular(AppRadius.md),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(
                  preview ? Icons.visibility_outlined : Icons.bolt_rounded,
                  size: 18,
                  color: preview ? AppColors.primaryDeep : AppColors.slate500,
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    preview
                        ? 'ON — settle → receipt preview → tap Print Bill. '
                              'Best when you verify each bill first.'
                        : 'OFF — settle → bill prints straight away, no '
                              'preview. Billing grid stays ready for the next '
                              'customer (rush-hour mode).',
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                      color: AppColors.slate500,
                    ),
                  ),
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

  // =====================================================================
  // Shop / store profile (About shop + Manage shop)
  // =====================================================================

  /// `PUT /api/store` — persist every editable shop field (owner only).
  Future<void> _saveShopDetails() async {
    if (_savingStore) return;

    final name = _shopName.text.trim();
    if (name.isEmpty) {
      setState(() {
        _storeMessage = 'Please enter the shop name.';
        _storeError = true;
      });
      return;
    }

    final currency = _shopCurrency.text.trim().isEmpty
        ? 'INR'
        : _shopCurrency.text.trim().toUpperCase();

    setState(() {
      _savingStore = true;
      _storeMessage = '';
      _storeError = false;
    });

    try {
      final fresh = await _session.updateStore({
        'name': name,
        'phone': _shopPhone.text.trim(),
        'alternate_phone': _shopAlternatePhone.text.trim(),
        'address': _shopAddress.text.trim(),
        'city': _shopCity.text.trim(),
        'state': _shopState.text.trim(),
        'pincode': _shopPincode.text.trim(),
        'gstin': _shopGstin.text.trim(),
        'fssai_license': _shopFssai.text.trim(),
        'upi_vpa': _shopUpi.text.trim(),
        'currency': currency,
        'default_gst_rate':
            double.tryParse(_shopGstRate.text.trim())?.clamp(0, 28) ?? 0,
        'is_gst_enabled': _shopGstEnabled,
        'print_header': _shopPrintHeader.text.trim(),
        'print_footer': _shopPrintFooter.text.trim(),
      });
      if (!mounted) return;
      setState(() {
        _fillStoreForm(fresh);
        _storeMessage = 'Shop details saved.';
        _storeError = false;
      });
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _storeMessage = error.errorFor('name') ??
            error.errorFor('currency') ??
            error.errorFor('default_gst_rate') ??
            error.errorFor('gstin') ??
            error.errorFor('pincode') ??
            error.message;
        _storeError = true;
      });
    } finally {
      if (mounted) setState(() => _savingStore = false);
    }
  }

  /// `GET /api/store` — re-pull the shop details on demand.
  Future<void> _refreshShop() async {
    if (_savingStore) return;
    setState(() {
      _savingStore = true;
      _storeMessage = '';
      _storeError = false;
    });
    try {
      final fresh = await _session.refreshStore();
      if (!mounted) return;
      setState(() {
        _fillStoreForm(fresh);
        _storeMessage = 'Shop details refreshed.';
        _storeError = false;
      });
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _storeMessage = error.message;
        _storeError = true;
      });
    } finally {
      if (mounted) setState(() => _savingStore = false);
    }
  }

  /// Bottom sheet asking where the logo should come from.
  Future<ImageSource?> _chooseLogoSource() {
    return showModalBottomSheet<ImageSource>(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(
          top: Radius.circular(AppRadius.xxl),
        ),
      ),
      builder: (sheetContext) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 12),
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: BizBiteTheme.hairline,
                borderRadius: BorderRadius.circular(AppRadius.full),
              ),
            ),
            const SizedBox(height: 14),
            const Text(
              'Shop logo',
              style: TextStyle(
                fontSize: 16.5,
                fontWeight: FontWeight.w800,
                color: AppColors.ink,
              ),
            ),
            const SizedBox(height: 2),
            const Text(
              'Square PNG or JPG works best (max 2 MB).',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w500,
                color: AppColors.muted,
              ),
            ),
            const SizedBox(height: 10),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined,
                  color: AppColors.primaryDeep),
              title: const Text(
                'Choose from gallery',
                style: TextStyle(fontWeight: FontWeight.w700),
              ),
              onTap: () => Navigator.of(sheetContext).pop(ImageSource.gallery),
            ),
            if (_cameraSupported)
              ListTile(
                leading: const Icon(Icons.photo_camera_outlined,
                    color: AppColors.primaryDeep),
                title: const Text(
                  'Take a photo',
                  style: TextStyle(fontWeight: FontWeight.w700),
                ),
                onTap: () => Navigator.of(sheetContext).pop(ImageSource.camera),
              ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }

  /// Pick an image and `POST /api/store/logo` it (owner only).
  ///
  /// The picked bytes are uploaded directly (not a file path) so the same
  /// code path works on Android/iOS and Flutter Web.
  Future<void> _pickAndUploadLogo() async {
    if (_savingLogo) return;

    final source = await _chooseLogoSource();
    if (source == null || !mounted) return;

    XFile? picked;
    try {
      picked = await _imagePicker.pickImage(
        source: source,
        maxWidth: 1200,
        maxHeight: 1200,
        imageQuality: 90,
      );
    } catch (_) {
      picked = null;
    }
    if (picked == null) return;

    try {
      final bytes = await picked.readAsBytes();
      if (bytes.length > 2 * 1024 * 1024) {
        setState(() {
          _storeMessage = 'That image is larger than 2 MB — pick a smaller one.';
          _storeError = true;
        });
        return;
      }

      setState(() {
        _savingLogo = true;
        _storeMessage = '';
        _storeError = false;
      });

      final fresh = await _session.uploadStoreLogo(
        bytes: bytes,
        filename: picked.name.isEmpty ? 'logo.jpg' : picked.name,
      );
      if (!mounted) return;
      setState(() {
        _fillStoreForm(fresh);
        _storeMessage = 'Shop logo updated.';
        _storeError = false;
      });
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _storeMessage = error.errorFor('logo') ?? error.message;
        _storeError = true;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _storeMessage = 'Could not read that image. Please try another one.';
        _storeError = true;
      });
    } finally {
      if (mounted) setState(() => _savingLogo = false);
    }
  }

  /// `DELETE /api/store/logo` — drop the uploaded logo (owner only).
  Future<void> _removeLogo() async {
    if (_savingLogo) return;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Remove shop logo?'),
        content: const Text(
          'Receipts and the store card fall back to the shop monogram.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(false),
            child: const Text('Keep'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(dialogContext).pop(true),
            child: const Text('Remove'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    setState(() {
      _savingLogo = true;
      _storeMessage = '';
      _storeError = false;
    });
    try {
      final fresh = await _session.removeStoreLogo();
      if (!mounted) return;
      setState(() {
        _fillStoreForm(fresh);
        _storeMessage = 'Shop logo removed.';
        _storeError = false;
      });
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _storeMessage = error.message;
        _storeError = true;
      });
    } finally {
      if (mounted) setState(() => _savingLogo = false);
    }
  }

  // ---------------------------------------------------------------------
  // Shop UI building blocks
  // ---------------------------------------------------------------------

  BoxDecoration _cardDecoration() => BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppColors.border),
        boxShadow: AppShadows.card,
      );

  /// Circular shop logo with a monogram fallback (also covers a logo URL that
  /// cannot be fetched — offline, wrong APP_URL, removed file).
  Widget _shopLogoAvatar({double size = 52}) {
    final store = _session.store;
    final logoUrl = store?.logoUrl ?? '';
    final initial = store?.initial ?? 'B';
    final fallback = Text(
      initial,
      style: TextStyle(
        fontSize: size * 0.4,
        fontWeight: FontWeight.w800,
        color: Colors.white,
      ),
    );

    return Container(
      width: size,
      height: size,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        gradient: AppGradients.brandMain,
        shape: BoxShape.circle,
        border: Border.all(color: AppColors.border),
        boxShadow: AppShadows.card,
      ),
      child: logoUrl.isEmpty
          ? fallback
          : ClipOval(
              child: Image.network(
                logoUrl,
                width: size,
                height: size,
                fit: BoxFit.cover,
                errorBuilder: (context, error, stackTrace) => fallback,
              ),
            ),
    );
  }

  Widget _shopInfoRow(IconData icon, String label, String value) {
    if (value.trim().isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(top: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 16, color: AppColors.primaryDeep),
          const SizedBox(width: 8),
          SizedBox(
            width: 86,
            child: Text(
              label,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: AppColors.muted,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontSize: 12.5,
                fontWeight: FontWeight.w700,
                color: AppColors.ink,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _shopChip(IconData icon, String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: AppColors.surfaceMuted,
        borderRadius: BorderRadius.circular(AppRadius.sm),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 13, color: AppColors.slate500),
          const SizedBox(width: 5),
          Text(
            label,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: AppColors.slate500,
            ),
          ),
        ],
      ),
    );
  }

  /// "About shop" — read-only store branding for EVERY signed-in staff member.
  Widget _shopCard() {
    final store = _session.store;

    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              _shopLogoAvatar(),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'ABOUT SHOP',
                      style: TextStyle(
                        fontSize: 10.5,
                        fontWeight: FontWeight.w800,
                        letterSpacing: 1.1,
                        color: AppColors.muted,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      store == null || store.name.isEmpty
                          ? 'No store linked'
                          : store.name,
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
                      store == null
                          ? 'This login has no store attached.'
                          : (store.fullAddress.isEmpty
                              ? 'Add the shop address under Manage shop.'
                              : store.fullAddress),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 11.5,
                        fontWeight: FontWeight.w500,
                        color: AppColors.muted,
                      ),
                    ),
                  ],
                ),
              ),
              IconButton(
                tooltip: 'Refresh shop details',
                onPressed: _savingStore ? null : _refreshShop,
                icon: _savingStore
                    ? const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2.2),
                      )
                    : const Icon(Icons.refresh_rounded, size: 20),
              ),
            ],
          ),
          StatusBanner(text: _storeMessage, error: _storeError),
          if (store != null) ...[
            const SizedBox(height: AppSpacing.sm),
            _shopInfoRow(Icons.phone_rounded, 'Phone', store.phone),
            _shopInfoRow(
              Icons.phone_forwarded_rounded,
              'Alt. phone',
              store.alternatePhone,
            ),
            _shopInfoRow(Icons.qr_code_rounded, 'UPI VPA', store.upiVpa),
            _shopInfoRow(Icons.receipt_rounded, 'GSTIN', store.gstin),
            _shopInfoRow(
              Icons.verified_user_rounded,
              'FSSAI',
              store.fssaiLicense,
            ),
            const SizedBox(height: AppSpacing.md),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                _shopChip(Icons.currency_exchange_rounded, store.currency),
                _shopChip(
                  Icons.account_balance_rounded,
                  store.isGstEnabled
                      ? 'GST ${store.defaultGstRate.toStringAsFixed(0)}%'
                      : 'GST off',
                ),
                if (store.city.isNotEmpty)
                  _shopChip(Icons.location_city_rounded, store.city),
              ],
            ),
            if (store.printHeader.isNotEmpty ||
                store.printFooter.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.md),
              _receiptTemplatePreview(store),
            ],
            if (!_session.isAdmin) ...[
              const SizedBox(height: AppSpacing.sm),
              const Text(
                'Only the store owner can change these details.',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: AppColors.muted,
                ),
              ),
            ],
          ],
        ],
      ),
    );
  }

  /// Read-only preview of the owner's thermal-receipt template lines.
  Widget _receiptTemplatePreview(StoreProfileModel store) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.surfaceMuted,
        borderRadius: BorderRadius.circular(AppRadius.md),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Receipt template',
            style: TextStyle(
              fontSize: 11.5,
              fontWeight: FontWeight.w800,
              color: AppColors.ink,
            ),
          ),
          if (store.printHeader.isNotEmpty) ...[
            const SizedBox(height: 4),
            Text(
              'Top: ${store.printHeader}',
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w500,
                color: AppColors.slate500,
              ),
            ),
          ],
          if (store.printFooter.isNotEmpty) ...[
            const SizedBox(height: 2),
            Text(
              'Bottom: ${store.printFooter}',
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w500,
                color: AppColors.slate500,
              ),
            ),
          ],
        ],
      ),
    );
  }

  // ---------------------------------------------------------------------
  // Manage shop (owner / admin only)
  // ---------------------------------------------------------------------

  /// "Manage shop" — the owner's editor for every store field the "About
  /// shop" card displays, plus the logo upload / replace / remove actions.
  Widget _manageShopCard() {
    final busy = _savingStore || _savingLogo;
    final hasLogo = (_session.store?.logoUrl ?? '').isNotEmpty;

    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text(
            'Manage shop',
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w800,
              color: AppColors.ink,
            ),
          ),
          const SizedBox(height: 2),
          const Text(
            'Owner only — logo, address, tax ids, collection UPI and the '
            'printed receipt template. Staff see these read-only.',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w500,
              color: AppColors.muted,
            ),
          ),
          const SizedBox(height: AppSpacing.lg),
          Row(
            children: [
              _shopLogoAvatar(size: 56),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    OutlinedButton.icon(
                      onPressed: busy ? null : _pickAndUploadLogo,
                      icon: _savingLogo
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child:
                                  CircularProgressIndicator(strokeWidth: 2.2),
                            )
                          : const Icon(
                              Icons.add_photo_alternate_outlined,
                              size: 18,
                            ),
                      label: Text(
                        _savingLogo
                            ? 'Uploading…'
                            : (hasLogo ? 'Change logo' : 'Upload logo'),
                      ),
                    ),
                    if (hasLogo)
                      TextButton.icon(
                        onPressed: busy ? null : _removeLogo,
                        style: TextButton.styleFrom(
                          foregroundColor: AppColors.error,
                        ),
                        icon: const Icon(
                          Icons.delete_outline_rounded,
                          size: 18,
                        ),
                        label: const Text(
                          'Remove logo',
                          style: TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.lg),
          ..._shopFormFields(),
          const SizedBox(height: AppSpacing.lg),
          FilledButton.icon(
            onPressed: busy ? null : _saveShopDetails,
            icon: _savingStore
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2.2),
                  )
                : const Icon(Icons.save_outlined, size: 18),
            label: Text(_savingStore ? 'Saving…' : 'Save shop details'),
          ),
        ],
      ),
    );
  }

  /// The editable shop fields, grouped by section. Kept in one place so the
  /// Manage-shop card stays readable.
  List<Widget> _shopFormFields() {
    return [
      _shopSectionLabel('Shop identity'),
      _shopField(
        _shopName,
        'Shop name',
        Icons.storefront_rounded,
        textCapitalization: TextCapitalization.words,
      ),
      _shopField(
        _shopPhone,
        'Phone',
        Icons.phone_outlined,
        keyboardType: TextInputType.phone,
      ),
      _shopField(
        _shopAlternatePhone,
        'Alternate phone (optional)',
        Icons.phone_forwarded_outlined,
        keyboardType: TextInputType.phone,
      ),
      const SizedBox(height: AppSpacing.md),
      _shopSectionLabel('Address'),
      _shopField(
        _shopAddress,
        'Street / building',
        Icons.location_on_outlined,
        textCapitalization: TextCapitalization.words,
      ),
      _shopField(
        _shopCity,
        'City',
        Icons.location_city_rounded,
        textCapitalization: TextCapitalization.words,
      ),
      _shopField(
        _shopState,
        'State',
        Icons.map_outlined,
        textCapitalization: TextCapitalization.words,
      ),
      _shopField(
        _shopPincode,
        'PIN code',
        Icons.pin_drop_outlined,
        keyboardType: TextInputType.number,
      ),
      const SizedBox(height: AppSpacing.md),
      _shopSectionLabel('Tax & compliance'),
      _shopField(
        _shopGstin,
        'GSTIN (optional)',
        Icons.receipt_long_outlined,
        textCapitalization: TextCapitalization.characters,
      ),
      _shopField(
        _shopFssai,
        'FSSAI licence (optional)',
        Icons.verified_user_outlined,
      ),
      _shopField(
        _shopUpi,
        'Collection UPI VPA (optional)',
        Icons.qr_code_rounded,
        hint: 'e.g. myshop@upi',
      ),
      _shopField(
        _shopCurrency,
        'Currency code',
        Icons.currency_exchange_rounded,
        textCapitalization: TextCapitalization.characters,
        maxLength: 3,
      ),
      _gstDefaultsRow(),
      const SizedBox(height: AppSpacing.md),
      _shopSectionLabel('Receipt template'),
      _shopField(
        _shopPrintHeader,
        'Receipt header line',
        Icons.vertical_align_top_rounded,
        hint: 'e.g. MY RESTAURANT',
      ),
      _shopField(
        _shopPrintFooter,
        'Receipt footer line',
        Icons.vertical_align_bottom_rounded,
        hint: 'e.g. Thank you, visit again!',
      ),
    ];
  }

  // ---------------------------------------------------------------------
  // Manage-shop form primitives
  // ---------------------------------------------------------------------

  Widget _shopSectionLabel(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 2),
      child: Text(
        text.toUpperCase(),
        style: const TextStyle(
          fontSize: 10.5,
          fontWeight: FontWeight.w800,
          letterSpacing: 1.1,
          color: AppColors.muted,
        ),
      ),
    );
  }

  Widget _shopField(
    TextEditingController controller,
    String label,
    IconData icon, {
    TextInputType? keyboardType,
    TextCapitalization textCapitalization = TextCapitalization.none,
    String? hint,
    int? maxLength,
  }) {
    return Padding(
      padding: const EdgeInsets.only(top: AppSpacing.md),
      child: TextField(
        controller: controller,
        keyboardType: keyboardType,
        textCapitalization: textCapitalization,
        maxLength: maxLength,
        decoration: InputDecoration(
          labelText: label,
          hintText: hint,
          // `maxLength` would otherwise draw a "0/3" counter under the field.
          counterText: '',
          prefixIcon: Icon(icon),
        ),
      ),
    );
  }

  /// GST defaults: an on/off switch plus the store-wide default rate.
  Widget _gstDefaultsRow() {
    return Padding(
      padding: const EdgeInsets.only(top: AppSpacing.md),
      child: Column(
        children: [
          Row(
            children: [
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Charge GST on bills',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: AppColors.ink,
                      ),
                    ),
                    SizedBox(height: 1),
                    Text(
                      'Store-level default — per-item rates on the menu still '
                      'govern each bill.',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w500,
                        color: AppColors.muted,
                      ),
                    ),
                  ],
                ),
              ),
              Switch.adaptive(
                value: _shopGstEnabled,
                activeThumbColor: AppColors.primary,
                onChanged: _savingStore
                    ? null
                    : (value) => setState(() => _shopGstEnabled = value),
              ),
            ],
          ),
          _shopField(
            _shopGstRate,
            'Default GST rate (%)',
            Icons.percent_rounded,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
          ),
        ],
      ),
    );
  }
}
