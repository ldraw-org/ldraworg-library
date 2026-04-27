<?php

namespace App\Services\LDraw\Managers\Part;

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Jobs\UpdateRebrickable;
use App\Jobs\UpdateParentParts;
use App\Services\LDraw\Managers\StickerSheetManager;
use App\Services\LDraw\Render\LDView;
use App\Models\Part\Part;
use App\Models\Part\UnknownPartNumber;
use App\Services\LDraw\Managers\RebrickablePartManager;
use App\Services\LDraw\Rebrickable;
use App\Services\Parser\ParsedPartCollection;
use App\Services\Part\BasePartSync;
use App\Services\Part\ImageGenerator;
use App\Services\Part\SubpartSync;
use App\Services\Part\Validator;
use App\Settings\LibrarySettings;
use Illuminate\Support\Facades\Auth;

class PartManager
{
    public function __construct(
        public LDView $render,
        protected LibrarySettings $settings,
        protected Rebrickable $rebrickable,
        protected StickerSheetManager $stickerManager,
        protected RebrickablePartManager $rebrickablePartManager,
        protected SubpartSync $subpartSync,
        protected ImageGenerator $imageGenerator,
        protected Validator $validator,
        protected BasePartSync $basePartSync,
    ) {
    }

}
