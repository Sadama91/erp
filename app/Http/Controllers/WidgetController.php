<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserSetting;
use App\Services\WidgetConfig;

class WidgetController extends Controller
{
    public function updateSettings(Request $request)
    {
        $userId = auth()->id();
        $userSetting = UserSetting::where('user_id', $userId)
                                  ->where('page', 'homepage')
                                  ->first();

        $updatedSettings = $request->input('widgets');

        if (!$userSetting) {
            $userSetting = new UserSetting();
            $userSetting->user_id = $userId;
            $userSetting->page = 'home';
        }
        dd($userId,$userSettings);
        $userSetting->settings = json_encode(['widgets' => $updatedSettings]);
        $userSetting->save();

        return response()->json(['success' => true]);
    }
}
