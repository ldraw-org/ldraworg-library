<div>
    <x-fas-square @class([
        'inline w-5',
        'fill-lime-400' => $getRecord()->vote_type == \App\Enums\VoteType::AdminCertify || $getRecord()->vote_type == \App\Enums\VoteType::AdminFastTrack,
        'fill-green-500' => $getRecord()->vote_type == \App\Enums\VoteType::Certify,
        'fill-red-600' => $getRecord()->vote_type == \App\Enums\VoteType::Hold,

    ]) />
    <span>{{$getRecord()->vote_type->label()}}</span>
</div>
