<?php

namespace App\Services;

use App\Models\Reporting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class ReportService
{
    /**
     * Voer de raw SQL uit die in $reporting->query staat en retourneer een Collection.
     *
     * @param  Reporting  $reporting
     * @return Collection
     *
     * @throws QueryException
     */
    public function generateReport(Reporting $reporting): Collection
    {
        // 1) Haal de complete SQL op
        $sql = $reporting->query;

        // 2) Voer de query uit
        $rows = DB::select($sql);

        // 3) Zet om naar collectie van arrays
        return collect($rows)->map(fn($r) => (array) $r);
    }
}
