<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\AppSetting;

class QrCodeService
{
    private const DEFAULT_RUNNER_FIELDS = [
        'event_name',
        'race_number',
        'registration_reference',
        'runner_name',
        'race_distance',
        'kit_type',
        'tshirt_size',
        'team',
        'payment_status',
    ];

    public static function runnerFields(): array
    {
        return [
            'event_name' => __('Event name'),
            'race_number' => __('Race number'),
            'registration_reference' => __('Registration reference'),
            'runner_name' => __('Runner name'),
            'race_distance' => __('Race distance and category'),
            'kit_type' => __('Kit type'),
            'tshirt_size' => __('T-shirt size'),
            'team' => __('Team name'),
            'payment_status' => __('Payment status'),
        ];
    }

    public static function defaultRunnerFields(): array
    {
        return self::DEFAULT_RUNNER_FIELDS;
    }

    /**
     * Builds the configured QR text for a paid runner.
     */
    public static function buildRunnerPayload(Registration $registration): ?string
    {
        if ($registration->payment_status !== 'paid' || ! $registration->bib_number) {
            return null;
        }

        $selectedFields = AppSetting::get('runner_qr_fields', self::DEFAULT_RUNNER_FIELDS);
        if (! is_array($selectedFields)) {
            $selectedFields = self::DEFAULT_RUNNER_FIELDS;
        }

        $isFullKit = str_contains(strtolower((string) $registration->kit_type), 'full');
        $kitLabel = $isFullKit ? 'Full Kit' : 'Basic Kit';

        $values = [
            'event_name' => 'KFS RUNNERS MARATHON',
            'race_number' => 'RACE NUMBER: ' . $registration->bib_number,
            'registration_reference' => 'REGISTRATION REF: ' . $registration->registration_code,
            'runner_name' => 'RUNNER: ' . trim($registration->first_name . ' ' . $registration->last_name),
            'race_distance' => 'DISTANCE: ' . ($registration->race?->distance_label ?? '—') . ' (' . ($registration->race?->name ?? 'Race') . ')',
            'kit_type' => 'KIT: ' . $kitLabel,
            'tshirt_size' => 'T-SHIRT SIZE: ' . (filled($registration->shirt_size) ? strtoupper(trim($registration->shirt_size)) : 'N/A'),
            'team' => 'TEAM: ' . ($registration->team?->name ?? $registration->team_name ?? 'Individual'),
            'payment_status' => 'PAYMENT: PAID',
        ];

        $lines = collect(self::DEFAULT_RUNNER_FIELDS)
            ->filter(fn (string $key) => in_array($key, $selectedFields, true))
            ->map(fn (string $key) => $values[$key])
            ->values();

        return $lines->isNotEmpty() ? $lines->implode("\n") : null;
    }

    /**
     * Generates a Base64 PNG Data URI for the given text.
     */
    public static function generateDataUri(string $text, int $size = 280, string $fgColor = '000000', string $bgColor = 'FFFFFF'): string
    {
        $engine = new PureQrEngine($text, [
            'w' => $size,
            'h' => $size,
            'wq' => 2,
            'fc' => $fgColor,
            'bc' => $bgColor,
        ]);

        $img = $engine->render_image();
        ob_start();
        imagepng($img);
        $raw = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($raw);
    }

    /**
     * Generates the complete runner QR Code Data URI directly from a Registration model.
     */
    public static function generateRunnerQr(Registration $registration, int $size = 280): ?string
    {
        $payload = self::buildRunnerPayload($registration);
        if ($payload === null) {
            return null;
        }

        return self::generateDataUri($payload, $size);
    }
}
