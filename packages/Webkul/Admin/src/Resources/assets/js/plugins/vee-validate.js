/**
 * We are defining all the global rules here and configuring
 * all the `vee-validate` settings.
 */
import { configure, defineRule, Field, Form, ErrorMessage } from "vee-validate";
import { localize, setLocale } from "@vee-validate/i18n";
import ar from "@vee-validate/i18n/dist/locale/ar.json";
import de from "@vee-validate/i18n/dist/locale/de.json";
import en from "@vee-validate/i18n/dist/locale/en.json";
import es from "@vee-validate/i18n/dist/locale/es.json";
import fr from "@vee-validate/i18n/dist/locale/fr.json";
import hi_IN from "../../locales/hi_IN.json";
import ja from "@vee-validate/i18n/dist/locale/ja.json";
import nl from "@vee-validate/i18n/dist/locale/nl.json";
import mn from "@vee-validate/i18n/dist/locale/mn.json";
import ru from "@vee-validate/i18n/dist/locale/ru.json";
import zh_CN from "@vee-validate/i18n/dist/locale/zh_CN.json";
import * as AllRules from '@vee-validate/rules';

window.defineRule = defineRule;

export default {
    install: (app) => {
        /**
         * Global components registration;
         */
        app.component("VForm", Form);
        app.component("VField", Field);
        app.component("VErrorMessage", ErrorMessage);

        window.addEventListener("load", () => setLocale(document.documentElement.attributes.lang.value));

        /**
         * Registration of all global validators.
         */
        Object.keys(AllRules)
            .filter(rule => typeof AllRules[rule] === 'function')
            .forEach(rule => defineRule(rule, AllRules[rule]));

        /**
         * This regular expression allows phone numbers with the following conditions:
         * - The phone number can start with an optional "+" sign.
         * - After the "+" sign, there should be one or more digits.
         *
         * This validation is sufficient for global-level phone number validation. If
         * someone wants to customize it, they can override this rule.
         */
        defineRule("phone", (value) => {
            if (! value || ! value.length) {
                return true;
            }

            if (! /^\+?\d+$/.test(value)) {
                return false;
            }

            return true;
        });

        defineRule("address", (value) => {
            if (!value || !value.length) {
                return true;
            }

            if (
                !/^[a-zA-Z0-9\s.\/*'\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\u0590-\u05FF\u3040-\u309F\u30A0-\u30FF\u0400-\u04FF\u0D80-\u0DFF\u3400-\u4DBF\u2000-\u2A6D\u00C0-\u017F\u0980-\u09FF\u0900-\u097F\u4E00-\u9FFF,\(\)-]{1,60}$/iu.test(
                    value
                )
            ) {
                return false;
            }

            return true;
        });

        defineRule("decimal", (value, { decimals = '*', separator = '.' } = {}) => {
            if (value === null || value === undefined || value === '') {
                return true;
            }

            if (Number(decimals) === 0) {
                return /^-?\d*$/.test(value);
            }

            const regexPart = decimals === '*' ? '+' : `{1,${decimals}}`;
            const regex = new RegExp(`^[-+]?\\d*(\\${separator}\\d${regexPart})?([eE]{1}[-]?\\d+)?$`);

            return regex.test(value);
        });

        defineRule("required_if", (value, { condition = true } = {}) => {
            if (condition) {
                if (value === null || value === undefined || value === '') {
                    return false;
                }
            }

            return true;
        });

        defineRule("", () => true);

        configure({
            /**
             * Built-in error messages and custom error messages are available. Multiple
             * locales can be added in the same way.
             */
            generateMessage: localize({
                ar_AE: {
                    ...ar,
                    messages: {
                        ...ar.messages,
                        phone: "يجب أن يكون هذا {field} رقم هاتف صالحًا",
                    },
                },
        
                de_DE: {
                    ...de,
                    messages: {
                        ...de.messages,
                        phone: "Dieses {field} muss eine gültige Telefonnummer sein.",
                    },
                },

                en: {
                    ...en,
                    messages: {
                        ...en.messages,
                        phone: "This {field} must be a valid phone number",
                    },
                },

                en_US: {
                    code: "en_US",
                    messages: {
                        ...en.messages,
                        phone: "This {field} must be a valid phone number",
                    },
                },

                es_ES: {
                    ...es,
                    messages: {
                        ...es.messages,
                        phone: "Este {field} debe ser un número de teléfono válido.",
                    },
                },
        
                fr_FR: {
                    ...fr,
                    messages: {
                        ...fr.messages,
                        phone: "Ce {field} doit être un numéro de téléphone valide.",
                    },
                },
        
                hi_IN: {
                    ...hi_IN,
                    messages: {
                        ...hi_IN.messages,
                        phone: "यह {field} कोई मान्य फ़ोन नंबर होना चाहिए।",
                    },
                },
        
                ja_JP: {
                    ...ja,
                    messages: {
                        ...ja.messages,
                        phone: "この{field}は有効な電話番号である必要があります。",
                    },
                },

                nl_NL: {
                    ...nl,
                    messages: {
                        ...nl.messages,
                        phone: "Dit {field} moet een geldig telefoonnummer zijn.",
                    },
                },

                mn_MN: {
                    ...mn,
                    messages: {
                        ...mn.messages,
                        phone: "Энэ {field} хүчинтэй утасны дугаар байх ёстой.",
                    },
                },

                ru_RU: {
                    ...ru,
                    messages: {
                        ...ru.messages,
                        phone: "Это {field} должно быть действительным номером телефона.",
                    },
                },
        
                zh_CN: {
                    ...zh_CN,
                    messages: {
                        ...zh_CN.messages,
                        phone: "这个 {field} 必须是一个有效的电话号码。",
                    },
                },
            }),

            validateOnBlur: true,
            validateOnInput: true,
            validateOnChange: true,
        });
    },
};
