<?php

namespace App\Http\Middleware;

use App\Services\FieldRuleEngine;
use Closure;
use Illuminate\Http\Request;

class FieldRuleValidator
{
    public function __construct(private readonly FieldRuleEngine $engine)
    {
    }

    public function handle(Request $request, Closure $next, string $targetForm = 'student_registration', string $studentTypeField = 'application_type')
    {
        $formData = $request->all();
        $studentType = trim((string) $request->input($studentTypeField, '')) ?: null;

        $guestKey = strtolower(trim((string) $request->input('email', '')));
        if ($guestKey !== '') {
            $guestKey = substr(sha1($guestKey), 0, 40);
        } else {
            $guestKey = null;
        }

        $items = $this->engine->evaluate(
            $targetForm,
            $formData,
            $studentType,
            null,
            $guestKey,
            'guest.public'
        );

        $blockMessages = [];
        foreach ($items as $item) {
            if (($item['severity'] ?? null) === 'block') {
                $blockMessages[] = (string) ($item['message'] ?? 'Basvuru bu asamada onay gerektiriyor.');
            }
        }

        if (!empty($blockMessages)) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($blockMessages);
        }

        return $next($request);
    }
}

