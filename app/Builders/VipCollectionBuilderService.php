<?php

namespace App\Builders;

use Modules\Vip\Entities\Vip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VipCollectionBuilderService
{
    protected bool $useCache;
    protected Collection|Builder $query;

    public function __call($method, $arguments)
    {
        $result = $this->query->{$method}(...$arguments);

        if ($result instanceof Builder || $result instanceof Collection) {
            $this->query = $result;
            return $this;
        }

        return $result;
    }

    public function __construct()
    {
        $this->useCache = Vip::$useCache;

        $this->query = $this->useCache ? Vip::getCached() : Vip::query();
    }

    public function where($key, $operator = null, $value = null): static
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->query = $this->query->where($k, $v);
            }
        } else {
            if (func_num_args() === 2) {
                $value = $operator;
                $operator = '=';
            }

            $this->query = $this->query->where($key, $operator, $value);
        }

        return $this;
    }


    public function whereIn($column, $values): static
    {
        $this->query = $this->query->whereIn($column, $values);

        return $this;
    }

    public function orderBy($column,string $direction = 'asc'): static
    {
        $this->query =
            $this->useCache ?
                ($direction == 'asc' ? $this->query->sortBy($column) : $this->query->sortByDesc($column))
                : $this->query->orderBy($column, $direction);

        return $this;
    }

    public function orderByDesc($column): static
    {
        return $this->orderBy($column, 'desc');
    }

    public function select($columns): static
    {
        if (!$this->useCache) {
            $this->query = $this->query->select($columns);
        } else {
            $this->query = $this->query->map(function ($item) use ($columns) {
                return collect($item)->only($columns)->toArray();
            });
        }

        return $this;
    }

    public function limit(int $count): static
    {
        $this->query = $this->useCache
            ? $this->query->take($count)
            : $this->query->limit($count);

        return $this;
    }

    public function count(): int
    {
        return $this->query->count();
    }

    public function get()
    {
        return $this->useCache ? collect($this->query->values()) : $this->query->get();
    }

    public function first()
    {
        return $this->query->first();
    }

    public function find($id)
    {
        return $this->useCache ? Vip::getCached()->firstWhere('id', $id) : Vip::find($id);
    }
}
