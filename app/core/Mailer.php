<?php
/**
 * CLASSE Mailer — envoi d'e-mails transactionnels (HTML) via la fonction PHP mail().
 * Suffisant sur la plupart des hébergements mutualisés (type Hostinger). L'adresse
 * d'expéditeur est configurable dans l'admin (réglage 'mail_from') ; à défaut, on
 * dérive « no-reply@<domaine> ». Aucune dépendance externe (pas de Composer).
 */
class Mailer
{
    /** Adresse d'expéditeur (réglage admin, sinon dérivée du domaine). */
    public static function from(): string
    {
        $from = trim((string) Settings::get('mail_from', ''));
        if ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return $from;
        }
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $host = preg_replace('/:\d+$/', '', $host); // retire un éventuel :port
        return 'no-reply@' . ($host !== '' ? $host : 'localhost');
    }

    /** Envoie un e-mail HTML. Retourne true si mail() a accepté l'envoi. */
    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !function_exists('mail')) {
            return false;
        }
        $fromAddr = self::from();
        $fromName = (string) Settings::get('main_title', 'RPN');

        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
        $headers .= 'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $fromAddr . '>' . "\r\n";
        $headers .= 'Reply-To: ' . $fromAddr . "\r\n";

        $subjectEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        return @mail($to, $subjectEnc, $htmlBody, $headers);
    }

    /** Gabarit HTML simple et sobre pour un e-mail (titre + contenu + pied). */
    public static function template(string $title, string $bodyHtml): string
    {
        $site = htmlspecialchars((string) Settings::get('main_title', 'RPN'));
        return '<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;color:#23232b;">'
            . '<div style="height:6px;background:linear-gradient(90deg,#e63946 0 33%,#14110f 33% 66%,#2a9d4a 66% 100%);"></div>'
            . '<div style="padding:24px;background:#fbfbfd;border:1px solid #e7e7ee;border-top:none;border-radius:0 0 12px 12px;">'
            . '<h2 style="color:#14110f;margin:0 0 12px;">' . htmlspecialchars($title) . '</h2>'
            . $bodyHtml
            . '<p style="margin-top:24px;font-size:12px;color:#6b7280;">— ' . $site . '</p>'
            . '</div></div>';
    }
}
