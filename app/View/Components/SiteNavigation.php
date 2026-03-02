<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SiteNavigation extends Component
{
    public array $menu_items = [];

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->menu_items = [
            ['name' => 'Home', 'url' => route('home')],
        ];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.site-navigation');
    }
}
