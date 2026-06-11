<?php

namespace App\Console\Commands;

use Database\Seeders\Theme\RichThemeSeeder;
use Database\Seeders\Theme\StoreAssetsSeeder;
use Illuminate\Console\Command;

class SeedThemeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme:seed 
                            {--fresh : Clear existing theme data before seeding}
                            {--assets-only : Seed only store assets}
                            {--themes-only : Seed only themes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed theme system with rich fake data (themes, navigation, assets)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🎨 Seeding Theme System Data...');
        $this->newLine();

        if ($this->option('fresh')) {
            $this->clearThemeData();
        }

        $assetsOnly = $this->option('assets-only');
        $themesOnly = $this->option('themes-only');

        if ($assetsOnly) {
            $this->seedAssets();
        } elseif ($themesOnly) {
            $this->seedThemes();
        } else {
            $this->seedAssets();
            $this->seedThemes();
        }

        $this->newLine();
        $this->info('✅ Theme data seeding complete!');
        
        return Command::SUCCESS;
    }

    private function clearThemeData(): void
    {
        $this->warn('🗑️  Clearing existing theme data...');
        
        \DB::statement('SET CONSTRAINTS ALL DEFERRED');
        
        \DB::table('theme_blocks')->delete();
        \DB::table('theme_sections')->delete();
        \DB::table('themes')->delete();
        \DB::table('navigation_menu_items')->delete();
        \DB::table('navigation_menus')->delete();
        \DB::table('store_assets')->delete();
        
        \DB::table('stores')->update([
            'active_theme_id' => null,
            'logo_url' => null,
            'favicon_url' => null,
        ]);

        $this->info('   ✓ Theme data cleared');
        $this->newLine();
    }


    private function seedAssets(): void
    {
        $this->info('📦 Seeding Store Assets...');
        $this->call(StoreAssetsSeeder::class);
    }

    private function seedThemes(): void
    {
        $this->info('🎨 Seeding Rich Themes...');
        $this->call(RichThemeSeeder::class);
    }
}
