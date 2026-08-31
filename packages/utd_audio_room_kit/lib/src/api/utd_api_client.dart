import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:pretty_dio_logger/pretty_dio_logger.dart';

/// HTTP client for the UTD Stream Engine API.
///
/// Base URL: `https://engine.udt-stream.com`
/// All seat & speaker endpoints live under `/api/v1/rooms/:name/...`
class UTDApiClient {
  /// Default base URL for the UTD Stream Engine API.
  /// Use `engine.udt-stream.com` directly — `udt-stream.com` is Cloudflare-
  /// fronted and being deprecated.
  static const String defaultBaseUrl = 'https://engine.udt-stream.com';

  final Dio _dio;
  final String baseUrl;

  /// Creates a new [UTDApiClient].
  ///
  /// [baseUrl] defaults to [defaultBaseUrl] (`https://engine.udt-stream.com`).
  UTDApiClient({
    this.baseUrl = defaultBaseUrl,
    String? appId,
    String? appSecret,
    Dio? dio,
  }) : _dio = dio ?? Dio() {
    _dio.options
      ..baseUrl = baseUrl
      ..headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json; charset=utf-8',
        if (appId != null) 'X-App-Id': appId,
        if (appSecret != null) 'X-App-Secret': appSecret,
      }
      // Fail fast into the caller's retry path: a hung engine must not eat
      // the whole join budget (15s/15s let one stuck call stall entry).
      ..connectTimeout = const Duration(seconds: 5)
      ..receiveTimeout = const Duration(seconds: 8)
      ..responseType = ResponseType.json;

    if (kDebugMode) {
      _dio.interceptors.add(
        PrettyDioLogger(
          requestHeader: true,
          requestBody: true,
          responseBody: true,
          responseHeader: false,
          error: true,
          compact: true,
          maxWidth: 90,
        ),
      );
    }
  }

  /// Update the API key (e.g. after re-authentication).
  void updateApiKey(String apiKey) {
    _dio.options.headers['X-API-Key'] = apiKey;
  }

  /// Update the authorization token.
  void updateToken(String token) {
    _dio.options.headers['Authorization'] = 'Bearer $token';
  }

  // ---------------------------------------------------------------------------
  // HTTP Methods
  // ---------------------------------------------------------------------------

  Future<Map<String, dynamic>> get(
    String path, {
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await _dio.get(
      path,
      queryParameters: queryParameters,
    );
    return _parseResponse(response);
  }

  Future<Map<String, dynamic>> post(
    String path, {
    Map<String, dynamic>? data,
  }) async {
    final response = await _dio.post(path, data: data);
    return _parseResponse(response);
  }

  Future<Map<String, dynamic>> put(
    String path, {
    Map<String, dynamic>? data,
  }) async {
    final response = await _dio.put(path, data: data);
    return _parseResponse(response);
  }

  Future<Map<String, dynamic>> delete(
    String path, {
    Map<String, dynamic>? data,
  }) async {
    final response = await _dio.delete(path, data: data);
    return _parseResponse(response);
  }

  // ---------------------------------------------------------------------------
  // Private
  // ---------------------------------------------------------------------------

  Map<String, dynamic> _parseResponse(Response response) {
    if (response.data is Map<String, dynamic>) {
      return response.data as Map<String, dynamic>;
    }
    return {'data': response.data};
  }

  /// Dispose resources.
  void dispose() {
    _dio.close();
  }
}
