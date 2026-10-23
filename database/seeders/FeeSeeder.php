<?php

namespace Database\Seeders;

use App\Models\FeeBand;
use Illuminate\Database\Seeder;

/** The published tuition table. Only seeds an empty table, so office edits survive. */
class FeeSeeder extends Seeder
{
    public function run(): void
    {
        if (FeeBand::query()->exists()) {
            return;
        }
        foreach ((array) config('hillel.fees') as $i => $row) {
            FeeBand::create($row + ['sort_order' => $i]);
        }
    }
}
