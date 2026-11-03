import {Loc} from "main.core";

export const VersionStatus = {
    'STATUS_INSTALLED': 'installed',
    'STATUS_NEW': 'new',
    'STATUS_UNKNOWN': 'unknown',
}
export const VersionAction = {
    'ACTION_UP': 'up',
    'ACTION_DOWN': 'down',
}

export const TextLabels = {
    '[/]': '</span>',
    '[tab]': '<span class="sp-indent-20">',
    '[unknown]': '<span class="sp-blue">',
    '[installed]': '<span class="sp-green">',
    '[new]': '<span class="sp-red">',
    '[blue]': '<span class="sp-blue">',
    '[info]': '<span class="sp-blue">',
    '[pink]': '<span class="sp-pink">',
    '[green]': '<span class="sp-green">',
    '[success]': '<span class="sp-green">',
    '[up]': '<span class="sp-green">',
    '[red]': '<span class="sp-red">',
    '[error]': '<span class="sp-red">',
    '[down]': '<span class="sp-red">',
    '[yellow]': '<span class="sp-yellow">',
    '[label]': '<span class="sp-label">',
    '[label:blue]': '<span class="sp-label sp-label-blue">',
    '[label:pink]': '<span class="sp-label sp-label-pink">',
    '[label:red]': '<span class="sp-label sp-label-red">',
    '[label:green]': '<span class="sp-label sp-label-green">',
    '[label:yellow]': '<span class="sp-label sp-label-yellow">',
    '[b]': '<span class="sp-bold">',
}

export function getMessage(text) {
    return Loc.hasMessage('SPRINT_MIGRATION_RU_' + text) ? Loc.getMessage('SPRINT_MIGRATION_RU_' + text) : text;
}

export function colorize(text) {
    let out = escapeHtml(String(text || ''));

    out = makeLinksHtml(out);
    for (const [key, val] of Object.entries(TextLabels)) {
        out = out.replaceAll(key, val)
    }
    return out
}

export function showContextMenu(node, menu) {
    if (!node.OPENER) {
        BX.adminShowMenu(node, menu, {active_class: 'sp-active', public_frame: '0'});
    } else {
        node.OPENER.SetMenu(menu);
    }
}

export function makeLinksHtml(msg) {
    if (!msg.includes('http')) {
        return msg;
    }

    return msg.replace(
        /(http|https):\/\/[a-zA-Z0-9\-.]+\.[a-zA-Z]{2,6}(\/[^\s()\[\]{}<>"'`]*)?/g,
        (url) => `<a target="_blank" rel="noopener noreferrer" href="${url}">${url}</a>`
    );

}

export function escapeHtml(str) {
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}