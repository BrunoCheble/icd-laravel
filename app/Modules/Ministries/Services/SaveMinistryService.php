<?php

namespace App\Modules\Ministries\Services;

use App\Modules\Ministries\Models\Ministry;
use Illuminate\Support\Facades\DB;

class SaveMinistryService
{
    /** Creates or updates a ministry; only one is the site's "Pastores". */
    public function execute(array $data, ?Ministry $ministry = null): Ministry
    {
        return DB::transaction(function () use ($data, $ministry) {
            $ministry ??= new Ministry();
            $pastoral = (bool) ($data['is_pastoral'] ?? false);
            if ($pastoral) {
                Ministry::query()->where('key', Ministry::PASTORAL)->when($ministry->exists, fn ($query) => $query->whereKeyNot($ministry->id))->update(['key' => null]);
            }
            $ministry->fill([
                'name' => $data['name'],
                'key' => $pastoral ? Ministry::PASTORAL : ($ministry->key === Ministry::PASTORAL ? null : $ministry->key),
            ])->save();

            return $ministry;
        });
    }
}
