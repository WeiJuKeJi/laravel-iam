<?php

namespace WeiJuKeJi\LaravelIam\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use WeiJuKeJi\LaravelIam\Models\LoginLog;
use WeiJuKeJi\LaravelIam\Models\User;

class AuthService
{
    protected function resolveUserModelClass(): string
    {
        $model = config('iam.models.user', User::class);

        return is_a($model, User::class, true) ? $model : User::class;
    }

    /**
     * 尝试登录，并返回令牌相关信息。
     *
     * @throws ValidationException
     */
    public function attemptLogin(string $account, string $password, string $ip, ?string $userAgent = null): array
    {
        $userModel = $this->resolveUserModelClass();
        $user = $userModel::query()
            ->where(function ($query) use ($account) {
                $query->where('email', $account)
                    ->orWhere('username', $account)
                    ->orWhere('phone', $account);
            })
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            // 记录失败日志
            LoginLog::recordFailure(
                account: $account,
                reason: '账号或密码不正确',
                ip: $ip,
                userAgent: $userAgent
            );

            throw ValidationException::withMessages([
                'account' => '账号或密码不正确',
            ]);
        }

        if ($user->status !== 'active') {
            // 记录失败日志
            LoginLog::recordFailure(
                account: $account,
                reason: '账号已被禁用',
                ip: $ip,
                userAgent: $userAgent
            );

            throw ValidationException::withMessages([
                'account' => '账号已被禁用，请联系管理员。',
            ]);
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->save();

        // 记录成功日志
        LoginLog::recordSuccess(
            user: $user,
            account: $account,
            ip: $ip,
            userAgent: $userAgent
        );

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
        $user->loadMissing(['roles.permissions', 'permissions', 'department']);

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
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'user_type' => $user->user_type,
            'status' => $user->status,
            'avatar' => $avatar,
            'department_id' => $user->department_id,
            'department' => $user->department ? [
                'id' => $user->department->id,
                'name' => $user->department->name,
            ] : null,
            'roles' => $roles,
            'permissions' => $permissions,
            'created_at' => $user->created_at?->toDateTimeString(),
            'last_login_at' => $user->last_login_at?->toDateTimeString(),
            'last_login_ip' => $user->last_login_ip,
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
