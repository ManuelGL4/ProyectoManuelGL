class TimerController {
    constructor(model, view) {
        this.model = model;
        this.view = view;

        this.model.loadFromLocalStorage();
        this.view.updateTimerUI(this.model.timers);
        this.view.updateTotalTime(this.model.totalElapsedTimes);

        setInterval(() => {
            this.view.updateCurrentTime();
            this.view.updateTotalTime(this.model.totalElapsedTimes);
            this.updateActiveTimers();
        }, 1000);
    }

    async startTimer(taskId) {
        for (let existingTaskId in this.model.timers) {
            if (this.model.timers[existingTaskId]) {
                await this.stopTimer(existingTaskId);
            }
        }

        if (this.model.timers[taskId]) {
            return;
        }

        if (!this.model.totalElapsedTimes[taskId]) {
            this.model.totalElapsedTimes[taskId] = 0;
        }

        this.model.tokens[taskId] = this.model.generateToken();
        const noteInput = document.querySelector(`input.notes[data-task-id="${taskId}"]`);
        const note = noteInput ? noteInput.value : "";
        const projectIdInput = document.querySelector(`input[data-task-id="${taskId}"]`);
        const projectId = projectIdInput ? projectIdInput.value : "";

        this.model.startTimes[taskId] = new Date();
        this.model.timers[taskId] = setInterval(() => {
            const now = new Date().getTime();
            this.model.totalElapsedTimes[taskId] += 1;
            this.view.updateTimerUI(this.model.timers);
        }, 1000);

        await this.model.sendStartRequest(taskId, note, projectId);
    }

    async stopTimer(taskId) {
        clearInterval(this.model.timers[taskId]);
        delete this.model.timers[taskId];
        await this.model.sendStopRequest(taskId, "", "");
        this.view.updateTimerUI(this.model.timers);
    }

    updateActiveTimers() {
        for (const taskId in this.model.timers) {
            if (this.model.timers[taskId]) {
                this.model.totalElapsedTimes[taskId] += 1;
            }
        }
    }
}


/*class TimerController {
    constructor(model, view) {
        this.model = model;
        this.view = view;

        this.model.loadFromLocalStorage();
        this.view.updateTimerUI(this.model.timers);
        this.view.updateTotalTime(this.model.totalElapsedTimes);

        // Bind start and stop events
        this.view.bindStartStopEvents(
            (taskId) => this.startTimer(taskId),
            (taskId) => this.stopTimer(taskId)
        );

        setInterval(() => {
            this.view.updateCurrentTime();
            this.view.updateTotalTime(this.model.totalElapsedTimes);
            this.updateActiveTimers();
        }, 1000);
    }

    // ... (rest of the previous controller code remains the same)
}*/ 
export default TimerController;