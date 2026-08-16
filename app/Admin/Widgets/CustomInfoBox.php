<?php

namespace App\Admin\Widgets;

use Encore\Admin\Widgets\Widget;

class CustomInfoBox extends Widget
{
    protected $view = 'admin.widgets.custom-info-box'; 

    protected $data = [];

    /**
     * CustomInfoBox constructor.
     *
     * @param string $name
     * @param string $icon
     * @param string $color
     * @param string $info
     * @param string $iconSize
     */
    public function __construct($name, $icon, $color, $info = 0, $iconSize = '20px')
    {
        $this->data = [
            'name' => $name,
            'icon' => $icon,
            'color' => $color,
            'info' => $info,
            'iconSize' => $iconSize,
        ];

        $this->class("small-box bg-$color");
    }

    /**
     * Render the widget.
     *
     * @return string
     */
    public function render()
    {
        $variables = array_merge($this->data, ['attributes' => $this->formatAttributes()]);

        return view($this->view, $variables)->render();
    }
}
