<?php

namespace App\Console\Commands;

use App\Models\Family;
use Illuminate\Console\Command;

/** Prints a fresh single-use family-portal link (support calls, demos without email). */
class FamilyLink extends Command
{
    protected $signature = 'app:family-link {email}';

    protected $description = 'Print a single-use sign-in link for a family portal account';

    public function handle(): int
    {
        $family = Family::query()->where('email', strtolower((string) $this->argument('email')))->first();
        if (! $family) {
            $this->error('No family account with that email.');

            return self::FAILURE;
        }

        $this->line($family->loginUrl());
        $this->comment('Works once, for '.Family::LINK_MINUTES.' minutes.');

        return self::SUCCESS;
    }
}
