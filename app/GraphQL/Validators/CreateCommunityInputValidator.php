<?php

namespace App\GraphQL\Validators;

use Nuwave\Lighthouse\Validation\Validator;

final class CreateCommunityInputValidator extends Validator
{
    /**
     * Return the validation rules.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'users.sync' => [
                'required'
            ]
        ];
    }

    /**
     * Return messages for validation errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'users.sync' => 'The moderator ID is required. The community needs at least one moderator.'
        ];
    }
}
