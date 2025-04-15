<?php

namespace App\Providers;

use App\Enums\LibraryIcon;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Forms\Components\Select;
use Filament\Support\Facades\FilamentIcon;
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
            'green' => Color::rgb('rgb(' . Color::Green[400] . ')'),
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
                ->persistFiltersInSession()
                ->persistSearchInSession()
                ->persistSortInSession()
                ->striped()
                ->paginated([10, 25, 50, 100])
                ->defaultPaginationPageOption(25);
        });

        FilamentIcon::register([
            'pagination.first-button' => LibraryIcon::PageFirst->value,
            'pagination.last-button' => LibraryIcon::PageLast->value,
            'pagination.next-button' => LibraryIcon::PageNext->value,
            'tables::filters.query-builder.constraints.boolean' => LibraryIcon::BooleanConstraint->value,
            'tables::filters.query-builder.constraints.date' => LibraryIcon::DateSelect->value,
            'tables::filters.query-builder.constraints.relationship' => LibraryIcon::RelationshipConstraint->value,
            'tables::filters.query-builder.constraints.select' => LibraryIcon::Select->value,
            'tables::filters.query-builder.constraints.text' => LibraryIcon::TextConstraint->value,
        ]);
    }
}
