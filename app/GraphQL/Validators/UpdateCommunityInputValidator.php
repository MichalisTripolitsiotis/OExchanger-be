<?php

namespace App\GraphQL\Validators;

use Illuminate\Validation\Rule;
use Nuwave\Lighthouse\Validation\Validator;

final class UpdateCommunityInputValidator extends Validator
{
    /**
     * Return the validation rules.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                Rule::unique('communities', 'name')->ignore($this->arg('id'), 'id')
            ],
            'moderators.sync' => [
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
            'moderators.sync' => 'The moderator ID is required. The community needs at least one moderator.'
        ];
    }
}
