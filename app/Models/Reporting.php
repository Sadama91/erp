<?PHP

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reporting extends Model
{
    protected $table = 'reporting';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'query',
        'available_filters',
        'reporting_columns',
        'views'
    ];

    protected $casts = [
        'available_filters' => 'array',
        'reporting_columns' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

