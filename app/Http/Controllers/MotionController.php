<?php

namespace App\Http\Controllers;

use App\Models\Meetings\Motion;

class MotionController extends AbstractCRUDController
{
    protected string $modelClass = Motion::class;
    protected $model = Motion::class;


}
