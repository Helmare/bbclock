/**
 * A class which contains information and events for a calendar component.
 */
class Calendar extends EventTarget {
    constructor(element) {
        super();
        var now = new Date();

        this.month = now.getMonth();
        this.year = now.getFullYear();
        this.selected = new Date(0);
        this.element = element;
    }

    /**
     * Initialize calendar by creating header elements and binding events.
     * @returns {Calendar} this object for chaining.
     */
    init() {
        var self = this;
        var calendar = this.element;

        // Create header elements.
        var lastMonth = document.createElement('div');
        lastMonth.innerHTML = '<i class="material-icons">chevron_left</i>';
        lastMonth.classList.add('header-btn');
        lastMonth.addEventListener('click', () => self.prevMonth());

        var nextMonth = document.createElement('div');
        nextMonth.innerHTML = '<i class="material-icons">chevron_right</i>';
        nextMonth.classList.add('header-btn');
        nextMonth.addEventListener('click', () => self.nextMonth());

        var header = document.createElement('div');
        header.classList.add('header');

        var footer = document.createElement('div');
        footer.innerText = '&nbsp;';
        footer.classList.add('footer');

        // Append calendar.
        calendar.appendChild(lastMonth);
        calendar.appendChild(header);
        calendar.appendChild(nextMonth);
        calendar.appendChild(footer);

        // Add day header elements.
        DAY_NAMES.forEach((e, i) => {
            var dayHeader = document.createElement('div');
            dayHeader.innerText = e.substring(0, 3);
            dayHeader.classList.add('day-header');

            calendar.appendChild(dayHeader);
        });

        // Initial render.
        this.render();
        this.select(new Date());
        return this;
    }

    /**
     * Renders the calendar's days for the specified month.
     */
    render() {
        var self = this;
        var calendar = this.element;

        this.dispatchEvent(new Event('pre-render'));

        // Set header month name.
        calendar.querySelector('.header').innerText = MONTH_NAMES[this.month] + ' ' + this.year;

        // Clear previous days.
        calendar.querySelectorAll('.day').forEach(function(e, i) {
            calendar.removeChild(e);
        });

        // Render new days.
        var startDay = new Date(this.year, this.month, 1).getDay();
        for(var i = 0; i < 42; i++) {
            var day = document.createElement('div');
            day.classList.add('day');

            // Find current date.
            var date = new Date(this.year, this.month, i - startDay + 1);

            // Sets attributes for date
            day.setAttribute('date', date.toLocaleISOString());

            // Sets whether date is selected.
            if(date.isSimilarTo(this.selected)) day.setAttribute('selected', '');

            // Sets whether this is from last month or next month.
            if(date.getMonth() < this.month) {
                day.setAttribute('last-month', '');
            }
            else if(date.getMonth() > this.month) {
                day.setAttribute('next-month', '');
            }

            // On Click
            day.addEventListener('click', function() {
                if(this.hasAttribute('last-month') || this.hasAttribute('next-month')) return;
                self.select(new Date(this.getAttribute('date')));
            });

            // Append to parent.
            day.innerHTML = '<p>' + date.getDate() + '</p>';
            calendar.appendChild(day);
        }

        // Post render event.
        this.dispatchEvent(new Event('post-render'));
    }

    /**
     * Sets the selected property, loops through all the days to render properly, and calls the onSelect event.
     * @param {Date} date 
     */
    select(date) {
        date = date.removeTime();
        if(date.getTime() == this.selected.getTime()) return;
        this.selected = date;

        var days = this.element.querySelectorAll('.day');
        var startDay = new Date(this.year, this.month, 1).getDay();

        for(var i = 0; i < 42; i++) {
            var day = days[i];
            var date = new Date(this.year, this.month, i - startDay + 1);

            if(date.isSimilarTo(this.selected)) {
                day.setAttribute('selected', '');
                this.dispatchEvent(new CustomEvent('select', { detail: {element: day, date: this.selected }}));
            }
            else {
                day.removeAttribute('selected');
            }
        }
    }

    /**
     * Moves the calendar to the next month.
     */
    nextMonth() {
        this.month += 1;
        if(this.month > 11) {
            this.month = 0;
            this.year += 1;
        }

        this.render();
    }
    /**
     * Moves the calendar to the previous month.
     */
    prevMonth() {
        this.month -= 1;
        if(this.month < 0) {
            this.month = 11;
            this.year -= 1;
        }

        this.render();
    }
}