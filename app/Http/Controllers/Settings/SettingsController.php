<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $general = Setting::where('group', 'Time')->get();
        return view('settings.settings', compact('general'));
    }
    public function general(Request $rq)
    {
        $general = Setting::where('group', 'Time')->get();
        foreach ($general as $conf) {
            $conf->value = is_null($rq[$conf->key]) ? 0 : $rq[$conf->key];
            $conf->save();
        }

        return AccionCorrecta('', '');
    }
}