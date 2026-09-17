<?php

return [
    "urls" => [
        "backend_url" => env("APP_URL"),
        "dealer_url"  => env("DEALER_URL"),
        "admin_url"   => env("ADMIN_URL"),
        "buyer_url"   => env("BUYER_URL"),
        // Optional CDN / Cloudflare URL for public files (no trailing slash).
        // Example: https://cdn.omnicarsgh.com — leave empty to use APP_URL/storage
        "cdn_url"     => env("CDN_URL", env("FILESYSTEM_CDN_URL")),
    ],
];
