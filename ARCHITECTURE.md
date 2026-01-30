# Architecture Documentation

## System Architecture

### Multi-Domain Setup

The system is designed to work with three subdomains:
- `car.com` - Buyer interface
- `seller.car.com` - Seller interface  
- `admin.car.com` - Admin interface

Each subdomain uses a different authentication guard configured in `config/auth.php`.

### Authentication Flow

1. **Admin Authentication**
   - Uses Laravel Passport
   - Guard: `admin`
   - Provider: `admins`

2. **Seller Authentication**
   - Uses Laravel Passport
   - Guard: `seller`
   - Provider: `sellers`
   - Requires OTP verification for registration

3. **Buyer Authentication**
   - Uses Laravel Passport
   - Guard: `buyer`
   - Provider: `buyers`
   - Optional authentication for alerts

### Database Design

#### Core Tables
- `admins` - Admin users
- `buyers` - Buyer users
- `sellers` - Seller users (individual or dealer)
- `otp_verifications` - OTP codes for mobile verification

#### Car Management
- `brands` - Car brands (Toyota, Honda, etc.)
- `car_models` - Car models (Camry, Civic, etc.)
- `cars` - Car listings
- `car_images` - Car images (up to 10 per car)

#### Payment System
- `payments` - Payment records
- `payment_cars` - Pivot table for payment-car relationship

#### Alert System
- `buyer_alerts` - Buyer alert criteria
- `alert_notifications` - Sent alert notifications

### Service Layer Architecture

Business logic is separated into service classes:

1. **OtpService** - Handles OTP generation and verification
2. **CarService** - Car creation, image upload, expiry management
3. **PaymentService** - Payment processing and car activation
4. **CarSearchService** - Advanced search and filtering
5. **AlertService** - Alert matching and notification

### Queue System

Uses Laravel Horizon with Redis for background processing:

1. **SendAlertNotification** - Sends alerts when matching cars are found
2. **ExpireCars** - Marks cars as expired daily
3. **DeleteExpiredCars** - Deletes cars expired 5+ days ago
4. **SendExpiryReminder** - Sends reminders 3 days before expiry

### API Design

#### Versioning
All endpoints are versioned under `/api/v1/`

#### Response Format
```json
{
    "success": true,
    "message": "Action successful",
    "status_code": 200,
    "data": {},
    "reason": "Optional reason"
}
```

#### Rate Limiting
- API: 60 requests/minute
- OTP: 3 requests/minute
- Search: 120 requests/minute

### Security Features

1. **OTP Verification** - 6-digit code, 10-minute expiry
2. **Multi-guard Authentication** - Separate guards per user type
3. **Rate Limiting** - Per-endpoint rate limits
4. **API Versioning** - Versioned endpoints for backward compatibility
5. **Policies** - Authorization policies for resource access

### Scheduled Tasks

Configured in `app/Console/Kernel.php`:
- Daily: Expire cars
- Daily: Delete expired cars (5+ days)
- Daily: Send expiry reminders

### File Storage

Car images are stored in `storage/app/public/cars/{car_id}/` with:
- Primary image flag
- Sort order
- Up to 10 images per car

### Payment Flow

1. Seller uploads car(s) → Status: `pending`
2. Seller creates payment → Payment record created
3. Payment processed via MoMo → Callback received
4. Payment confirmed → Cars activated, status: `active`
5. Cars expire after duration → Status: `expired`
6. Auto-delete after 5 days → Soft delete

### Alert Flow

1. Buyer creates alert with criteria
2. New car uploaded → Observer triggers
3. AlertService checks if car matches alert
4. If match → Queue SendAlertNotification job
5. Job sends SMS/Email to buyer
6. Notification record created to prevent duplicates

### Performance Optimizations

1. **Database Indexes** - All foreign keys and searchable fields indexed
2. **Eager Loading** - Relationships loaded to prevent N+1 queries
3. **Queue Processing** - Heavy operations moved to background
4. **Caching** - Registration data cached temporarily
5. **Pagination** - All list endpoints paginated

### Error Handling

- Standardized API responses
- Try-catch blocks in services
- Queue job retries for failed jobs
- Logging for debugging

### Testing Strategy

- Unit tests for services
- Feature tests for API endpoints
- Integration tests for payment flow
- Queue job tests
