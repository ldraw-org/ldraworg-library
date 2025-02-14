<?php

namespace App\LDraw\Check;

use App\Enums\License;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Models\User;
use App\Models\Part\Part;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\PartCategory;
use App\Settings\LibrarySettings;

class PartChecker
{
    public function __construct(
        protected LibrarySettings $settings
    ) {
    }

    public function check(ParsedPart $part, ?string $filename = null): ?array
    {
        $errors = $this->checkFile($part, $filename);
        $herrors = $this->checkHeader($part);
        return is_null($errors) ? $herrors : array_merge($errors, $herrors ?? []);
    }

    public function checkCanRelease(Part $part): ?array
    {
        $part->load('descendants', 'ancestors');
        $errors = [];
        if (!$part->isTexmap()) {
            $errors = $this->check(ParsedPart::fromPart($part)) ?? [];
        }
        if ($part->isUnofficial()) {
            $hascertparents = !is_null($part->official_part) ||
                $part->type->inPartsFolder() || $part->type == PartType::Helper ||
                $this->hasCertifiedParentInParts($part);
            if (!$hascertparents) {
                $errors[] = 'No certified parents in the parts directory';
            }
            if (!$this->hasAllSubpartsCertified($part)) {
                $errors[] = 'Has uncertified subfiles';
            }
            if (count($part->missing_parts) > 0) {
                $errors[] = 'Has missing part references';
            }
            if ($part->manual_hold_flag) {
                $errors[] = 'Manual hold back by admin';
            }
            if ($part->license !== License::CC_BY_4) {
                $errors[] = "Part License {$part->license->value} not authorized for library";
            }
        }
        $can_release = count($errors) == 0;
        return compact('can_release', 'errors');
    }

    public function hasCertifiedParentInParts(Part $part): bool
    {
        return $part->ancestors->whereIn('type', PartType::partsFolderTypes())->where('vote_sort', 1)->count() > 0;
    }

    public function hasAllSubpartsCertified(Part $part): bool
    {
        return $part->descendants->where('vote_sort', '!=', 1)->count() == 0;
    }

    public function checkFile(ParsedPart $part, ?string $filename = null): ?array
    {
        $errors = [];
        if (!is_null($part->name)) {
            $part->name = mb_strtolower($part->name);
            if (! $this->checkLibraryApprovedName($part->name)) {
                $errors[] = __('partcheck.name.invalidchars');
            } elseif (! $this->checkUnknownPartNumber($part->name)) {
                $errors[] = __('partcheck.name.xparts');
            }
            $n = basename(str_replace('\\', '/', $part->name));
            if (!is_null($filename) && $n !== mb_strtolower($filename)) {
                $errors[] = "Name: and filename do not match";
            }
        }
        $text = explode("\n", $part->body);

        foreach ($text as $index => $line) {
            if (! $this->validLine($line)) {
                $errors[] = __('partcheck.line.invalid', ['value' => $index + $part->header_length]);
            } elseif (! $this->checkLineAllowedBodyMeta($line)) {
                $errors[] = __('partcheck.line.invalidmeta', ['value' => $index + $part->header_length]);
            }
        }
        $selfref = in_array($part->name, $part->subparts['subparts'] ?? []);
        if ($selfref) {
            $errors[] = __('partcheck.selfreference');
        }

        return count($errors) > 0 ? $errors : null;
    }

    public function checkHeader(ParsedPart $part): ?array
    {
        // Ensure header required metas are present
        $errors = [];
        $missing = [
            'description' => !is_null($part->description),
            'name' => !is_null($part->name),
            'author' => !is_null($part->username) || !is_null($part->realname),
            'ldraw_org' => !is_null($part->type),
            'license' => !is_null($part->license),
        ];
        $exit = false;
        foreach ($missing as $meta => $status) {
            if ($status == false) {
                $errors[] = __('partcheck.missing', ['attribute' => $meta]);
                $exit = true;
            }
        }
        if ($exit) {
            return $errors;
        }

        $name = str_replace('\\', '/', $part->name);

        // Description Checks
        if (! $this->checkLibraryApprovedDescription($part->description)) {
            $errors[] = __('partcheck.description.invalidchars');
        }

        if (
            $part->descriptionCategory !== 'Moved' &&
            $part->descriptionCategory !== 'Sticker' &&
            $part->metaCategory !== 'Sticker Shortcut' &&
            $part->type->inPartsFolder() &&
            !$this->checkDescriptionForPatternText($name, $part->description)
        ) {
            $errors[] = __('partcheck.description.patternword');
        }

        // Note: Name: checks are done in the LDrawFile rule
        // Author checks
        if (! $this->checkAuthorInUsers($part->username ?? '', $part->realname ?? '')) {
            $errors[] = __('partcheck.author.registered', ['value' => $part->realname ?? $part->username]);
        }

        // !LDRAW_ORG Part type checks
        if (! $this->checkNameAndPartType($part->name, $part->type)) {
            $errors[] = __('partcheck.type.path', ['name' => $name, 'type' => $part->type->value]);
        }
        if ($part->type == PartType::Subpart && $part->description[0] != '~') {
            $errors[] = __('partcheck.type.subpartdesc');
        }

        //Check qualifiers
        if (!is_null($part->qual)) {
            switch ($part->qual) {
                case PartTypeQualifier::PhysicalColour:
                    $errors[] = __('partcheck.type.phycolor');
                    break;
                case PartTypeQualifier::Alias:
                    if (!$part->type->inPartsFolder()) {
                        $errors[] = __('partcheck.type.alias');
                    }
                    if ($part->description[0] != '=') {
                        $errors[] = __('partcheck.type.aliasdesc');
                    }
                    break;
                case PartTypeQualifier::FlexibleSection:
                    if ($part->type != PartType::Part) {
                        $errors[] = __('partcheck.type.flex');
                    }
                    if (! preg_match('#^[a-z0-9_-]+?k[a-z0-9]{2}(p[a-z0-9]{2,3})?\.dat#', $name, $matches)) {
                        $errors[] = __('partcheck.type.flexname');
                    }
                    break;
            }
        }
        // !LICENSE checks
        if (! $this->checkLibraryApprovedLicense($part->license)) {
            $errors[] = __('partcheck.license.approved');
        }
        // BFC CERTIFY CCW Check
        if (! $this->checkLibraryBFCCertify($part->bfcwinding)) {
            $errors[] = __('partcheck.bfc');
        }
        // Category Check
        $validCategory = false;
        if ($part->type->inPartsFolder()) {
            if (!empty($part->metaCategory)) {
                $validCategory = $this->checkCategory($part->metaCategory);
                $cat = $part->metaCategory;
            } else {
                $validCategory = $this->checkCategory($part->descriptionCategory);
                $cat = $part->descriptionCategory;
            }
            if (!$validCategory) {
                $errors[] = __('partcheck.category.invalid', ['value' => $cat]);
            } elseif ($cat == 'Moved' && ($part->description[0] != '~')) {
                $errors[] = __('partcheck.category.movedto');
            }
        }
        // Keyword Check
        if (
            $part->descriptionCategory !== 'Moved' &&
            $part->type->inPartsFolder() &&
            $part->descriptionCategory !== 'Moved' &&
            $part->descriptionCategory !== 'Sticker' &&
            $part->metaCategory !== 'Sticker Shortcut' &&
            !$this->checkPatternForSetKeyword($name, $part->keywords ?? [])
        ) {
            $errors[] = __('partcheck.keywords');
        }

        // Check History
        if (!is_null($part->history)) {
            $hcount = count($part->history);
            if ($hcount != mb_substr_count($part->rawText, '!HISTORY')) {
                $errors[] = __('partcheck.history.invalid');
            }
            foreach ($part->history as $hist) {
                if (is_null(User::fromAuthor($hist['user'])->first())) {
                    $errors[] = __('partcheck.history.author');
                }
            }
        }
        return count($errors) > 0 ? $errors : null;
    }

    public function validLine(string $line): bool
    {
        $line = trim(preg_replace('#\h{2,}#u', ' ', $line));
        if (empty($line)) {
            return true;
        }
        if (is_null(config('ldraw.patterns.line_type_' . $line[0]))) {
            return false;
        }

        return preg_match(config('ldraw.patterns.line_type_' . $line[0]), $line, $matches) > 0;
    }

    public function checkLibraryApprovedDescription(string $description): bool
    {
        return preg_match(config('ldraw.patterns.library_approved_description'), $description, $matches);
    }

    public function checkDescriptionForPatternText(string $name, string $description): bool
    {
        $isPattern = preg_match('#^[a-z0-9_-]+?p[a-z0-9]{2,3}\.dat$#i', $name, $matches);
        $hasPatternText = preg_match('#^.*?\sPattern(\s\((Obsolete|Needs Work|Hollow Stud|Blocked Hollow Stud|Solid Stud)\))?$#ui', $description, $matches);
        return !$isPattern || $hasPatternText;
    }

    public function checkLibraryApprovedName(string $name): bool
    {
        return preg_match(config('ldraw.patterns.library_approved_name'), $name, $matches);
    }

    public function checkNameAndPartType(string $name, ?PartType $type): bool
    {
        $name = str_replace('\\', '/', $name);
        // Automatic fail if no Name:, LDRAW_ORG line, or DAT file has TEXTURE type
        if (is_null($type) || $type->isImageFormat()) {
            return false;
        }

        // Construct the name implied by the part type
        $aname = str_replace(['p/', 'parts/'], '', $type->folder() . '/' . basename($name));

        return $name === $aname;
    }

    public function checkAuthorInUsers(string $username, string $realname): bool
    {
        return !is_null(User::fromAuthor($username, $realname)->first());
    }

    public function checkLibraryApprovedLicense(?License $license): bool
    {
        return !is_null($license);
    }

    public function checkLibraryBFCCertify(?string $bfc): bool
    {
        return $bfc === 'CCW';
    }

    public function checkCategory(string $category): bool
    {
        return !is_null(PartCategory::firstWhere('category', $category));
    }

    public function checkPatternForSetKeyword(string $name, array $keywords): bool
    {
        $isPatternOrSticker = preg_match('#^[a-z0-9_-]+?[pd][a-z0-9]{2,3}\.dat$#i', $name, $matches);
        if ($isPatternOrSticker) {
            if (count($keywords) === 0) {
                return false;
            }
            $setfound = false;
            foreach ($keywords as $word) {
                if (mb_strtolower(explode(' ', trim($word))[0]) == 'set' || mb_strtolower(explode(' ', trim($word))[0]) == 'cmf' || mb_strtolower($word) == 'build-a-minifigure') {
                    $setfound = true;
                    break;
                }
            }
            if (! $setfound) {
                return false;
            }
        }
        return true;
    }

    public function checkUnknownPartNumber(string $name): bool
    {
        return $name !== '' && $name[0] !== 'x';
    }

    public function checkLineAllowedBodyMeta(string $line): bool
    {
        $words = explode(' ', trim($line));
        return $words[0] !== '0' ||
            trim($line) === '0' ||
            ($words[0] === '0' && count($words) > 1 && in_array($words[1], $this->settings->allowed_body_metas, true));
    }
}
