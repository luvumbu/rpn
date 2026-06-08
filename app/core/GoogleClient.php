<?php
/**
 * CLASSE GoogleClient
 * Regroupe TOUTES les fonctions liées à Google OAuth qui étaient répétées :
 *  - construire le lien de connexion
 *  - échanger le "code" contre un token
 *  - récupérer le profil de l'utilisateur
 * La logique cURL (utilisée plusieurs fois) est centralisée dans request().
 */
class GoogleClient
{
    /** Clé publique Google (réglage admin sinon valeur du config). */
    private function clientId(): string
    {
        return Settings::get('google_client_id', GOOGLE_CLIENT_ID);
    }

    /** Clé secrète Google (réglage admin sinon valeur du config). */
    private function clientSecret(): string
    {
        return Settings::get('google_client_secret', GOOGLE_CLIENT_SECRET);
    }

    /** Construit le lien "Se connecter avec Google". */
    public function getAuthUrl(string $state): string
    {
        return GOOGLE_AUTH_URL . '?' . http_build_query([
            'client_id'     => $this->clientId(),
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'access_type'   => 'online',
            'state'         => $state,
            'prompt'        => 'select_account',
        ]);
    }

    /** Échange le code reçu contre un access_token. */
    public function fetchToken(string $code): ?array
    {
        $res = $this->request(GOOGLE_TOKEN_URL, [
            'code'          => $code,
            'client_id'     => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        ]);

        return !empty($res['access_token']) ? $res : null;
    }

    /** Récupère le profil Google (nom, email, photo...). */
    public function fetchUserInfo(string $accessToken): ?array
    {
        $res = $this->request(GOOGLE_USERINFO_URL, null, [
            'Authorization: Bearer ' . $accessToken,
        ]);

        return !empty($res['email']) ? $res : null;
    }

    /**
     * Fonction HTTP unique réutilisée pour TOUS les appels vers Google.
     * - $postFields fourni  → requête POST
     * - $postFields à null   → requête GET
     */
    private function request(string $url, ?array $postFields = null, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($postFields !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        }
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?: [];
    }
}
