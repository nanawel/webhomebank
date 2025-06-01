var I18n = I18n || {};
I18n = function(language, currency) {
    this.setLocale(language);
    this.setCurrency(currency);
};
I18n.prototype = {
    locales: ['en-US', 'en'],
    currency: 'USD',
    timezone: 'UTC',

    setLocale: function(language) {
        if (typeof language == 'string') {
            this.locales = language.split(',');
        }
    },
    setCurrency: function(currency) {
        if (typeof currency == 'string') {
            this.currency = currency;
        }
    },

    formatDate: function(date) {
        for (var l in this.locales) {
            var locale = this.locales[l];
            try {
                if (typeof date == 'number' && parseInt(date)) {
                    date = new Date(parseInt(date));
                }
                else if (typeof date == 'string') {
                    date = new Date(date);
                }
                if (date instanceof Date) {
                    date = date.toLocaleString(locale, {
                        year    : 'numeric',
                        month   : '2-digit',
                        day     : '2-digit',
                        timeZone: this.timezone
                    });
                    break;
                }
            }
            catch (e) {
                console.log(e);
            }
        }
        return date;
    },

    formatNumber: function (number, decimal) {
        for (var l in this.locales) {
            var locale = this.locales[l];
            try {
                number = parseFloat(number).toLocaleString(locale, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                break;
            }
            catch (e) {
                console.log(e);
            }
        }
        return number;
    },

    formatCurrency: function (number, currency) {
        currency = typeof currency != 'undefined' ? currency : this.currency;
        for (var l in this.locales) {
            var locale = this.locales[l];
            try {
                number = parseFloat(number).toLocaleString(locale, {
                    style: 'currency',
                    currency: currency,
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                break;
            }
            catch (e) {
                console.log(e);
            }
        }
        return number;
    }
};

export default I18n;
