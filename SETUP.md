# Setup Guide

## Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Redis (for queues)
- Node.js & NPM (for frontend assets if needed)

## Step-by-Step Installation

### 1. Install Dependencies

```bash
composer install
```

### 2. Environment Configuration

Copy the `.env.example` file and configure:

```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with your database credentials:

```env
APP_NAME="Ghana Car Sales"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

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

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### 3. Database Setup

Create the database:

```sql
CREATE DATABASE ghanacarsales;
```

Run migrations:

```bash
php artisan migrate
```

Seed initial data (brands and models):

```bash
php artisan db:seed --class=BrandSeeder
```

### 4. Install Laravel Passport

```bash
php artisan passport:install
```

This will create OAuth clients. Note the client IDs for your frontend applications.

### 5. Install Laravel Horizon

```bash
php artisan horizon:install
php artisan horizon:publish
```

### 6. Storage Link

Create symbolic link for storage:

```bash
php artisan storage:link
```

### 7. Start Development Server

```bash
php artisan serve
```

### 8. Start Queue Worker (Horizon)

In a separate terminal:

```bash
php artisan horizon
```

## Domain Configuration

### Local Development

For local development, you can use:

1. **Modify hosts file** (`C:\Windows\System32\drivers\etc\hosts` on Windows or `/etc/hosts` on Linux/Mac):

```
127.0.0.1 car.local
127.0.0.1 seller.car.local
127.0.0.1 admin.car.local
```

2. **Configure virtual hosts** in your web server (Apache/Nginx) or use Laravel Valet/Homestead.

### Production

Configure your DNS to point:
- `car.com` → Your server IP
- `seller.car.com` → Your server IP
- `admin.car.com` → Your server IP

Configure your web server to handle all three domains.

## Testing the API

### Admin Registration

```bash
curl -X POST http://admin.car.local/api/v1/admin/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Admin User",
    "email": "admin@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Seller OTP Request

```bash
curl -X POST http://seller.car.local/api/v1/seller/send-otp \
  -H "Content-Type: application/json" \
  -d '{
    "mobile_number": "0244123456",
    "seller_type": "individual",
    "terms_accepted": true
  }'
```

### Buyer Search

```bash
curl -X GET "http://car.local/api/v1/buyer/cars/search?brand_id=1&min_price=10000&max_price=50000" \
  -H "Accept: application/json"
```

## Queue Configuration

Make sure Redis is running:

```bash
redis-server
```

Or if using Docker:

```bash
docker run -d -p 6379:6379 redis
```

## Scheduled Tasks

Add to your server's crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## SMS Integration

Update `app/Services/OtpService.php` to integrate with your SMS provider:

```php
public function sendOtpSms(string $mobileNumber, string $otp): void
{
    // Example with Twilio
    $client = new \Twilio\Rest\Client($sid, $token);
    $client->messages->create(
        $mobileNumber,
        [
            'from' => '+1234567890',
            'body' => "Your OTP is: {$otp}"
        ]
    );
}
```

## Payment Integration

Update `app/Http/Controllers/Api/V1/Seller/PaymentController.php` to integrate with MoMo payment gateway.

## Troubleshooting

### Passport Issues

If you get authentication errors, try:

```bash
php artisan passport:keys
php artisan config:clear
php artisan cache:clear
```

### Queue Not Processing

1. Check Redis is running
2. Check Horizon is running
3. Check queue connection in `.env`

### Storage Issues

If images aren't loading:

```bash
php artisan storage:link
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

## Next Steps

1. Configure SMS provider
2. Integrate MoMo payment gateway
3. Set up email service
4. Configure production environment
5. Set up monitoring and logging
6. Write tests
