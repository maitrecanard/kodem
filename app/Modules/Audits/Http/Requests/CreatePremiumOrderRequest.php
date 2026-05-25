<?php

declare(strict_types=1);

namespace App\Modules\Audits\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePremiumOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:120'],
            'email'            => ['required', 'email:rfc,dns', 'max:180'],
            'domain'           => ['required', 'string', 'max:253', 'regex:/^[a-z0-9.-]+\.[a-z]{2,}$/i'],
            'options'          => [
                'required',
                'array:seo,security',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (empty($value['seo']) && empty($value['security'])) {
                        $fail('Sélectionnez au moins un type d\'audit (SEO ou Sécurité).');
                    }
                },
            ],
            'options.seo'      => ['boolean'],
            'options.security' => ['boolean'],
            'rgpd_consent'     => ['accepted'],
            'website'          => ['nullable', 'size:0'],   // honeypot
            'form_loaded_at'   => [
                'required',
                'integer',
                'min:' . now()->subDay()->timestamp,
                'max:' . now()->timestamp,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'domain.regex' => 'Le domaine doit être au format "exemple.fr" (sans https://).',
            'rgpd_consent.accepted' => 'Vous devez accepter le traitement de vos données.',
        ];
    }

    public function passedValidation(): void
    {
        $domain = strtolower($this->input('domain'));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');
        $this->merge(['domain' => $domain]);
    }
}
