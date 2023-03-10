<?php

namespace App\Http\Traits;

use App\Models\Day;

trait DashboardTrait
{
    public function getSalesTotal()
    {
        return Day::whereNotNull('finish_day')->orderBy('created_at', 'DESC')->get();
    }
    public function getLastDay()
    {
        return Day::whereNotNull('finish_day')->orderBy('created_at', 'DESC')->first();
    }
}
