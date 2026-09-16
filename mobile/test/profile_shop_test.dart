// My Profile → "About shop" / "Manage shop" widget tests.
//
// Pins the role split the backend enforces (GET /api/store open to both staff
// roles, PUT + logo writes `role:admin`): the profile screen must show the
// shop summary to everyone and the owner editor only to admins.
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:bizbite/core/network/dio_client.dart';
import 'package:bizbite/core/storage/secure_token_storage.dart';
import 'package:bizbite/core/storage/token_store.dart';
import 'package:bizbite/features/auth/data/models/store_profile_model.dart';
import 'package:bizbite/features/auth/data/models/user_model.dart';
import 'package:bizbite/features/auth/data/repositories/auth_repository.dart';
import 'package:bizbite/features/auth/data/repositories/store_repository.dart';
import 'package:bizbite/features/auth/session_controller.dart';
import 'package:bizbite/presentation/screens/profile_screen.dart';
import 'package:bizbite/presentation/services/print_settings.dart';
import 'package:bizbite/presentation/theme/bizbite_theme.dart';

/// In-memory TokenStore so tests never touch Keychain/Keystore.
class _MemoryTokenStore extends TokenStore {
  String? token;
  String? cachedUser;
  String? cachedStore;

  @override
  Future<void> saveToken(String value) async => token = value;

  @override
  Future<String?> readToken() async => token;

  @override
  Future<void> deleteToken() async => token = null;

  @override
  Future<void> saveCachedUser(String userJson) async => cachedUser = userJson;

  @override
  Future<String?> readCachedUser() async => cachedUser;

  @override
  Future<void> deleteCachedUser() async => cachedUser = null;

  @override
  Future<void> saveCachedStore(String storeJson) async =>
      cachedStore = storeJson;

  @override
  Future<String?> readCachedStore() async => cachedStore;

  @override
  Future<void> deleteCachedStore() async => cachedStore = null;

  @override
  Future<void> clearSession() async {
    token = null;
    cachedUser = null;
    cachedStore = null;
  }

  @override
  Future<bool> hasSession() async => token != null && token!.isNotEmpty;
}

/// Deliberately left without a logo URL: `Image.network` would need a mocked
/// HTTP client, and the monogram fallback is the path under test here.
const StoreProfileModel _store = StoreProfileModel(
  id: 7,
  name: 'Apna Zaika',
  phone: '9876543210',
  alternatePhone: '9876500000',
  address: '12 MG Road',
  city: 'Indore',
  state: 'MP',
  pincode: '452001',
  gstin: '23ABCDE1234F1Z5',
  fssaiLicense: '1234567890123',
  upiVpa: 'apna@upi',
  printHeader: 'APNA ZAIKA',
  printFooter: 'Thank you, visit again!',
);

SessionController _signedInSession({required UserRole role}) {
  final client = DioClient(tokenStorage: SecureTokenStorage());
  final session = SessionController(
    tokenStore: _MemoryTokenStore(),
    authRepository: AuthRepository(client: client),
    storeRepository: StoreRepository(client: client),
    client: client,
  );

  session.user = UserModel(
    id: role == UserRole.admin ? 1 : 2,
    name: 'Riya Sharma',
    email: 'riya@apna.test',
    phone: '9000000000',
    role: role,
    storeId: _store.id,
  );
  session.store = _store;
  return session;
}

Future<void> _pumpProfile(
  WidgetTester tester,
  SessionController session,
) async {
  // A tall surface so every card in the ListView is laid out (off-screen
  // children of a ListView are never mounted, so `find.text` would miss them).
  tester.view.physicalSize = const Size(1200, 9000);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);

  await tester.pumpWidget(
    MaterialApp(
      theme: BizBiteTheme.light(),
      home: ProfileScreen(
        session: session,
        printSettings: PrintSettings(),
      ),
    ),
  );
  await tester.pump();
}

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));

  testWidgets('owner sees the About shop summary AND the Manage shop editor',
      (WidgetTester tester) async {
    await _pumpProfile(tester, _signedInSession(role: UserRole.admin));

    // --- About shop (everyone) -------------------------------------------
    expect(find.text('ABOUT SHOP'), findsOneWidget);
    expect(find.text('12 MG Road, Indore, MP, 452001'), findsOneWidget);
    // Found twice: the About-shop summary row AND the pre-filled editor field.
    expect(find.text('apna@upi'), findsNWidgets(2));
    expect(find.text('Top: APNA ZAIKA'), findsOneWidget);
    expect(find.text('Bottom: Thank you, visit again!'), findsOneWidget);
    expect(find.byTooltip('Refresh shop details'), findsOneWidget);

    // --- Manage shop (owner only) ----------------------------------------
    expect(find.text('Manage shop'), findsOneWidget);
    expect(find.text('Upload logo'), findsOneWidget);
    expect(find.text('Save shop details'), findsOneWidget);
    expect(find.text('Charge GST on bills'), findsOneWidget);

    // The editor is pre-filled from the cached store block.
    expect(find.widgetWithText(TextField, 'Apna Zaika'), findsOneWidget);
    expect(find.widgetWithText(TextField, '12 MG Road'), findsOneWidget);
    expect(find.widgetWithText(TextField, 'Indore'), findsOneWidget);
    expect(find.widgetWithText(TextField, '452001'), findsOneWidget);
    expect(find.widgetWithText(TextField, '23ABCDE1234F1Z5'), findsOneWidget);
    expect(find.widgetWithText(TextField, 'INR'), findsOneWidget);
  });

  testWidgets('cashier sees a read-only About shop card, never the editor',
      (WidgetTester tester) async {
    await _pumpProfile(tester, _signedInSession(role: UserRole.cashier));

    expect(find.text('ABOUT SHOP'), findsOneWidget);
    // Exactly one occurrence: the summary heading — there is no form copy.
    expect(find.text('Apna Zaika'), findsOneWidget);
    expect(
      find.text('Only the store owner can change these details.'),
      findsOneWidget,
    );

    expect(find.text('Manage shop'), findsNothing);
    expect(find.text('Save shop details'), findsNothing);
    expect(find.text('Upload logo'), findsNothing);
    expect(find.widgetWithText(TextField, 'Apna Zaika'), findsNothing);
  });
}
