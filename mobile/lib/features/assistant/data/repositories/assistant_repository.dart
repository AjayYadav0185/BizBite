import 'package:dio/dio.dart';

import '../../../../core/config/api_config.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/utils/parse_utils.dart';
import '../models/assistant_models.dart';

/// Typed gateway for the owner-only AI Store Assistant.
///
///   POST /assistant/ask  { question, history? } -> { reply, model, ... }
///
/// ONLINE ONLY, like payments: the reply is generated server-side from the
/// store's live data, so there is nothing to replay from an outbox later —
/// an offline attempt gets an explicit, owner-ready message instead.
class AssistantRepository {
  AssistantRepository({required this._client});

  final DioClient _client;

  /// Ask one question, replaying the last few turns for follow-up context.
  Future<AssistantReply> ask({
    required String question,
    List<AssistantMessage> history = const [],
  }) async {
    try {
      final response = await _client.post<dynamic>(
        ApiConfig.assistantAsk,
        data: {
          'question': question,
          // Keep the wire payload small: server caps at 8 turns anyway.
          if (history.isNotEmpty)
            'history': history
                .toList(growable: false)
                .sublist(history.length > 8 ? history.length - 8 : 0)
                .map((m) => m.toApi())
                .toList(),
        },
      );
      return AssistantReply.fromMap(toMap(response.data));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }
}
