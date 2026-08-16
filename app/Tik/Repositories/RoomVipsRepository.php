<?php

namespace App\Tik\Repositories;

use Modules\Vip\Entities\Vip;
use App\Tik\Repositories\AbstractRepository;
use Illuminate\Database\Eloquent\Model;
use Modules\Reals\Entities\Real;

class RoomVipsRepository extends AbstractRepository
{

    public function __construct()
    {
        $model = new Vip();
        parent::__construct($model);

        if (!$this->model instanceof Vip) return;
    }

    public function all($perPage, $Page)
    {
        $Vips = $this->model->where('type',4)->orderBy('exp');
     
        return $Vips->paginate($perPage, ['*'], 'page', $Page);
    }

  


    public function find($id)
    {
      return  $this->model->query()->find($id);
    }

    public function search($input)
    {
        $query = $this->model->query();

        $query->where('name_ar', 'like', '%' . trim($input) . '%')
        ->orWhere('name_en', 'like', '%' . trim($input) . '%');
        
        
        $result = $query->get(); 
        
        return $result ;
     
    }
   
    public function delete($Vip)
    {
        $Vip->delete();
        return true;
    }

    public function store($data)
    {
        return  $this->model->create($data);
     
    }
}
