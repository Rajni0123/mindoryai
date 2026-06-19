import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:razorpay_flutter/razorpay_flutter.dart';
import '../../core/theme/app_theme.dart';
import '../../providers/providers.dart';
import '../../widgets/common_widgets.dart';

class PlansScreen extends ConsumerStatefulWidget {
  const PlansScreen({super.key});

  @override
  ConsumerState<PlansScreen> createState() => _PlansScreenState();
}

class _PlansScreenState extends ConsumerState<PlansScreen> {
  bool _loading = true;
  bool _trialLoading = false;
  List<Map<String, dynamic>> _plans = [];
  Map<String, dynamic>? _trialOffer;
  Razorpay? _razorpay;
  Map<String, dynamic>? _pendingOrder;
  String? _pendingSubscriptionId;

  @override
  void initState() {
    super.initState();
    _load();
    _razorpay = Razorpay();
    _razorpay!.on(Razorpay.EVENT_PAYMENT_SUCCESS, _onPaymentSuccess);
    _razorpay!.on(Razorpay.EVENT_PAYMENT_ERROR, _onPaymentError);
  }

  @override
  void dispose() {
    _razorpay?.clear();
    super.dispose();
  }

  Future<void> _load() async {
    final api = ref.read(apiServiceProvider);
    try {
      final results = await Future.wait([
        api.getPlans(),
        api.getTrialOffer(),
      ]);
      final raw = results[0] as List;
      final trial = results[1] as Map<String, dynamic>;
      setState(() {
        _plans = raw.map((e) => Map<String, dynamic>.from(e as Map)).toList();
        _trialOffer = trial;
      });
    } catch (_) {
      setState(() {
        _plans = [];
        _trialOffer = null;
      });
    } finally {
      setState(() => _loading = false);
    }
  }

  Map<String, dynamic> get _ui {
    final ui = _trialOffer?['ui'];
    if (ui is Map) return Map<String, dynamic>.from(ui);
    return {};
  }

  bool get _showTrialBanner {
    if (_trialOffer == null) return false;
    if (_trialOffer!['enabled'] != true) return false;
    return _trialOffer!['eligible'] == true;
  }

  @override
  Widget build(BuildContext context) {
    final ui = _ui;
    return Scaffold(
      appBar: AppBar(
        title: Text(ui['screen_title']?.toString() ?? 'Choose Your Plan'),
      ),
      body: _loading
          ? const Center(
              child: CircularProgressIndicator(color: AppColors.primary))
          : RefreshIndicator(
              onRefresh: _load,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    if (_showTrialBanner) ...[
                      _trialPaywallCard(ui),
                      const SizedBox(height: 28),
                      Text(
                        ui['screen_subtitle']?.toString() ??
                            'Ya full plan choose karo',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 16),
                    ] else ...[
                      Text(
                        ui['screen_subtitle']?.toString() ??
                            'Unlock your full potential',
                        style: const TextStyle(
                          fontSize: 22,
                          fontWeight: FontWeight.w800,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'Paid plans — Lite, Pro, Ultimate',
                        style: TextStyle(color: AppColors.textSecondary),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 28),
                    ],
                    if (_plans.isEmpty)
                      const Text('No plans available',
                          style: TextStyle(color: AppColors.textMuted))
                    else
                      ..._plans.asMap().entries.map((e) {
                        final p = e.value;
                        final features = (p['features'] as List?)
                                ?.map((f) => f.toString())
                                .toList() ??
                            [];
                        final price = p['price'];
                        final priceStr = price is num
                            ? '₹${price == price.roundToDouble() ? price.toInt() : price}'
                            : '₹${p['price'] ?? 0}';
                        final period =
                            p['billing_period']?.toString() ?? 'month';
                        return Padding(
                          padding: EdgeInsets.only(
                              bottom: e.key < _plans.length - 1 ? 14 : 0),
                          child: _planCard(
                            name: p['name']?.toString() ?? 'Plan',
                            price: priceStr,
                            period: '/$period',
                            features: features.isNotEmpty
                                ? features
                                : ['See plan details on website'],
                            isPopular: p['popular'] == true ||
                                p['slug']?.toString() == 'pro',
                            onTap: () => _subscribe(context, p),
                          ),
                        );
                      }),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _trialPaywallCard(Map<String, dynamic> ui) {
    final features = (ui['features_trial'] as List?)
            ?.map((e) => e.toString())
            .toList() ??
        [];
    final trust = (ui['trust_items'] as List?)
            ?.map((e) => e.toString())
            .toList() ??
        [];

    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            AppColors.primary.withValues(alpha: 0.15),
            AppColors.primary.withValues(alpha: 0.05),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.4)),
      ),
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: AppColors.primary,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              ui['hero_badge']?.toString() ?? '🔥 Limited Offer',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 11,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          const SizedBox(height: 14),
          Text(
            ui['hero_title']?.toString() ?? 'Sirf ₹1 me 2 Din',
            style: const TextStyle(
              fontSize: 26,
              fontWeight: FontWeight.w900,
              height: 1.15,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            ui['hero_subtitle']?.toString() ??
                'Full Lite access — AI chat, quiz, scan',
            style: const TextStyle(
              color: AppColors.textSecondary,
              fontSize: 14,
            ),
          ),
          const SizedBox(height: 16),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                ui['hero_price_strike']?.toString() ?? '₹79',
                style: const TextStyle(
                  decoration: TextDecoration.lineThrough,
                  color: AppColors.textMuted,
                  fontSize: 18,
                ),
              ),
              const SizedBox(width: 10),
              Text(
                ui['hero_price_trial']?.toString() ?? '₹1',
                style: const TextStyle(
                  fontSize: 40,
                  fontWeight: FontWeight.w900,
                  color: AppColors.primary,
                ),
              ),
              const SizedBox(width: 6),
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Text(
                  ui['hero_price_note']?.toString() ?? '2 din ke liye',
                  style: const TextStyle(color: AppColors.textSecondary),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          ...features.map(
            (f) => Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: Row(
                children: [
                  const Icon(Icons.check_circle_rounded,
                      color: AppColors.success, size: 18),
                  const SizedBox(width: 8),
                  Expanded(child: Text(f, style: const TextStyle(fontSize: 13))),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),
          ...trust.map(
            (t) => Padding(
              padding: const EdgeInsets.only(bottom: 4),
              child: Row(
                children: [
                  const Icon(Icons.shield_outlined,
                      size: 16, color: AppColors.textSecondary),
                  const SizedBox(width: 6),
                  Text(t,
                      style: const TextStyle(
                          fontSize: 12, color: AppColors.textSecondary)),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _trialLoading ? null : _startTrial,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
              ),
              child: _trialLoading
                  ? Text(ui['cta_loading']?.toString() ?? 'Loading…')
                  : Text(
                      ui['cta_primary']?.toString() ?? '₹1 me Start Karo',
                      style: const TextStyle(fontWeight: FontWeight.w800),
                    ),
            ),
          ),
          const SizedBox(height: 10),
          Text(
            ui['legal_short']?.toString() ?? '',
            style: const TextStyle(fontSize: 10, color: AppColors.textMuted),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _planCard({
    required String name,
    required String price,
    required String period,
    required List<String> features,
    required bool isPopular,
    required VoidCallback onTap,
  }) {
    return Stack(
      clipBehavior: Clip.none,
      children: [
        AppCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(name,
                  style: const TextStyle(
                      fontSize: 20, fontWeight: FontWeight.w800)),
              const SizedBox(height: 8),
              Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(price,
                      style: const TextStyle(
                          fontSize: 32,
                          fontWeight: FontWeight.w900,
                          color: AppColors.primary)),
                  Text(period,
                      style: const TextStyle(color: AppColors.textSecondary)),
                ],
              ),
              const SizedBox(height: 16),
              ...features.map((f) => Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: Row(
                      children: [
                        const Icon(Icons.check_circle_rounded,
                            color: AppColors.success, size: 18),
                        const SizedBox(width: 10),
                        Expanded(
                            child: Text(f,
                                style: const TextStyle(fontSize: 14))),
                      ],
                    ),
                  )),
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: onTap,
                  style: ElevatedButton.styleFrom(
                    backgroundColor:
                        isPopular ? AppColors.primary : AppColors.surface,
                    foregroundColor:
                        isPopular ? Colors.white : AppColors.textPrimary,
                  ),
                  child: Text(isPopular ? 'Get $name' : 'Choose $name'),
                ),
              ),
            ],
          ),
        ),
        if (isPopular)
          Positioned(
            top: -10,
            right: 16,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
              decoration: BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Text('POPULAR',
                  style: TextStyle(
                      color: Colors.white,
                      fontSize: 10,
                      fontWeight: FontWeight.w800)),
            ),
          ),
      ],
    );
  }

  Future<void> _startTrial() async {
    setState(() => _trialLoading = true);
    try {
      final res = await ref.read(apiServiceProvider).startTrialAutopay();
      final subscriptionId = res['subscription_id']?.toString();
      final key = res['razorpay_key']?.toString() ?? '';
      if (subscriptionId == null || subscriptionId.isEmpty) {
        throw Exception('No subscription_id from server');
      }
      _pendingSubscriptionId = subscriptionId;
      _pendingOrder = null;
      _razorpay?.open({
        'key': key,
        'subscription_id': subscriptionId,
        'name': 'BlinkStudy',
        'description': _trialOffer?['headline']?.toString() ?? '₹1 Trial',
        'theme': {'color': '#0D9488'},
      });
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Trial start failed: $e')),
      );
    } finally {
      if (mounted) setState(() => _trialLoading = false);
    }
  }

  Future<void> _subscribe(BuildContext context, Map<String, dynamic> plan) async {
    final planId = plan['id'] as int?;
    if (planId == null) return;
    final price = plan['price'];
    if (price is num && price == 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Subscribe to a paid plan to continue')),
      );
      return;
    }
    try {
      final res =
          await ref.read(apiServiceProvider).createPaymentOrder(planId: planId);
      final order = res['order'] as Map<String, dynamic>?;
      if (order == null) return;
      _pendingOrder = order;
      _pendingSubscriptionId = null;
      final gateway = order['gateway_data'] as Map<String, dynamic>? ?? {};
      final options = {
        'key': gateway['key'] ?? gateway['razorpay_key'] ?? '',
        'amount': ((order['amount'] as num) * 100).toInt(),
        'currency': order['currency'] ?? 'INR',
        'order_id': order['id'],
        'name': 'BlinkStudy',
        'description': plan['name']?.toString() ?? 'Subscription',
      };
      _razorpay?.open(options);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Payment error: $e')),
      );
    }
  }

  void _onPaymentSuccess(PaymentSuccessResponse response) async {
    final ui = _ui;
    try {
      if (_pendingSubscriptionId != null) {
        await ref.read(apiServiceProvider).verifyTrialAutopay(
              subscriptionId: _pendingSubscriptionId!,
              paymentId: response.paymentId ?? '',
              signature: response.signature ?? '',
            );
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                ui['success_body']?.toString() ??
                    'Trial activated! Enjoy Lite for 2 days.',
              ),
            ),
          );
          ref.read(authProvider.notifier).refreshUser();
          await _load();
        }
      } else {
        await ref.read(apiServiceProvider).verifyPayment(
              orderId: _pendingOrder?['id']?.toString() ?? '',
              paymentId: response.paymentId ?? '',
              signature: response.signature,
            );
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Payment successful! Plan activated.')),
          );
          ref.read(authProvider.notifier).refreshUser();
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Verify failed: $e')),
        );
      }
    } finally {
      _pendingSubscriptionId = null;
      _pendingOrder = null;
    }
  }

  void _onPaymentError(PaymentFailureResponse response) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(response.message ?? 'Payment failed')),
    );
    _pendingSubscriptionId = null;
    _pendingOrder = null;
  }
}
