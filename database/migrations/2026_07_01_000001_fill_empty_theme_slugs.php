<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $themes = DB::table('themes')
            ->whereNull('slug')
            ->orWhere('slug', '')
            ->get();

        foreach ($themes as $theme) {
            $baseSlug = Str::slug($theme->name);
            $slug = $baseSlug;
            $counter = 2;

            while (DB::table('themes')
                ->where('store_id', $theme->store_id)
                ->where('slug', $slug)
                ->where('id', '!=', $theme->id)
                ->exists()
            ) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            DB::table('themes')
                ->where('id', $theme->id)
                ->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
    }
};
