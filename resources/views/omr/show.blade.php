<x-layout.omr>
    <x-slot:title>LDraw.org Official Model Repository - {{$set->name}}</x-slot>
    <x-slot:breadcrumbs>
      <x-breadcrumb-item class="active" item="Set Detail" />
    </x-slot>
    <div class="flex flex-col space-y-2">    
        <div class="rounded border text-xl font-bold bg-gray-200 p-2">{{$set->number}} - {{$set->name}}</div>
        <div class="grid grid-cols-12 gap-2">
            <div class="col-span-8">
                <img class = 'object-scale-down'
                src="{{version("images/omr/models/" . substr($set->mainModel()->filename(), 0, -4) . '.png')}}" alt="{{$set->number}} - {{$set->name}}" title="{{$set->number}} - {{$set->name}}">
            </div>
            <div class="flex flex-col col-span-4 space-y-2">
                <div class="rounded border text-lg font-bold bg-gray-200 p-2">Models</div>
                @foreach($set->models->sortBy('alt_model') as $model)
                    <div class="flex flex-col rounded border">
                        <div class="font-bold bg-gray-200 p-2">
                            {{$model->alt_model_name ?? 'Main Model'}}
                        </div>
                        <div class="p-2">
                            <span class="font-bold pr-2">Author:</span>{{$model->user->author_string}}
                        </div>
                        <div class="grid grid-cols-3 p-2">
                            <div>
                                <span class="font-bold">Missing Parts</span><br>
                                {{$model->missing_parts ? 'Yes' : 'No'}}
                            </div>    
                            <div>
                                <span class="font-bold">Missing Patterns</span><br>
                                {{$model->missing_patterns ? 'Yes' : 'No'}}
                            </div>    
                            <div>
                                <span class="font-bold">Missing Stickers</span><br>
                                {{$model->missing_stickers ? 'Yes' : 'No'}}
                            </div>    
                        </div>
                        <a class="rounded-lg border bg-blue-500 font-bold px-4 py-2 text-white m-2 w-fit" href="{{asset('library/omr/' . $model->filename())}}">Download</a>
                    </div>
                @endforeach    
            </div>
        </div>
    </div>
</x-layout.omr>