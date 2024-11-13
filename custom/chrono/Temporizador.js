class TimerModel {
    constructor() {
        this.timers = {};
        this.startTimes = {};
        this.totalElapsedTimes = {};
        this.startDates = {};
        this.endDates = {};
        this.tokens = {};
        this.apiKey = localStorage.getItem("apiKey");
        this.userId = localStorage.getItem("userId");
    }

    loadFromLocalStorage() {
        const storedTimers = localStorage.getItem("timers");
        const storedStartTimes = localStorage.getItem("startTimes");
        const storedElapsedTimes = localStorage.getItem("totalElapsedTimes");
        const storedStartDates = localStorage.getItem("startDates");
        const storedEndDates = localStorage.getItem("endDates");

        if (storedTimers) {
            this.timers = JSON.parse(storedTimers);
        }
        if (storedStartTimes) {
            this.startTimes = JSON.parse(storedStartTimes);
        }
        if (storedElapsedTimes) {
            this.totalElapsedTimes = JSON.parse(storedElapsedTimes);
        }
        if (storedStartDates) {
            this.startDates = JSON.parse(storedStartDates);
        }
        if (storedEndDates) {
            this.endDates = JSON.parse(storedEndDates);
        }
    }

    saveToLocalStorage() {
        localStorage.setItem("timers", JSON.stringify(this.timers));
        localStorage.setItem("startTimes", JSON.stringify(this.startTimes));
        localStorage.setItem("totalElapsedTimes", JSON.stringify(this.totalElapsedTimes));
        localStorage.setItem("startDates", JSON.stringify(this.startDates));
        localStorage.setItem("endDates", JSON.stringify(this.endDates));
    }

    generateToken() {
        let token = Math.random().toString(36).substring(2) + Date.now().toString(36);
        while (token.length < 34) {
            token += Math.random().toString(34).substring(2);
        }
        return token.substring(0, 34);
    }

    async sendStartRequest(taskId, note, projectId) {
        const startDateForTask = this.formatDateForMySQL(this.startTimes[taskId]);
        const taskData = {
            date_time_event: startDateForTask,
            event_location_ref: "Prueba",
            event_type: 2,
            note: note,
            fk_userid: this.userId,
            fk_task: taskId,
            fk_project: projectId,
            token: this.tokens[taskId]
        };

        try {
            const response = await fetch('http://localhost/khonos-ORTRAT/api/index.php/chronoapi/chrono', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'DOLAPIKEY': '9b1ea5dfacefebb40fb1591891764236f4241041'
                },
                body: JSON.stringify(taskData)
            });
            return await response.json();
        } catch (error) {
            console.error('Error al guardar el inicio del temporizador:', error);
        }
    }

    async sendStopRequest(taskId, note, projectId) {
        const endDateForTask = this.formatDateForMySQL(new Date());
        const taskData = {
            event_type: 3,
            date_time_event: endDateForTask,
            event_location_ref: "Prueba",
            note: note,
            fk_userid: this.userId,
            fk_task: taskId,
            fk_project: projectId,
            token: this.tokens[taskId]
        };

        try {
            const response = await fetch('http://localhost/khonos-ORTRAT/api/index.php/chronoapi/chrono', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'DOLAPIKEY': '9b1ea5dfacefebb40fb1591891764236f4241041'
                },
                body: JSON.stringify(taskData)
            });
            return await response.json();
        } catch (error) {
            console.error('Error al guardar la detención del temporizador:', error);
        }
    }

    formatDateForMySQL(date) {
        const utcYear = date.getUTCFullYear();
        const utcMonth = String(date.getUTCMonth() + 1).padStart(2, '0');
        const utcDay = String(date.getUTCDate()).padStart(2, '0');
        const utcHours = String(date.getUTCHours()).padStart(2, '0');
        const utcMinutes = String(date.getUTCMinutes()).padStart(2, '0');
        const utcSeconds = String(date.getUTCSeconds()).padStart(2, '0');
        return `${utcYear}-${utcMonth}-${utcDay} ${utcHours}:${utcMinutes}:${utcSeconds}`;
    }
}
export default TimerModel;