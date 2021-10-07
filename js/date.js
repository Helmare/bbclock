const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saterday'];
const MILLIS_PER_HOUR = 3600000.0;

/**
 * Checks whether two dates are similar.
 * 
 * @param {Date} date 
 */
Date.prototype.isSimilarTo = function(date) {
    return this.getFullYear() == date.getFullYear() &&
           this.getMonth() == date.getMonth() &&
           this.getDate() == date.getDate();
}
/**
 * Removes the time component of a date.
 */
Date.prototype.removeTime = function() {
    return new Date(this.getFullYear(), this.getMonth(), this.getDate())
}
/**
 * Removes one minute from the current time.
 */
Date.prototype.lessThan = function() {
    return new Date(this.getTime() - 60000);
}

/**
 * Gets which month it is as string form.
 */
Date.prototype.getMonthString = function() {
    return MONTH_NAMES[this.getMonth()]
}

/**
 * Converts a date to a time string.
 */
Date.prototype.toTimeString = function() {
    var hour = this.getHours();
    var minute = this.getMinutes().toString();
    var am = 'a';

    if(hour >= 12) {
        hour -= 12;
        am = 'p';
    }
    if(hour == 0) hour = 12;

    if(minute.length == 1) minute = '0' + minute;

    return hour + ':' + minute + am;
}
Date.prototype.toMilitaryTimeString = function() {
    var hour = this.getHours().toString();
    var minute = this.getMinutes().toString();
    if(hour.length == 1) hour = '0' + hour;
    if(minute.length == 1) minute = '0' + minute;

    return hour + ':' + minute;
}
/**
 * Converts a date to a date string without a year.
 */
Date.prototype.toYearlessString = function() {
    var str = '';
    str += this.getMonthString() + ' ';

    var date = this.getDate();
    str += date;
    if(date <= 3 || date >= 21) {
        if(str.endsWith('1')) str += 'st';
        else if(str.endsWith('2')) str += 'nd';
        else if(str.endsWith('3')) str += 'rd';
        else str += 'th';
    }
    else str += 'th';

    return str;
}
/**
 * Converts to ISO 8601 string with offset.
 */
Date.prototype.toLocaleISOString = function() {
    var tzo = -this.getTimezoneOffset(),
        dif = tzo >= 0 ? '+' : '-',
        pad = function(num) {
            var norm = Math.floor(Math.abs(num));
            return (norm < 10 ? '0' : '') + norm;
        };
    return this.getFullYear() +
        '-' + pad(this.getMonth() + 1) +
        '-' + pad(this.getDate()) +
        'T' + pad(this.getHours()) +
        ':' + pad(this.getMinutes()) +
        ':' + pad(this.getSeconds()) +
        dif + pad(tzo / 60) +
        ':' + pad(tzo % 60);
}
