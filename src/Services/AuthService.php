<?php

namespace WeiJuKeJi\LaravelIam\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use WeiJuKeJi\LaravelIam\Models\User;

class AuthService
{
    /**
     * 尝试登录，并返回令牌相关信息。
     *
     * @throws ValidationException
     */
    public function attemptLogin(string $account, string $password, string $ip): array
    {
        $user = User::query()
            ->where(function ($query) use ($account) {
                $query->where('email', $account)
                    ->orWhere('username', $account);
            })
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'account' => '账号或密码不正确',
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'account' => '账号已被禁用，请联系管理员。',
            ]);
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->save();

        $token = $user->createToken('admin-access', ['*']);

        return $this->formatTokenPayload($token);
    }

    /**
     * 注销当前访问令牌。
     */
    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token) {
            $token->delete();
        }
    }

    /**
     * 构建当前用户信息。
     */
    public function profile(User $user): array
    {
        $user->loadMissing(['roles.permissions', 'permissions']);

        $roles = $user->roles
            ->pluck('name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $permissions = $user->getAllPermissions()
            ->pluck('name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $avatar = data_get($user->metadata, 'avatar')
            ?? ($user->avatar ?? 'https://i.gtimg.cn/club/item/face/img/2/16022_100.gif');

        return [
            'username' => $user->username ?? $user->name,
            'roles' => $roles,
            'permissions' => $permissions,
            'avatar' => $avatar,
        ];
    }

    protected function formatTokenPayload(NewAccessToken $token): array
    {
        $expiration = config('sanctum.expiration');
        $expiresAt = $expiration ? now()->addMinutes($expiration)->toDateTimeString() : null;

        $payload = [
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ];

        if ($expiresAt) {
            $payload['expires_at'] = $expiresAt;
        }

        return $payload;
    }
}
