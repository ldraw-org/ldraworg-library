<?php

namespace App\Livewire\Tracker;

use App\Enums\License;
use App\Settings\LibrarySettings;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ConfirmCA extends Component
{
    public function updateLicense(LibrarySettings $settings)
    {
        $user = Auth::user();
        if ($user->license != License::CC_BY_4) {
            $user->license = License::from($settings->default_part_license);
        }
        $user->ca_confirm = true;
        $user->save();
        if (session('ca_route_redirect')) {
            return $this->redirectRoute(session('ca_route_redirect'));
        }
        return $this->redirectRoute('tracker.main');
    }

    #[Layout('components.layout.tracker')]
    public function render()
    {
        return view('livewire.tracker.confirm-c-a');
    }
}
