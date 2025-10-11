<x-slot:title>
    {{$unofficial ? 'Unofficial' : 'Official'}} Part List
</x-slot>
<div>
    <div class="flex flex-col space-y-4">
        <div class="grid grid-cols-2 justify-stretch items-center">
            <div class="justify-self-start">
                <p class="text-2xl font-bold">{{$unofficial ? 'Unofficial' : 'Official'}} Part List</p>
            </div>
            @if ($unofficial)
                <div class="justify-self-end">
                    <p class="text-right">Server Time: {{date('Y-m-d H:i:s')}}</p>
                    <x-part.unofficial-part-count />
                </div>
            @endif    
        </div>
        {{ $this->table }}
    </div>
</div>
