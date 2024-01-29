<?php

namespace App\Models\User;

use App\Models\Cart;
use App\Models\Country;
use App\Models\Feedback;
use App\Models\Logs\SmsLog;
use App\Models\Orders\Order;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use libphonenumber\PhoneNumberUtil;

/**
 * @property int $id
 * @property int|null $cart_token
 * @property int $group_id
 * @property string|null $email
 * @property string|null $last_name
 * @property string|null $patronymic_name
 * @property string|null $phone
 * @property \Illuminate\Support\Carbon|null $birth_date
 * @property \Illuminate\Support\Carbon|null $phone_verified_at
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $firstName
 * @property string $first_name
 * @property-read \App\Models\User\Group|null $group
 * @property-read \App\Models\Cart|null $cart
 * @property-read \App\Models\User\UserPassport|null $passport
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Orders\Order[] $orders
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Feedback[] $reviews
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Logs\SmsLog[] $mailings
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'first_name',
        'last_name',
        'patronymic_name',
        'phone',
        'email',
        'birth_date',
        'created_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'datetime',
        'phone_verified_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    public static function boot(): void
    {
        parent::boot();

        self::created(static function (self $user) {
            $user->setCountryByPhone();
        });
    }

    /**
     * Find user by phone number
     */
    public static function getByPhone(string $phone): ?self
    {
        return self::query()->where('phone', $phone)->first();
    }

    /**
     * User's discount group
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class)->withDefault(Group::defaultData());
    }

    /**
     * User's cart
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'cart_token');
    }

    /**
     * User passport
     */
    public function passport(): HasOne
    {
        return $this->hasOne(UserPassport::class);
    }

    /**
     * User addresses
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * User last address
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function lastAddress()
    {
        return $this->hasOne(Address::class)->orderBy('id', 'desc');
    }

    /**
     * Get fisrt user address if exist
     *
     * @return \App\Models\Address
     */
    public function getFirstAddress()
    {
        return optional($this->addresses[0] ?? null);
    }

    /**
     * Get fisrt user address country id if exist
     */
    public function getFirstAddressCountryId(): ?int
    {
        return $this->getFirstAddress()->country_id;
    }

    /**
     * Get fisrt full user address if exist
     */
    public function getFirstFullAddress(): ?string
    {
        if (!$address = $this->getFirstAddress()) {
            return null;
        }

        $addressParts = array_filter([
            optional($address->country)->name,
            $address->city,
            $address->address,
        ]);

        return implode(', ', $addressParts);
    }

    /**
     * Check user has addresses
     */
    public function hasAddresses(): bool
    {
        return !empty($this->getFirstAddress()->id);
    }

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullName()
    {
        return "{$this->last_name} {$this->first_name} {$this->patronymic_name}";
    }

    /**
     * Interact with the user's first name.
     *
     * @param  string  $firstName
     */
    public function firstName(): Attribute
    {
        return Attribute::make(
            get: fn ($firstName): string => Str::ucfirst($firstName)
        );
    }

    /**
     * User's orders
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * User's reviews
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * Mailings sent to the user
     */
    public function mailings(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    /**
     * Farmat date in admin panel
     *
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('d.m.Y H:i:s');
    }

    /**
     * Update datetime in phone_verified_at field
     */
    public function updatePhoneVerifiedAt(): bool
    {
        return $this->forceFill([
            'phone_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Route notifications for the SmsTraffic channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    public function routeNotificationForSmsTraffic($notification)
    {
        return $this->phone;
    }

    /**
     * Check if required fields filled
     */
    public function hasRequiredFields(): bool
    {
        return !empty($this->first_name) && !empty($this->last_name)
            && !empty($this->getFirstAddress()->city);
    }

    /**
     * Save user country by his phone
     */
    public function setCountryByPhone(): void
    {
        /** @var Address $address */
        $address = $this->addresses()->firstOrNew();

        $phoneUtil = PhoneNumberUtil::getInstance();
        $parsedPhone = $phoneUtil->parse($this->phone);
        $countryCode = $phoneUtil->getRegionCodeForNumber($parsedPhone);

        $countryId = Country::query()->where('code', $countryCode)->value('id');

        if ($countryId) {
            $address->country_id = $countryId;
            $address->save();
        }
    }

    /**
     * Get calculated & cached user data
     */
    public function getCachedUser(): CachedUser
    {
        return Cache::rememberForever(
            $this->getCacheKey(),
            fn () => new CachedUser(...$this->getDataForCache())
        );
    }

    /**
     * Generate key for cache
     */
    public function getCacheKey(): string
    {
        return "user-{$this->id}";
    }

    /**
     * Check if user has review after order (cached)
     */
    public function hasReviewAfterOrder(): bool
    {
        return $this->getCachedUser()->hasReviewAfterOrder;
    }

    /**
     * Calculate user data for cache
     */
    private function getDataForCache(): array
    {
        return [
            'hasReviewAfterOrder' => $this->_hasReviewAfterOrder(),
        ];
    }

    /**
     * Check if user has review after order
     */
    private function _hasReviewAfterOrder(): bool
    {
        /** @var Order $lastOrder */
        if ($lastOrderDate = $this->orders()->latest()->value('created_at')) {
            return $this->reviews()->where('created_at', '>', $lastOrderDate)
                ->whereHas('media')->exists();
        }

        return false;
    }

    /**
     * Calculate user's completed orders cost
     */
    public function completedOrdersCost(): float
    {
        $cost = 0;
        $this->orders->each(function (Order $order) use (&$cost) {
            if ($order->isCompleted()) {
                $cost += $order->getItemsPrice() / $order->rate;
            }
        });

        return $cost;
    }
}
