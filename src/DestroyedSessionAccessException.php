<?php
/* Sesh | https://gitlab.com/byjoby/sesh | MIT License */
namespace Sesh;

/**
 * Thrown when a destroyed session is accessed outside the grace period.
 *
 * If the underlying system allows it, catching one of these should lead to any
 * other sessions attached to the userID
 */
class DestroyedSessionAccessException extends \Exception
{
    public $userID;
    public $oldSID;
    public $newSID;

    public function __construct($oldSID, $newSID, $userID)
    {
        $this->userID = $userID;
        $this->oldSID = $oldSID;
        $this->newSID = $newSID;
        parent::__construct("New SID \"$newSID\" assigned to replace destroyed SID \"$oldSID\" which had the user id \"$userID\"");
    }
}
