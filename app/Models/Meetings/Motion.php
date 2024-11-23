<?php

namespace App\Models\Meetings;

use App\Models\AbstractModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Motion extends AbstractModel
{
    use HasFactory;

    protected static string $prefix = 'antrag';
    protected static string $prefixPlural = 'antraege';
    public static array $exceptRoutes = ['web' => ['show'], 'api' => ['show']];
    public static array $validationRules = [
        'title' => 'required|string|max:255',
        'body' => 'nullable|string',
        'minutes' => 'nullable|string',
        'public' => 'nullable|check',
        'service_id' => 'nullable|int|exists:services,id',
    ];

    public static $adminIcon = 'mdi mdi-vote';
    public static $adminGroup = 'Sitzungen';


    protected $fillable = ['id', 'title', 'body', 'minutes', 'business_id', 'voted', 'aye', 'nay', 'abstention'];
}
