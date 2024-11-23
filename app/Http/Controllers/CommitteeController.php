<?php

namespace App\Http\Controllers;

use App\Models\Meetings\Committee;

class CommitteeController extends AbstractCRUDController
{

    protected string $modelClass = Committee::class;
    protected $model = Committee::class;


}
