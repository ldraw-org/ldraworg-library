<?php

namespace App\Providers;

use App\Enums\LibraryIcon;
use Filament\Support\View\SupportIconAlias;
use Filament\Tables\View\TablesIconAlias;
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
            'green' => Color::Green,
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
                ->emptyState(view('filament.tables.empty', ['none' => 'None']))
                ->persistFiltersInSession()
                ->persistSearchInSession()
                ->persistSortInSession()
                ->striped()
                ->paginated([10, 25, 50, 100])
                ->defaultPaginationPageOption(25);
        });

        FilamentIcon::register([
            SupportIconAlias::PAGINATION_FIRST_BUTTON => LibraryIcon::PageFirst->value,
            SupportIconAlias::PAGINATION_LAST_BUTTON => LibraryIcon::PageLast->value,
            SupportIconAlias::PAGINATION_NEXT_BUTTON => LibraryIcon::PageNext->value,
            SupportIconAlias::PAGINATION_PREVIOUS_BUTTON => LibraryIcon::PagePrevious->value,
            TablesIconAlias::FILTERS_QUERY_BUILDER_CONSTRAINTS_BOOLEAN => LibraryIcon::BooleanConstraint->value,
            TablesIconAlias::FILTERS_QUERY_BUILDER_CONSTRAINTS_DATE => LibraryIcon::DateSelect->value,
            TablesIconAlias::FILTERS_QUERY_BUILDER_CONSTRAINTS_RELATIONSHIP => LibraryIcon::RelationshipConstraint->value,
            TablesIconAlias::FILTERS_QUERY_BUILDER_CONSTRAINTS_SELECT => LibraryIcon::Select->value,
            TablesIconAlias::FILTERS_QUERY_BUILDER_CONSTRAINTS_TEXT => LibraryIcon::TextConstraint->value,
            TablesIconAlias::COLUMNS_ICON_COLUMN_FALSE => LibraryIcon::TableFalse->value,
            TablesIconAlias::COLUMNS_ICON_COLUMN_TRUE => LibraryIcon::TableTrue->value,
            TablesIconAlias::HEADER_CELL_SORT_ASC_BUTTON => LibraryIcon::TableSortAsc->value,
            TablesIconAlias::HEADER_CELL_SORT_BUTTON => LibraryIcon::TableSortDesc->value,
            TablesIconAlias::HEADER_CELL_SORT_DESC_BUTTON => LibraryIcon::TableSortDesc->value,
            TablesIconAlias::ACTIONS_FILTER => LibraryIcon::TableFilter->value,
        ]);
    }
}
