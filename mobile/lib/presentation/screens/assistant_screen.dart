import 'package:flutter/material.dart';

import '../../features/assistant/assistant_controller.dart';
import '../../features/assistant/data/models/assistant_models.dart';
import '../theme/bizbite_theme.dart';

/// Owner-only "Store Assistant" — an AI chat grounded in THIS store's data
/// (menu, stock, today's KPIs, staff, tables, campaigns). The server rejects
/// cashiers (403) and off-topic questions, so the screen stays honest:
///
///   - Suggestion chips seed common owner questions.
///   - The composer disables itself while a question is in flight.
///   - Failures paint red bubbles; they never enter the API history.
class AssistantScreen extends StatefulWidget {
  const AssistantScreen({super.key, required this.controller});

  final AssistantController controller;

  @override
  State<AssistantScreen> createState() => _AssistantScreenState();
}

class _AssistantScreenState extends State<AssistantScreen> {
  final TextEditingController _input = TextEditingController();
  final ScrollController _scroll = ScrollController();
  final FocusNode _focus = FocusNode();

  static const List<String> _suggestions = [
    'How many bills did we close today?',
    'Which items are low or out of stock?',
    "What's today's revenue so far?",
    'What are my best sellers today?',
    'Which tables are occupied right now?',
  ];

  @override
  void initState() {
    super.initState();
    widget.controller.addListener(_onChanged);
  }

  @override
  void dispose() {
    widget.controller.removeListener(_onChanged);
    _input.dispose();
    _scroll.dispose();
    _focus.dispose();
    super.dispose();
  }

  /// Keep the newest bubble in view after every transcript change.
  void _onChanged() {
    if (!mounted) return;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_scroll.hasClients) return;
      _scroll.animateTo(
        _scroll.position.maxScrollExtent,
        duration: const Duration(milliseconds: 260),
        curve: Curves.easeOut,
      );
    });
  }

  Future<void> _send([String? preset]) async {
    final text = (preset ?? _input.text).trim();
    if (text.isEmpty || widget.controller.sending) return;
    if (preset == null) _input.clear();
    _focus.unfocus();
    await widget.controller.ask(text);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Store Assistant'),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        actions: [
          ListenableBuilder(
            listenable: widget.controller,
            builder: (context, _) {
              if (!widget.controller.hasConversation) {
                return const SizedBox.shrink();
              }
              return IconButton(
                tooltip: 'New chat',
                icon: const Icon(Icons.refresh_rounded, size: 22),
                onPressed: widget.controller.clear,
              );
            },
          ),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(child: _transcript()),
            _composer(),
          ],
        ),
      ),
    );
  }

  Widget _transcript() {
    return ListenableBuilder(
      listenable: widget.controller,
      builder: (context, _) {
        final messages = widget.controller.messages;

        if (messages.isEmpty) return _emptyState();

        return ListView.builder(
          controller: _scroll,
          padding: const EdgeInsets.fromLTRB(14, 14, 14, 8),
          itemCount: messages.length + (widget.controller.sending ? 1 : 0),
          itemBuilder: (context, index) {
            if (index == messages.length) return _typingBubble();
            return _bubble(messages[index]);
          },
        );
      },
    );
  }

  Widget _emptyState() {
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 20),
      children: [
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: BizBiteTheme.hairline),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: const BoxDecoration(
                  gradient: AppGradients.brandMain,
                  borderRadius: BorderRadius.all(Radius.circular(12)),
                ),
                child: const Icon(Icons.auto_awesome_rounded,
                    color: Colors.white, size: 22),
              ),
              const SizedBox(height: 12),
              const Text(
                'Ask about your store',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w800,
                  color: AppColors.ink,
                ),
              ),
              const SizedBox(height: 4),
              const Text(
                'Answers come from your own store data — menu & prices, '
                'stock, today\u2019s sales, refunds, staff, tables and '
                'campaigns. Anything else is politely refused.',
                style: TextStyle(
                  fontSize: 12.5,
                  height: 1.35,
                  color: AppColors.muted,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        const Padding(
          padding: EdgeInsets.only(left: 2, bottom: 8),
          child: Text(
            'TRY ASKING',
            style: TextStyle(
              fontSize: 10.5,
              fontWeight: FontWeight.w800,
              letterSpacing: 1.2,
              color: AppColors.muted,
            ),
          ),
        ),
        ..._suggestions.map(_suggestionTile),
      ],
    );
  }

  Widget _suggestionTile(String question) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Material(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        child: InkWell(
          onTap: () => _send(question),
          borderRadius: BorderRadius.circular(12),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: BizBiteTheme.hairline),
            ),
            child: Row(
              children: [
                const Icon(Icons.chat_bubble_outline_rounded,
                    size: 16, color: AppColors.primaryDeep),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    question,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: AppColors.ink,
                    ),
                  ),
                ),
                const Icon(Icons.arrow_upward_rounded,
                    size: 14, color: AppColors.faintMuted),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _typingBubble() {
    return Align(
      alignment: Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: BizBiteTheme.hairline),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(
              width: 14,
              height: 14,
              child: CircularProgressIndicator(strokeWidth: 2),
            ),
            const SizedBox(width: 10),
            Text(
              'Checking your store data…',
              style: TextStyle(
                fontSize: 12.5,
                fontWeight: FontWeight.w600,
                color: Colors.grey.shade600,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _bubble(AssistantMessage message) {
    final isUser = message.isUser;

    return Align(
      alignment: isUser ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.82,
        ),
        decoration: BoxDecoration(
          color: message.error
              ? AppColors.errorBg
              : isUser
                  ? AppColors.primaryDeep
                  : Colors.white,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(14),
            topRight: const Radius.circular(14),
            bottomLeft: Radius.circular(isUser ? 14 : 4),
            bottomRight: Radius.circular(isUser ? 4 : 14),
          ),
          border: isUser && !message.error
              ? null
              : Border.all(
                  color: message.error
                      ? AppColors.errorAlt
                      : BizBiteTheme.hairline,
                ),
        ),
        child: Text(
          message.content,
          style: TextStyle(
            fontSize: 13.5,
            height: 1.35,
            fontWeight: FontWeight.w600,
            color: message.error
                ? AppColors.errorDeep
                : isUser
                    ? Colors.white
                    : AppColors.ink,
          ),
        ),
      ),
    );
  }
  Widget _composer() {
    return Container(
      padding: const EdgeInsets.fromLTRB(12, 8, 12, 10),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: BizBiteTheme.hairline)),
      ),
      child: Row(
        children: [
          Expanded(
            child: TextField(
              controller: _input,
              focusNode: _focus,
              minLines: 1,
              maxLines: 4,
              textInputAction: TextInputAction.send,
              onSubmitted: (_) => _send(),
              maxLength: 500,
              style: const TextStyle(
                fontSize: 13.5,
                fontWeight: FontWeight.w600,
                color: AppColors.ink,
              ),
              decoration: InputDecoration(
                hintText: 'Ask about sales, stock, staff…',
                counterText: '',
                filled: true,
                fillColor: AppColors.surfaceMuted,
                contentPadding:
                    const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: BorderSide.none,
                ),
              ),
            ),
          ),
          const SizedBox(width: 8),
          ListenableBuilder(
            listenable: widget.controller,
            builder: (context, _) {
              final busy = widget.controller.sending;
              return SizedBox(
                width: 46,
                height: 46,
                child: FilledButton(
                  onPressed: busy ? null : () => _send(),
                  style: FilledButton.styleFrom(
                    padding: EdgeInsets.zero,
                    shape: const CircleBorder(),
                    backgroundColor: AppColors.primaryDeep,
                  ),
                  child: busy
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Icon(Icons.send_rounded, size: 20),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

}

