<?php

namespace App\View\Components;

use Illuminate\View\Component;

class DropdownSelect extends Component
{
    public $options;
    public $selected;
    public $placeholder;
    public $name;

    public function __construct($options = [], $selected = null, $placeholder = 'Select an option', $name = 'dropdown')
    {
        $this->options = $options;
        $this->selected = $selected;
        $this->placeholder = $placeholder;
        $this->name = $name;
    }

    public function render()
    {
        return view('components.dropdown-select');
    }
}

