document.addEventListener('DOMContentLoaded', function() {
    var punches = document.querySelector('.punch-container');

    var cal = new Calendar(document.querySelector('.calendar'));

    cal.addEventListener('post-render', () => {
        var days = cal.element.querySelectorAll('.day');
        var start = new Date(Date.parse(days[0].getAttribute('date')));
        var end = new Date(Date.parse(days[41].getAttribute('date')) + 86400000);

        BBClockAPI.fetchPunches(start, end).then((workTimes) => {
            days.forEach((day, i) => {
                var start = new Date(Date.parse(day.getAttribute('date')));
                var end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 1);

                var attr = '';
                var assoc = 'DEFAULT';
                workTimes.forEach((workTime, i) => {
                    if(attr == 'overnight' || assoc != 'DEFAULT') return;

                    if(workTime.overlaps(start, end)) {
                        assoc = workTime.in.assoc;
                        attr = (assoc == 'DEFAULT' && workTime.isOvernight()) ? 'overnight' : 'punched';
                    }
                });

                if(attr.length > 0) {
                    day.setAttribute(attr, '');
                    day.setAttribute('assoc', assoc);
                }
            });

            var footer = cal.element.querySelector('.footer');
            footer.innerHTML = (workTimes.duration(
                new Date(cal.year, cal.month, 1),
                new Date(new Date(cal.year, cal.month + 1, 1).getTime() - 60000),
                ['DEFAULT']
            ) / MILLIS_PER_HOUR).toFixed(3) + ' hours in placement this month.';
        });
    });

    // Select Event
    cal.addEventListener('select', (e) => {
        var date = e.detail.date;
        var start = date.removeTime();
        var end = new Date(date.getFullYear(), date.getMonth(), date.getDate() + 1);

        BBClockAPI.fetchPunches(start, end).then((workTimes) => {
            if(workTimes.length() == 0) {
                punches.innerHTML = '<p class="punch-message">No punches recorded for this day.</p>';
            }
            else {
                punches.innerHTML = '';
                workTimes.forEach((workTime, i) => {
                    punches.appendChild(workTime.toElement())
                });
            }
        });
    });

    cal.init();
});