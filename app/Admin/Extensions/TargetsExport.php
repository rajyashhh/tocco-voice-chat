<?php

namespace App\Admin\Extensions;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class TargetsExport implements FromView
{
    public $targets;
    public $selectedColumns;

    public function __construct($targets, $selectedColumns)
    {
        $this->targets = $targets;
        $this->selectedColumns = $selectedColumns;
    }

    public function view(): View
    {
        return view('target_pdf', [
            'targets' => $this->targets,
            'selectedColumns' => $this->selectedColumns,
        ]);
    }
}
