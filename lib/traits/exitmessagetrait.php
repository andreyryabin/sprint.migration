<?php

namespace Sprint\Migration\Traits;

use Sprint\Migration\Exceptions\MigrationException;

/**
 * @deprecated
 */
trait ExitMessageTrait
{
    /**
     * @deprecated
     * @param $cond
     * @param $msg
     *
     * @throws MigrationException
     */
    public function exitIf($cond, $msg)
    {
        if ($cond) {
            throw new MigrationException($msg);
        }
    }

    /**
     * @deprecated
     * @param $msg
     *
     * @throws MigrationException
     */
    public function exitWithMessage($msg)
    {
        throw new MigrationException($msg);
    }

    /**
     * @deprecated
     * @param $var
     * @param $msg
     *
     * @throws MigrationException
     */
    public function exitIfEmpty($var, $msg)
    {
        if (empty($var)) {
            throw new MigrationException($msg);
        }
    }
}
