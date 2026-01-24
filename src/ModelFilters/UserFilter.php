<?php

namespace WeiJuKeJi\LaravelIam\ModelFilters;

use EloquentFilter\ModelFilter;
use Illuminate\Support\Arr;
use WeiJuKeJi\LaravelIam\Models\Department;

class UserFilter extends ModelFilter
{
    protected $blacklist = ['page', 'per_page'];

    public function status(string|array $status): self
    {
        $statuses = array_filter(Arr::wrap($status));

        if (empty($statuses)) {
            return $this;
        }

        return $this->whereIn('status', $statuses);
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
                ->orWhere('email', 'ilike', $like)
                ->orWhere('username', 'ilike', $like)
                ->orWhere('phone', 'ilike', $like);
        });
    }

    public function email(string $email): self
    {
        return $this->where('email', $email);
    }

    public function username(string $username): self
    {
        return $this->where('username', $username);
    }

    public function role(string|array|int $role): self
    {
        $roles = array_filter(Arr::wrap($role), fn ($value) => $value !== null && $value !== '');

        if (empty($roles)) {
            return $this;
        }

        $ids = array_map('intval', array_filter($roles, fn ($value) => is_numeric($value)));
        $names = array_filter($roles, fn ($value) => ! is_numeric($value));

        return $this->whereHas('roles', function ($query) use ($ids, $names) {
            $query->where(function ($inner) use ($ids, $names) {
                if (! empty($ids)) {
                    $inner->whereIn('roles.id', $ids);

                    if (! empty($names)) {
                        $inner->orWhereIn('roles.name', $names);
                    }

                    return;
                }

                if (! empty($names)) {
                    $inner->whereIn('roles.name', $names);
                }
            });
        });
    }

    public function department(int|string $departmentId): self
    {
        if (empty($departmentId)) {
            return $this;
        }

        $department = Department::find((int) $departmentId);

        if (! $department) {
            return $this;
        }

        // 获取该部门及其所有子部门的 ID
        $departmentIds = $department->descendants()
            ->pluck('id')
            ->push($department->id)
            ->toArray();

        return $this->whereIn('department_id', $departmentIds);
    }

    public function userType(string $userType): self
    {
        if (empty($userType)) {
            return $this;
        }

        return $this->where('user_type', $userType);
    }
}
