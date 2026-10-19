<?php

namespace App\Modules\Visitors\Http\Requests;

use App\Enums\InvitedBy;
use Illuminate\Validation\Rule;

/**
 * A visitor registered at the reception, on the public page: the admin form's fields, how the visitor heard of the
 * church ("invited_by_other": someone, Instagram, Google…) and a hidden field against bots.
 */
class PublicVisitorRequest extends VisitorRequest
{
    public function prepareForValidation(): void
    {
        app()->setLocale('pt');
        parent::prepareForValidation();
        // "+351 912 345 678" -> "912345678"; other countries keep their code.
        $phone = preg_replace('/^00/', '', preg_replace('/\D+/', '', (string) $this->input('phone_number')));
        $this->merge(['phone_number' => preg_replace('/^351(?=9\d{8}$)/', '', $phone) ?: null]);
    }

    public function rules(): array
    {
        return parent::rules() + [
            'invited_by_other' => ['nullable', Rule::in(array_keys(InvitedBy::options()))],
            'website' => ['prohibited'],
        ];
    }
}
