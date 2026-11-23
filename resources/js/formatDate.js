import moment from "moment-jalaali";
import { useSettingRepository } from "@/store/SettingRepository";

const DATE_PATTERN =
    /^(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})(?:[ T](\d{1,2}):(\d{2})(?::(\d{2}))?)?/;

function parseAppDate(value) {
    const text = String(value).trim();
    const match = text.match(DATE_PATTERN);
    if (!match) {
        const parsed = moment(text);
        return parsed.isValid() ? parsed : null;
    }

    const year = Number(match[1]);
    const month = match[2].padStart(2, "0");
    const day = match[3].padStart(2, "0");
    const hasTime = match[4] != null;
    const clock = hasTime
        ? ` ${match[4].padStart(2, "0")}:${match[5]}`
        : "";

    if (year < 1700) {
        const jalali = moment(
            `${year}/${month}/${day}${clock}`,
            hasTime ? "jYYYY/jMM/jDD HH:mm" : "jYYYY/jMM/jDD"
        );
        return jalali.isValid() ? jalali : null;
    }

    const gregorian = moment(
        `${year}-${month}-${day}${clock}`,
        hasTime ? "YYYY-MM-DD HH:mm" : "YYYY-MM-DD"
    );
    return gregorian.isValid() ? gregorian : null;
}

export function formatDisplayDate(value) {
    if (value == null || value === "") return value;
    if (typeof value === "number") return value;

    const text = String(value).trim();
    const hasTime = /[ T]\d{1,2}:\d{2}/.test(text);
    const parsed = parseAppDate(text);
    if (!parsed) return value;

    let miladi = false;
    try {
        miladi = useSettingRepository().dateType === "gregorian";
    } catch {
        miladi = localStorage.getItem("dateType") === "gregorian";
    }

    if (miladi) {
        return parsed.format(hasTime ? "YYYY/MM/DD HH:mm" : "YYYY/MM/DD");
    }

    return parsed.format(hasTime ? "jYYYY/jMM/jDD HH:mm" : "jYYYY/jMM/jDD");
}
