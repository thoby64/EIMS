<?php

namespace App\Services;

use App\Models\AssetCategory;
use Illuminate\Support\Facades\DB;

class AssetTagGenerator
{
    public function next(AssetCategory $category, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        DB::table('asset_tag_sequences')->insertOrIgnore([
            'asset_category_id' => $category->id,
            'year' => $year,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('asset_tag_sequences')
            ->where('asset_category_id', $category->id)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        $next = $sequence->last_number + 1;
        DB::table('asset_tag_sequences')->where('id', $sequence->id)->update([
            'last_number' => $next,
            'updated_at' => now(),
        ]);

        return sprintf('EIMS-%s-%s-%d-%06d', $category->group->code, $category->code, $year, $next);
    }
}
