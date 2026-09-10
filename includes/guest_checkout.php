<?php
/**
 * Commande invité — coordonnées en session (nom + téléphone).
 */

if (!function_exists('guest_checkout_is_connected')) {
    function guest_checkout_is_connected(): bool
    {
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
    }
}

if (!function_exists('guest_checkout_has_info')) {
    function guest_checkout_has_info(): bool
    {
        if (guest_checkout_is_connected()) {
            return true;
        }

        $nom = trim((string) ($_SESSION['guest_checkout']['nom'] ?? ''));
        $tel = trim((string) ($_SESSION['guest_checkout']['telephone'] ?? ''));

        return $nom !== '' && $tel !== '';
    }
}

if (!function_exists('guest_checkout_get_info')) {
    function guest_checkout_get_info(): array
    {
        return [
            'nom' => trim((string) ($_SESSION['guest_checkout']['nom'] ?? '')),
            'telephone' => trim((string) ($_SESSION['guest_checkout']['telephone'] ?? '')),
        ];
    }
}

if (!function_exists('guest_checkout_split_name')) {
    function guest_checkout_split_name(string $full): array
    {
        $full = trim($full);
        if ($full === '') {
            return ['nom' => 'Client', 'prenom' => 'Invité'];
        }

        $parts = preg_split('/\s+/u', $full, 2);
        if (count($parts) === 1) {
            return ['nom' => $parts[0], 'prenom' => '-'];
        }

        return ['nom' => $parts[1], 'prenom' => $parts[0]];
    }
}

if (!function_exists('guest_checkout_save_info')) {
    function guest_checkout_save_info(string $nom, string $telephone): array
    {
        require_once __DIR__ . '/../models/model_users.php';

        $nom = trim($nom);
        $telephone = trim($telephone);

        if ($nom === '') {
            return ['success' => false, 'message' => 'Veuillez renseigner votre nom.'];
        }

        if (mb_strlen($nom) < 2) {
            return ['success' => false, 'message' => 'Le nom semble trop court.'];
        }

        if ($telephone === '') {
            return ['success' => false, 'message' => 'Veuillez renseigner votre numéro de téléphone.'];
        }

        $digits = users_normalize_phone_digits($telephone);
        if ($digits === '' || strlen($digits) < 8) {
            return ['success' => false, 'message' => 'Le numéro de téléphone semble incomplet.'];
        }

        $e164 = '+' . ltrim($telephone, '+');
        if ($e164 === '+' || !preg_match('/^\+\d{8,15}$/', str_replace(' ', '', $e164))) {
            $e164 = '+' . $digits;
        }

        $_SESSION['guest_checkout'] = [
            'nom' => $nom,
            'telephone' => $e164,
        ];

        return ['success' => true, 'message' => 'Coordonnées enregistrées.'];
    }
}

if (!function_exists('guest_checkout_save_from_post')) {
    function guest_checkout_save_from_post(): array
    {
        if (guest_checkout_is_connected()) {
            return ['success' => true, 'message' => ''];
        }

        if (!isset($_POST['guest_nom']) && !isset($_POST['guest_telephone'])) {
            if (guest_checkout_has_info()) {
                return ['success' => true, 'message' => ''];
            }

            return ['success' => false, 'message' => 'Veuillez renseigner votre nom et votre téléphone.'];
        }

        return guest_checkout_save_info(
            (string) ($_POST['guest_nom'] ?? ''),
            (string) ($_POST['guest_telephone'] ?? '')
        );
    }
}

if (!function_exists('guest_checkout_clear')) {
    function guest_checkout_clear(): void
    {
        unset($_SESSION['guest_checkout']);
    }
}

if (!function_exists('guest_checkout_message_needs_info')) {
    function guest_checkout_message_needs_info(string $message): bool
    {
        return stripos($message, 'nom') !== false
            || stripos($message, 'téléphone') !== false
            || stripos($message, 'telephone') !== false;
    }
}
