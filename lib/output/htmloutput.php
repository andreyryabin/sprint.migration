<?php

namespace Sprint\Migration\Output;

class HtmlOutput extends AbstractOutput
{

    private array $messages = [];

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function out(string $msg, ...$vars): void
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $msg = $this->applyFormat($msg);

        $this->messages[] = $msg;
    }

    public function outProgress(string $msg, int $val, int $total): void
    {
        $msg = '[label]' . $msg . ' ' . $val . ' / ' . $total . '[/]';

        echo '<div class="sp-progress">' . $this->applyFormat($msg) . '</div>';
    }

    private function applyFormat(string $msg)
    {
        if (str_contains($msg, 'http')) {
            $msg = $this->makeLinksHtml($msg);
        }

        return $msg;
    }

    private static function makeLinksHtml($msg)
    {
        $regex = "/(http|https):\/\/[a-zA-Z0-9\-.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
        if (preg_match_all($regex, $msg, $urls)) {
            foreach (array_unique($urls[0]) as $url) {
                $msg = str_replace($url, '<a target="_blank" href="' . $url . '">' . $url . '</a>', $msg);
            }
        }

        return $msg;
    }
}
