/**
 * A class which represents a single punch.
 */
class Punch {
    /**
     * Creates a Punch instance.
     *  
     * @param {number} id 
     * @param {string} auth 
     * @param {Date} time 
     * @param {string} assoc
     */
    constructor(id, auth, time, assoc) {
        this.id = id;
        this.auth = auth;
        this.time = time;
        this.assoc = assoc;
    }

    /**
     * @returns {Element} an element which represents a single punch inside a WorkTime element.
     */
    toElement() {
        var element = Punch.emptyElement();
        element.appendChild(document.createTextNode(this.time.toTimeString()));
        
        var date = document.createElement('p');
        date.classList.add('date');
        date.innerText = this.time.toYearlessString();

        element.appendChild(date);

        return element;
    }

    /**
     * Creates a punch instance from a JSON object.
     * 
     * @param {Object} obj - A json object.
     * @param {number} obj.punch_id
     * @param {string} obj.punch_auth
     * @param {string} obj.punch_time - The time value in ISO 8601.
     * @param {string} obj.punch_assoc - The association of the punch.
     * @returns the Punch instance created.
     */
    static fromJson(obj) {
        return new Punch(obj.punch_id, obj.punch_auth, new Date(Date.parse(obj.punch_time)), obj.punch_assoc);
    }

    /**
     * @returns {Element} and empty punch element inside a WorkTime element.
     */
    static emptyElement() {
        var element = document.createElement('div');
        element.classList.add('time');

        return element;
    }
}
/**
 * A class which represents a Punch in and a Punch out.
 */
class WorkTime {
    /**
     * Creates an instance of WorkTime.
     * 
     * @param {Punch} punchIn 
     * @param {Punch} punchOut 
     */
    constructor(punchIn, punchOut = null) {
        this.in = punchIn;
        this.out = punchOut;
    }

    /**
     * @returns {boolean} Whether this WorkTime has an out Punch.
     */
    isPunchedOut() {
        return this.out != null;
    }
    /**
     * @returns {boolean} Whether this WorkTime spans multiple days.
     */
    isOvernight() {
        var out = this.isPunchedOut() ? this.out.time : new Date();
        return !this.in.time.isSimilarTo(out);
    }

    /**
     * Checks whether this WorkTime overlaps the start and end times.
     * 
     * @param {Date} start 
     * @param {Date} end 
     * @returns {boolean} whether this WorkTime overlaps the start and end times.
     */
    overlaps(start, end) {
        var timeIn = this.in.time.getTime();
        var timeOut = this.isPunchedOut() ? this.out.time.getTime() : new Date().getTime();
        var startTime = start.getTime();
        var endTime = end.lessThan().getTime();

        return Math.min(endTime, timeOut) >= Math.max(startTime, timeIn);
    }

    /**
     * @return {number} Gets the total duration of the WorkTime in hours.
     */
    duration() {
        return  (this.out.time.getTime() - this.in.time.getTime()) / 3600000;
    }

    /**
     * @returns {Element} the element built to represent this WorkTime.
     */
    toElement() {
        var container = document.createElement('div');
        container.classList.add('punch');
        container.setAttribute('punch_assoc', this.in.assoc);

        var icon = document.createElement('i');
        icon.classList.add('material-icons');
        icon.innerHTML = 'brightness_5';

        if(this.in.assoc == 'DEFAULT') {
            if(this.isOvernight()) {
                icon.innerHTML = 'brightness_4';
                container.setAttribute('overnight', '');
            }
        }
        else if(this.in.assoc == 'ZOOM') {
            icon.innerHTML = 'video_label'
        }

        container.appendChild(this.in.toElement());
        container.innerHTML += '<hr>';
        container.appendChild(icon);
        container.innerHTML += '<hr>';

        if(this.isPunchedOut()) container.appendChild(this.out.toElement());
        else {
            var element = Punch.emptyElement();
            element.innerHTML = '...';
            container.appendChild(element);
        }

        return container;
    }

    /**
     * Creates an instance of WorkTime of a json object.
     * 
     * @param {Object} obj - A json object.
     * @param {Object} obj.in
     * @param {number} obj.in.punch_id
     * @param {string} obj.in.punch_auth
     * @param {string} obj.in.punch_time - The time value in ISO 8601.
     * 
     * @param {Object} obj.out - This object is optional.
     * @param {number} obj.out.punch_id
     * @param {string} obj.out.punch_auth
     * @param {string} obj.out.punch_time - The time value in ISO 8601.
     */
    static fromJson(obj) {
        var punchIn = Punch.fromJson(obj.in);

        var punchOut = null;
        if(obj.out !== undefined) {
            punchOut = Punch.fromJson(obj.out);
        }

        return new WorkTime(punchIn, punchOut);
    }
}

/**
 * A list of WorkTime instances with helper methods.
 */
class WorkTimeList {
    constructor(arr = []) {
        this.arr = arr;
    }

    /**
     * A helper function which calls the internal array's forEach.
     * @param {*} callback 
     */
    forEach(callback) {
        this.arr.forEach(callback);
    }

    forEachOverlapping(start, end, callback, assocs = []) {
        this.arr.forEach((workTime, i) => {
            // Only include assocs from the assocs array. An empty array means include all.
            var include = assocs.length == 0;
            for(let i = 0; i < assocs.length; i++) {
                if(workTime.in.assoc == assocs[i]) {
                    include = true;
                    break;
                }
            }
            if(include && workTime.overlaps(start, end)) callback(workTime, i)
        });
    }

    /**
     * A helper functions which returns the internal array's length.
     */
    length() {
        return this.arr.length;
    }

    /**
     * Calculates the duration off all the work times between the start date and end date.
     * 
     * @param {Date} start 
     * @param {Date} end
     * @param {Array<string>} assocs
     * @return {number} the total duration of work times between start and end dates in milliseconds.
     */
    duration(start, end, assocs = []) {
        var duration = 0;
        this.forEach((workTime) => {
            // Only include assocs from the assocs array. An empty array means include all.
            var include = assocs.length == 0;
            for(let i = 0; i < assocs.length; i++) {
                if(workTime.in.assoc == assocs[i]) {
                    include = true;
                    break;
                }
            }
            if(include == false) return;

            // Only work times which overlap the start and end time.
            if(!workTime.overlaps(start, end)) return;
            
            var startTime = start.getTime();
            var endTime = end.lessThan().getTime();
            var timeIn = workTime.in.time.getTime();
            var timeOut = (workTime.isPunchedOut()) ? workTime.out.time.getTime() : new Date().getTime();

            if(timeIn < startTime) timeIn = startTime;
            if(timeOut > endTime) timeOut = endTime;

            duration += timeOut - timeIn;
        });

        return duration;
    }

    /**
     * Calculates the duration of all DEFAULT the work times between the start date and end date.
     * 
     * @param {Date} start 
     * @param {Date} end
     * @param {Array<string>} assocs
     * @param {number} roundTo how many decimal places to round to.
     * @return {string} the total duration of work times between start and end dates in a percentage of the total time.
     */
    percent(start, end, assocs = [], roundTo = 3) {
        var dur = this.duration(start, end, assocs);
        return (100 * dur / (end.getTime() - start.getTime())).toFixed(roundTo);
    }

    /**
     * Extracts worktimes from start to end.
     * @param {Date} start 
     * @param {Date} end 
     * @return {WorkTimeList} the work time list between start and end.
     */
    extract(start, end) {
        var _arr = [];
        this.arr.forEach((workTime) => {
            if(workTime.overlaps(start, end)) _arr.push(workTime);
        });
        return new WorkTimeList(_arr);
    }
}

// const API_ENDPOINT = 'https://hazdryx.com/bbclock/api/punch.php';
const API_ENDPOINT = 'http://localhost/bbclock/api/punch.php';
class BBClockAPI {
    /**
     * @param {Date} start
     * @param {Date} end
     * @returns {Promise<WorkTimeList>}
     */
    static async fetchPunches(start = null, end = null) {
        var base = new Date();
        if(start != null) base = start;

        var data = '?timezone=' + (new Date(base.getFullYear(), 0, 1).getTimezoneOffset() / -60);
        if(start != null) data += '&start=' + (Math.floor(start.getTime() / 1000));
        if(end != null) data += '&end=' + (Math.floor(end.getTime() / 1000));

        return fetch(API_ENDPOINT + data, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())   
        .then(json => new Promise((resolve, reject) => {
            if(json.status != 0) reject(json.error);
            else {
                var workTimes = new WorkTimeList();
                json.data.forEach((wtJson, i) => {
                    workTimes.arr.push(WorkTime.fromJson(wtJson));
                });

                resolve(workTimes);
            }
        }));
    }
}