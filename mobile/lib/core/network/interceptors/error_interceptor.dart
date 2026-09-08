import 'package:dio/dio.dart';

import '../api_exception.dart';

/// Central failure translation layer: raw `DioException` -> typed
/// [ApiException] with a cashier-ready message, so no BLoC or widget ever
/// has to parse HTTP internals.
///
/// Laravel response shapes handled here:
///
///   401 {"message": "Unauthenticated."}                       -> session expiry
///   403 {"message": "..."}                                    -> forbidden
///   404 {...}                                                 -> not found
///   409/422 {"message": "...", "errors": {field: [..]}}       -> validation
///   5xx HTML/plain body                                       -> server
class ErrorInterceptor extends Interceptor {
  ErrorInterceptor({this.onSessionExpired});

  /// Fired exactly once when a 401 surfaces on an authenticated call.
  /// The auth Cubit listens and flushes the token + navigates to Login.
  final void Function(ApiException exception)? onSessionExpired;

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    final exception = _translate(err);

    if (exception.isAuthError) {
      onSessionExpired?.call(exception);
    }

    handler.next(err.copyWith(error: exception));
  }

  ApiException _translate(DioException err) {
    switch (err.type) {
      case DioExceptionType.connectionError:
      case DioExceptionType.connectionTimeout:
        return ApiException.network(cause: err);
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
      case DioExceptionType.transformTimeout:
        return ApiException.timeout(cause: err);
      case DioExceptionType.cancel:
        return ApiException.cancelled(cause: err);
      case DioExceptionType.badCertificate:
        return ApiException.badCertificate(cause: err);
      case DioExceptionType.badResponse:
        return _translateBadResponse(err);
      case DioExceptionType.unknown:
        return ApiException.unknown(
          message: err.error is ApiException
              ? (err.error as ApiException).message
              : null,
          cause: err,
        );
    }
  }

  ApiException _translateBadResponse(DioException err) {
    final status = err.response?.statusCode;
    final data = err.response?.data;

    // Laravel JSON body? (Dio already decoded it when responseType is json.)
    final Map<String, dynamic> body =
        data is Map<String, dynamic> ? data : const {};

    final serverMessage = _extractMessage(body);

    switch (status) {
      case 401:
        return ApiException.unauthenticated(
          message: serverMessage ?? 'Session expired. Please sign in again.',
          cause: err,
        );
      case 403:
        return ApiException(
          message: serverMessage ?? 'Your role is not allowed to do that.',
          statusCode: status,
          type: ApiExceptionType.forbidden,
          original: err,
        );
      case 404:
        return ApiException.notFound(cause: err);
      case 422:
        return ApiException.validation(
          message: serverMessage ?? 'Please review the highlighted fields.',
          errors: _extractFieldErrors(body),
          cause: err,
        );
      default:
        if (status != null && status >= 400 && status < 500) {
          // 409 (unavailable items), 409-style domain rejections etc.
          return ApiException(
            message: serverMessage ?? 'Request rejected by the server.',
            statusCode: status,
            type: ApiExceptionType.validation,
            original: err,
          );
        }
        return ApiException.server(statusCode: status, cause: err);
    }
  }

  /// Pulls `message` out of Laravel error envelopes; falls back through
  /// array-shaped messages (Laravel sometimes returns a list).
  String? _extractMessage(Map<String, dynamic> body) {
    final message = body['message'];
    if (message is String && message.trim().isNotEmpty) {
      return message.trim();
    }
    if (message is List && message.isNotEmpty) {
      return message.first.toString();
    }
    return null;
  }

  /// Normalizes Laravel's `errors` map ({field: [..]} or {field: "msg"}).
  Map<String, List<String>> _extractFieldErrors(Map<String, dynamic> body) {
    final raw = body['errors'];
    if (raw is! Map) return const {};

    return raw.map((key, value) {
      if (value is List) {
        return MapEntry(key.toString(), value.map((e) => e.toString()).toList());
      }
      return MapEntry(key.toString(), <String>[value.toString()]);
    });
  }
}
