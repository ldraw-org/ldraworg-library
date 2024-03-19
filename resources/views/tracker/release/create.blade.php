<x-layout.tracker>
  <x-slot:title>Create Release Step 1</x-slot>
  <x-message.session-error />
  <form class="ui form" enctype="multipart/form-data" action="{{route('tracker.release.create2')}}" method="POST">
  @csrf
  @forelse ($parts as ['id' => $id, 'description' => $description, 'name' => $name, 'filename' => $filename, 'check' => $check, 'warnings' => $warnings])
  @if($loop->first)
  <table class="ui celled table">
  <thead>
    <tr>
      <th>Release</th>
      <th>Image</th>
      <th>Name</th>
      <th>Description</th>
      <th>Notes</th>
      <th>Edit</th>
    </tr>
  </thead>
  <tbody>
  @endif  
    <tr @class([
      'red' => !$check['can_release'],
      'green' => $check['can_release'],
    ])>
      <td>
        <div class="field">
          <div class="ui toggle checkbox">
            <input type="checkbox" name="ids[]" value="{{$id}}" @checked($check['can_release'])>
          </div>
        </div>  
      </td>
      <td><img class="ui image" src="{{asset('images/library/unofficial/' . substr($filename, 0, -4) . '_thumb.png')}}" alt='part thumb image' title="part_thumb" ></td>
      <td>{{$name}}</td>
      <td><a href="{{route('tracker.show', $id)}}">{{$description}}</a></td>
      <td>
        @foreach($check['errors'] as $error)
          {{$error}}<br>
        @endforeach 
        @foreach($warnings as $warning)
          {{$warning}}<br>
        @endforeach 
      </td>
      <td>{{--<a href="{{route('tracker.edit', $id)}}">Edit</a>--}}</td>
    </tr>      
  @if($loop->last)
  </tbody>  
  </table>
  <div class="field">
    <label for="ldrawfiles">Files for the base (ldraw) folder (Note: No validation will be done these files)</label>
    <div class="ui file input">
      <input name="ldrawfiles[]" type="file" multiple="multiple">
    </div>
  </div>
  <button class="ui button" type="submit">Submit</button>
  @endif
  @empty
  None
  @endforelse
  </form>
</x-layout.tracker>