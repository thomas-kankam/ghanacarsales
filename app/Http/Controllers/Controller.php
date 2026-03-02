<?php
namespace App\Http\Controllers;

use App\Traits\ApiTransformer;
use App\Traits\AppNotifications;
use App\Traits\Helpers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests, ApiTransformer, AppNotifications, Helpers;

    public const API_SUCCESS          = 200;
    public const API_CREATED          = 201;
    public const API_ACCEPTED         = 202;
    public const API_NO_CONTENT       = 204;
    public const API_BAD_REQUEST      = 400;
    public const API_UNAUTHORIZED     = 401;
    public const API_FORBIDDEN        = 403;
    public const API_NOT_FOUND        = 404;
    public const API_VALIDATION_ERROR = 422;
    public const API_FAIL             = 500;
}
