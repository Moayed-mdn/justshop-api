<?php

namespace App\Enums\Subscription;

enum SubscriptionEventTypeEnum: string
{
    case CREATED              = 'created';
    case TRIAL_STARTED        = 'trial_started';
    case TRIAL_ENDING         = 'trial_ending';
    case TRIAL_ENDED          = 'trial_ended';
    case ACTIVATED            = 'activated';
    case TRIAL_CONVERTED      = 'trial_converted';
    case PAYMENT_RECOVERED    = 'payment_recovered';
    case GRACE_PERIOD_STARTED = 'grace_period_started';
    case TRIAL_EXPIRED        = 'trial_expired';
    case STATUS_CHANGED       = 'status_changed';
    case RENEWED              = 'renewed';
    case PAYMENT_FAILED       = 'payment_failed';
    case ENTERED_PAST_DUE     = 'entered_past_due';
    case ENTERED_GRACE        = 'entered_grace';
    case SUSPENDED            = 'suspended';
    case UPGRADED             = 'upgraded';
    case DOWNGRADED           = 'downgraded';
    case DOWNGRADE_SCHEDULED  = 'downgrade_scheduled';
    case CANCELED             = 'canceled';
    case REACTIVATED          = 'reactivated';
    case PAUSED               = 'paused';
    case RESUMED              = 'resumed';
    case EXPIRED              = 'expired';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
