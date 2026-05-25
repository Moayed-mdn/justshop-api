<?php
// app/Models/Address.php
namespace App\Models;

use App\Models\Concerns\HasStoreScoping;
use App\Enums\Address\AddressTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, SoftDeletes, HasStoreScoping;

    protected $fillable = [
        'store_id', 'user_id', 'type', 'is_default', 'first_name', 'last_name', 'company',
        'address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 
        'country', 'phone'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'type'       => AddressTypeEnum::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getFullAddressAttribute()
    {
        $address = $this->address_line_1;
        if ($this->address_line_2) {
            $address .= ', ' . $this->address_line_2;
        }
        $address .= ', ' . $this->city . ', ' . $this->state . ' ' . $this->postal_code;
        return $address;
    }
}