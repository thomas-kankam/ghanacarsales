# Project Structure

## Directory Overview

```
ghanacarsales/
├── app/
│   ├── Actions/                    # Action classes (future use)
│   ├── Console/
│   │   └── Kernel.php             # Scheduled tasks
│   ├── Exceptions/
│   │   └── Handler.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── BaseApiController.php
│   │   │       └── V1/
│   │   │           ├── Admin/
│   │   │           │   └── AuthController.php
│   │   │           ├── Buyer/
│   │   │           │   ├── CarController.php
│   │   │           │   └── AlertController.php
│   │   │           ├── Seller/
│   │   │           │   ├── AuthController.php
│   │   │           │   ├── CarController.php
│   │   │           │   └── PaymentController.php
│   │   │           └── Common/
│   │   │               └── BrandController.php
│   │   ├── Middleware/
│   │   │   ├── Authenticate.php
│   │   │   └── SetApiGuard.php
│   │   ├── Requests/
│   │   │   ├── Admin/
│   │   │   │   └── AdminRegisterRequest.php
│   │   │   ├── Buyer/
│   │   │   │   ├── BuyerSearchRequest.php
│   │   │   │   └── BuyerAlertRequest.php
│   │   │   └── Seller/
│   │   │       ├── SellerRegisterRequest.php
│   │   │       ├── CarUploadRequest.php
│   │   │       └── OtpVerifyRequest.php
│   │   └── Resources/
│   │       ├── CarResource.php
│   │       ├── CarImageResource.php
│   │       ├── BrandResource.php
│   │       └── CarModelResource.php
│   ├── Jobs/
│   │   ├── SendAlertNotification.php
│   │   ├── ExpireCars.php
│   │   ├── DeleteExpiredCars.php
│   │   └── SendExpiryReminder.php
│   ├── Mail/
│   │   └── EmailPasswordChange.php
│   ├── Models/
│   │   ├── Admin.php
│   │   ├── Buyer.php
│   │   ├── Seller.php
│   │   ├── OtpVerification.php
│   │   ├── Brand.php
│   │   ├── CarModel.php
│   │   ├── Car.php
│   │   ├── CarImage.php
│   │   ├── Payment.php
│   │   ├── PaymentCar.php
│   │   ├── BuyerAlert.php
│   │   └── AlertNotification.php
│   ├── Observers/
│   │   └── CarObserver.php
│   ├── Policies/
│   │   └── CarPolicy.php
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── AuthServiceProvider.php
│   │   ├── EventServiceProvider.php
│   │   └── RouteServiceProvider.php
│   └── Services/
│       ├── OtpService.php
│       ├── CarService.php
│       ├── PaymentService.php
│       ├── CarSearchService.php
│       └── AlertService.php
├── bootstrap/
├── config/
│   ├── auth.php                   # Multi-guard configuration
│   ├── horizon.php                # Horizon configuration
│   ├── queue.php
│   └── ...
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_admins_table.php
│   │   ├── 2024_01_01_000002_create_buyers_table.php
│   │   ├── 2024_01_01_000003_create_sellers_table.php
│   │   ├── 2024_01_01_000004_create_otp_verifications_table.php
│   │   ├── 2024_01_01_000005_create_brands_table.php
│   │   ├── 2024_01_01_000006_create_models_table.php
│   │   ├── 2024_01_01_000007_create_cars_table.php
│   │   ├── 2024_01_01_000008_create_car_images_table.php
│   │   ├── 2024_01_01_000009_create_payments_table.php
│   │   ├── 2024_01_01_000010_create_payment_cars_table.php
│   │   ├── 2024_01_01_000011_create_buyer_alerts_table.php
│   │   └── 2024_01_01_000012_create_alert_notifications_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── BrandSeeder.php
├── routes/
│   ├── api.php                    # API routes
│   ├── web.php
│   └── console.php
├── resources/
│   ├── views/
│   │   └── emails/
│   │       └── admin-credentials.blade.php
│   └── ...
├── storage/
├── tests/
├── composer.json
├── README.md
├── ARCHITECTURE.md
├── SETUP.md
└── PROJECT_STRUCTURE.md
```

## Key Files Explained

### Controllers
- **BaseApiController**: Base controller with standardized API responses
- **AuthController**: Authentication for each user type
- **CarController**: Car management (upload, list, delete)
- **PaymentController**: Payment processing
- **AlertController**: Buyer alert management
- **BrandController**: Brand/model listing

### Services
- **OtpService**: OTP generation and verification
- **CarService**: Car CRUD operations, image handling, expiry management
- **PaymentService**: Payment creation and processing
- **CarSearchService**: Advanced search with filters
- **AlertService**: Alert matching and notification triggering

### Models
All models include:
- Proper relationships
- Fillable attributes
- Casts for data types
- Soft deletes where applicable

### Jobs
Background jobs for:
- Alert notifications
- Car expiry management
- Expiry reminders

### Policies
Authorization policies for resource access control.

### Observers
Model observers for automatic actions (e.g., checking alerts when cars are activated).

## Naming Conventions

- **Controllers**: PascalCase, descriptive (e.g., `CarController`)
- **Services**: PascalCase with "Service" suffix (e.g., `CarService`)
- **Models**: Singular PascalCase (e.g., `Car`)
- **Migrations**: Descriptive with timestamp prefix
- **Routes**: RESTful, versioned (e.g., `/api/v1/seller/cars`)

## Code Organization Principles

1. **Separation of Concerns**: Business logic in services, not controllers
2. **Single Responsibility**: Each class has one clear purpose
3. **DRY (Don't Repeat Yourself)**: Reusable services and base classes
4. **API Versioning**: All endpoints versioned for backward compatibility
5. **Standardized Responses**: Consistent API response format
