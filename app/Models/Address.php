<?php
// app/Models/Address.php
namespace App\Models;

use App\Models\Concerns\HasStoreScoping;
use App\Enums\Address\AddressTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, SoftDeletes, HasStoreScoping;

    protected $fillable = [
        'store_id', 
        'user_id', 
        'name',
        'type', 
        'is_default_shipping',
        'is_default_billing',
        'first_name', 
        'last_name', 
        'company',
        'address_line_1', 
        'address_line_2', 
        'city', 
        'state', 
        'postal_code', 
        'country', 
        'phone',
        'email',
        'latitude',
        'longitude',
        'delivery_instructions',
    ];

    protected $casts = [
        'is_default_shipping' => 'boolean',
        'is_default_billing' => 'boolean',
        'type' => AddressTypeEnum::class,
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the user that owns this address.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the store associated with this address (optional).
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get orders that used this as shipping address.
     */
    public function shippingOrders()
    {
        return $this->hasMany(Order::class, 'shipping_address_id');
    }

    /**
     * Get orders that used this as billing address.
     */
    public function billingOrders()
    {
        return $this->hasMany(Order::class, 'billing_address_id');
    }

    /**
     * Get full name from first and last name.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get formatted full address.
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->address_line_1;
        
        if ($this->address_line_2) {
            $address .= ', ' . $this->address_line_2;
        }
        
        if ($this->company) {
            $address = $this->company . ', ' . $address;
        }
        
        $address .= ', ' . $this->city;
        
        if ($this->state) {
            $address .= ', ' . $this->state;
        }
        
        $address .= ' ' . $this->postal_code;
        $address .= ', ' . $this->country;
        
        return $address;
    }

    /**
     * Get address as array suitable for API responses.
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'company' => $this->company,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_default_shipping' => $this->is_default_shipping,
            'is_default_billing' => $this->is_default_billing,
            'delivery_instructions' => $this->delivery_instructions,
        ];
    }

    /**
     * Scope to get default shipping address for a user.
     */
    public function scopeDefaultShipping($query, int $userId)
    {
        return $query->where('user_id', $userId)
            ->where('is_default_shipping', true);
    }

    /**
     * Scope to get default billing address for a user.
     */
    public function scopeDefaultBilling($query, int $userId)
    {
        return $query->where('user_id', $userId)
            ->where('is_default_billing', true);
    }

    /**
     * Set this address as the default shipping address.
     * Unsets any other default shipping addresses for the user.
     */
    public function setAsDefaultShipping(): void
    {
        // Unset all other default shipping addresses for this user
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default_shipping' => false]);

        $this->update(['is_default_shipping' => true]);
    }

    /**
     * Set this address as the default billing address.
     * Unsets any other default billing addresses for the user.
     */
    public function setAsDefaultBilling(): void
    {
        // Unset all other default billing addresses for this user
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default_billing' => false]);

        $this->update(['is_default_billing' => true]);
    }

    /**
     * Check if this address is valid for a given store.
     */
    public function isValidForStore(Store $store): bool
    {
        $settings = StoreAddressSetting::where('store_id', $store->id)->first();
        
        if (!$settings) {
            return true; // No restrictions if settings not configured
        }

        $errors = $settings->validateAddress($this->toArray());
        return empty($errors);
    }
}