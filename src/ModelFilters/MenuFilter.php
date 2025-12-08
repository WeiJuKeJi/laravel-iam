<?php

namespace WeiJuKeJi\LaravelIam\ModelFilters;

use EloquentFilter\ModelFilter;

class MenuFilter extends ModelFilter
{
    public $relations = [];

    public function parent(int|string $id)
    {
        return $this->where('parent_id', $id === 'null' ? null : (int)$id);
    }

    public function name(string $value)
    {
        return $this->where('name', 'like', "%{$value}%");
    }

    public function path(string $value)
    {
        return $this->where('path', 'like', "%{$value}%");
    }

    public function isEnabled(int|string $value)
    {
        return $this->where('is_enabled', (bool)$value);
    }
}
