<?php

namespace App\Models;

use App\Facades\Currency;
use App\Facades\Sale;
use App\Models\ProductAttributes;
use App\Services\SearchService;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Class Product
 *
 * @package App
 *
 * @property \App\Category      $category
 * @property string             $title
 * @property string             $slug
 * ...
 */
class Product extends Model implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;

    /**
     * Default sorting
     */
    const DEFAULT_SORT = 'rating';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Ссылка на товар
     *
     * @var string
     */
    protected $url = null;

    /**
     * Категория товара
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    /**
     * коллекция
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }
    // /**
    //  * Картинки товара
    //  *
    //  * @return Illuminate\Database\Eloquent\Relations\MorphMany
    //  */
    // public function images()
    // {
    //     return $this->morphMany(Images::class, 'entity')->orderBy('sorting');
    // }
    /**
     * Получить шильды для продукта
     *
     * @return string
     */
    public function getLabelsAttribute()
    {
        return 'labels';
    }
    /**
     * Размеры
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function sizes()
    {
        return $this->morphedByMany(Size::class, 'attribute', 'product_attributes');
    }
    /**
     * Цвет
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function colors()
    {
        return $this->morphedByMany(Color::class, 'attribute', 'product_attributes');
    }
    /**
     * материалы
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function fabrics()
    {
        return $this->morphedByMany(Fabric::class, 'attribute', 'product_attributes');
    }
    /**
     * Типы каблука
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function heels()
    {
        return $this->morphedByMany(Heel::class, 'attribute', 'product_attributes');
    }
    /**
     * Стили
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function styles()
    {
        return $this->morphedByMany(Style::class, 'attribute', 'product_attributes');
    }
    /**
     * Сезон
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function season()
    {
        return $this->belongsTo(Season::class);
    }
    /**
     * Теги
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function tags()
    {
        return $this->morphedByMany(Tag::class, 'attribute', 'product_attributes');
    }
    /**
     * Бренд
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Производитель
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function manufacturer()
    {
        return $this->belongsTo(ProductAttributes\Manufacturer::class);
    }

    /**
     * Slug для фильтра
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function url()
    {
        return $this->morphOne(Url::class, 'model');
    }
    /**
     * Получить полное название продукта
     *
     * @return void
     */
    public function getFullName()
    {
        return ($this->brand->name ?? 'VITACCI'). ' ' . $this->title;
    }
    /**
     * Получить ссылку на товар
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url ?? ($this->url = $this->category->getUrl() . '/' . $this->slug);
    }
    /**
     * Размеры изображений
     *
     * @param Media $media
     * @return void
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
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeSorting(Builder $query, string $type)
    {
        switch ($type) {
            case 'newness':
                return $query->orderByDesc('created_at')->orderByDesc('id');

            case 'rating':
            default:
                return $query->orderByDesc('rating')->orderByDesc('id');

            case 'price-up':
                return $query->orderBy('price')->orderBy('id');

            case 'price-down':
                return $query->orderByDesc('price')->orderByDesc('id');

            // case 'discount':
            //     return $query->orderByDesc('discount')->orderByDesc('id');
        }
    }
    /**
     * Поиск товаров
     *
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $search = null)
    {
        if (!empty($search)) {
            $searchService = new SearchService($search);
            $query->where(function ($query) use ($searchService) {
                $searchService->generateSearchQuery($query, 'title')
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
            $query->orderBy('created_at', 'desc');
        }
        return $query;
    }

    /**
     * Get only products with sale
     *
     * @param Builder $query
     * @param float $amount
     * @return Builder
     */
    public function scopeOnlyWithSale(Builder $query, float $amount = 0.01)
    {
        return $query->whereRaw('((`old_price` - `price`) / `old_price`) > ?', $amount);
    }

    /**
     * Get only new products
     *
     * @param Builder $query
     * @param int $days
     * @return Builder
     */
    public function scopeOnlyNew(Builder $query, int $days = 10)
    {
        return $query->where('created_at', '>', now()->subDays($days));
    }

    /**
     * Применить акцияю к продукту
     *
     * @return void
     */
    public function applySale()
    {
        if (!isset($this->sale)) {
            Sale::applyForProduct($this);
        }
    }

    /**
     * Get fianl price after apply other sales
     *
     * @return float
     */
    protected function getFinalPrice()
    {
        if (!isset($this->final_price)) {
            $this->applySale();
            $this->final_price = $this->sale['price'] ?? $this->price;
        }
        return $this->final_price;;
    }

    /**
     * get product price
     *
     * @return float
     */
    public function getPrice()
    {
        return Currency::convert($this->getFinalPrice());
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
     *
     * @return float
     */
    public function getFixedOldPrice()
    {
        return $this->old_price > $this->price ? $this->old_price : $this->price;
    }

    /**
     * Get fianl old price after apply other sales
     *
     * @return float
     */
    protected function getFinalOldPrice()
    {
        if (!isset($this->final_old_price)) {
            $this->applySale();
            $this->final_old_price = $this->getFixedOldPrice();
        }
        return $this->final_old_price;
    }

    /**
     * get product old price
     *
     * @return float
     */
    public function getOldPrice()
    {
        return Currency::convert($this->getFinalOldPrice());
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
     * Calculate sale percentage
     *
     * @return integer
     */
    public function getSalePercentage(): int
    {
        return ceil((1 - ($this->getFinalPrice() / $this->getFinalOldPrice())) * 100);
    }
}
