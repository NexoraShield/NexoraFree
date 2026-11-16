<?php
/**
 * The customers of the site that are connected to this bot.
 *
 * The hub writes the file panel/customers.json into the code of the bot (and
 * the key of the signature in panel/panel.key): the password of a customer is
 * never written down, only its hash.
 */

/** All the customers of this bot. */
function nexora_bot_customers(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $customers = [];
    $file = dirname(__DIR__) . '/customers.json';
    if (is_file($file)) {
        $raw = json_decode((string) file_get_contents($file), true);
        if (is_array($raw) && isset($raw['customers']) && is_array($raw['customers'])) {
            $customers = $raw['customers'];
        }
    }
    $cache = $customers;
    return $customers;
}

/** The customer of a name (the name is not case sensitive). */
function nexora_customer_by_name(string $name): ?array
{
    $name = trim($name);
    if ($name === '') {
        return null;
    }
    foreach (nexora_bot_customers() as $customer) {
        if (strcasecmp((string) ($customer['username'] ?? ''), $name) === 0) {
            return $customer;
        }
    }
    return null;
}

/** The customer of a name and a password, or nothing. */
function nexora_customer_login(string $username, string $password): ?array
{
    $customer = nexora_customer_by_name($username);
    if ($customer === null || $password === '') {
        return null;
    }
    if (!password_verify($password, (string) ($customer['password_hash'] ?? ''))) {
        return null;
    }
    return $customer;
}

/** Signs a customer in. */
function nexora_customer_login_session(array $customer): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_regenerate_id(true);
    $_SESSION['nx_customer'] = (string) ($customer['id'] ?? '');
    $_SESSION['nx_customer_since'] = time();
}

/** The customer that is signed in (the session of the panel). */
function nexora_customer_session(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        return null;
    }
    $id = (string) ($_SESSION['nx_customer'] ?? '');
    if ($id === '') {
        return null;
    }
    foreach (nexora_bot_customers() as $customer) {
        if ((string) ($customer['id'] ?? '') === $id) {
            return $customer;
        }
    }
    return null;
}

/** Signs the customer out. */
function nexora_customer_logout(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        return;
    }
    unset($_SESSION['nx_customer'], $_SESSION['nx_customer_since']);
}

/**
 * Is the address of the machine of the hub itself?
 *
 * The site of the customers and the panel of the bot are on the same machine,
 * so the request comes from a private address (the web server of the bot sees
 * the bridge of docker, not 127.0.0.1).
 */
function nexora_customer_is_local(string $address): bool
{
    if ($address === '') {
        return false;
    }
    if (!filter_var($address, FILTER_VALIDATE_IP)) {
        return false;
    }
    // the addresses that are not private and not reserved are from the
    // outside: they are not trusted.
    return !filter_var(
        $address,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    );
}

/**
 * The customer that comes from the site of the customers.
 *
 * The site sends the id of the customer and a signature of it. The signature
 * is only trusted when the request comes from the machine of the hub (a
 * private address), so nobody from the outside can come in with a made up id.
 */
function nexora_customer_from_site(): ?array
{
    $id = (string) ($_SERVER['HTTP_X_NX_CUSTOMER'] ?? '');
    $signature = (string) ($_SERVER['HTTP_X_NX_SIGNATURE'] ?? '');
    if ($id === '' || $signature === '') {
        return null;
    }
    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if (!nexora_customer_is_local($remote)) {
        return null;
    }
    $keyFile = dirname(__DIR__) . '/panel.key';
    if (!is_file($keyFile)) {
        return null;
    }
    $key = trim((string) file_get_contents($keyFile));
    if ($key === '') {
        return null;
    }
    if (!hash_equals(hash_hmac('sha256', $id, $key), $signature)) {
        return null;
    }
    foreach (nexora_bot_customers() as $customer) {
        if ((string) ($customer['id'] ?? '') === $id) {
            return $customer;
        }
    }
    return null;
}
