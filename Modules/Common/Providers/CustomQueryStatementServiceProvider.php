<?php

namespace Modules\Common\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Modules\Common\Helpers\Helper;

class CustomQueryStatementServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Builder::macro('whereLikeRaw', function ($attributes, string $searchTerm, $prefix = true, $suffix = true) {
            $searchTerm = Helper::checkSpecialCharacter($searchTerm);
            $prefix && $searchTerm = '%' . $searchTerm;
            $suffix && $searchTerm = $searchTerm . '%';

            $this->whereRaw("UPPER(" . $attributes . ") LIKE UPPER ('{$searchTerm}')");

            return $this;
        });
        Builder::macro('orWhereLikeRaw', function ($attributes, string $searchTerm, $prefix = true, $suffix = true) {
            $searchTerm = Helper::checkSpecialCharacter($searchTerm);
            $prefix && $searchTerm = '%' . $searchTerm;
            $suffix && $searchTerm = $searchTerm . '%';

            $this->orWhereRaw("UPPER(" . $attributes . ") LIKE UPPER ('{$searchTerm}')");

            return $this;
        });
    }

    public function register()
    {

    }
}
