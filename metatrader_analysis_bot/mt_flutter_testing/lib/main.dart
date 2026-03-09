import 'dart:math';

import 'package:flutter/material.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const MetaTraderTestingApp());
}

class MetaTraderTestingApp extends StatelessWidget {
  const MetaTraderTestingApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'MetaTrader Flutter Testing',
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF0B7285)),
      ),
      home: const AnalysisDashboardPage(),
    );
  }
}

class AnalysisDashboardPage extends StatefulWidget {
  const AnalysisDashboardPage({super.key});

  @override
  State<AnalysisDashboardPage> createState() => _AnalysisDashboardPageState();
}

class _AnalysisDashboardPageState extends State<AnalysisDashboardPage> {
  String symbol = 'EURUSD';
  String timeframe = 'M15';
  late List<StrategySignal> signals;

  @override
  void initState() {
    super.initState();
    signals = _generateSignals();
  }

  List<StrategySignal> _generateSignals() {
    final rng = Random();
    final strategies = <String>[
      'EMA + RSI',
      'Donchian Breakout',
      'Bollinger Mean Reversion',
      'ATR Volatility Filter',
    ];

    return strategies.map((name) {
      final score = 40 + rng.nextInt(56);
      final side = score > 68
          ? 'BUY'
          : score < 52
          ? 'SELL'
          : 'HOLD';

      return StrategySignal(
        name: name,
        side: side,
        confidence: score / 100,
        note: _noteFor(name, side),
      );
    }).toList();
  }

  String _noteFor(String strategy, String side) {
    switch (strategy) {
      case 'EMA + RSI':
        return side == 'BUY'
            ? 'Trend up, momentum healthy'
            : side == 'SELL'
            ? 'Trend weakening and momentum fading'
            : 'No clean trend alignment';
      case 'Donchian Breakout':
        return side == 'BUY'
            ? 'Price broke recent high band'
            : side == 'SELL'
            ? 'Price broke recent low band'
            : 'Range still intact';
      case 'Bollinger Mean Reversion':
        return side == 'BUY'
            ? 'Price below lower band'
            : side == 'SELL'
            ? 'Price above upper band'
            : 'Price near middle band';
      default:
        return side == 'HOLD'
            ? 'Volatility neutral'
            : 'Volatility expansion detected';
    }
  }

  void _refreshSignals() {
    setState(() {
      signals = _generateSignals();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('MetaTrader Analysis Test (No Auth)'),
        actions: [
          IconButton(
            tooltip: 'Refresh simulated analysis',
            onPressed: _refreshSignals,
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _headerCard(),
          const SizedBox(height: 12),
          _languageChoicesCard(),
          const SizedBox(height: 12),
          const Text(
            'Strategy Output (Simulated)',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 8),
          ...signals.map(_strategyCard),
          const SizedBox(height: 12),
          const Text(
            'Testing Mode: Authentication is disabled for this build.',
            style: TextStyle(color: Colors.black54),
          ),
        ],
      ),
    );
  }

  Widget _headerCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Wrap(
          spacing: 16,
          runSpacing: 12,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: [
            _chip('Symbol', symbol),
            _chip('Timeframe', timeframe),
            _chip('Strategies', '${signals.length}'),
            _chip('Runtime', 'Flutter Web/Android'),
          ],
        ),
      ),
    );
  }

  Widget _chip(String label, String value) {
    return Chip(
      label: Text('$label: $value'),
      backgroundColor: const Color(0xFFE6F4F1),
      side: BorderSide.none,
    );
  }

  Widget _languageChoicesCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: const [
            Text(
              'Language Choices',
              style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700),
            ),
            SizedBox(height: 8),
            ...[
              Text('1. Flutter/Dart: UI and operator dashboard (this app).'),
              SizedBox(height: 4),
              Text('2. Python: strategy research and model experimentation.'),
              SizedBox(height: 4),
              Text(
                '3. MQL5: terminal-native execution adapter when going live.',
              ),
              SizedBox(height: 4),
              Text('4. Node.js: orchestration/API gateway if needed.'),
            ],
          ],
        ),
      ),
    );
  }

  Widget _strategyCard(StrategySignal signal) {
    final color = switch (signal.side) {
      'BUY' => Colors.green,
      'SELL' => Colors.red,
      _ => Colors.blueGrey,
    };

    return Card(
      child: ListTile(
        title: Text(signal.name),
        subtitle: Text(signal.note),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                signal.side,
                style: TextStyle(color: color, fontWeight: FontWeight.w700),
              ),
            ),
            const SizedBox(height: 4),
            Text('${(signal.confidence * 100).toStringAsFixed(0)}%'),
          ],
        ),
      ),
    );
  }
}

class StrategySignal {
  StrategySignal({
    required this.name,
    required this.side,
    required this.confidence,
    required this.note,
  });

  final String name;
  final String side;
  final double confidence;
  final String note;
}
