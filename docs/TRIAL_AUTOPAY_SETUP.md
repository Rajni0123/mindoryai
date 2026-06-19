# ₹1 Trial + UPI Autopay — Setup Guide

BlinkStudy textbook-style trial: **₹1 → 2 days Lite → auto ₹79/month** via Razorpay Subscriptions.

---

## Part 1 — Server setup (one time)

### 1. Run migration

```bash
php artisan migrate
```

### 2. Configure `.env`

```env
RAZORPAY_KEY_ID=rzp_live_xxxxx
RAZORPAY_KEY_SECRET=your_secret
RAZORPAY_WEBHOOK_SECRET=whsec_xxxxx

TRIAL_AUTOPAY_ENABLED=true
TRIAL_PRICE=1
TRIAL_DAYS=2
TRIAL_RENEWAL_PRICE=79
```

### 3. Create Razorpay billing plan

```bash
php artisan trial:setup-razorpay-plan
```

Add to `.env`:

```env
RAZORPAY_LITE_MONTHLY_PLAN_ID=plan_xxxxxxxxxxxx
```

### 4. Clear config

```bash
php artisan config:clear
```

---

## Part 2 — Razorpay Dashboard webhook

Dashboard: https://dashboard.razorpay.com — use **Test Mode** first.

### Step 1 — Open Webhooks

1. Log in → enable **Test Mode** (top-left toggle)
2. **Account & Settings** (gear) → **Webhooks** → **+ Add New Webhook**

### Step 2 — Webhook URL

| Field | Value |
|-------|--------|
| URL | `https://YOUR-DOMAIN.com/api/webhooks/razorpay` |
| Secret | auto-generated — copy to `.env` |

Local dev: `ngrok http 8000` → use `https://xxx.ngrok.io/api/webhooks/razorpay`

### Step 3 — Enable events

| Event | Purpose |
|-------|---------|
| `subscription.authenticated` | Start 2-day Lite trial |
| `subscription.charged` | ₹79 monthly renewal |
| `subscription.cancelled` | Stop autopay |
| `subscription.halted` | Payment failed |

### Step 4 — Save & copy secret

1. **Create Webhook**
2. **Reveal Secret** → `RAZORPAY_WEBHOOK_SECRET=whsec_...`
3. `php artisan config:clear`

### Step 5 — Test

Webhook page → **Send Test Webhook** → `subscription.authenticated` → expect **200 OK**.

---

## Part 3 — UPI Autopay

1. **Settings → Payment Methods** → enable **UPI Autopay**
2. Complete KYC for live mode
3. Confirm **Subscriptions** enabled (contact Razorpay support if missing)

---

## Part 4 — Mobile API

| Endpoint | Use |
|----------|-----|
| `GET /api/trial/offer` | Paywall UI copy |
| `POST /api/trial/start` | Get `subscription_id` |
| Razorpay checkout | Pass `subscription_id` (not `order_id`) |
| `POST /api/trial/verify` | Activate after payment |
| `POST /api/trial/cancel` | Cancel autopay |

---

## Part 5 — Test checklist

- [ ] New user eligible on `/trial/offer`
- [ ] `/trial/start` returns `subscription_id`
- [ ] Razorpay test payment succeeds
- [ ] `/trial/verify` activates Lite
- [ ] Same user cannot retry trial
- [ ] Webhook returns 200
- [ ] `/trial/cancel` works

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| 403 webhook | Wrong `RAZORPAY_WEBHOOK_SECRET` |
| No activation | Call `/trial/verify`; check `storage/logs/laravel.log` |
| No ₹79 charge | Normal — charges after `TRIAL_DAYS` (2 days) |

```bash
tail -f storage/logs/laravel.log | grep -i trial
```
