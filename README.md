# Ghana Car Sales - Laravel Backend API

A comprehensive Laravel backend system for a multi-domain car sales platform with separate interfaces for buyers, sellers, and administrators.

## Architecture Overview

This project follows senior Laravel developer best practices with:

- **Multi-domain architecture**: `car.com` (buyers), `seller.car.com` (sellers), `admin.car.com` (admins)
- **Multi-guard authentication**: Separate guards for admin, buyer, and seller
- **Service layer architecture**: Business logic separated into service classes
- **Queue-based processing**: Laravel Horizon with Redis for background jobs
- **API versioning**: Versioned API endpoints (v1)
- **Comprehensive security**: Rate limiting, OTP verification, API authentication

## Project Structure

```
app/
├── Actions/              # Action classes for single-purpose operations
├── Services/             # Business logic services
│   ├── OtpService.php
│   ├── CarService.php
│   ├── PaymentService.php
│   ├── CarSearchService.php
│   └── AlertService.php
├── Jobs/                 # Queue jobs
│   ├── SendAlertNotification.php
│   ├── ExpireCars.php
│   ├── DeleteExpiredCars.php
│   └── SendExpiryReminder.php
├── Policies/            # Authorization policies
│   └── CarPolicy.php
├── Observers/            # Model observers
│   └── CarObserver.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── BaseApiController.php
│   │       └── V1/
│   │           ├── Admin/
│   │           ├── Buyer/
│   │           ├── Seller/
│   │           └── Common/
│   ├── Requests/         # Form request validators
│   │   ├── Admin/
│   │   ├── Buyer/
│   │   └── Seller/
│   └── Resources/        # API resources
│       ├── CarResource.php
│       ├── CarImageResource.php
│       ├── BrandResource.php
│       └── CarModelResource.php
├── Models/               # Eloquent models
└── Mail/                 # Mail classes
```

## Features

### Seller Flow
1. **Registration with OTP**: Mobile number verification via OTP
2. **Car Upload**: Upload car details with up to 10 images (5 mandatory)
3. **Payment System**: MoMo payment integration for listing activation
4. **Auto-expiry**: Cars expire after payment period, auto-delete after 5 days
5. **Expiry Reminders**: Notifications 3 days before expiry

### Buyer Flow
1. **Advanced Search**: Filter by brand, model, year, price, mileage, features, etc.
2. **Sorting**: Sort by price, year, or mileage
3. **Car Alerts**: Set up alerts for specific car criteria
4. **Dealer Viewing**: View all cars from a specific dealer

### Admin Flow
1. **Admin Management**: Admin user registration and authentication
2. **System Management**: Full system access

## Installation

1. **Install Dependencies**
```bash
composer install
```

2. **Environment Setup**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Configure Environment Variables**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ghanacarsales
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

HORIZON_DOMAIN=null
HORIZON_PATH=horizon
```

4. **Run Migrations**
```bash
php artisan migrate
```

5. **Seed Database**
```bash
php artisan db:seed --class=BrandSeeder
```

6. **Install Passport**
```bash
php artisan passport:install
```

7. **Publish Horizon**
```bash
php artisan horizon:install
php artisan horizon:publish
```

8. **Start Queue Worker**
```bash
php artisan horizon
```

## API Endpoints

### Common Endpoints
- `GET /api/v1/brands` - Get all active brands with models

### Admin Endpoints (admin.car.com)
- `POST /api/v1/admin/register` - Register admin
- `POST /api/v1/admin/login` - Admin login

### Seller Endpoints (seller.car.com)
- `POST /api/v1/seller/send-otp` - Send OTP to mobile number
- `POST /api/v1/seller/verify-otp` - Verify OTP and register
- `POST /api/v1/seller/cars` - Upload car (requires auth)
- `GET /api/v1/seller/cars` - List seller's cars
- `GET /api/v1/seller/cars/{id}` - Get car details
- `DELETE /api/v1/seller/cars/{id}` - Delete car
- `GET /api/v1/seller/payment/summary` - Get payment summary
- `POST /api/v1/seller/payment/create` - Create payment
- `POST /api/v1/seller/payment/callback` - Payment callback

### Buyer Endpoints (car.com)
- `GET /api/v1/buyer/cars/search` - Search cars with filters
- `GET /api/v1/buyer/cars/{id}` - Get car details
- `GET /api/v1/buyer/sellers/{sellerId}/cars` - Get dealer's cars
- `POST /api/v1/buyer/alerts` - Create alert
- `POST /api/v1/buyer/alerts/deactivate` - Deactivate alerts

## Scheduled Tasks

The following tasks run automatically:

- **Daily**: Mark expired cars
- **Daily**: Delete cars expired 5+ days ago
- **Daily**: Send expiry reminders (3 days before expiry)

## Security Features

1. **Rate Limiting**:
   - API: 60 requests/minute
   - OTP: 3 requests/minute
   - Search: 120 requests/minute

2. **OTP Verification**: 6-digit OTP with 10-minute expiry

3. **Multi-guard Authentication**: Separate authentication for admin, buyer, seller

4. **API Versioning**: All endpoints versioned (v1)

## Database Indexes

All critical columns are indexed for optimal performance:
- Foreign keys
- Searchable fields (price, year, mileage, location)
- Status and expiry dates
- Mobile numbers and emails

## Queue Jobs

- `SendAlertNotification`: Sends alerts to buyers when matching cars are uploaded
- `ExpireCars`: Marks cars as expired when payment period ends
- `DeleteExpiredCars`: Deletes cars expired 5+ days ago
- `SendExpiryReminder`: Sends reminders 3 days before expiry

## Payment Integration

The system is set up for MoMo (Mobile Money) integration. The payment callback endpoint handles payment confirmations and activates car listings.

## Testing

```bash
php artisan test
```

## Production Deployment

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Configure Redis for queues
3. Set up Horizon monitoring
4. Configure SMS provider for OTP
5. Integrate MoMo payment gateway
6. Set up proper email service
7. Configure domain routing for subdomains

## License

MIT
