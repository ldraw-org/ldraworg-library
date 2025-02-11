<div>
    @foreach ($getRecord()->items as $item)
        <ul>
            <li>{{$item->item}} - {{$item->votes->count()}}</li>
        </ul>
    @endforeach
</div>
