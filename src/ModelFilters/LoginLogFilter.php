<?php

namespace WeiJuKeJi\LaravelIam\ModelFilters;

use EloquentFilter\ModelFilter;

class LoginLogFilter extends ModelFilter
{
    protected $blacklist = ['page', 'per_page'];

    public function userId(int|string $userId): self
    {
        if (empty($userId)) {
            return $this;
        }

        return $this->where('user_id', (int) $userId);
    }

    public function username(string $username): self
    {
        if (empty($username)) {
            return $this;
        }

        return $this->where('username', 'ilike', "%{$username}%");
    }

    public function account(string $account): self
    {
        if (empty($account)) {
            return $this;
        }

        return $this->where('account', 'ilike', "%{$account}%");
    }

    public function status(string $status): self
    {
        if (! in_array($status, ['success', 'failed'])) {
            return $this;
        }

        return $this->where('status', $status);
    }

    public function ip(string $ip): self
    {
        if (empty($ip)) {
            return $this;
        }

        return $this->where('ip', 'ilike', "%{$ip}%");
    }

    public function loginType(string $loginType): self
    {
        if (empty($loginType)) {
            return $this;
        }

        return $this->where('login_type', $loginType);
    }

    public function startDate(string $startDate): self
    {
        if (empty($startDate)) {
            return $this;
        }

        return $this->where('login_at', '>=', $startDate);
    }

    public function endDate(string $endDate): self
    {
        if (empty($endDate)) {
            return $this;
        }

        return $this->where('login_at', '<=', $endDate);
    }
}
