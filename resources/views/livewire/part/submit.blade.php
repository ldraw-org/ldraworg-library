 <x-slot:title>
     Parts Tracker File Submit Form
</x-slot>
<x-slot:breadcrumbs>
    <x-breadcrumb-item class="active" item="Submit" />
</x-slot>    
<div>
    <div class="text-2xl font-bold">
        Parts Tracker File Submit Form
    </div>

    <p>
        Use this form to upload <span class="fold-bold">new</span> files to the 
        Parts Tracker and to update already-submitted <span class="fold-bold">unofficial</span> files.
    </p>
    <div>
        @if (count($this->part_errors) > 0)
            <x-message icon type="error">
                @foreach($this->part_errors as $error)
                    {{$error}}<br>
                @endforeach
            </x-message>
        @endif
    </div>
    <form wire:submit="create">
        {{ $this->form }}

        <x-filament::button type="submit">
            <x-filament::loading-indicator wire:loading class="h-5 w-5" />
            Submit
        </x-filament::button>
    </form>
    <x-filament::modal id="post-submit" width="5xl" :close-by-clicking-away="false" :close-button="false">
        <x-slot name="heading">
            Submit Successful
        </x-slot>
        <p>
            The following files passed validation checks and have been submitted to the Parts Tracker
        </p>
        <table class="border rounded-lg w-full">
            <thead class="border-b-2 border-b-black">
                <tr class="*:bg-gray-200 *:font-bold *:justify-self-start *:p-2">
                    <th>Image</th>
                    <th>Part</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($submitted_parts as $p)
                    <tr class="*:p-2">
                        <td>
                            <img class="ui centered image" src="{{$p['image']}}" alt='part thumb image' title="part_thumb">
                        </td>
                        <td>{{$p['filename']}}</td>
                        <td>
                            <a href="{{$p['route']}}">{{$p['description']}}</a>
                        </td>
                    </tr>
                @endforeach 
            </tbody>
        </table>
        <x-filament::button wire:click="postSubmit">
            Ok
        </x-filament::button>
    </x-filament::modal>
</div>