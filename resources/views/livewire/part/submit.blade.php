 <x-slot:title>
     Parts Tracker File Submit Form
</x-slot>
<div>
    <div class="text-2xl font-bold">
        Parts Tracker File Submit Form
    </div>
    <div>
        @forelse($this->part_errors as $part => $error_list)
            <x-message icon type="error">
                <x-slot:header>{{ $part }}</x-slot>
                <ul>
                    @foreach($error_list as $error)
                        <li>
                            @if (!is_null($error->text))
                                <x-accordion id="partError{{$loop->iteration}}">
                                    <x-slot name="header">
                                        <div>{{$error->message()}}</div>
                                    </x-slot>
                                    <div class="px-4 text-black">
                                        Line text: {{ $error->text }}
                                    </div>
                                </x-accordion>
                            @else
                                {{ $error->message() }}
                            @endif
                        </li>
                    @endforeach
                </ul>
           </x-message>
        @empty
            {{-- Do nothing --}}
        @endforelse
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
        <table class="border border-gray-200 rounded-lg w-full">
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