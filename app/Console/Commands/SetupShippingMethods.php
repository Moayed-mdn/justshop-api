<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\ShippingMethodsSeeder;
use Database\Seeders\StoreAddressSettingsSeeder;

class SetupShippingMethods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:setup-shipping';

    /**
     * The console description of the console command.
     *
     * @var string
     */
    protected $description = 'Setup default shipping methods and address settings for all stores';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Setting up shipping methods and address settings...');
        $this->newLine();

        // Run address settings seeder
        $this->info('Creating store address settings...');
        $this->call('db:seed', ['--class' => StoreAddressSettingsSeeder::class]);
        
        $this->newLine();
        
        // Run shipping methods seeder
        $this->info('Creating shipping methods and zones...');
        $this->call('db:seed', ['--class' => ShippingMethodsSeeder::class]);
        
        $this->newLine();
        $this->info('✓ Shipping setup completed successfully!');
        $this->info('Your stores now have default shipping methods available.');
        
        return self::SUCCESS;
    }
}
