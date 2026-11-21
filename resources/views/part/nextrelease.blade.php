<x-layout.tracker>
    <x-slot:title>Parts In Next Release</x-slot>
    <div class="flex flex-col space-y-2">
        <livewire:tables.next-release-parts-table />
        <x-accordion id="officialMinorEdits">
            <x-slot name="header" class="text-md font-bold">
                Official Parts with Minor Edits (Changes to keywords, license, and/or user/real name)
            </x-slot>
            <livewire:tables.official-minor-edits />
        </x-accordion>
    </div>
</x-layout.tracker>
