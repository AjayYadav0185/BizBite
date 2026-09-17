import 'package:flutter/foundation.dart';

import '../../core/network/api_exception.dart';
import 'data/models/assistant_models.dart';
import 'data/repositories/assistant_repository.dart';

/// State holder for the owner's Store Assistant chat (plain ChangeNotifier,
/// same style as [WalletController] / [ReportsController] — no codegen).
///
///   - [messages] is the visible transcript (questions, answers, error notes).
///   - Error bubbles are flagged [AssistantMessage.error] and never replayed
///     to the API — only real user/assistant turns go into `history`.
///   - ONLINE ONLY: asking requires the server (the AI answer is generated
///     there from live store data), so network failures surface as bubbles.
class AssistantController extends ChangeNotifier {
  AssistantController({required this._repository});

  final AssistantRepository _repository;

  List<AssistantMessage> messages = const [];
  bool sending = false;
  bool clearedBannerShown = false;

  /// True once the transcript holds at least one successful answer — lets the
  /// UI hide the suggestion chips after the first real exchange.
  bool get hasConversation =>
      messages.any((m) => !m.error && m.role == 'assistant');

  /// Ask one question. Returns an error message for the UI, or null on success.
  Future<String?> ask(String rawQuestion) async {
    final question = rawQuestion.trim();
    if (question.isEmpty || sending) return null;

    // Optimistically paint the question, then wait for the server.
    _append(AssistantMessage(role: 'user', content: question));
    sending = true;
    notifyListeners();

    try {
      // Everything BEFORE this question becomes replay context (cap 8 turns,
      // error bubbles excluded — they never reached the model).
      final turns = messages
          .where((m) => !m.error && m != messages.last)
          .toList(growable: false);
      final history =
          turns.length > 8 ? turns.sublist(turns.length - 8) : turns;

      final reply = await _repository.ask(
        question: question,
        history: history,
      );

      // Guard against post-await misuse.
      if (reply.text.isEmpty) {
        _append(const AssistantMessage(
          role: 'assistant',
          content: 'The assistant returned an empty answer. Please rephrase.',
          error: true,
        ));
        return null;
      }

      _append(AssistantMessage(role: 'assistant', content: reply.text));
      return null;
    } on ApiException catch (error) {
      // 403 means a cashier reached this screen somehow — offer a hint that
      // matches the server rule (assistant is owner-only).
      final message = error.statusCode == 403
          ? 'The Store Assistant is available to the store owner only.'
          : error.message;
      _append(AssistantMessage(role: 'assistant', content: message, error: true));
      return message;
    } catch (_) {
      const fallback = 'Something went wrong. Please try again.';
      _append(const AssistantMessage(
        role: 'assistant',
        content: fallback,
        error: true,
      ));
      return fallback;
    } finally {
      sending = false;
      notifyListeners();
    }
  }

  /// Wipe the transcript ("New chat").
  void clear() {
    if (messages.isEmpty) return;
    messages = const [];
    notifyListeners();
  }

  void _append(AssistantMessage message) {
    messages = [...messages, message];
    notifyListeners();
  }
}
