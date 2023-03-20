<x-layout.main>
  <form class="ui form" action="{{route('tracker.doupdatemissing', $part)}}" method="post">
    @csrf
    @method('PUT')
    <input type="hidden" name="part_id" value="{{$part->id}}">
    <x-form.select name="new_part_id" :options="\App\Models\Part::unofficial()->orderBy('filename')->pluck('filename','id')->all()" selected="{{old('new_part_id') ?? ''}}" class="ui search dropdown"/>
    <div class="field">
      <button class="ui button" type="submit">Submit</button>
    </div>
  </form>
</x-layout.main>