<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SiteLayout extends Component
{
    public string $title;

    /**
     * Create a new component instance.
     */
    public function __construct(?string $title)
    {
        if ($title) {
            $this->title = $title.' | StockTrader';
        } else {
            $this->title = 'StockTrader';
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.site-layout');
    }
}
