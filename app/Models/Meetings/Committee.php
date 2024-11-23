<?php

namespace App\Models\Meetings;

use App\Models\AbstractModel;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Committee extends AbstractModel
{
    use HasFactory;

    protected static string $prefix = 'gremium';
    protected static string $prefixPlural = 'gremien';
    public static array $exceptRoutes = ['web' => ['show'], 'api' => ['show']];
    public static array $validationRules = [
        'name' => 'required|string|max:255',
        'code' => 'nullable|string|max:255',
        'has_public_meetings' => 'nullable|check',
        'has_private_meetings' => 'nullable|check',
        'cities.*' => 'nullable',
        'users.*' => 'nullable',
        'services.*' => 'nullable',
    ];

    public static $adminIcon = 'mdi mdi-account-group-outline';
    public static $adminGroup = 'Sitzungen';



    protected $fillable = [
        'id', 'name', 'code', 'has_public_meetings', 'has_private_meetings',
    ];

    public function cities()
    {
        return $this->belongsToMany(City::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('role');
    }

    public function services()
    {
        return $this->belongsToMany(Service::class);
    }
}
