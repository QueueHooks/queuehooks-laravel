<?php

namespace Queuehooks\QueuehooksLaravel;

use Illuminate\Foundation\Http\FormRequest;

class QueueHooksIngestRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'queue'   => ['required', 'string'],
            'payload' => ['required', 'array'],
        ];
    }
}
