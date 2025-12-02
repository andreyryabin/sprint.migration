<?php

namespace Sprint\Migration\Output;

class HtmlOutput extends AbstractOutput
{
    private array $colors = [
        '/'            => '</span>',
        'tab'          => '<span class="sp-indent-20">',
        'unknown'      => '<span class="sp-blue"">',
        'installed'    => '<span class="sp-green">',
        'new'          => '<span class="sp-red">',
        'blue'         => '<span class="sp-blue">',
        'pink'         => '<span class="sp-pink">',
        'green'        => '<span class="sp-green">',
        'up'           => '<span class="sp-green">',
        'red'          => '<span class="sp-red">',
        'down'         => '<span class="sp-red">',
        'yellow'       => '<span class="sp-yellow">',
        'label'        => '<span class="sp-label">',
        'label:blue'   => '<span class="sp-label sp-label-blue">',
        'label:pink'   => '<span class="sp-label sp-label-pink">',
        'label:red'    => '<span class="sp-label sp-label-red">',
        'label:green'  => '<span class="sp-label sp-label-green">',
        'label:yellow' => '<span class="sp-label sp-label-yellow">',
        'b'            => '<span class="sp-bold">',
    ];

    public function out(string $msg, ...$vars): void
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $msg = $this->applyFormat($msg);

        $class = $options['class'] ?? 'sp-out';

        echo $msg ? '<div class="' . $class . '">' . $msg . '</div>' : '';
    }

    public function outProgress(string $msg, int $val, int $total): void
    {
        $msg = '[label]' . $msg . ' ' . $val . ' / ' . $total . '[/]';

        echo '<div class="sp-progress">' . $this->applyFormat($msg) . '</div>';
    }

    private function applyFormat(string $msg)
    {
        foreach ($this->colors as $key => $val) {
            $msg = str_replace('[' . $key . ']', $val, $msg);
        }

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
