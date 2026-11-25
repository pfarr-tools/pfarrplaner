<?php

namespace App\Http\Requests;

use App\Models\People\User;
use Illuminate\Foundation\Http\FormRequest;

/** Validate a Super-Admin's re-authenticated two-factor recovery request. */
class RecoverTwoFactorRequest extends FormRequest
{
    /** Only a Super-Admin may submit recovery for a different account. */
    public function authorize(): bool
    {
        $actor = $this->user();
        $target = $this->route('user');

        return $actor instanceof User
            && $target instanceof User
            && $actor->getKey() !== $target->getKey()
            && $actor->can('recoverTwoFactor', $target);
    }

    /**
     * Return a current TOTP code and a constrained support reference.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'regex:/\A[0-9]{6,8}\z/'],
            'support_reference' => ['required', 'string', 'max:100', 'regex:/\A[A-Za-z0-9._-]+\z/'],
        ];
    }
}
