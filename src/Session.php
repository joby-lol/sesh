<?php
/* Sesh | https://gitlab.com/byjoby/sesh | MIT License */
namespace Sesh;

/**
 * Session management singleton class.
 *
 * Provides tools for managing session data, including user ID information.
 * Automatically regenerates session ID when user ID changes, or when enough
 * browser/connection information changes.
 *
 * Regenerates session IDs in a secure fashion, with a default five-minute
 * grace period for destroyed IDs, and throws an exception if a destroyed
 * session is accessed after the grace period.
 */
class Session implements SessionInterface
{
    use SessionTrait,SessionPublicTrait;

    protected static $instances = [];

    protected $magicNumber = 8;
    protected $destroyAllOnCreate = false;
    protected $destroyedSessionGracePeriod = 600;

    protected $changeImpact = array(
        'time_touch' => 1,
        'user_ip' => 1,
        'user_ua' => 5,
        'user_https' => 10
    );
    protected $regenThreshold = 2;
    protected $deauthThreshold = 12;

    protected $regened = false;

    public static function &getInstance(string $uniqueID = null) : SessionInterface
    {
        $key = md5(serialize($uniqueID));
        if (!static::$instances[$key]) {
            $class = get_called_class();
            static::$instances[$key] = new $class($uniqueID);
        }
        return static::$instances[$key];
    }

    public function userID(string $id = null) : ?string
    {
        if ($id) {
            $this->session['user_id'] = $id;
        }
        if ($id === false) {
            $this->session['user_id'] = null;
        }
        return $this->session['user_id'];
    }

    public function deauthorize()
    {
        $this->session['user_id'] = null;
        $this->sessionTokenCleanup(true);
    }

    protected function __construct(string $uniqueID = null)
    {
        $this->sessionTraitInit($uniqueID);
        if (!$this->session || $this->session['magic_number'] < $this->magicNumber) {
            $this->createSession();
        }
        //check for destroyed session
        if (isset($this->session['destroyed'])) {
            if ($this->session['destroyed'] < time() - $this->destroyedSessionGracePeriod) {
                $userID = $this->session['destroyed_user_id'] = $this->userID();
                $oldSID = $this->session['destroyed_user_sid'] = $this->session['user_sid'];
                $newSID = $this->session['user_sid'] = $this->sessionGenerateSID();
                $this->deauthorize();
                $this->regenerateID();
                throw new DestroyedSessionAccessException($oldSID, $newSID, $userID);
            }
        }
        //update this session and see how much has changed
        $changeLevel = $this->updateSession();
        //check whether changes warrant session regeneration or deauthorization
        if ($changeLevel >= $this->regenThreshold) {
            $this->regenerateID();
        }
        if ($changeLevel >= $this->deauthThreshold) {
            $this->deauthorize();
        }
    }

    public function regenerateID()
    {
        $this->session['destroyed'] = time();
        session_regenerate_id();
        unset($this->session['destroyed']);
    }

    protected function touchSession()
    {
        $this->session['time_touch'] = time();
    }

    protected function updateSession()
    {
        $level = 0;
        $new = array(
            'time_touch' => time(),
            'user_ip' => $this->sessionUserIP(),
            'user_ua' => $this->sessionUserUA(),
            'user_https' => $this->sessionUserHTTPS(),
        );
        foreach ($new as $key => $value) {
            if ($value != $this->session[$key]) {
                $this->session[$key] = $value;
                if (isset($this->changeImpact[$key])) {
                    $level = $this->changeImpact[$key];
                }
            }
        }
        return $level;
    }

    protected function createSession()
    {
        if ($this->destroyAllOnCreate) {
            session_destroy();
            session_start();
            $this->sessionTraitInit(null, true);
        }
        $this->session = array(
            'magic_number' => $this->magicNumber,
            'time_init' => time(),
            'time_touch' => time(),
            'user_sid' => $this->sessionGenerateSID(),
            'user_ip' => $this->sessionUserIP(),
            'user_ua' => $this->sessionUserUA(),
            'user_https' => $this->sessionUserHTTPS(),
            'user_id' => null,
            'user_rememberme' => false
        );
        $this->regened = true;
    }

    protected function sessionUserHTTPS()
    {
        return boolval(@$_SERVER['HTTPS']);
    }

    protected function sessionUserIP()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    protected function sessionUserUA()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    protected function sessionGenerateSID()
    {
        return bin2hex(random_bytes(16));
    }
}
