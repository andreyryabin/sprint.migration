import {Loc} from "main.core";
import {markRaw} from "ui.vue3";

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

export function colorclass(type) {
    return MessageTypes[type] ? {'class': MessageTypes[type]} : {};
}

export function cleanAttrs(attr) {
    //Убирает: null, undefined, '', NaN, 0, false
    return Object.fromEntries(
        Object.entries(attr).filter(([_, v]) =>
            !!v
        )
    );
}

export function cleanAttrs2(attr) {
    //Убирает: null, undefined, '', NaN.
    //Сохраняет: 0 и false
    return Object.fromEntries(
        Object.entries(attr).filter(([_, v]) =>
            v !== null && v !== undefined && v !== '' && !Number.isNaN(v)
        )
    );
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

export function makeString(val) {
    return val == null ? '' : String(val);
}


export function filterOptions(search, options, keys = ['id', 'name', 'group']) {
    if (search) {
        const s1 = makeString(search).toLowerCase();
        return options.filter((item) => {
            const s2 = keys
                .map((key) => makeString(item[key]))
                .join(' ')
                .toLowerCase();
            return s2.includes(s1);
        });
    }
    return options;
}

export function groupOptions(options, gKey='group') {
    let tree = {};

    options.forEach((item) => {
        if (!tree[item[gKey]]) {
            tree[item[gKey]] = [];
        }
        tree[item[gKey]].push(item);
    });

    return tree;
}

export function extractResponse(resp, messages, progress) {
    if (resp.data && resp.data.progress) {
        const next = resp.data.progress.map(markRaw);
        progress.splice(0, progress.length, ...next);
    }

    if (resp.data && resp.data.messages) {
        messages.push(...resp.data.messages.map(markRaw));
    }

    if (resp && resp.errors) {
        resp.errors.forEach(item => messages.push(markRaw({
            type: 'error',
            message: item.message
        })));
    }

    if (resp?.status === 'error' && resp.data?.ajaxRejectData?.xhr?.responseText) {
        messages.push(markRaw({
            type: 'error',
            message: resp.data?.ajaxRejectData?.xhr?.responseText
        }));
    }
}