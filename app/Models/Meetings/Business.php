<?php

namespace App\Models\Meetings;

use App\Models\AbstractModel;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Business extends AbstractModel
{
    use HasFactory;

    protected static string $prefix = 'top';
    protected static string $prefixPlural = 'tops';
    public static array $exceptRoutes = ['web' => ['show'], 'api' => ['show']];
    public static array $validationRules = [
        'title' => 'required|string|max:255',
        'body' => 'nullable|string',
        'minutes' => 'nullable|string',
        'public' => 'nullable|check',
        'service_id' => 'nullable|int|exists:services,id',
    ];

    public static $adminIcon = 'mdi mdi-note-text-outline';
    public static $adminGroup = 'Sitzungen';
    public static $adminTitle = 'TOPs';


    protected $fillable = ['id', 'title', 'body', 'minutes', 'public', 'service_id'];

    public function service() {
        return $this->belongsTo(Service::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->title ?? '';
    }
}
