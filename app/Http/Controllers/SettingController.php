<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{    // Haal de lijst van instellingen op
    public function index()
    {        
        // Haal de models op uit de app/Models directory
        $models = $this->getModels();
        $settings = Setting::all(); // Of gebruik een groepering op basis van categorie: ->groupBy('category')
        return view('settings', compact('settings','models'));
    }

    // Haal de activiteit logs op voor een specifieke instelling
    public function getActivityLogs($id)
    {
        $setting = Setting::findOrFail($id);
        $activityLogs = Activity::where('subject_type', Setting::class)
                                ->where('subject_id', $setting->id)
                                ->orderBy('created_at', 'desc')
                                ->get();

        return response()->json(['logs' => $activityLogs]);
    }
    // Nieuwe instelling toevoegen via AJAX
    public function store(Request $request)
    {
        $active = filter_var($request->active, FILTER_VALIDATE_BOOLEAN);

        // Valideer met de unieke combinatie van category en key
        try {
            $validatedData = $request->validate([
                'key' => [
                    'required',
                    'string',
                    Rule::unique('settings')->where(function ($query) use ($request) {
                        return $query->where('category', $request->category);
                    }),
                ],
                'value' => 'required|string',
                'category' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
        
            // Maak de instelling aan
            $setting = Setting::create([
                'key' => $request->key,
                'value' => $request->value, // Opslaan als een string
                'category' => $request->category,
                'active' => $active ?? true, // Zorg ervoor dat active een boolean is
            ]);
        
        // Log de activiteit
        activity()
            ->performedOn($setting)
            ->causedBy(auth()->user())
            ->log('Instelling toegevoegd');

        return response()->json(['success' => 'Instelling toegevoegd']);
    }

  // Werk een bestaande instelling bij
  public function update(Request $request, $id)
  {
     
    $active = filter_var($request->active, FILTER_VALIDATE_BOOLEAN);
    $request->validate([
        'key' => 'required|string',
        'value' => 'required|string',
        'category' => 'required|string',
    ]);

        $setting = Setting::findOrFail($id);
        $oldValue = $setting->value;


      // Werk de instelling bij
      $setting->update([
          'key' => $request->key,
          'value' => $request->value,
          'category' => $request->category,
          'active' => $active, // Zorg ervoor dat de instelling actief blijft
      ]);

      // Log de activiteit (inclusief oude en nieuwe waarden)
      activity()
          ->performedOn($setting)
          ->causedBy(auth()->user())
          ->withProperties([
              'old' => $oldValue,
              'new' => $request->value
          ])
          ->log('Instelling bijgewerkt');

      return response()->json(['success' => 'Instelling bijgewerkt']);
  }
    // Zet een instelling inactief (soft delete)
    public function activate($id)
    {
        
        $setting = Setting::findOrFail($id);
        $setting->update(['active' => true]);

        // Log de deactivering
        activity()
            ->performedOn($setting)
            ->causedBy(auth()->user())
            ->log('Instelling geactiveerd');

        return response()->json(['success' => 'Instelling geactiveerd']);
    }
    // Zet een instelling inactief (soft delete)
    public function deactivate($id)
    {
        $setting = Setting::findOrFail($id);
        $setting->update(['active' => false]);

        // Log de deactivering
        activity()
            ->performedOn($setting)
            ->causedBy(auth()->user())
            ->log('Instelling gedeactiveerd');

        return response()->json(['success' => 'Instelling gedeactiveerd']);
    }
  

    public function getSetting($category, $key)
    {
        $setting = Setting::where('category', $category)
                          ->where('key', $key)
                          ->first();

        // Decodeer de JSON-waarde van de instelling
        return $setting ? json_decode($setting->value, true) : null;
    }

        // Functie om models uit de map app/Models te halen
        private function getModels()
        {
            // Zoek naar alle bestanden in app/Models en filter op PHP-bestanden
            $files = File::allFiles(app_path('Models'));
    
            $models = [];
            foreach ($files as $file) {
                $modelName = pathinfo($file)['filename']; // Haal de bestandsnaam zonder extensie
                $models[] = $modelName; // Voeg de modelnaam toe aan de lijst
            }
    
            return $models;
        }
}
