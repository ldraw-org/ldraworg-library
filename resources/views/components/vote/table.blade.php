@props(['votes'])
@if ($votes->count() ?? false)
  <div class="w-fit overflow-hidden shadow ring-1 ring-gray-300 ring-opacity-5 sm:rounded-lg">
    <table class="divide-y divide-gray-300">
      <thead class="bg-gray-50">
        <tr>
          <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">User</th>
          <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Vote</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white">
      @foreach ($votes as $vote)
          <tr wire:key="{{$vote->user_id}}-{{$vote->vote_type->value}}">
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ $vote->user->name }}</td>
            <td @class([
                  'whitespace-nowrap px-3 py-4 text-sm',
                  'bg-green-100' => $vote->vote_type == \App\Enums\VoteType::Certify,
                  'bg-red-300' => $vote->vote_type == \App\Enums\VoteType::Hold,
                  'bg-lime-200' => $vote->vote_type == \App\Enums\VoteType::AdminReview || $vote->vote_type == \App\Enums\VoteType::AdminFastTrack,
                  ])>{{ $vote->vote_type->label() }}</td>
          </tr>
      @endforeach
      </tbody>
    </table>
  </div>
@else
<p>
    None
</p>
@endif
