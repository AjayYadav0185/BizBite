// Single source of truth for every SQLite cache key in `cache_entries`.
//
// Keys are namespaced with `family.` prefixes so an optimistic patch can
// target a whole family (`queue.%`) and an endpoint can drop everything it
// owns without touching other screens' caches.
class CacheKeys {
  CacheKeys._();

  /// Whole-screen reads — value is the RAW API envelope (`{'orders': [...]}`)
  /// so the repository's existing parser can decode it unchanged.
  static const String menu = 'menu.snapshot';
  static const String tables = 'console.tables';
  static const String campaigns = 'console.campaigns';
  static const String staff = 'console.staff';
  static const String shifts = 'shifts.list';
  static const String wallet = 'wallet.balance';

  /// Envelope key holding the list inside each cached payload.
  static const String ordersListKey = 'orders';
  static const String shiftsListKey = 'shifts';
  static const String tablesListKey = 'tables';
  static const String campaignsListKey = 'campaigns';
  static const String staffListKey = 'staff';
  static const String itemsListKey = 'items';
  static const String categoriesListKey = 'categories';

  /// `GET /api/orders?status=open&type=dine_in` → one cache per filter combo,
  /// so switching filters offline never mixes two result sets.
  static String queue({bool openOnly = false, String? type}) {
    final scope = openOnly ? 'open' : 'all';
    final kind = (type ?? '').trim().isEmpty ? 'any' : type!.trim();
    return 'queue.$scope.$kind';
  }

  static const String queuePrefix = 'queue.';

  /// Per-day / per-range report caches (read-only, never patched).
  static String hourly(String date) => 'report.hourly.$date';
  static String bestSellers(String from, String to) => 'report.best.$from.$to';
  static String range(String from, String to) => 'report.range.$from.$to';

  /// Everything owned by a signed-out session is wiped with this list.
  static const List<String> all = [
    menu,
    tables,
    campaigns,
    staff,
    shifts,
    wallet,
  ];

  /// Prefixes wiped on sign-out (families with dynamic keys).
  static const List<String> prefixes = [
    queuePrefix,
    'report.hourly.',
    'report.best.',
    'report.range.',
  ];
}
