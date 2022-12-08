<?php

namespace Sprint\Migration\Traits;

use Sprint\Migration\Exceptions\MigrationException;

trait ExitMessageTrait
{
    /**
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
     * @param $msg
     *
     * @throws MigrationException
     */
    public function exitWithMessage($msg)
    {
        throw new MigrationException($msg);
    }

    /**
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
