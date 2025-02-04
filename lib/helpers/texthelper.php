<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class TextHelper extends Helper
{
    public function htmlspecialcharsDecodeRecursive($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->htmlspecialcharsDecodeRecursive($value);
            }
        } else {
            $data = htmlspecialchars_decode($data);
        }
        return $data;
    }
}
