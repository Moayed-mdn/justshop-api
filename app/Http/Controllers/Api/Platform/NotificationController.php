<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Traits\HandlesNotificationEndpoints;

class NotificationController extends Controller
{
    use HandlesNotificationEndpoints;
}
