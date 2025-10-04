<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Menu extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $rol,
        public ?array $items,
    )
    {
        $this->setMenuItems();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.menu');
    }

    protected function setMenuItems(): void
    {
        switch ($this->rol) {
            case 'supervisor':
                $this->items = [
                    [
                        'title' => 'Pacientes',
                        'link' => '#',
                    ],
                    [
                        'title' => 'Administración de usuarios',
                        'link' => route('dashboard.usuarios.index'),
                    ],
                ];
                break;
        }
    }
}
