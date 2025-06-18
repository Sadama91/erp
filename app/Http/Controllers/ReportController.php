<?php

namespace App\Http\Controllers;

use App\Models\Reporting;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }
    
    public function editData(Reporting $report)
{
    // Zorg dat je in App\Models\Reporting hebt:
    // protected $casts = [
    //   'views'             => 'array',
    //   'reporting_columns' => 'array',
    // ];

    // Nu zijn dit al arrays:
    $available_filters   = $report->available_filters         ?? [];
    $columns = $report->reporting_columns ?? [];

    return response()->json([
        'name'              => $report->name,
        'description'       => $report->description,
        'query'             => $report->query,
        // geef direct de array door, geen json_decode:
        'available_filters'             => $available_filters,
        'reporting_columns' => $columns,
    ]);
}

    
    public function filters(Reporting $report): JsonResponse
    {
        $available_filters = is_string($report->available_filters)
            ? json_decode($report->available_filters, true)
            : ($report->available_filters ?: []);
    
        $filterOptions = [];
    
        foreach ($available_filters as $viewKey => $cfg) {
            foreach ($cfg['filters'] ?? [] as $col => $conf) {
                if (($conf['type'] ?? '') === 'json_array'
                  && !empty($conf['table'])
                  && !empty($conf['value_column'])
                  && !empty($conf['label_column'])
                ) {
                    $filterOptions[$col] = DB::table($conf['table'])
                        ->select([
                            "{$conf['value_column']} AS value",
                            "{$conf['label_column']} AS label",
                        ])
                        ->orderBy($conf['label_column'])
                        ->get()
                        ->map(fn($row) => (array)$row)
                        ->all();
                }
            }
        }
    
        return response()->json([
            'views'         => $available_filters,
            'filterOptions' => $filterOptions,
        ]);
    }
    

    public function index()
    {
        $reports = Reporting::where('user_id', auth()->id())->get();
        return view('reports.index', compact('reports'));
    }

    public function generate(Request $request)
{
    $report = Reporting::findOrFail($request->reporting_id);

    // Decode JSON columns and view configurations
    $columns = is_string($report->reporting_columns)
        ? json_decode($report->reporting_columns, true)
        : ($report->reporting_columns ?: []);
    $views = is_string($report->views)
        ? json_decode($report->views, true)
        : ($report->views ?: []);

    // Determine selected preset and its defaults
    $selectedView = $request->query('view', array_key_first($views) ?? '');
    $viewConfig   = $views[$selectedView] ?? [];

    // Build filters and initial bindings
    $filters  = [];
    $bindings = [];
    foreach ($viewConfig['filters'] ?? [] as $col => $conf) {
        if (($conf['type'] ?? '') === 'date_range') {
            $start = $request->input('period1', $conf['default'][0] ?? null);
            $end   = $request->input('period2', $conf['default'][1] ?? null);
            if ($start && $end) {
                $filters['created_at'] = [$start, $end];
                $bindings['period1']   = $start;
                $bindings['period2']   = $end;
            } else {
                $bindings['period1'] = null;
                $bindings['period2'] = null;
            }
        }
        // other filter types can be handled here...
    }

    // Prepare SQL and collect its named placeholders
    $sql = trim($report->query);
    preg_match_all('/:([a-zA-Z0-9_]+)/', $sql, $matches);
    $paramNames = $matches[1] ?? [];

    // Ensure every placeholder has a binding (or null)
    $toBind = [];
    foreach ($paramNames as $name) {
        $toBind[$name] = $bindings[$name] ?? null;
    }

    // Log query and bindings
    Log::info('Executing report query', [
        'sql'      => $sql,
        'bindings' => $toBind,
    ]);

    // Execute via PDO
    $pdo  = DB::connection()->getPdo();
    $stmt = $pdo->prepare($sql);
    foreach ($toBind as $name => $value) {
        $stmt->bindValue(":{$name}", $value);
    }
    $stmt->execute();

    $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
    $data = collect($rows);

    return view('reports.result', [
        'data'         => $data,
        'report'       => $report,
        'columns'      => $columns,
        'views'        => $views,
        'selectedView' => $selectedView,
        'filters'      => $filters,
        'bindings'     => $toBind,
        'sql'          => $sql,
    ]);
}


public function store(Request $request)
{
    $this->validateReportInput($request);

    $report = Reporting::create([
        'user_id'           => auth()->id(),
        'name'              => $request->input('name'),
        'description'       => $request->input('description'),
        'query'             => trim($request->input('query')),
        'available_filters' => $request->input('available_filters'),
        'reporting_columns' => $request->input('reporting_columns'),
        'format'            => $request->input('format', 'default'),
    ]);

    return redirect()
        ->route('reports.index')
        ->with('success', "Rapport “{$report->name}” aangemaakt.");
}

public function update(Request $request, Reporting $report)
{
    // ownership check
    abort_if($report->user_id !== auth()->id(), 403);

    $this->validateReportInput($request);

    $report->update([
        'name'              => $request->input('name'),
        'description'       => $request->input('description'),
        'query'             => trim($request->input('query')),
        'available_filters' => $request->input('available_filters'),
        'reporting_columns' => $request->input('reporting_columns'),
        'format'            => $request->input('format', $report->format),
    ]);

    return redirect()
        ->route('reports.index')
        ->with('success', "Rapport “{$report->name}” bijgewerkt.");
}

/**
 * Valideer zowel voor store als update:
 *  - standaard Laravel‐checks op velden
 *  - controle dat named parameters in de SQL exact overeenkomen
 *    met de filter-keys in available_filters.
 */
protected function validateReportInput(Request $request): void
{
    $data = $request->validate([
        'name'              => 'required|string|max:255',
        'description'       => 'nullable|string',
        'query'             => 'required|string',
        'available_filters' => 'required|json',
        'reporting_columns' => 'required|json',
        'format'            => 'nullable|in:default,excel,pdf',
    ]);

   /* // 1) Vind alle :placeholders in de query
    preg_match_all('/:([a-zA-Z0-9_]+)/', $data['query'], $m);
    $params = array_unique($m[1] ?? []);

    // 2) Decode filters en haal alle filter-keys
    $filtersJson = json_decode($data['available_filters'], true) ?: [];
    $filterKeys = [];
    foreach ($filtersJson as $preset) {
        foreach (($preset['filters'] ?? []) as $key => $_) {
            $filterKeys[] = $key;
        }
    }
    $filterKeys = array_unique($filterKeys);

    // 3) Vergelijk
    $missingInFilters = array_diff($params, $filterKeys);
    $extraInFilters   = array_diff($filterKeys, $params);

    if ($missingInFilters || $extraInFilters) {
        $msgs = [];
        if ($missingInFilters) {
            $msgs[] = 'SQL uses parameters not defined as filters: '.implode(', ', $missingInFilters);
        }
        if ($extraInFilters) {
            $msgs[] = 'Filters defined not present in SQL: '.implode(', ', $extraInFilters);
        }
        throw ValidationException::withMessages([
            'available_filters' => [implode(' | ', $msgs)],
        ]);
    }*/
}
}
