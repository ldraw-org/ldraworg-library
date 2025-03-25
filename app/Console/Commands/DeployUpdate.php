<?php

namespace App\Console\Commands;

use App\Enums\PartError;
use App\LDraw\Check\Checks\ValidType2Lines;
use App\LDraw\Check\Checks\ValidType3Lines;
use App\LDraw\Check\Checks\ValidType4Lines;
use App\LDraw\Check\Checks\ValidType5Lines;
use App\LDraw\PartManager;
use App\Models\LdrawColour;
use App\Models\Part\Part;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\Vector;
use MCordingley\LinearAlgebra\Matrix;
use MCordingley\LinearAlgebra\Vector as LinearAlgebraVector;

class DeployUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the app after update deployments';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $t = microtime(true);
        $ldraw_color = Cache::get('ldraw_color_codes');
        $total = microtime(true) - $t;
        $this->info($total);
        $t = microtime(true);
        $ldraw_color = LdrawColour::pluck('code')->all();
        $total = microtime(true) - $t;
        $this->info($total);

    }
}
