<?php

namespace App\Console\Commands;

use App\Events\PartRenamed;
use App\LDraw\PartManager;
use App\Models\Part\Part;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

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
        $files = 'parts/6142275g.dat,parts/3817cpde.dat,parts/003238b.dat,parts/u9167.dat,parts/20460bp02.dat,parts/20460bp05.dat,parts/31622.dat,parts/4790.dat,parts/3816cps0.dat,parts/168135b.dat,parts/20461bp00.dat,parts/20460bp0c.dat,parts/20461bp01.dat,parts/3815bphf.dat,parts/3817cp71.dat,parts/973psq.dat,parts/92912.dat,parts/3816cpbg.dat,parts/973py7.dat,parts/3815bp72.dat,parts/168135a.dat,parts/u9163.dat,parts/s9.dat,parts/3817cpc97.dat,parts/3816cpc6a.dat,parts/3816cpdd1.dat,parts/3817cpb8.dat,parts/3816cp61.dat,parts/003439a.dat,parts/3817cpy2.dat,parts/160205d.dat,parts/3815bps3.dat,parts/168135c.dat,parts/4616b.dat,parts/3816cpw2.dat,parts/165355d.dat,parts/86037b.dat,parts/u9107p05c03.dat,parts/3817cpx2.dat,parts/2544.dat,parts/86037a.dat,parts/50899.dat,parts/3816cpy1.dat,parts/3817cpc67.dat,parts/3817cpsk.dat,parts/20460bpx1.dat,parts/u588p01c04.dat,parts/3817cpd6e.dat,parts/160205a.dat,parts/20461bp07.dat,parts/3817cpx1.dat,parts/168135e.dat,parts/165355c.dat,parts/20461bpx0.dat,parts/u9151p01c03.dat,parts/165385a.dat,parts/50861.dat,parts/3816cphf.dat,parts/20460bp04.dat,parts/2384p06.dat,parts/4523.dat,parts/20461bp03.dat,parts/88618p01.dat,parts/3816cpu1.dat,parts/6415918u.dat,parts/44817.dat,parts/3816cpc97.dat,parts/3817cp6f.dat,parts/3816cph0.dat,parts/20460bp07.dat,parts/u588p02c04.dat,parts/3815bpy1.dat,parts/3816cpsk.dat,parts/20461bp02.dat,parts/4536.dat,parts/163555c.dat,parts/12893.dat,parts/3817cp70.dat,parts/4529.dat,parts/190295d.dat,parts/3815bpaz.dat,parts/164325cc01.dat,parts/3816cpx1.dat,parts/3817cpaz.dat,parts/32028.dat,parts/3815bps6.dat,parts/u9151p01c04.dat,parts/3817cp4f.dat,parts/004315a.dat,parts/20461bp0c.dat,parts/2384p05.dat,parts/168135d.dat,parts/u9150p02c03.dat,parts/20461bpckc.dat,parts/u9107p01c03.dat,parts/20461bp0a.dat,parts/71405b.dat,parts/3816cpw3.dat,parts/3890.dat,parts/3816cps3.dat,parts/160205b.dat,parts/20460bpd87.dat,parts/3816cpq0.dat,parts/20460bpd92.dat,parts/s2.dat,parts/92244p13.dat,parts/u9151p02c03.dat,parts/4622303t.dat,parts/3815bpy0.dat,parts/3817cpq1.dat,parts/3816cpx2.dat,parts/3816cps1.dat,parts/2384p02.dat,parts/168135g.dat,parts/6942.dat,parts/u9107p04c03.dat,parts/s7.dat,parts/003238c.dat,parts/3816cps6.dat,parts/43373.dat,parts/581c02.dat,parts/3817cpc4b.dat,parts/20461bpd87.dat,parts/564c01.dat,parts/3816cp60.dat,parts/3815bpy2.dat,parts/4506.dat,parts/3816cphb.dat,parts/88618c01.dat,parts/20460bp0a.dat,parts/20460bpx0.dat,parts/3816cp40.dat,parts/2384p01.dat,parts/20460bp06.dat,parts/3817cpbg.dat,parts/20460bp01.dat,parts/3817cpbd.dat,parts/s/3069p06b.dat,parts/s/3069p06a.dat,parts/s/79768s01.dat,parts/s/79387s02.dat,parts/s/79768s02.dat,parts/s/2867s05.dat,parts/s/3069p06c.dat,parts/u9107p02c02.dat,parts/3628.dat,parts/165385b.dat,parts/190736a.dat,parts/4622303r.dat,parts/20461bp06.dat,parts/98393e.dat,parts/45749.dat,parts/3816cpy2.dat,parts/20460bp08.dat,parts/72602.dat,parts/190775.dat,parts/3817cpb9.dat,parts/30154.dat,parts/6142617i.dat,parts/20461bpx1.dat,parts/3816cpd6e.dat,parts/30133.dat,parts/98393g.dat,parts/3039dy1.dat,parts/88285.dat,parts/3817cphb.dat,parts/37822.dat,parts/3816cpc67.dat,parts/3816cpde.dat,parts/3816cpb9.dat,parts/51378.dat,parts/71472.dat,parts/s8.dat,parts/2470.dat,parts/164325c.dat,parts/165355a.dat,parts/3816cps5.dat,parts/581c01.dat,parts/564c02.dat,parts/3817cpw2.dat,parts/3816cpbd.dat,parts/u9150p01c02.dat,parts/3817cp60.dat,parts/3816cp41.dat,parts/84637bp07.dat,parts/518.dat,parts/2384p03.dat,parts/3816cpb8.dat,parts/20461bp09.dat,parts/3815bpdd1.dat,parts/u9107p04c02.dat,parts/98393b.dat,parts/3817cpc6a.dat,parts/33008.dat,parts/3816cpc4b.dat,parts/6797.dat,parts/20461bp05.dat,parts/3817cpm1.dat,parts/20461bp04.dat,parts/164325b.dat,parts/163155a.dat,parts/20460bp00.dat,parts/3817cpw1.dat,parts/47297.dat,parts/u9482c01.dat,parts/2384p07.dat,parts/004751b.dat,parts/30166.dat,parts/20461bp08.dat,parts/3816cpm0.dat,parts/4341.dat,parts/30377.dat,parts/973pf9.dat,parts/30213d01.dat,parts/3817cps0.dat,parts/s10.dat,parts/3816cpcbd.dat,parts/3817cpw3.dat,parts/3817cpdd1.dat,parts/4761.dat,parts/968.dat,parts/92908.dat,parts/969.dat,parts/3816cp62.dat,parts/3817cps6.dat,parts/79768.dat,parts/20461bpd92.dat,parts/3817cpx0.dat,parts/165375a.dat,parts/29117a.dat,parts/3816cpq1.dat,parts/3816cp8a.dat,parts/3817cps1.dat,parts/6342869bc01.dat,parts/2384p04.dat,parts/3816cp70.dat,parts/20460bp03.dat,parts/u9150p02c04.dat,parts/164325d.dat,parts/3816cpw1.dat,parts/190735b.dat,parts/20460bp09.dat,parts/6007019a.dat,parts/u588p01c05.dat,parts/165385c.dat,parts/92910.dat,parts/191915k.dat,parts/3817cpy0.dat,parts/98393c.dat,parts/3816cpm1.dat,parts/3817cpm0.dat,parts/3816cpaz.dat,parts/168135f.dat,parts/2384p08.dat,parts/6526.dat,parts/50922.dat,parts/3816cp71.dat,parts/s1.dat,parts/190735a.dat,parts/3816cpx0.dat,parts/20461bp0e.dat,parts/21019bp03.dat,parts/3817cp42.dat,parts/3817cph0.dat';
        $f = explode(',', $files);
        $parts = Part::official()
            ->doesntHave('unofficial_part')
            ->whereIn('filename', $f)
            ->each(function (Part $part) {
                $part->has_minor_edit = true;
                $part->save();
            });
    }
}
