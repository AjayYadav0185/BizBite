import 'package:dio/dio.dart';

/// Typed, UI-ready failure model for every BizBite API error.
///
/// The [ErrorInterceptor] translates raw DioException objects into this type
/// so that Cubits never touch HTTP internals — they catch [ApiException] and
/// render [message] directly to the cashier.

/// Unwrap a thrown error into its typed [ApiException].
///
/// Repositories rethrow this from DioException catches so that every
/// controller/BLoC sees exactly one failure type, with a cashier-ready message.
ApiException apiExceptionFrom(Object? error) {
  if (error is ApiException) return error;
  if (error is DioException) {
    final inner = error.error;
    if (inner is ApiException) return inner;
    return ApiException.unknown(
      message: inner is String ? inner.toString() : null,
      cause: error,
    );
  }
  return ApiException.unknown(cause: error);
}

class ApiException implements Exception {
  ApiException({
    required this.message,
    this.statusCode,
    this.errors = const {},
    this.type = ApiExceptionType.unknown,
    this.original,
  });

  /// Cashier-safe explanation (already extracted from Laravel payloads).
  final String message;

  /// HTTP status code, when the failure crossed the wire.
  final int? statusCode;

  /// Laravel field-level validation errors (422): {field: [msg, msg]}.
  final Map<String, List<String>> errors;

  /// Machine-readable failure class for branching in BLoC states.
  final ApiExceptionType type;

  /// The originating exception (for logging, never for UI).
  final Object? original;

  bool get isAuthError => type == ApiExceptionType.unauthenticated;
  bool get isNetworkError =>
      type == ApiExceptionType.network ||
      type == ApiExceptionType.timeout ||
      type == ApiExceptionType.cancelled;
  bool get isValidationError => type == ApiExceptionType.validation;

  /// First validation message for a given field, if any.
  String? errorFor(String field) => errors[field]?.firstOrNull;

  @override
  String toString() => 'ApiException($type, $statusCode): $message';

  // ------------------------------------------------------------------
  // Factories used by the ErrorInterceptor
  // ------------------------------------------------------------------

  factory ApiException.network({Object? cause}) => ApiException(
        message:
            'Cannot reach the BizBite server. Check the outlet Wi-Fi connection.',
        type: ApiExceptionType.network,
        original: cause,
      );

  factory ApiException.timeout({Object? cause}) => ApiException(
        message: 'The server took too long to respond. Please retry.',
        type: ApiExceptionType.timeout,
        original: cause,
      );

  factory ApiException.unauthenticated({String? message, Object? cause}) =>
      ApiException(
        message: message ?? 'Session expired. Please sign in again.',
        statusCode: 401,
        type: ApiExceptionType.unauthenticated,
        original: cause,
      );

  factory ApiException.forbidden({Object? cause}) => ApiException(
        message: 'Your role is not allowed to perform this action.',
        statusCode: 403,
        type: ApiExceptionType.forbidden,
        original: cause,
      );

  factory ApiException.notFound({Object? cause}) => ApiException(
        message: 'The requested resource no longer exists.',
        statusCode: 404,
        type: ApiExceptionType.notFound,
        original: cause,
      );

  factory ApiException.validation({
    required String message,
    Map<String, List<String>> errors = const {},
    Object? cause,
  }) =>
      ApiException(
        message: message,
        statusCode: 422,
        errors: errors,
        type: ApiExceptionType.validation,
        original: cause,
      );

  factory ApiException.server({int? statusCode, Object? cause}) => ApiException(
        message: 'BizBite server error. Please try again in a moment.',
        statusCode: statusCode,
        type: ApiExceptionType.server,
        original: cause,
      );

  factory ApiException.cancelled({Object? cause}) => ApiException(
        message: 'Request cancelled.',
        type: ApiExceptionType.cancelled,
        original: cause,
      );

  factory ApiException.badCertificate({Object? cause}) => ApiException(
        message: 'Untrusted server certificate. Contact your administrator.',
        type: ApiExceptionType.badCertificate,
        original: cause,
      );

  factory ApiException.unknown({String? message, Object? cause}) => ApiException(
        message: message ?? 'Something went wrong. Please try again.',
        type: ApiExceptionType.unknown,
        original: cause,
      );
}

enum ApiExceptionType {
  network,
  timeout,
  cancelled,
  badCertificate,
  unauthenticated,
  forbidden,
  notFound,
  validation,
  server,
  unknown,
}
