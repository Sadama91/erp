<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserSetting;
use Illuminate\Support\Facades\Auth;

class UserSettingController extends Controller
{
    /**
     * Haal de instellingen op voor een bepaalde pagina.
     */
    public function getSettings(Request $request, string $page)
    {
        $settings = UserSetting::where('user_id', Auth::id())
            ->where('page', $page)
            ->first();

        return response()->json($settings?->settings ?? []);
    }

    /**
     * Sla de instellingen op of update ze.
     */
    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'page' => 'required|string',
            'settings' => 'required|array',
        ]);

        $userSetting = UserSetting::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'page' => $validated['page'],
            ],
            ['settings' => $validated['settings']]
        );

        return response()->json(['message' => 'Instellingen opgeslagen', 'data' => $userSetting]);
    }
}
