<?php

namespace App\Http\Controllers;

use App\Http\Resources\CountryResource;
use App\Models\User;
use Orion\Http\Controllers\RelationController;

class UserCountryController extends RelationController
{
    protected $model = User::class;

    protected $relation = 'country';

    protected $resource = CountryResource::class;
}
