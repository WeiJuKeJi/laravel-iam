<?php

namespace WeiJuKeJi\LaravelIam\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account' => ['required_without:username', 'string'],
            'username' => ['required_without:account', 'string'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function attributes(): array
    {
        return [
            'account' => '登录账号',
            'username' => '用户名',
            'password' => '密码',
        ];
    }
}
