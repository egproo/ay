<?php
namespace Jpos;

class PosUser {
    private array $permission = [];
    private ?int $jpos_user_id = null;
    private ?int $user_group_id = null;
    private ?string $username = null;
    private ?string $firstname = null;
    private ?string $lastname = null;
    private ?string $image = null;
    private ?string $email = null;
    private array $storeinfo = [];
    private ?string $def_location = null;
    private ?string $def_currency = null;
    private ?string $def_language = null;
    private ?int $def_location_id = null;
    private ?int $def_currency_id = null;
    private ?int $def_language_id = null;
    private object $db;
    private object $request;
    private object $session;
    private object $load;

    public function __construct(private readonly object $registry) {
        $this->db = $registry->get('db');
        $this->request = $registry->get('request');
        $this->session = $registry->get('session');
        $this->load = $registry->get('load');

        if (isset($this->session->data['jpos_user_id'])) {
            $user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "jpos_user WHERE jpos_user_id = '" . (int)$this->session->data['jpos_user_id'] . "' AND status = '1'");

            if ($user_query->num_rows) {
                $this->jpos_user_id = $user_query->row['jpos_user_id'];
                $this->username = $user_query->row['username'];
                $this->firstname = $user_query->row['firstname'];
                $this->lastname = $user_query->row['lastname'];
                $this->image = $user_query->row['image'];
                $this->email = $user_query->row['email'];
                $this->storeinfo = $user_query->row;
                $this->def_location = $this->getLocationName($user_query->row['def_jpos_location_id']);
                $this->def_currency = $this->getCurrencyTitle($user_query->row['def_currency_id']);
                $this->def_language = $this->getLanguageName($user_query->row['def_language_id']);
                $this->def_location_id = $user_query->row['def_jpos_location_id'];
                $this->def_currency_id = $user_query->row['def_currency_id'];
                $this->def_language_id = $user_query->row['def_language_id'];

                $this->db->query("UPDATE " . DB_PREFIX . "jpos_user SET ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "' WHERE jpos_user_id = '" . (int)$this->session->data['jpos_user_id'] . "'");
            } else {
                $this->logout();
            }
        }
    }

    public function login(string $username, string $password): bool {
        $user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "jpos_user WHERE username = '" . $this->db->escape($username) . "' AND (password = SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('" . $this->db->escape(htmlspecialchars($password, ENT_QUOTES)) . "'))))) OR password = '" . $this->db->escape(md5($password)) . "') AND status = '1'");

        if ($user_query->num_rows) {
            $this->session->data['jpos_user_id'] = $user_query->row['jpos_user_id'];

            $this->jpos_user_id = $user_query->row['jpos_user_id'];
            $this->username = $user_query->row['username'];
            $this->firstname = $user_query->row['firstname'];
            $this->lastname = $user_query->row['lastname'];
            $this->image = $user_query->row['image'];
            $this->email = $user_query->row['email'];
            $this->storeinfo = $user_query->row;
            $this->def_location = $this->getLocationName($user_query->row['def_jpos_location_id']);
            $this->def_currency = $this->getCurrencyTitle($user_query->row['def_currency_id']);
            $this->def_language = $this->getLanguageName($user_query->row['def_language_id']);
            $this->def_location_id = $user_query->row['def_jpos_location_id'];
            $this->def_currency_id = $user_query->row['def_currency_id'];
            $this->def_language_id = $user_query->row['def_language_id'];

            $langauge_info = $this->db->query("SELECT * FROM " . DB_PREFIX . "language WHERE language_id = '" . (int)$user_query->row['def_language_id'] . "'");
            if ($langauge_info->num_rows) {
                $this->session->data['language'] = $langauge_info->row['code'];
            }

            $currency_info = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "currency WHERE currency_id = '" . (int)$user_query->row['def_currency_id'] . "'");

            if ($currency_info->num_rows) {
                $this->session->data['currency'] = $currency_info->row['code'];

                unset($this->session->data['jpos_shipping_method']);
                unset($this->session->data['jpos_shipping_methods']);
            }

            return true;
        } else {
            return false;
        }
    }

    private function getLocationName(int $jpos_location_id): string {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "jpos_location WHERE jpos_location_id = '" . $jpos_location_id . "'");
        return $query->num_rows ? $query->row['name'] : '';
    }

    private function getCurrencyTitle(int $currency_id): string {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "currency WHERE currency_id = '" . $currency_id . "'");
        return $query->num_rows ? $query->row['title'] : '';
    }

    private function getLanguageName(int $language_id): string {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "language WHERE language_id = '" . $language_id . "'");
        return $query->num_rows ? $query->row['name'] : '';
    }

    public function logout(): void {
        unset($this->session->data['jpos_user_id']);

        $this->permission = [];
        $this->jpos_user_id = null;
        $this->user_group_id = null;
        $this->username = null;
        $this->firstname = null;
        $this->lastname = null;
        $this->image = null;
        $this->email = null;
        $this->storeinfo = [];
        $this->def_location = null;
        $this->def_currency = null;
        $this->def_language = null;
        $this->def_location_id = null;
        $this->def_currency_id = null;
        $this->def_language_id = null;
    }

    public function hasPermission(string $key, string $value): bool {
        return isset($this->permission[$key]) && in_array($value, $this->permission[$key]);
    }

    public function isLogged(): ?int {
        return $this->jpos_user_id;
    }

    public function getId(): ?int {
        return $this->jpos_user_id;
    }

    public function getUserName(): ?string {
        return $this->username;
    }

    public function getFirstName(): ?string {
        return $this->firstname;
    }

    public function getLastName(): ?string {
        return $this->lastname;
    }

    public function getImage(): ?string {
        return $this->image;
    }

    public function getEmail(): ?string {
        return $this->email;
    }

    public function getStoreInfo(): array {
        return $this->storeinfo;
    }

    public function getDefaultLocation(): ?string {
        return $this->def_location;
    }

    public function getDefaultCurrency(): ?string {
        return $this->def_currency;
    }

    public function getDefaultLanguage(): ?string {
        return $this->def_language;
    }

    public function getDefaultLocationId(): ?int {
        return $this->def_location_id;
    }

    public function getDefaultCurrencyId(): ?int {
        return $this->def_currency_id;
    }

    public function getDefaultLanguageId(): ?int {
        return $this->def_language_id;
    }

    public function getFullName(): string {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }

    public function getGroupId(): ?int {
        return $this->user_group_id;
    }

    public function getInfo(): array {
        return [
            'firstname' => $this->getFirstName(),
            'lastname' => $this->getLastName(),
            'email' => $this->getEmail(),
            'image' => $this->getImage(),
            'jpos_user_id' => $this->getId(),
            'def_location' => $this->getDefaultLocation(),
            'def_currency' => $this->getDefaultCurrency(),
            'def_language' => $this->getDefaultLanguage(),
            'def_location_id' => $this->getDefaultLocationId(),
            'def_currency_id' => $this->getDefaultCurrencyId(),
            'def_language_id' => $this->getDefaultLanguageId()
        ];
    }
}