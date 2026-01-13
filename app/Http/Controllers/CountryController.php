<?php

namespace App\Http\Controllers;

use App\Http\Requests\CountryRequest;
use App\Http\Resources\Collections\CountryCollection;
use App\Http\Resources\CountryResource;
use App\Models\Country;
use Orion\Concerns\DisablePagination;
use Orion\Http\Controllers\Controller;

class CountryController extends Controller
{
    use DisablePagination;

    protected $model = Country::class;

    protected $resource = CountryResource::class;

    protected $collectionResource = CountryCollection::class;

    protected $request = CountryRequest::class;

    public function limit(): int
    {
        return config('app.limit_pagination', 15);
    }

    public function maxLimit(): int
    {
        return config('app.max_pagination', 100);
    }

    public function searchableBy(): array
    {
        return ['country_code', 'country_name'];
    }

    public function includes(): array
    {
        return ['users']; // Si vous avez une relation avec les utilisateurs
    }

    public function sortableBy(): array
    {
        return ['id', 'country_code', 'country_name', 'created_at', 'updated_at'];
    }

    public function filterableBy(): array
    {
        return ['id', 'country_code', 'country_name'];
    }

    protected function afterStore($request, $country): void
    {
        \Log::info("Pays créé: {$country->id}");
    }

    protected function afterUpdate($request, $country): void
    {
        \Log::info("Pays mis à jour: {$country->id}");
    }

    protected function beforeDestroy($request, $country): void
    {
        \Log::info("Suppression du pays: {$country->id}");
    }
}
