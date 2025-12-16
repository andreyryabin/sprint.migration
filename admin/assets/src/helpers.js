import {Loc} from "main.core";


export function getMessage(text) {
    return Loc.hasMessage(text) ? Loc.getMessage(text) : text;
}

export function getResponseErrors(response) {
    if (response && response.errors) {
        return response.errors.map(item => item.message).join(', ');
    }
    return '';
}