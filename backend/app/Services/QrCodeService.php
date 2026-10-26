<?php

namespace App\Services;

use App\Models\Registration;

class QrCodeService
{
    /**
     * Builds the formatted text data for a runner's QR Code.
     * Includes race number (bib), distance, kit, and t-shirt size (if full kit).
     */
    public static function buildRunnerPayload(Registration $registration): string
    {
        $code = $registration->registration_code;
        $name = trim($registration->first_name . ' ' . $registration->last_name);
        $distance = $registration->race?->distance_label ?? '—';
        $raceName = $registration->race?->name ?? 'Race';
        
        $isFullKit = str_contains(strtolower((string) $registration->kit_type), 'full');
        $kitLabel = $isFullKit ? 'Full Kit' : 'Basic Kit';

        $lines = [
            'KFS RUNNERS MARATHON',
            "RACE NUMBER: {$code}",
            "RUNNER: {$name}",
            "DISTANCE: {$distance}" . ($raceName ? " ({$raceName})" : ""),
            "KIT: {$kitLabel}",
        ];

        // If full kit, add the t-shirt size
        if ($isFullKit && filled($registration->shirt_size)) {
            $lines[] = 'T-SHIRT SIZE: ' . strtoupper(trim($registration->shirt_size));
        }

        if (filled($registration->team_name)) {
            $lines[] = 'TEAM: ' . $registration->team_name;
        }

        $paymentStatus = strtoupper($registration->payment_status ?: 'PENDING');
        $lines[] = "PAYMENT: {$paymentStatus}";

        return implode("\n", $lines);
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
    public static function generateRunnerQr(Registration $registration, int $size = 280): string
    {
        $payload = self::buildRunnerPayload($registration);

        return self::generateDataUri($payload, $size);
    }
}
