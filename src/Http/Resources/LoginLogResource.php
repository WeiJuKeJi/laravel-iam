<?php

namespace WeiJuKeJi\LaravelIam\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LoginLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'username' => $this->username,
            'account' => $this->account,
            'status' => $this->status,
            'status_text' => $this->status === 'success' ? '成功' : '失败',
            'failure_reason' => $this->failure_reason,
            'ip' => $this->ip,
            'user_agent' => $this->user_agent,
            'login_type' => $this->login_type,
            'login_type_text' => $this->getLoginTypeText(),
            'metadata' => $this->metadata,
            'login_at' => $this->login_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'username' => $this->user->username,
                    'email' => $this->user->email,
                ];
            }),
        ];
    }

    protected function getLoginTypeText(): string
    {
        return match ($this->login_type) {
            'password' => '密码登录',
            'sms' => '短信验证码',
            'oauth' => '第三方登录',
            'qrcode' => '扫码登录',
            default => $this->login_type,
        };
    }
}
