<?php

namespace App\Models\OneC;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
abstract class AbstractOneCModel extends Model
{
    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'sqlsrv';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'ROW_ID';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Auto Trim Field From Database
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function hydrate(array $objects)
    {
        return parent::hydrate(
            array_map(function ($object) {
                foreach ($object as $k => $v) {
                    if (is_string($v)) {
                        $object->$k = trim($v);
                    }
                }

                return $object;
            }, $objects)
        );
    }
}
