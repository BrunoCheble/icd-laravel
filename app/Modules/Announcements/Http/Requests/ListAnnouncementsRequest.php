<?php

namespace App\Modules\Announcements\Http\Requests;

use App\Enums\AnnouncementType;
use App\Modules\Announcements\Services\ListAnnouncementsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAnnouncementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(ListAnnouncementsService::STATUSES)],
            'type' => ['nullable', Rule::in(array_keys(AnnouncementType::options()))],
        ];
    }
}
