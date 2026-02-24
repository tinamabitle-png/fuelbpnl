import 'package:flutter/material.dart';

class FeedbackPanel extends StatefulWidget {
  const FeedbackPanel({super.key, this.compact = false});

  final bool compact;

  @override
  State<FeedbackPanel> createState() => _FeedbackPanelState();
}

class _FeedbackPanelState extends State<FeedbackPanel> {
  final TextEditingController _controller = TextEditingController();
  int _mood = 0;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final titleStyle = TextStyle(
      color: const Color(0xFFE2E8F0),
      fontSize: widget.compact ? 16 : 18,
      fontWeight: FontWeight.w700,
    );

    return Container(
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF0B1220), Color(0xFF111827)],
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      padding: const EdgeInsets.all(12),
      child: Column(
        children: [
          Text('Send Feedback', style: titleStyle),
          const SizedBox(height: 8),
          TextField(
            controller: _controller,
            maxLines: 5,
            decoration: InputDecoration(
              hintText: 'Your feedback...',
              hintStyle: const TextStyle(color: Color(0xFF94A3B8)),
              fillColor: const Color(0xFF020617),
              filled: true,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: Color(0xFF334155)),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: Color(0xFF334155)),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: Color(0xFF7C3AED)),
              ),
            ),
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              _smallMoodButton(1, Icons.sentiment_satisfied_alt_rounded),
              const SizedBox(width: 6),
              _smallMoodButton(2, Icons.sentiment_neutral_rounded),
              const Spacer(),
              Expanded(
                flex: 2,
                child: OutlinedButton(
                  onPressed: () {
                    FocusScope.of(context).unfocus();
                    final text = _controller.text.trim();
                    if (text.isEmpty) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('Enter feedback before sending.'),
                        ),
                      );
                      return;
                    }
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text('Feedback captured (mock).'),
                      ),
                    );
                    _controller.clear();
                    setState(() => _mood = 0);
                  },
                  style: OutlinedButton.styleFrom(
                    backgroundColor: const Color(0xFF0F172A),
                    side: const BorderSide(color: Color(0xFF334155)),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    minimumSize: const Size.fromHeight(46),
                  ),
                  child: const Icon(
                    Icons.send_rounded,
                    color: Color(0xFFE2E8F0),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _smallMoodButton(int value, IconData icon) {
    final selected = _mood == value;
    return SizedBox(
      width: 48,
      child: OutlinedButton(
        onPressed: () => setState(() => _mood = value),
        style: OutlinedButton.styleFrom(
          padding: const EdgeInsets.all(0),
          minimumSize: const Size(44, 44),
          backgroundColor: selected
              ? const Color(0xFF7C3AED)
              : const Color(0xFF0F172A),
          side: BorderSide(
            color: selected ? const Color(0xFF7C3AED) : const Color(0xFF334155),
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
        child: Icon(
          icon,
          color: selected ? Colors.white : const Color(0xFF94A3B8),
        ),
      ),
    );
  }
}
