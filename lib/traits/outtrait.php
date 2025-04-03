<?php

namespace Sprint\Migration\Traits;

use Sprint\Migration\Out;
use Throwable;

trait OutTrait
{

    protected function out($msg, ...$vars)
    {
        Out::out(...func_get_args());
    }

    protected function outIf($cond, $msg, ...$vars)
    {
        Out::outIf(...func_get_args());
    }

    protected function outProgress($msg, $val, $total)
    {
        Out::outProgress(...func_get_args());
    }

    protected function outNotice($msg, ...$vars)
    {
        Out::outNotice(...func_get_args());
    }

    protected function outNoticeIf($cond, $msg, ...$vars)
    {
        Out::outNoticeIf(...func_get_args());
    }

    protected function outInfo($msg, ...$vars)
    {
        Out::outInfo(...func_get_args());
    }

    protected function outInfoIf($msg, ...$vars)
    {
        Out::outInfoIf(...func_get_args());
    }

    protected function outSuccess($msg, ...$vars)
    {
        Out::outSuccess(...func_get_args());
    }

    protected function outSuccessIf($msg, ...$vars)
    {
        Out::outSuccessIf(...func_get_args());
    }

    protected function outWarning($msg, ...$vars)
    {
        Out::outWarning(...func_get_args());
    }

    protected function outWarningIf($msg, ...$vars)
    {
        Out::outWarningIf(...func_get_args());
    }

    protected function outError($msg, ...$vars)
    {
        Out::outError(...func_get_args());
    }

    protected function outErrorIf($msg, ...$vars)
    {
        Out::outErrorIf(...func_get_args());
    }

    protected function outDiff($arr1, $arr2)
    {
        Out::outDiff(...func_get_args());
    }

    protected function outDiffIf($cond, $arr1, $arr2)
    {
        Out::outDiffIf(...func_get_args());
    }

    protected function outMessages($messages = [])
    {
        Out::outMessages(...func_get_args());
    }
    protected function outException(Throwable $exception)
    {
        Out::outException(...func_get_args());
    }
}
