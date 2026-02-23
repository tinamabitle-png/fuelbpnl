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
      color: Colors.blueGrey.shade300,
      fontSize: widget.compact ? 18 : 20,
      fontWeight: FontWeight.w700,
    );

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.blueGrey.shade200),
      ),
      padding: const EdgeInsets.all(10),
      child: Column(
        children: [
          Text('Send Feedback', style: titleStyle),
          const SizedBox(height: 8),
          TextField(
            controller: _controller,
            maxLines: 5,
            decoration: InputDecoration(
              hintText: 'Your feedback...',
              hintStyle: TextStyle(
                color: Colors.blueGrey.shade500.withValues(alpha: 0.7),
              ),
              fillColor: Colors.blueGrey.shade100,
              filled: true,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(10),
                borderSide: BorderSide(color: Colors.blueGrey.shade200),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(10),
                borderSide: BorderSide(color: Colors.blueGrey.shade200),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(10),
                borderSide: BorderSide(color: Colors.blueGrey.shade600),
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
                    backgroundColor: Colors.blueGrey.shade100,
                    side: BorderSide(color: Colors.blueGrey.shade200),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                    minimumSize: const Size.fromHeight(46),
                  ),
                  child: Icon(
                    Icons.send_rounded,
                    color: Colors.blueGrey.shade700,
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
              ? Colors.blue.shade400
              : Colors.blueGrey.shade100,
          side: BorderSide(
            color: selected ? Colors.blue.shade400 : Colors.blueGrey.shade200,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(10),
          ),
        ),
        child: Icon(
          icon,
          color: selected ? Colors.blue.shade100 : Colors.blueGrey.shade600,
        ),
      ),
    );
  }
}
