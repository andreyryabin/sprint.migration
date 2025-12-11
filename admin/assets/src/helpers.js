import {Loc} from "main.core";


export function getMessage(text) {
    return Loc.hasMessage(text) ? Loc.getMessage(text) : text;
}