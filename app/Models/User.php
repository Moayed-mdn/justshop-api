<?php

namespace App\Models;

use App\Enums\Auth\OnboardingStepEnum;
use App\Enums\Auth\ActorContextEnum;
use App\Support\Auth\ActorResolver;
use App\Enums\RoleEnum;
use App\Enums\Address\AddressTypeEnum;
use App\Notifications\CustomResetPassword;
use App\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'google_id',
        'avatar',
        'is_active',
        'onboarding_step',
        'onboarding_completed_at',
        'last_active_store_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'onboarding_step' => OnboardingStepEnum::class,
            'onboarding_completed_at' => 'datetime',
        ];
    }


    public function isOnboardingCompleted(): bool
    {
        // Customers are considered completed by default as they don't have onboarding
        if ($this->getActorContext() === ActorContextEnum::CUSTOMER) {
            return true;
        }

        return $this->onboarding_step === OnboardingStepEnum::COMPLETED;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    public function getActorContext(): ActorContextEnum
    {
        return app(ActorResolver::class)->resolve($this);
    }

    public function activeStore()
    {
        return $this->belongsTo(Store::class, 'last_active_store_id');
    }


    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail());
    }


    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPassword($token));
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function shippingAddresses()
    {
        return $this->addresses()->where('type', AddressTypeEnum::SHIPPING);
    }

    public function billingAddresses()
    {
        return $this->addresses()->where('type', AddressTypeEnum::BILLING);
    }

    public function defaultShippingAddress()
    {
        return $this->shippingAddresses()->where('is_default', true)->first();
    }

    public function defaultBillingAddress()
    {
        return $this->billingAddresses()->where('is_default', true)->first();
    }

    public function defaultPaymentMethod()
    {
        return $this->paymentMethods()->where('is_default', true)->first();
    }


    public function getAvatarUrl(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http')) {
            return $this->avatar;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url($this->avatar);
    }

    public function stores(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_user')
            ->withPivot('id', 'role')
            ->withTimestamps();
    }

    public function carts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function cartForStore(int $storeId): ?\App\Models\Cart
    {
        return $this->carts()
            ->where('store_id', $storeId)
            ->first();
    }
}
