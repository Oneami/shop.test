<?php

namespace App\Models;

use App\Facades\Currency;
use App\Services\SearchService;
use App\Traits\ProductSales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Class Product
 *
 * @property int $id
 * @property \App\Models\Category $category
 * @property \App\Models\Brand $brand
 * @property Collection<Size> $sizes
 * @property string $sku (new title)
 * @property string $slug
 * @property float $price
 * @property float $old_price
 * @property int $category_id
 * @property string $color_txt
 * @property string $fabric_top_txt
 * @property string $fabric_inner_txt
 * @property string $fabric_insole_txt
 * @property string $fabric_outsole_txt
 * @property string $heel_txt
 * @property string $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * ...
 */
class Product extends Model implements HasMedia
{
    use SoftDeletes;
    use ProductSales;
    use InteractsWithMedia {
        getFirstMediaUrl as traitGetFirstMediaUrl;
    }

    /**
     * Default sorting
     */
    public const DEFAULT_SORT = 'rating';

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Ссылка на товар
     */
    protected ?string $url = null;

    /**
     * Категория товара
     */
    public function category(): Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * коллекция
     */
    public function collection(): Relations\BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * Размеры
     */
    public function sizes(): Relations\MorphToMany
    {
        return $this->morphedByMany(Size::class, 'attribute', 'product_attributes');
    }

    /**
     * Цвет
     */
    public function colors(): Relations\MorphToMany
    {
        return $this->morphedByMany(Color::class, 'attribute', 'product_attributes');
    }

    /**
     * материалы
     */
    public function fabrics(): Relations\MorphToMany
    {
        return $this->morphedByMany(Fabric::class, 'attribute', 'product_attributes');
    }

    /**
     * Типы каблука
     */
    public function heels(): Relations\MorphToMany
    {
        return $this->morphedByMany(Heel::class, 'attribute', 'product_attributes');
    }

    /**
     * Стили
     */
    public function styles(): Relations\MorphToMany
    {
        return $this->morphedByMany(Style::class, 'attribute', 'product_attributes');
    }

    /**
     * Сезон
     */
    public function season(): Relations\BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Теги
     */
    public function tags(): Relations\MorphToMany
    {
        return $this->morphedByMany(Tag::class, 'attribute', 'product_attributes');
    }

    /**
     * Бренд
     */
    public function brand(): Relations\BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Производитель
     */
    public function manufacturer(): Relations\BelongsTo
    {
        return $this->belongsTo(ProductAttributes\Manufacturer::class);
    }

    /**
     * Slug для фильтра
     */
    public function url(): Relations\MorphOne
    {
        return $this->morphOne(Url::class, 'model');
    }

    /**
     * Get the favorite associated with the product.
     */
    public function favorite(): Relations\HasOne
    {
        return $this->hasOne(Favorite::class);
    }

    /**
     * Product group relation.
     */
    public function productGroup()
    {
        return $this->belongsTo(ProductGroup::class);
    }

    /**
     * Get product simple name (category name + brand name)
     */
    public function simpleName(): string
    {
        return $this->category->title . ' ' . $this->brand->name;
    }

    /**
     * Get product short name (category name + id)
     */
    public function shortName(): string
    {
        return $this->category->title . ' ' . $this->id;
    }

    /**
     * Simple name + id
     */
    public function extendedName(): string
    {
        return $this->simpleName() . ' ' . $this->id;
    }

    /**
     * Получить полное название продукта
     */
    public function getFullName(): string
    {
        return $this->brand->name . ' ' . $this->sku;
    }

    /**
     * Получить ссылку на товар
     */
    public function getUrl(): string
    {
        return $this->url ?? ($this->url = $this->category->getUrl() . '/' . $this->slug);
    }

    /**
     * Размеры изображений
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(100);
        $this->addMediaConversion('catalog')->width(300);
        $this->addMediaConversion('normal')->width(700);
        $this->addMediaConversion('full')->width(1200);
    }

    /**
     * Сортировка товаров
     *
     * @param  Builder  $query
     * @param  string  $type
     * @return Builder
     */
    public function scopeSorting(Builder $query, string $type)
    {
        return match ($type) {
            'newness' => $query->orderByDesc('created_at')->orderByDesc('id'),
            'price-up' => $query->orderBy('price')->orderBy('id'),
            'price-down' => $query->orderByDesc('price')->orderByDesc('id'),
            default => $query->orderByDesc('rating')->orderByDesc('id'), // rating
            // 'discount' => $query->orderByDesc('discount')->orderByDesc('id'),
        };
    }

    /**
     * Поиск товаров
     *
     * @param  Builder  $query
     * @param  string  $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $search = null)
    {
        if (empty($search)) {
            return $query;
        }
        $searchService = new SearchService($search);

        if ($searchService->useSimpleSearch()) {
            $searchValue = $searchService->getIds()[0];

            return $searchService->generateSearchQuery($query, 'sku')
                ->orWhere('id', $searchValue);
        }

        $query->where(function ($query) use ($searchService) {
            $searchService->generateSearchQuery($query, 'sku')
                ->orWhereIn('id', $searchService->getIds())
                ->orWhereHas('brand', function (Builder $query) use ($searchService) {
                    $searchService->generateSearchQuery($query, 'name');
                })
                ->orWhereHas('category', function (Builder $query) use ($searchService) {
                    $searchService->generateSearchQuery($query, 'title');
                })
                ->orWhere(function (Builder $query) use ($searchService) {
                    $searchService->generateSearchQuery($query, 'color_txt');
                })
                ->orWhereHas('tags', function (Builder $query) use ($searchService) {
                    $searchService->generateSearchQuery($query, 'name');
                });
        });

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get only products with discount
     *
     * @param  Builder  $query
     * @param  float  $amount
     * @return Builder
     */
    public function scopeOnlyWithDiscount(Builder $query, float $amount = 0.01)
    {
        return $query->whereRaw('((`old_price` - `price`) / `old_price`) > ?', $amount);
    }

    /**
     * Get only new products
     *
     * @param  Builder  $query
     * @param  int  $days
     * @return Builder
     */
    public function scopeOnlyNew(Builder $query, int $days = 10)
    {
        // return $query->where('created_at', '>', now()->subDays($days));
        return $query->where('old_price', 0);
    }

    /**
     * Check product's discount
     */
    public function hasDiscount(): bool
    {
        return $this->getPrice() < $this->getOldPrice();
    }

    /**
     * Get product price
     *
     * @param  string|null  $currencyCode
     * @return float
     */
    public function getPrice(?string $currencyCode = null): float
    {
        return Currency::convert($this->getFinalPrice(), $currencyCode);
    }

    /**
     * get product price
     *
     * @return float
     */
    public function getFormattedPrice()
    {
        return Currency::convertAndFormat($this->getFinalPrice());
    }

    /**
     * Get fixed wrong old price
     */
    public function getFixedOldPrice(): float
    {
        return $this->old_price > $this->price ? $this->old_price : $this->price;
    }

    /**
     * Get fianl old price after apply other sales
     */
    public function getFinalOldPrice(): float
    {
        $this->applySales();

        return $this->getFixedOldPrice();
    }

    /**
     * Get product old price
     *
     * @param  string|null  $currencyCode
     * @return float
     */
    public function getOldPrice(?string $currencyCode = null): float
    {
        return Currency::convert($this->getFinalOldPrice(), $currencyCode);
    }

    /**
     * get product old price
     *
     * @return float
     */
    public function getFormattedOldPrice()
    {
        return Currency::convertAndFormat($this->getFinalOldPrice());
    }

    /**
     * getFirstMediaUrl & check empty media
     *
     * @param  string  $collectionName
     * @param  string  $conversionName
     * @return string
     */
    public function getFirstMediaUrl(string $collectionName = 'default', string $conversionName = ''): string
    {
        if (!($url = $this->traitGetFirstMediaUrl($collectionName, $conversionName))) {
            $url = '/storage/products/0/deleted.jpg';
        }

        return $url;
    }

    /**
     * Set default values for product
     *
     * @param  int  $id
     * @return void
     */
    public function setDefaultValues(int $id = 0)
    {
        $this->id = $id;
        $this->sku = 'Товар удалён';
        $this->deleted_at = $this->created_at = Carbon::createFromDate(2017);

        $this->setRelation('category', Category::getDefault());
        $this->setRelation('brand', Brand::getDefault());
    }

    /**
     * Is the model new
     *
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->old_price == 0;
    }

    /**
     * Checks that the product has only one size
     */
    public function hasOneSize(): bool
    {
        return $this->sizes->count() === 1 && $this->sizes->first()->slug === Size::ONE_SIZE_SLUG;
    }

    /**
     * Check min installmnet price
     */
    public function availableInstallment(): bool
    {
        return $this->getPrice() >= Config::findCacheable('installment')['min_price'];
    }
}
