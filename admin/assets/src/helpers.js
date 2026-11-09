import {Loc} from "main.core";

const MessageLabels = {
    '[/]': '</span>',
    '[new]': '<span class="sp-red">',
    '[unknown]': '<span class="sp-blue">',
    '[installed]': '<span class="sp-green">',

    '[info]': '<span class="sp-blue">',
    '[warning]': '<span class="sp-red">',
    '[notice]': '<span class="sp-green">',
    '[error]': '<span class="sp-label sp-label-red">',
    '[success]': '<span class="sp-label sp-label-green">',

    '[blue]': '<span class="sp-blue">',
    '[pink]': '<span class="sp-pink">',
    '[green]': '<span class="sp-green">',
    '[red]': '<span class="sp-red">',
    '[yellow]': '<span class="sp-yellow">',

    '[label]': '<span class="sp-label">',
    '[label:blue]': '<span class="sp-label sp-label-blue">',
    '[label:pink]': '<span class="sp-label sp-label-pink">',
    '[label:red]': '<span class="sp-label sp-label-red">',
    '[label:green]': '<span class="sp-label sp-label-green">',
    '[label:yellow]': '<span class="sp-label sp-label-yellow">',

    '[b]': '<span class="sp-bold">',
    '[tab]': '<span class="sp-indent-20">',
}

const MessageTypes = {
    'new': 'sp-red',
    'installed': 'sp-green',
    'unknown': 'sp-blue',
    'info': 'sp-blue',
    'error': 'sp-red',
    'warning': 'sp-red',
    'success': 'sp-green',
    'notice': 'sp-green',
}

export function getMessage(text) {
    return Loc.hasMessage('SPRINT_MIGRATION_RU_' + text) ? Loc.getMessage('SPRINT_MIGRATION_RU_' + text) : text;
}

export function colorize(text) {
    let out = escapeHtml(String(text || ''));

    out = makeLinksHtml(out);
    for (const [key, val] of Object.entries(MessageLabels)) {
        out = out.replaceAll(key, val)
    }
    return out
}

export function colorbind(type) {
    return MessageTypes[type] ? {'class': MessageTypes[type]} : {};
}

export function progressMessage(item) {
    return `${item.message}: ${item.value} / ${item.total}`;
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