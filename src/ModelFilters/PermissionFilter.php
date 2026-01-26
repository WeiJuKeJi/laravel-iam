<?php

namespace WeiJuKeJi\LaravelIam\ModelFilters;

use EloquentFilter\ModelFilter;
use Illuminate\Support\Arr;

class PermissionFilter extends ModelFilter
{
    protected $blacklist = ['page', 'per_page'];

    public function guardName(string|array $guards): self
    {
        $guards = array_filter(Arr::wrap($guards));

        if (empty($guards)) {
            return $this;
        }

        return $this->whereIn('guard_name', $guards);
    }

    public function keywords(string $keywords): self
    {
        $keywords = trim($keywords);

        if ($keywords === '') {
            return $this;
        }

        $like = "%{$keywords}%";

        return $this->where(function ($query) use ($like) {
            $query->where('name', 'ilike', $like)
                ->orWhere('display_name', 'ilike', $like)
                ->orWhere('group', 'ilike', $like);
        });
    }

    public function group(string $group): self
    {
        $group = trim($group);

        if ($group === '') {
            return $this;
        }

        if (! str_contains($group, '.')) {
            return $this->where('group', 'ilike', $group.'.%');
        }

        return $this->where('group', $group);
    }
}
