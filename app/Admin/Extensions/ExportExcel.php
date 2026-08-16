<?php

namespace App\Admin\Extensions;


use Encore\Admin\Grid\Exporters\AbstractExporter;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;


use Maatwebsite\Excel\Facades\Excel;

abstract class ExportExcel extends AbstractExporter implements FromCollection, WithHeadings,WithCustomCsvSettings
{
    use Exportable;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var array
     */
    protected $headings = [];

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @return array
     */
    public function headings(): array
    {
        return $this->headings;
    }


    /**
     * {@inheritdoc}
     */
    public function export()
    {
        $this->download($this->fileName)->prepare(request())->send();
        exit;
    }

    public function download($fileName = null, $writerType = null, $headers = null)
    {
        return Excel::download($this, $this->fileName, null, $this->headings,['encoding' => 'UTF-8']);
    }

    public function store ( $filePath = null , $disk = null , $writerType = null , $diskOptions = [] )
    {
       return Excel::store($this, $this->fileName, 'local', \Maatwebsite\Excel\Excel::XLSX);
    }
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ";",
            'enclosure' => '',
        ];
    }

}
