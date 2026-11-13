<?php

namespace App\Console\Commands;

use App\Models\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * First-run setup for a new outlet: creates the store and its owner login.
 *
 *   php artisan bizbite:install                       (interactive)
 *   php artisan bizbite:install --store="Apna Dhaba" --email=owner@x.in --password=...
 *   php artisan bizbite:install --demo                (demo menu + demo logins, NOT for live outlets)
 *
 * --if-empty makes it a no-op when users already exist (safe in a container
 * entrypoint that runs on every boot).
 */
final class InstallCommand extends Command
{
    protected $signature = 'bizbite:install
        {--store= : Trade name printed on bills}
        {--name= : Owner full name}
        {--email= : Owner login email}
        {--password= : Owner password (min 8 chars, letters + numbers)}
        {--demo : Load the demo restaurant, menu and demo logins instead}
        {--if-empty : Do nothing if any user already exists}';

    protected $description = 'Create the store and owner account for a new BizBite installation';

    public function handle(): int
    {
        if ($this->option('if-empty') && User::query()->withoutGlobalScopes()->exists()) {
            $this->info('BizBite is already set up — nothing to do.');

            return self::SUCCESS;
        }

        if ($this->option('demo')) {
            $this->warn('Loading DEMO data with demo passwords. Never use this for a live outlet.');
            $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

            return self::SUCCESS;
        }

        $data = [
            'store' => $this->option('store') ?? $this->ask('Restaurant / trade name'),
            'name' => $this->option('name') ?? $this->ask('Owner name', 'Owner'),
            'email' => $this->option('email') ?? $this->ask('Owner login email'),
            'password' => $this->option('password') ?? $this->secret('Owner password (min 8, letters + numbers)'),
        ];

        $validator = Validator::make($data, [
            'store' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:'.(new User)->getTable().',email'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($data): void {
            $store = Store::query()->create([
                'name' => $data['store'],
                'currency' => 'INR',
                'default_gst_rate' => 5,
                'is_gst_enabled' => false,
                'is_active' => true,
            ]);

            $owner = User::query()->withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
                'role' => UserRole::Admin,
                'is_active' => true,
            ]);

            $store->forceFill(['owner_user_id' => $owner->id])->save();
        });

        $this->info("✓ Store \"{$data['store']}\" created. Sign in as {$data['email']}.");
        $this->line('  Next: Admin → Store & billing (GSTIN, FSSAI, UPI ID), then add your menu and cashiers.');

        return self::SUCCESS;
    }
}
