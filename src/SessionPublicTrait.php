<?php
/* Sesh | https://gitlab.com/byjoby/sesh | MIT License */
namespace Sesh;

/**
 * This trait just publicly exposes the functions in SessionTrait
 */
trait SessionPublicTrait
{
    public function getToken(string $name, int $ttl=3600*24) : string
    {
        return $this->sessionGetToken($name, $ttl);
    }

    public function checkToken(string $name, $value, bool $keep=false) : bool
    {
        return $this->sessionCheckToken($name, $value, $keep);
    }

    public function setFlash(string $name, $value)
    {
        return $this->sessionSetFlash($name, $value);
    }

    public function pushFlash(string $name, $value)
    {
        return $this->sessionPushFlash($name, $value);
    }

    public function getFlash(string $name, bool $keep = false)
    {
        return $this->sessionGetFlash($name, $keep);
    }
}
