<?php
/* Sesh | https://gitlab.com/byjoby/sesh | MIT License */
namespace Sesh;

$GLOBALS['FAKESESSID'] = null;
$GLOBALS['FAKESESSIONS'] = array();

/* shifty fakes of session management */
function session_regenerate_id()
{
    $old = session_id();
    $new = generate_session_id();
    $GLOBALS['FAKESESSID'] = $new;
    $GLOBALS['FAKESESSIONS'][$new] = $GLOBALS['FAKESESSIONS'][$old] = arraycopy($_SESSION);
}

function session_id()
{
    return $GLOBALS['FAKESESSID'];
}

function session_destroy()
{
    if ($GLOBALS['FAKESESSID']) {
        $GLOBALS['FAKESESSIONS'][$GLOBALS['FAKESESSID']] = arraycopy($_SESSION);
    }
    $GLOBALS['FAKESESSID'] = null;
    $_SESSION = array();
}

function session_start()
{
    if (session_id()) {
        throw new \Exception("Session already started");
    }
    set_session_id(generate_session_id());
}

function session_status()
{
    if (!session_id()) {
        return PHP_SESSION_NONE;
    }
    return PHP_SESSION_ACTIVE;
}

/* helpers for the above */
function generate_session_id()
{
    return bin2hex(random_bytes(16));
}

function set_session_id($id)
{
    $GLOBALS['FAKESESSID'] = $id;
    if (isset($GLOBALS['FAKESESSIONS'][$id])) {
        $_SESSION = $GLOBALS['FAKESESSIONS'][$id];
    } else {
        $_SESSION = array();
    }
}

function arraycopy($array)
{
    $out = array();
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $out[$key] = arraycopy($value);
        } else {
            $out[$key] = $value;
        }
    }
    return $out;
}
