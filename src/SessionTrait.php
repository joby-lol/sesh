<?php
/* Sesh | https://gitlab.com/byjoby/sesh | MIT License */
namespace Sesh;

/**
 * Provides tools for compartmentalizing session data by class, and further by
 * unique IDs within a class if necessary. Also provides tools for generating
 * and checking random tokens (such as for CSRF protection), as well as flash
 * values that become available on the next request and are removed only once
 * they are accessed.
 *
 * Note that flash values in this library work somewhat differently than you may
 * be used to from other tools. Flash values do not remove simply at the next
 * request. They don't become *available* until the next request, but remain
 * available on all subsequent requests until they are accessed. They only unset
 * once accessed.
 *
 * This is to avoid losing flash values to things like Ajax requests or
 * concurrent access to different parts of a site.
 */
trait SessionTrait
{
    protected $session;
    private $sessionTokens;
    private $sessionFlash;
    private $sessionKey;
    private $sessionUniqueKey;

    protected function sessionTraitInit(string $uniqueID = null, $force = false)
    {
        //start session if necessary
        if (session_status() == PHP_SESSION_NONE) {
            @session_start();//we don't want errors in our unit tests
        }
        //create a pointer to this class/uniques's session storage in $this->session
        if ($force || !$this->session) {
            $this->sessionKey = get_called_class();
            if (!isset($_SESSION[$this->sessionKey])) {
                $_SESSION[$this->sessionKey] = array(
                    'storage' => array(),
                    'tokens' => array(),
                    'flash' => array(
                        'now' => array(),
                        'next' => array()
                    ),
                    'unique' => array()
                );
            }
            //if there's no unique id, link into class space
            if (!$uniqueID) {
                $this->session =& $_SESSION[$this->sessionKey]['storage'];
                $this->sessionTokens =& $_SESSION[$this->sessionKey]['tokens'];
                $this->sessionFlash =& $_SESSION[$this->sessionKey]['flash'];
            } else {
                //otherwise link into unique storage
                $this->sessionUniqueKey = $uniqueID;
                if (!isset($_SESSION[$this->sessionKey]['unique'][$this->sessionUniqueKey])) {
                    $_SESSION[$this->sessionKey]['unique'][$this->sessionUniqueKey] = array(
                        'storage' => array(),
                        'tokens' => array(),
                        'flash' => array(
                            'now' => array(),
                            'next' => array()
                        )
                    );
                }
                $this->session =& $_SESSION[$this->sessionKey]['unique'][$this->sessionUniqueKey]['storage'];
                $this->sessionTokens =& $_SESSION[$this->sessionKey]['unique'][$this->sessionUniqueKey]['tokens'];
                $this->sessionFlash =& $_SESSION[$this->sessionKey]['unique'][$this->sessionUniqueKey]['flash'];
            }
        }
        //clean up tokens
        $this->sessionTokenCleanup();
        //advance "next" flash items into "now"
        $this->sessionAdvanceFlash();
    }

    protected function sessionAdvanceFlash()
    {
        if (!is_array($this->sessionFlash['now'])) {
            $this->sessionFlash['now'] = [];
        }
        if (!is_array($this->sessionFlash['next'])) {
            $this->sessionFlash['next'] = [];
        }
        $this->sessionFlash['now'] = array_merge_recursive(
            $this->sessionFlash['now'],
            $this->sessionFlash['next']
        );
        $this->sessionFlash['next'] = array();
    }

    protected function sessionTokenCleanup($destroy = false)
    {
        foreach ($this->sessionTokens as $key => $value) {
            if ($destroy || $value['expires'] < time()) {
                unset($this->sessionTokens[$key]);
            }
        }
    }

    protected function sessionGetToken($name, $ttl=3600*24)
    {
        if (@$this->sessionTokens[$name]['ttl'] != $ttl) {
            $this->sessionTokens[$name] = array(
                'token' => bin2hex(random_bytes(32))
            );
        }
        $this->sessionTokens[$name]['expires'] = time()+$ttl;
        $this->sessionTokens[$name]['ttl'] = $ttl;
        return $this->sessionTokens[$name]['token'];
    }

    protected function sessionCheckToken(string $name, $value, $keep=false)
    {
        $out = $value == @$this->sessionTokens[$name]['token'];
        if ($out && !$keep) {
            unset($this->sessionTokens[$name]);
        }
        return $out;
    }

    protected function sessionSetFlash(string $name, $value)
    {
        $this->sessionFlash['next'][$name] = $value;
    }

    protected function sessionPushFlash(string $name, $value)
    {
        if (!@is_array($this->sessionFlash[$name])) {
            $this->sessionFlash['next'][$name] = array();
        }
        $this->sessionFlash['next'][$name][] = $value;
    }

    protected function sessionGetFlash(string $name, bool $keep = false)
    {
        if (!($value = @$this->sessionFlash['now'][$name])) {
            return null;
        }
        if (!$keep) {
            unset($this->sessionFlash['now'][$name]);
        }
        return $value;
    }
}
