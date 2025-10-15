<?php

namespace App\Services\LDraw;

use App\Enums\EventType;
use App\Enums\PartCategory;
use App\Enums\VoteType;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use App\Models\User;
use App\Services\LDraw\Parse\Parser;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class LibraryImport
{
    // Current as of 2206
    public static $official_texture_authors = [
        'parts/textures/13710a.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/13710b.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/13710c.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/13710d.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/13710e.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/13710f.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/13710g.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/13710h.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/13710i.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/13710j.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/13710k.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/13710l.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/13710m.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/13710n.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/191764.png' => ['Steffen', '2002'],
        'parts/textures/191767.png' => ['Steffen', '2002'],
        'parts/textures/19201p01.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/19204p01.png' => ['Philippe Hurbain', '2001'],
        'parts/textures/27062p01.png' => ['Philippe Hurbain', '2001'],
        'parts/textures/27062p02.png' => ['Philippe Hurbain', '2001'],
        'parts/textures/36069ap01.png' => ['Philippe Hurbain', '2003'],
        'parts/textures/36069bp01.png' => ['Philippe Hurbain', '2001'],
        'parts/textures/39266p01.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/39266p02.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/39266p03.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/3960p0b.png' => ['Philippe Hurbain', '2201'],
        'parts/textures/4141502a.png' => ['Orion Pobursky', '2202'],
        'parts/textures/4141698a.png' => ['Orion Pobursky', '2202'],
        'parts/textures/47203p01.png' => ['Philippe Hurbain', '2205'],
        'parts/textures/47203p02.png' => ['Philippe Hurbain', '2205'],
        'parts/textures/47203p03.png' => ['Philippe Hurbain', '2203'],
        'parts/textures/47206p01.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/47206p02.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/6005049a.png' => ['Orion Pobursky', '2202'],
        'parts/textures/6022692a.png' => ['Orion Pobursky', '2202'],
        'parts/textures/6022692b.png' => ['Orion Pobursky', '2202'],
        'parts/textures/6022692c.png' => ['Orion Pobursky', '2202'],
        'parts/textures/60581p01.png' => ['Philippe Hurbain', '2205'],
        'parts/textures/6092p01pit1side1.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01pit1side2.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01pit1side3.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01pit1side4.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01pit2corner1.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01pit2corner2.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01pit2corner3.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01pit2side1.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01pit2side2.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01pit2side3.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01pit2side4.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01pit2side5.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01pit2side6.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01top.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01wall1.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01wall2.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01wall3.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01wall4.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01wall5.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01wall6.png' => ['Alex Taylor', '2203'],
        'parts/textures/6092p01wall7.png' => ['Alex Taylor', '2203'],
        'parts/textures/6115204a.png' => ['Marc Giraudet', '2202'],
        'parts/textures/6204380a.png' => ['Vincent Messenet', '2202'],
        'parts/textures/6299663d.png' => ['Evert-Jan Boer', '2003'],
        'parts/textures/6313371a.png' => ['Ulrich Röder', '2202'],
        'parts/textures/66645ap01.png' => ['Philippe Hurbain', '2001'],
        'parts/textures/66645bp01.png' => ['Philippe Hurbain', '2001'],
        'parts/textures/685p01.png' => ['Philippe Hurbain', '2003'],
        'parts/textures/685p02.png' => ['Philippe Hurbain', '2003'],
        'parts/textures/685p03.png' => ['Philippe Hurbain', '2003'],
        'parts/textures/685p05.png' => ['Philippe Hurbain', '2003'],
        'parts/textures/87079pxf.png' => ['Evert-Jan Boer', '2202'],
        'parts/textures/973p5aa.png' => ['Philippe Hurbain', '2205'],
        'parts/textures/973p5ab.png' => ['Philippe Hurbain', '2205'],
        'parts/textures/973paza.png' => ['Philippe Hurbain', '2205'],
        'parts/textures/973pazb.png' => ['Philippe Hurbain', '2205'],
        'parts/textures/98088p01.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/98088p02.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/98088p03.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/98088p04.png' => ['Philippe Hurbain', '2202'],
        'parts/textures/u9480.png' => ['Alex Taylor', '2202'],
        'parts/textures/u9481.png' => ['Alex Taylor', '2202'],
    ];

    public function parseEventFile(PartRelease $release, string $partFilename): void
    {
        $metafile = Parser::unixLineEndings(Storage::get("events/{$release->name}/unofficial/{$partFilename}.meta"));
        if (is_null($metafile)) {
            return;
        }

        if (Str::startsWith(basename($partFilename), 'x')) {
            $part = Part::official()->firstWhere('filename', pathinfo($partFilename, PATHINFO_DIRNAME) . '/'. substr(basename($partFilename), 1));
        } else {
            $part = Part::official()->firstWhere('filename', $partFilename);
        }


        if (is_null($part)) {
            dump($partFilename, pathinfo($partFilename, PATHINFO_DIRNAME) . '/'. substr(basename($partFilename), 1));
            return;
        };

        $unknown_user = User::firstWhere('name', 'Non-CA User');

        $admin_users = [
            'cwdee',
            'sbliss'
        ];
        $primary_admin_user_id = User::firstWhere('name', 'cwdee')->id;

        if ($release->created_at > new Carbon('2022-01-13 00:00:00')) {
            $admin_users[] = 'OrionP';
            $primary_admin_user_id = User::firstWhere('name', 'OrionP')->id;
        }

        $known_aliases = [
            'simlego' => 'Tore_Eriksson',
            'David Merryweather' => 'hazydavy',
            'Valemar' => 'rhsexton',
            'donsut67@aol.com' => 'technog',
            'donsut67' => 'technog',
            '2923' => 'bbroich',
            'spaceodysee' => 'Holly-Wood',
            'Holly' => 'Holly-Wood',
            'sjaacko' => 'Jaco',
            'DeanEarley' => 'DeannaEarley',
            'millennium359' => 'rhsexton',
        ];

        $date_ref = [
            '53401320810142620' => '2002-03-05 21:50:03',
            '46301924510211740' => '2002-07-06 06:06:02',
            '82214621023640' => '2002-03-06 14:36:01',
            '10521617910132890' => '2001-10-17 17:20:01',
            '4456320810142620' => '2002-04-28 21:08:03',
            '57441320810142620' => '2001-11-05 21:16:01',
            '57512113510241630' => '2004-04-07 16:46:01',
            '25311320810142620' => '2002-04-16 21:06:05',
            '44522212510231620' => '2011-03-16 03:20:02',
            '23141713510241630' => '2003-10-28 14:40:03',
            '44141713510241630' => '2003-10-28 14:40:03',
            '32352212510231620' => '2012-04-26 14:25:07'
        ];

        $date_pattern = '#^(?:\*\*\*\h+)?(?:At|On)\h+([a-zA-z]{3}\h+(?<mon>[a-zA-z]{3})\h+(?<day>[0-9]{1,2})\h+(?<hour>[0-9]{2})\:(?<min>[0-9]{2})\:(?<sec>[0-9]{2})\h+(?<year>[0-9]{4})|[0-9]{10,})#ium';
        $comment_pattern = '#Comments\:\n+(.*)$#ius';
        $submit_user_pattern = '#Submitted by\:\h+(.*?)(\s+proxy=.*?)?(?:\n|$)#ius';
        $reviewer_user_pattern = '#Reviewer\:\h+(.*?)\n#ius';
        $vote_pattern = '#Certification\:\s(.+?)\n#ius';
        $rename_pattern = '#part \'(.*)\' was renamed to \'(.*)\'\.#ius';
        $edit_pattern = '#a Parts Tracker Admin edited the header#ius';

        $events = explode(str_repeat('=', 70) . "\n", $metafile);

        $event_dates = [];
        foreach ($events as $event) {
            $event = trim($event);
            $data = [
                'part_id' => $part->id,
                'part_release_id' => $release->id,
            ];

            if ($event == '' || Str::startsWith($event, 'Previous reviews and updates')) {
                continue;
            }

            if (preg_match($date_pattern, $event, $matches)) {
                if (is_numeric($matches[1])) {
                    if (Arr::has($date_ref, $matches[1])) {
                        $data['created_at'] = new Carbon(Arr::get($date_ref, $matches[1]));
                    } elseif (count($event_dates) == 0) {
                        dump(array_slice(explode("\n", $metafile), 0, 10), $release->name, $partFilename, );
                    } else {
                        $data['created_at'] = $event_dates[array_key_last($event_dates)]->toMutable()->addSeconds(1);
                    }
                } else {
                    $date = "{$matches['year']}-{$matches['mon']}-{$matches['day']} {$matches['hour']}:{$matches['min']}:{$matches['sec']}";
                    $data['created_at'] = new Carbon($date);
                    $event_dates[] = $data['created_at']->toImmutable();
                }
            } else {
                dump('Date', $event, $matches, $release->name, $partFilename);
            }

            if (preg_match($comment_pattern, $event, $matches)) {
                $comment = Parser::fixEncoding(trim($matches[1]));
                if ($comment != '') {
                    $data['comment'] = $comment;
                }
            }

            if (preg_match($edit_pattern, $event, $matches)) {
                $data['event_type'] = EventType::HeaderEdit;
                $data['user_id'] = $primary_admin_user_id;
            } elseif (preg_match($rename_pattern, $event, $matches)) {
                $data['event_type'] = EventType::Rename;
                $data['user_id'] = $primary_admin_user_id;
                $data['moved_from_filename'] = $matches[1];
                $data['moved_to_filename'] = $matches[2];
            } elseif (mb_strpos($event, 'the file was initially submitted.') !== false
                || mb_strpos($event, 'a new version of the file was submitted.') !== false
            ) {
                $data['event_type'] = EventType::Submit;
                $data['initial_submit'] = mb_strpos($event, 'the file was initially submitted') !== false;
                if (preg_match($submit_user_pattern, $event, $matches)) {
                    $uname = preg_replace('#.*(\t.*)#iu', '', $matches[1]);
                    $uname = Arr::get($known_aliases, $uname) ?? $uname;
                    $user = User::firstWhere('name', $uname) ?? $unknown_user;
                    $data['user_id'] = $user->id;
                } else {
                    if ($part->category == PartCategory::Moved) {
                        $data['user_id'] = $part->subparts->first()->user->id;
                        //dump('Moved to', $event, $part->subparts->first()->user->name);
                    } else {
                        $data['user_id'] = $part->user->id;
                    }

                }
            } elseif (preg_match($reviewer_user_pattern, $event, $matches)) {
                $uname = Arr::get($known_aliases, $matches[1]) ?? $matches[1];
                $user = User::firstWhere('name', $uname) ?? $unknown_user;
                $data['user_id'] = $user->id;
                $data['event_type'] = EventType::Review;
                if (preg_match($vote_pattern, $event, $matches)) {
                    $vote = trim($matches[1]);
                    $data['vote_type'] = match($vote) {
                        'certify' => VoteType::Certify,
                        'hold' => VoteType::Hold,
                        'fasttrack' => VoteType::AdminFastTrack,
                        default => null,
                    };
                    if ($data['vote_type'] == VoteType::Certify && in_array($user->name, $admin_users)) {
                        $data['vote_type'] = VoteType::AdminReview;
                    } elseif (is_null($data['vote_type']) && array_key_exists('comment', $data)) {
                        $data['event_type'] = EventType::Comment;
                    }
                }
            }

            $part->events()->create($data);
        }
        $part->events()->create([
            'created_at' => $release->created_at,
            'event_type' => EventType::Release,
            'user_id' => $primary_admin_user_id,
            'part_release_id' => $release->id,
            'comment' => "Release {$release->name}",
        ]);
    }
}
