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

    /** Return password confirmation and a constrained support reference. */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'support_reference' => ['required', 'string', 'max:100', 'regex:/\A[A-Za-z0-9._-]+\z/'],
        ];
    }
}
