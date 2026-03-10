# Ghana Car Sales – Scenario Checklist

Use this to verify all flows after changes.

## 1. Dealer – Draft
- **Action:** POST `/api/dealer/upload_car` with `status=draft`, vehicle fields.
- **Expected:** Car created with `status=draft`; no payment, no approval; response has `car`, `payment: null`.

## 2. Dealer – Friend code
- **Action:** POST `/api/dealer/upload_car` with `plan_slug=friend_code`, vehicle fields, optional `dealer_code`.
- **Expected:** Car `status=pending_approval`, `plan_slug=friend_code`, `plan_price=0`; one Payment (amount 0, status paid); one PaymentItem (car_slug, price 0); one Approval (type friend_code, status pending). Admin can approve → car becomes published with start_date/expiry_date.

## 3. Dealer – Paid plan (e.g. 1_month)
- **Action:** POST `/api/dealer/upload_car` with `plan_slug=1_month`, vehicle fields.
- **Expected:** Car `status=pending_payment`, plan_slug/plan_price set; one Payment (amount = plan price); one PaymentItem (car_slug, price); response includes `payment_url` (Paystack or check_payment).

## 4. Payment callback (Paystack)
- **Action:** POST `/api/payment/callback` with Paystack webhook payload (or success redirect).
- **Expected:** Payment status → paid; each related car → `pending_approval`; one Approval per car (type listing_review). Admin can then approve.

## 5. Admin – Approve car
- **Action:** POST `/api/admin/cars/{id}/approve`.
- **Expected:** Car gets `status=published`, `start_date`, `expiry_date` from plan duration; Approval status → approved.

## 6. Admin – Reject car
- **Action:** POST `/api/admin/cars/{id}/reject` with optional `reason`.
- **Expected:** Car `status=rejected`; Approval status → rejected, reason stored.

## 7. Dealer – List cars
- **Action:** GET `/api/dealer/get_cars`.
- **Expected:** Paginated cars with `paymentItems.payment` loaded; transformer includes `payments` when loaded.

## 8. Dealer – Single car
- **Action:** GET `/api/dealer/single_car/{car}`.
- **Expected:** One car with associated payment(s) in response.

## 9. Buyer – Search
- **Action:** GET `/api/all_cars` (with optional filters).
- **Expected:** Only cars with `status=published` and (expiry_date null or > now).

## 10. Buyer – Single car
- **Action:** GET `/api/cars/{car}`.
- **Expected:** 404 if car not published; otherwise car details (view recorded).

## 11. Publish all drafts
- **Action:** GET `/api/dealer/publish_all_drafts`.
- **Expected:** All dealer’s draft cars → `pending_approval`; one Approval per car (type listing_review, no payment_slug). Admin can approve each.

## 12. Create payment (legacy route)
- **Action:** POST `/api/dealer/create_payment/{car}` with body `car_slugs[]`, `plan_slug`, etc.
- **Expected:** Payment + PaymentItems (one per car_slug); amount = plan_price × count(cars); response with `payment_url`.

## Quick simulation (Artisan Tinker)
```php
// After migrate and seed at least one dealer, plan:
$dealer = \App\Models\Dealer::first();
$plan = \App\Models\Plan::where('plan_slug', 'friend_code')->first();
// Create draft
$car = \App\Services\CarService::createCar($dealer, ['status' => 'draft', 'brand' => 'Toyota', 'model' => 'Camry', 'year_of_manufacture' => 2020]);
// Friend code flow
$car2 = \App\Services\CarService::createCar($dealer, ['status' => 'pending_approval', 'plan_slug' => 'friend_code', 'plan_price' => 0, 'brand' => 'Honda', 'model' => 'Accord', 'year_of_manufacture' => 2021]);
$payment = app(\App\Services\PaymentService::class)->createPaymentForCars($dealer, [$car2], $plan, null, null, 'friend_code');
$payment->update(['amount' => 0, 'status' => 'paid']);
app(\App\Services\ApprovalService::class)->createForCar($car2->car_slug, $dealer, 'friend_code', 'pending', null, $payment->payment_slug);
// Verify
\App\Models\Approval::where('car_slug', $car2->car_slug)->first();
```
