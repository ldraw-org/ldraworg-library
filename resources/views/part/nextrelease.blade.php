<x-layout.tracker>
    <x-slot:title>Parts In Next Release</x-slot>
    <x-slot:breadcrumbs>
        <x-breadcrumb-item class="active" item="Next Release" />
    </x-slot>
    <div class="text-xl font-bold">
        Parts In Next Release
    </div>  
    <p>
        These are the parts that currently qualify for the next update. While the
        parts on this list will generally be released in the next update, some of them 
        may be manually held back by the the Library Admin for various other reasons.
    </p>
    <p>  
        <livewire:tables.next-release-parts-table />
    </p>
    <p>
        <x-accordion id="officialMinorEdits">
            <x-slot name="header" class="text-md font-bold">
                Official Parts with Minor Edits (Changes to keywords, license, and/or user/real name)
            </x-slot>
            <livewire:tables.official-minor-edits />
        </x-accordion>
    </p>
</x-layout.tracker>