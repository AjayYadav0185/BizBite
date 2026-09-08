import 'dart:async';

import 'package:dio/dio.dart';
import 'package:pretty_dio_logger/pretty_dio_logger.dart';

import '../config/api_config.dart';
import '../storage/secure_token_storage.dart';
import 'api_exception.dart';
import 'interceptors/auth_interceptor.dart';
import 'interceptors/error_interceptor.dart';

/// The single HTTP gateway of the BizBite app.
///
/// Pipeline for every request:
///
///   caller -> [AuthInterceptor]   (attaches `Authorization: Bearer <token>`
///                                  from flutter_secure_storage)
///          -> [ErrorInterceptor]  (DioException -> typed [ApiException],
///                                  401 fires [onSessionExpired] so the auth
///                                  Cubit can force a logout)
///          -> PrettyDioLogger     (debug builds only)
///          -> Laravel 11 + Sanctum
///
/// Repositories depend on this class only — swapping the transport (or
/// pointing at a staging backend) is a one-line change in [ApiConfig].
class DioClient {
  DioClient({
    required SecureTokenStorage tokenStorage,
    void Function(ApiException exception)? onSessionExpired,
    String? baseUrlOverride,
  }) {
    _dio = Dio(
      BaseOptions(
        baseUrl: baseUrlOverride ?? ApiConfig.baseUrl,
        connectTimeout: ApiConfig.connectTimeout,
        sendTimeout: ApiConfig.sendTimeout,
        receiveTimeout: ApiConfig.receiveTimeout,
        headers: Map<String, String>.of(ApiConfig.defaultHeaders),
        responseType: ResponseType.json,
        // Non-2xx statuses are surfaced through the ErrorInterceptor instead
        // of being silently returned as data.
        validateStatus: (code) => code != null && code >= 200 && code < 300,
      ),
    );

    _dio.interceptors.addAll([
      AuthInterceptor(tokenStorage),
      ErrorInterceptor(onSessionExpired: onSessionExpired),
      if (ApiConfig.isVerboseLogging)
        PrettyDioLogger(
          requestHeader: true,
          requestBody: true,
          responseHeader: false,
          responseBody: true,
          error: true,
          compact: true,
        ),
    ]);
  }

  late final Dio _dio;

  /// Read-only escape hatch (file uploads, printing diagnostics, tests).
  Dio get raw => _dio;

  // ------------------------------------------------------------------
  // Typed convenience verbs
  // ------------------------------------------------------------------

  Future<Response<T>> get<T>(
    String path, {
    Map<String, dynamic>? query,
    Options? options,
    CancelToken? cancelToken,
  }) {
    return _dio.get<T>(
      path,
      queryParameters: query,
      options: options,
      cancelToken: cancelToken,
    );
  }

  Future<Response<T>> post<T>(
    String path, {
    Object? data,
    Map<String, dynamic>? query,
    Options? options,
    CancelToken? cancelToken,
  }) {
    return _dio.post<T>(
      path,
      data: data,
      queryParameters: query,
      options: options,
      cancelToken: cancelToken,
    );
  }

  Future<Response<T>> put<T>(
    String path, {
    Object? data,
    Map<String, dynamic>? query,
    Options? options,
    CancelToken? cancelToken,
  }) {
    return _dio.put<T>(
      path,
      data: data,
      queryParameters: query,
      options: options,
      cancelToken: cancelToken,
    );
  }

  Future<Response<T>> delete<T>(
    String path, {
    Object? data,
    Map<String, dynamic>? query,
    Options? options,
    CancelToken? cancelToken,
  }) {
    return _dio.delete<T>(
      path,
      data: data,
      queryParameters: query,
      options: options,
      cancelToken: cancelToken,
    );
  }

  /// Explicitly authenticate this client with an already-fetched token.
  ///
  /// Used right after `POST /api/login` resolves so subsequent calls in the
  /// same flow (e.g. `GET /api/menu` pre-fetch) carry the header even before
  /// the secure storage write round-trips.
  void useTokenForNextRequests(String token) {
    _dio.options.headers['Authorization'] = 'Bearer $token';
  }

  void close() {
    _dio.close(force: true);
  }
}
