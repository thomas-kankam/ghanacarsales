# Paystack integration

## Webhook URL (server-to-server)

- **Set in Paystack Dashboard:** Settings → API Keys & Webhooks → Webhook URL.
- **Production:** `https://backend.ghanacarsales.com/api/payment/webhook`
- **Generic:** `https://your-api-domain.com/api/payment/webhook`
- Paystack sends **POST** requests here when a charge succeeds (or fails). The backend:
  - Verifies the `x-paystack-signature` header (if `PAYSTACK_WEBHOOK_SECRET` is set).
  - Finds the payment by `reference` and marks it paid (or failed).
  - Creates approvals for each car so admin can approve.

## Callback (browser redirect)

- **Backend** sets `callback_url` to the **backend** when initializing (e.g. `https://backend.ghanacarsales.com/api/payment/callback`). Frontend does not send a callback URL unless you want to override.
- After payment, Paystack redirects the user to that backend URL with `?reference=xxx` (and `trxref=xxx`).
- **Backend callback** then:
  - Looks up the payment by reference (webhook may already have set status to paid/failed).
  - If still pending, verifies with Paystack API and updates the payment.
  - Redirects the user to the **frontend**:
    - **Success:** `APP_FRONTEND_URL/payment/success?reference=xxx`
    - **Failure:** `APP_FRONTEND_URL/payment/failure?reference=xxx` (or `?reason=...`)
    - **Pending/unknown:** `APP_FRONTEND_URL/payment/callback?reference=xxx`
- Frontend only needs routes `/payment/success` and `/payment/failure` (and optionally `/payment/callback` for pending). Optionally call `check_payment?reference_id=xxx` (authenticated) to load payment details.

## Flow

1. **Frontend** sends data (car_slugs, plan_slug, etc.) to backend. No callback_url needed.
2. **Backend** calls Paystack `transaction/initialize` with backend callback URL, returns `payment_url` and `reference`.
3. Frontend redirects user to `payment_url`.
4. User pays on Paystack. Paystack:
   - Sends **webhook** to backend → backend marks payment paid/failed and creates approvals.
   - Redirects user to **backend** callback with `?reference=xxx`.
5. **Backend callback** resolves payment (from DB or by verifying with Paystack), then redirects to frontend **success** or **failure** URL.
6. Frontend shows success or failure page; optionally call `check_payment` for details.

## .env

```
APP_FRONTEND_URL=https://ghanacarsales.com   # Used for payment callback/check fallback URLs

PAYSTACK_PUBLIC_KEY=pk_...
PAYSTACK_SECRET_KEY=sk_...
PAYSTACK_WEBHOOK_SECRET=...   # Required in production; from Paystack Dashboard → Webhook
PAYSTACK_PAYMENT_URL=https://api.paystack.co
```

- In **production**, `PAYSTACK_WEBHOOK_SECRET` must be set or webhook requests will be rejected.
- Set `APP_URL` to your backend base (e.g. `https://backend.ghanacarsales.com`) so the default callback URL is correct. Frontend can optionally call `POST /api/dealer/check_payment` with `reference_id` (and auth) to get payment details on the success/failure page.
