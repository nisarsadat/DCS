export const LocaleConfigs = {
    fa: {
        calendar: "jalali", // "jalali" | "gregorian"
        weekdays: ["ش", "ی", "د", "س", "چ", "پ", "ج"],
        months: [
            "حمل",
            "ثور",
            "جوزا",
            "سرطان",
            "اسد",
            "سنبله",
            "میزان",
            "عقرب",
            "قوس",
            "جدی",
            "دلو",
            "حوت",
        ],
        dir: {
            input: "ltr", 
            picker: "ltr", 
        },
        translations: {
            label: "شمسی",
            picker: "تقویم شمسی",
            prevMonth: "ماه قبل",
            nextMonth: "ماه بعد",
            now: "هم اکنون",
            submit: "تایید",
        },
        inputFormat: "jYYYY/jMM/jDD",
        displayFormat: "jYYYY/jMM/jDD",
    },
  
};

export const MiladiLocaleConfigs = {
    fa: {
        calendar: "gregorian",
        weekdays: ["ی", "د", "س", "چ", "پ", "ج", "ش"],
        months: [
            "جنوری",
            "فبروری",
            "مارچ",
            "اپریل",
            "می",
            "جون",
            "جولای",
            "اگست",
            "سپتمبر",
            "اکتوبر",
            "نومبر",
            "دسمبر",
        ],
        dir: {
            input: "ltr",
            picker: "ltr",
        },
        translations: {
            label: "میلادی",
            picker: "تقویم میلادی",
            text: "تقویم میلادی",
            prevMonth: "ماه قبل",
            nextMonth: "ماه بعد",
            now: "هم اکنون",
            submit: "تایید",
        },
        inputFormat: "YYYY/MM/DD",
        displayFormat: "",
    },
};
export const styles={
    "primary-color": "#a78bfb",
    "secondary-color": "#ddd6fe",
    "in-range-background": "#ddd6fe",
};