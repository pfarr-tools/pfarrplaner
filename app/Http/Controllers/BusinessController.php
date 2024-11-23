<?php

namespace App\Http\Controllers;

use App\Models\Meetings\Business;

class BusinessController extends AbstractCRUDController
{
    protected string $modelClass = Business::class;
    protected $model = Business::class;


}
