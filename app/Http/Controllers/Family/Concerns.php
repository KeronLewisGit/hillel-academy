<?php

namespace App\Http\Controllers\Family;

use App\Models\Application;
use App\Models\Family;
use Illuminate\Http\Request;

/** Every family route goes through own(): another family's application is a 404, not a 403. */
trait Concerns
{
    protected function family(Request $request): Family
    {
        $family = $request->user('family');
        abort_unless($family instanceof Family, 401);

        return $family;
    }

    protected function own(Request $request, Application $application): Application
    {
        abort_unless($application->family_id === $this->family($request)->id, 404);

        return $application;
    }
}
