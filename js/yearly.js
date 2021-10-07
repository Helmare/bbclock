const YEAR_START_MONTH = 0;
const YEAR_START_DAY = 1;

document.addEventListener('DOMContentLoaded', function() {
    var punches = document.querySelector('.punch-container');
    var start = new Date(_year, YEAR_START_MONTH, YEAR_START_DAY);
    var end = new Date(_year + 1, YEAR_START_MONTH, YEAR_START_DAY).lessThan();

    BBClockAPI.fetchPunches(start, end).then((workTimes) => {
        workTimes.arr.reverse();
        workTimes.forEach(function(workTime, i) {
            punches.appendChild(workTime.toElement());
        });

        punches.querySelector('.info').innerHTML = workTimes.percent(start, end, ['DEFAULT']) + '% placement for ' + _year + ' (' + workTimes.percent(start, end) + '% total)';
    });
});