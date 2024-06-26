<?php

namespace App\Models;

use App\Traits\AttributeFilterTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $model
 *
 * @property-read \App\Models\Url|null $url
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Size extends Model
{
    use AttributeFilterTrait;

    final const ONE_SIZE_ID = 1;

    final const ONE_SIZE_SLUG = 'size-none';

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'mysql';

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('sort', fn (Builder $query) => $query->orderBy('value'));
    }

    /**
     * Generate filter badge name
     */
    public function getBadgeName(): string
    {
        return 'Размер: ' . $this->name;
    }
}
