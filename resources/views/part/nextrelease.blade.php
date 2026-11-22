<x-layout.tracker>
    <x-slot:title>Parts In Next Release</x-slot>
    <div class="flex flex-col space-y-2">
        <x-page-title>Parts In Next Release</x-page-title>
        <p>
            These are the parts that currently qualify for the next update. While the
            parts on this list will generally be released in the next update, some of them
            may be manually held back by the the Library Admin for various other reasons.
        </p>
        <livewire:tables.next-release-parts-table />
        <x-accordion id="officialMinorEdits">
            <x-slot name="header" class="text-md font-bold">
                Official Parts with Minor Edits (Changes to keywords, license, and/or user/real name)
            </x-slot>
            <livewire:tables.official-minor-edits />
        </x-accordion>
    </div>
</x-layout.tracker>
