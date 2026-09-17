import '../../../../core/utils/parse_utils.dart';

/// One chat bubble in the Store Assistant conversation.
class AssistantMessage {
  const AssistantMessage({
    required this.role,
    required this.content,
    this.error = false,
  });

  /// `'user'` (the owner's question) or `'assistant'` (the bot's answer).
  final String role;

  /// Bubble text — questions are plain, answers may contain newlines.
  final String content;

  /// True when this bubble is an error notice (never sent back to the API).
  final bool error;

  bool get isUser => role == 'user';

  Map<String, dynamic> toApi() => {'role': role, 'content': content};
}

/// Server reply for POST /api/assistant/ask.
class AssistantReply {
  const AssistantReply({required this.text, this.model = ''});

  final String text;
  final String model;

  factory AssistantReply.fromMap(Map<String, dynamic> map) {
    return AssistantReply(
      text: toNullableString(map['reply']),
      model: toNullableString(map['model']),
    );
  }
}
