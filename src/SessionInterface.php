<?php
/* Sesh | https://gitlab.com/byjoby/sesh | MIT License */
namespace Sesh;

interface SessionInterface
{
    public static function &getInstance() : SessionInterface;
    public function userID(string $id = null) : ?string;
    public function deauthorize();
    public function regenerateID();

    /* these all come from SessionPublicTrait */
    public function getToken(string $name, int $ttl=3600*24) : string;
    public function checkToken(string $name, $value, bool $keep=false) : bool;
    public function setFlash(string $name, $value);
    public function pushFlash(string $name, $value);
    public function getFlash(string $name);
}
