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
  List<Map<String, dynamic>> _plans = [];
  Razorpay? _razorpay;
  Map<String, dynamic>? _pendingOrder;

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
    try {
      final api = ref.read(apiServiceProvider);
      final raw = await api.getPlans();
      setState(() {
        _plans = raw.map((e) => Map<String, dynamic>.from(e as Map)).toList();
      });
    } catch (_) {
      setState(() => _plans = []);
    } finally {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Choose Your Plan')),
      body: _loading
          ? const Center(
              child: CircularProgressIndicator(color: AppColors.primary))
          : RefreshIndicator(
              onRefresh: _load,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(20),
                child: Column(
                  children: [
                    const Text(
                      'Unlock your full potential',
                      style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'Plans loaded from blinkstudy.in',
                      style: TextStyle(color: AppColors.textSecondary),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 28),
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
                        final period = p['billing_period']?.toString() ?? 'month';
                        return Padding(
                          padding: EdgeInsets.only(bottom: e.key < _plans.length - 1 ? 14 : 0),
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

  Future<void> _subscribe(BuildContext context, Map<String, dynamic> plan) async {
    final planId = plan['id'] as int?;
    if (planId == null) return;
    final price = plan['price'];
    if (price is num && price == 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Free plan — already active')),
      );
      return;
    }
    try {
      final res = await ref.read(apiServiceProvider).createPaymentOrder(planId: planId);
      final order = res['order'] as Map<String, dynamic>?;
      if (order == null) return;
      _pendingOrder = order;
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
    try {
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
    } catch (_) {}
  }

  void _onPaymentError(PaymentFailureResponse response) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(response.message ?? 'Payment failed')),
    );
  }
}
