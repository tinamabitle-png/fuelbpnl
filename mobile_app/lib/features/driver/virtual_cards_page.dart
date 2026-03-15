import 'package:flutter/material.dart';

import '../../core/app_loader.dart';
import '../../core/fx_button.dart';
import '../../data/api_client.dart';
import '../../data/models.dart';

class DriverVirtualCardsPage extends StatefulWidget {
  const DriverVirtualCardsPage({super.key, required this.api});

  final ApiClient api;

  @override
  State<DriverVirtualCardsPage> createState() => _DriverVirtualCardsPageState();
}

class _DriverVirtualCardsPageState extends State<DriverVirtualCardsPage> {
  static const _themeBackgrounds = <String>[
    'https://assets.codepen.io/14762/snowy-mint.jpg',
    'https://assets.codepen.io/14762/egg-sour.jpg',
    'https://assets.codepen.io/14762/columbia-blue.jpg',
    'https://assets.codepen.io/14762/my-pink.jpg',
    'https://assets.codepen.io/14762/buttercup.jpg',
    'https://assets.codepen.io/14762/cream-whisper.jpg',
    'https://assets.codepen.io/14762/honeysuckle.jpg',
    'https://assets.codepen.io/14762/tonys-pink.jpg',
  ];

  bool loading = true;
  String? error;
  String cardholderName = 'Cardholder';
  Map<String, dynamic> wallet = <String, dynamic>{};
  List<VirtualCardItem> cards = <VirtualCardItem>[];
  List<RetailBrandItem> brands = <RetailBrandItem>[];
  final Map<int, String> revealedPanByCardId = <int, String>{};
  final Map<int, String> revealedCvvByCardId = <int, String>{};

  @override
  void initState() {
    super.initState();
    fetch();
  }

  int get openCount =>
      cards.where((c) => c.status == 'active' || c.status == 'frozen').length;

  double _toDouble(dynamic v) => v == null ? 0 : double.tryParse('$v') ?? 0.0;

  Future<void> fetch() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final profile = await widget.api.profile();
      final balance = await widget.api.walletBalance();
      final catalog = await widget.api.virtualCardBrands();
      final list = await widget.api.virtualCards();
      setState(() {
        cardholderName =
            (profile['name'] ?? profile['full_name'] ?? 'Cardholder')
                .toString();
        wallet = balance;
        brands = catalog;
        cards = list;
        revealedPanByCardId.clear();
        revealedCvvByCardId.clear();
      });
    } catch (e) {
      setState(() => error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> _create() async {
    if (brands.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Brand catalog is not available yet.')),
      );
      return;
    }

    final selected = await showModalBottomSheet<RetailBrandItem>(
      context: context,
      showDragHandle: true,
      builder: (context) {
        return SafeArea(
          child: ListView(
            padding: const EdgeInsets.symmetric(vertical: 8),
            children: [
              const ListTile(
                title: Text(
                  'Choose a retail brand',
                  style: TextStyle(fontWeight: FontWeight.w800),
                ),
                subtitle: Text('You can open up to 3 brand cards.'),
              ),
              ...brands.map((b) {
                final hasOpen = cards.any(
                  (c) =>
                      c.brand == b.slug &&
                      (c.status == 'active' || c.status == 'frozen'),
                );
                return ListTile(
                  leading: const Icon(Icons.storefront_outlined),
                  title: Text(b.name),
                  subtitle: Text(b.slug),
                  trailing: hasOpen
                      ? const Text(
                          'Open',
                          style: TextStyle(fontWeight: FontWeight.w800),
                        )
                      : null,
                  enabled: !hasOpen && openCount < 3,
                  onTap: () => Navigator.of(context).pop(b),
                );
              }),
            ],
          ),
        );
      },
    );
    if (selected == null) return;
    if (!mounted) return;

    final ctrl = TextEditingController(text: selected.name);
    final label = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Create ${selected.name} card'),
        content: TextField(
          controller: ctrl,
          decoration: const InputDecoration(
            labelText: 'Label (optional)',
            hintText: 'e.g. Fuel spending',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Cancel'),
          ),
          FxButton(
            label: 'Create',
            onPressed: () => Navigator.of(context).pop(ctrl.text.trim()),
          ),
        ],
      ),
    );
    ctrl.dispose();
    if (label == null) return;

    setState(() => loading = true);
    try {
      await widget.api.createVirtualCard(brand: selected.slug, label: label);
      await fetch();
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Virtual card created.')));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> _allocate(VirtualCardItem card) async {
    final ctrl = TextEditingController();
    final amountStr = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Allocate to ${card.label ?? 'Card #${card.id}'}'),
        content: TextField(
          controller: ctrl,
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          decoration: const InputDecoration(
            labelText: 'Amount (ZAR)',
            hintText: 'e.g. 200',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Cancel'),
          ),
          FxButton(
            label: 'Allocate',
            onPressed: () => Navigator.of(context).pop(ctrl.text.trim()),
          ),
        ],
      ),
    );
    ctrl.dispose();
    if (amountStr == null) return;
    if (!mounted) return;

    final amount = double.tryParse(amountStr) ?? 0;
    if (amount <= 0) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Enter a valid amount.')));
      return;
    }

    setState(() => loading = true);
    try {
      await widget.api.allocateToVirtualCard(cardId: card.id, amount: amount);
      await fetch();
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Funds allocated.')));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> _toggleFreeze(VirtualCardItem card) async {
    setState(() => loading = true);
    try {
      if (card.status == 'active') {
        await widget.api.freezeVirtualCard(card.id);
      } else if (card.status == 'frozen') {
        await widget.api.unfreezeVirtualCard(card.id);
      }
      await fetch();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> _close(VirtualCardItem card) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Close virtual card?'),
        content: const Text('Allocated amount will be reset to 0.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Cancel'),
          ),
          FxButton(
            label: 'Close card',
            onPressed: () => Navigator.of(context).pop(true),
          ),
        ],
      ),
    );
    if (ok != true) return;

    setState(() => loading = true);
    try {
      await widget.api.closeVirtualCard(card.id);
      await fetch();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> _toggleReveal(VirtualCardItem card) async {
    if (card.id == 0) return;
    final already = revealedPanByCardId.containsKey(card.id);
    if (already) {
      setState(() {
        revealedPanByCardId.remove(card.id);
        revealedCvvByCardId.remove(card.id);
      });
      return;
    }

    setState(() => loading = true);
    try {
      final data = await widget.api.revealVirtualCard(card.id);
      final pan = (data['pan'] ?? '').toString().trim();
      final cvv = (data['cvv'] ?? '').toString().trim();
      if (pan.isEmpty) {
        throw Exception('Card PAN is not available.');
      }
      if (!mounted) return;
      setState(() {
        revealedPanByCardId[card.id] = pan;
        if (cvv.isNotEmpty) revealedCvvByCardId[card.id] = cvv;
      });

      Future.delayed(const Duration(seconds: 15), () {
        if (!mounted) return;
        if (revealedPanByCardId[card.id] == pan) {
          setState(() {
            revealedPanByCardId.remove(card.id);
            revealedCvvByCardId.remove(card.id);
          });
        }
      });
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (loading) {
      return const Center(child: AppLoader());
    }
    if (error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(error!, textAlign: TextAlign.center),
              const SizedBox(height: 12),
              FxButton(
                label: 'Retry',
                onPressed: () {
                  fetch();
                },
              ),
            ],
          ),
        ),
      );
    }

    final w =
        (wallet['wallet'] as Map?)?.cast<String, dynamic>() ??
        <String, dynamic>{};
    final balance = _toDouble(w['balance']);
    final available = _toDouble(wallet['wallet_available_balance']);
    final reserved = _toDouble(wallet['wallet_reserved_voucher_balance']);
    final allocated = _toDouble(wallet['wallet_allocated_card_balance']);

    return RefreshIndicator(
      onRefresh: fetch,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
        children: [
          Row(
            children: [
              const Expanded(
                child: Text(
                  'Virtual Cards',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                ),
              ),
              FxButton(
                label: openCount >= 3 ? 'Limit reached' : 'Create',
                onPressed: openCount >= 3
                    ? null
                    : () {
                        _create();
                      },
              ),
            ],
          ),
          const SizedBox(height: 10),
          _walletSummary(
            balance: balance,
            available: available,
            reserved: reserved,
            allocated: allocated,
            openCount: openCount,
          ),
          const SizedBox(height: 12),
          if (brands.isEmpty) _emptyState() else ...brands.map(_brandCard),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _walletSummary({
    required double balance,
    required double available,
    required double reserved,
    required double allocated,
    required int openCount,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF0B1220),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Wallet (ZAR)',
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.86),
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              _metric('Balance', balance),
              _metric('Available', available),
              _metric('Reserved', reserved),
              _metric('Allocated', allocated),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 8,
                ),
                decoration: BoxDecoration(
                  color: const Color(0xFF111827),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFF1F2937)),
                ),
                child: Text(
                  'Open cards: $openCount / 3',
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            'Allocating funds does not add money — it reserves wallet funds for card spending.',
            style: TextStyle(color: Colors.white.withValues(alpha: 0.66)),
          ),
        ],
      ),
    );
  }

  Widget _metric(String label, double value) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: const Color(0xFF111827),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFF1F2937)),
      ),
      child: Text(
        '$label: ZAR ${value.toStringAsFixed(2)}',
        style: const TextStyle(
          color: Colors.white,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }

  Widget _emptyState() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF0B1220),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      child: const Text(
        'No virtual cards yet.\nTap Create to add one (max 3 open cards).',
        style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
      ),
    );
  }

  Widget _brandCard(RetailBrandItem brand) {
    final brandCards = cards.where((c) => c.brand == brand.slug).toList();
    final card = brandCards.firstWhere(
      (c) => c.status == 'active' || c.status == 'frozen',
      orElse: () =>
          brandCards.isNotEmpty ? brandCards.first : _placeholderCard(brand),
    );
    final i = brands.indexWhere((b) => b.slug == brand.slug);
    final themeIndex = i < 0 ? 1 : i;

    return _cardTile(
      card: card,
      titleOverride: brand.name,
      showCreate: card.id == 0,
      brandLogoUrl: brand.logoUrl,
      themeIndex: themeIndex,
    );
  }

  VirtualCardItem _placeholderCard(RetailBrandItem brand) {
    return VirtualCardItem(
      id: 0,
      status: 'none',
      currency: 'ZAR',
      allocatedAmount: 0,
      brand: brand.slug,
      label: brand.name,
      provider: 'flutterwave',
    );
  }

  Widget _cardTile({
    required VirtualCardItem card,
    String? titleOverride,
    bool showCreate = false,
    required int themeIndex,
    String? brandLogoUrl,
  }) {
    final status = card.status.toLowerCase().trim();
    final isOpen = status == 'active' || status == 'frozen';
    final title = (titleOverride ?? card.label ?? '').trim().isNotEmpty
        ? (titleOverride ?? card.label)!.trim()
        : (card.id > 0 ? 'Card #${card.id}' : 'Not created');

    final bgUrl = _themeBackgrounds[themeIndex % _themeBackgrounds.length];

    Color chipBg;
    Color chipFg;
    if (status == 'active') {
      chipBg = const Color(0xFF064E3B);
      chipFg = const Color(0xFFD1FAE5);
    } else if (status == 'frozen') {
      chipBg = const Color(0xFF1E293B);
      chipFg = const Color(0xFFBAE6FD);
    } else {
      chipBg = const Color(0xFF3F1D2A);
      chipFg = const Color(0xFFFED7AA);
    }

    final frozen = status == 'frozen';
    final revealed = card.id > 0 ? revealedPanByCardId[card.id] : null;
    final pan = (revealed ?? card.maskedPan ?? '•••• •••• •••• ••••').trim();
    final revealedCvv = card.id > 0 ? revealedCvvByCardId[card.id] : null;
    final cvv = (revealedCvv ?? '•••').trim();
    final expMonth = card.expiryMonth;
    final expYear = card.expiryYear;
    final expiry =
        (expMonth != null && expYear != null && expMonth > 0 && expYear > 0)
        ? '${expMonth.toString().padLeft(2, '0')}/${expYear.toString().substring(expYear.toString().length - 2)}'
        : '--/--';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      child: Column(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(14),
            child: ColorFiltered(
              colorFilter: frozen
                  ? const ColorFilter.matrix(<double>[
                      0.2126,
                      0.7152,
                      0.0722,
                      0,
                      0,
                      0.2126,
                      0.7152,
                      0.0722,
                      0,
                      0,
                      0.2126,
                      0.7152,
                      0.0722,
                      0,
                      0,
                      0,
                      0,
                      0,
                      1,
                      0,
                    ])
                  : const ColorFilter.mode(
                      Colors.transparent,
                      BlendMode.srcOver,
                    ),
              child: Stack(
                children: [
                  SizedBox(
                    height: 227,
                    width: double.infinity,
                    child: DecoratedBox(
                      decoration: BoxDecoration(
                        image: DecorationImage(
                          image: NetworkImage(bgUrl),
                          fit: BoxFit.cover,
                        ),
                      ),
                      child: const SizedBox.shrink(),
                    ),
                  ),
                  Positioned.fill(
                    child: DecoratedBox(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                          colors: [
                            Colors.white.withValues(alpha: 0.10),
                            Colors.white.withValues(alpha: 0.22),
                          ],
                        ),
                      ),
                    ),
                  ),
                  Positioned.fill(
                    child: Container(
                      decoration: BoxDecoration(
                        border: Border.all(
                          color: Colors.white.withValues(alpha: 0.65),
                        ),
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.all(18),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'VIRTUAL',
                              style: TextStyle(
                                letterSpacing: 1.4,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            if (brandLogoUrl != null)
                              Image.network(
                                brandLogoUrl,
                                height: 22,
                                errorBuilder: (_, _, _) =>
                                    const SizedBox.shrink(),
                              ),
                          ],
                        ),
                        const SizedBox(height: 18),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Text(
                                pan,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w900,
                                  letterSpacing: 2.2,
                                  fontSize: 16,
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Row(
                              mainAxisSize: MainAxisSize.min,
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                  children: [
                                    const Text(
                                      'EXP',
                                      style: TextStyle(
                                        fontSize: 10,
                                        fontWeight: FontWeight.w900,
                                        letterSpacing: 1.2,
                                      ),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      expiry,
                                      style: const TextStyle(
                                        fontSize: 14,
                                        fontWeight: FontWeight.w900,
                                        letterSpacing: 1.1,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(width: 14),
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                  children: [
                                    const Text(
                                      'CVV',
                                      style: TextStyle(
                                        fontSize: 10,
                                        fontWeight: FontWeight.w900,
                                        letterSpacing: 1.2,
                                      ),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      isOpen ? cvv : '---',
                                      style: const TextStyle(
                                        fontSize: 14,
                                        fontWeight: FontWeight.w900,
                                        letterSpacing: 1.1,
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ],
                        ),
                        const Spacer(),
                        if (frozen)
                          Center(
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 12,
                                vertical: 6,
                              ),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.75),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: const Text(
                                'FROZEN',
                                style: TextStyle(
                                  color: Color(0xFF111827),
                                  fontWeight: FontWeight.w800,
                                  letterSpacing: 1.2,
                                ),
                              ),
                            ),
                          ),
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: AnimatedOpacity(
                                opacity: frozen ? 0.55 : 1,
                                duration: const Duration(milliseconds: 120),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      title.toUpperCase(),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                        fontSize: 10,
                                        fontWeight: FontWeight.w700,
                                        letterSpacing: 1.2,
                                      ),
                                    ),
                                    const SizedBox(height: 6),
                                    Text(
                                      'Cardholder',
                                      style: TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w700,
                                        color: Colors.black.withValues(
                                          alpha: 0.72,
                                        ),
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      cardholderName,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                        fontSize: 16,
                                        fontWeight: FontWeight.w900,
                                        letterSpacing: 0.8,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Image.network(
                              'https://assets.codepen.io/14762/visa-virtual.svg',
                              height: 38,
                              errorBuilder: (_, _, _) =>
                                  const SizedBox.shrink(),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  Positioned(
                    left: 12,
                    bottom: 12,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.72),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        'Allocated: ${card.currency} ${card.allocatedAmount.toStringAsFixed(2)}',
                        style: const TextStyle(
                          color: Color(0xFF111827),
                          fontWeight: FontWeight.w800,
                          fontSize: 12,
                        ),
                      ),
                    ),
                  ),
                  Positioned(
                    right: 12,
                    bottom: 12,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        color: chipBg,
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        status.toUpperCase(),
                        style: TextStyle(
                          color: chipFg,
                          fontWeight: FontWeight.w900,
                          fontSize: 11,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              FxButton(
                label: card.id > 0 && revealedPanByCardId.containsKey(card.id)
                    ? 'Hide'
                    : 'Show',
                onPressed: isOpen && card.id > 0
                    ? () {
                        _toggleReveal(card);
                      }
                    : null,
              ),
              if (showCreate)
                FxButton(
                  label: openCount >= 3 ? 'Limit reached' : 'Create',
                  onPressed: openCount >= 3
                      ? null
                      : () {
                          _create();
                        },
                ),
              FxButton(
                label: 'Allocate',
                onPressed: isOpen
                    ? () {
                        _allocate(card);
                      }
                    : null,
              ),
              FxButton(
                label: status == 'frozen' ? 'Unfreeze' : 'Freeze',
                onPressed: status == 'active' || status == 'frozen'
                    ? () {
                        _toggleFreeze(card);
                      }
                    : null,
              ),
              FxButton(
                label: 'Close',
                onPressed: status == 'terminated' || card.id == 0
                    ? null
                    : () {
                        _close(card);
                      },
              ),
            ],
          ),
        ],
      ),
    );
  }
}
