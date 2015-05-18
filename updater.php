<?php

//for 2.0.3 to 2.0.4

function Sprint_DeleteDirRec($path)
{
    if (!file_exists($path))
        return False;

    if (is_file($path))
    {
        @unlink($path);
        return True;
    }

    if ($handle = @opendir($path))
    {
        while (($file = readdir($handle)) !== false)
        {
            if ($file == "." || $file == "..") continue;

            if (is_dir($path."/".$file))
            {
                Sprint_DeleteDirRec($path."/".$file);
            }
            else
            {
                @unlink($path."/".$file);
            }
        }
    }
    @closedir($handle);
    @rmdir($path);
    return true;
}
Sprint_DeleteDirRec(__DIR__ . '/lang/');