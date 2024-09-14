<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        FilamentColor::register([
            'red' => Color::Red,
            'yellow' => Color::Yellow,
            'blue' => Color::Blue,
        ]);

        Select::configureUsing(function (Select $select): void {
            $select
                ->optionsLimit(1000)
                ->native(false);
        });

        SelectFilter::configureUsing(function (SelectFilter $selectfilter): void {
            $selectfilter
                ->optionsLimit(1000)
                ->native(false);
        });

        Table::configureUsing(function (Table $table): void {
            $table
                ->emptyState(view('tables.empty', ['none' => 'None']))
                ->striped()
                ->paginated([10, 25, 50, 100])
                ->defaultPaginationPageOption(25);
        });
    }
}
