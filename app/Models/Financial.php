<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Financial
 *
 * @property $id
 * @property $member_id
 * @property $description
 * @property $amount
 * @property $date
 * @property $type
 * @property $category
 * @property $processed_at
 * @property $notes
 * @property $deleted_at
 * @property $created_at
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Financial extends Model
{
    use SoftDeletes;

    protected $perPage = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['member_id', 'description', 'amount', 'date', 'type', 'category', 'processed_at', 'notes'];


}
