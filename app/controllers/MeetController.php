<?php
/**
 * CONTRÔLEUR Meet — salons visio (liens Jitsi) enregistrés par les membres.
 *  - save : enregistre un lien créé
 *  - list : « Mes salons » (liste + ouvrir / copier / supprimer)
 *  - delete : retire un salon enregistré
 */
class MeetController
{
    private function uid(): int
    {
        if (!Session::has('user')) {
            redirect('');
        }
        return (int) (Session::user()['id'] ?? 0);
    }

    /** Enregistre un lien de salon pour le membre, puis va sur « Mes salons ». */
    public function save(): void
    {
        $uid   = $this->uid();
        $url   = (string) ($_POST['url'] ?? '');
        $label = (string) ($_POST['label'] ?? '');
        if (MeetingLink::add($uid, $url, $label) > 0) {
            Session::set('meet_notice', '✅ Salon enregistré dans « Mes salons ».');
        } else {
            Session::set('meet_notice', '⚠️ Lien invalide — non enregistré.');
        }
        redirect('meet/list');
    }

    /** Page « Mes salons » : liste des liens enregistrés. */
    public function list(): void
    {
        $uid = $this->uid();
        view('meet/list', [
            'user'   => Session::user(),
            'links'  => MeetingLink::forUser($uid),
            'notice' => Session::get('meet_notice'),
        ]);
        Session::remove('meet_notice');
    }

    /** Renomme un salon enregistré. */
    public function rename(): void
    {
        $uid = $this->uid();
        $id  = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            MeetingLink::rename($id, $uid, (string) ($_POST['label'] ?? ''));
            Session::set('meet_notice', '✏️ Nom du salon mis à jour.');
        }
        redirect('meet/list');
    }

    /** Supprime un salon enregistré. */
    public function delete(): void
    {
        $uid = $this->uid();
        $id  = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            MeetingLink::delete($id, $uid);
            Session::set('meet_notice', '🗑️ Salon retiré.');
        }
        redirect('meet/list');
    }
}
