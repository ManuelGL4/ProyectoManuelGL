class TimerView {
    constructor() {
        this.timerElements = {};
        this.totalTimeElement = document.getElementById('total-time');
        this.currentTimeElement = document.getElementById('current-time');
    }

    updateTimerUI(timers) {
        for (const taskId in timers) {
            if (!this.timerElements[taskId]) {
                this.createTimerElement(taskId);
            }
            this.timerElements[taskId].innerText = timers[taskId] ? 'Running' : 'Stopped';
        }
    }

    createTimerElement(taskId) {
        const timerElement = document.createElement('div');
        timerElement.id = `timer-${taskId}`;
        document.getElementById('timers-container').appendChild(timerElement);
        this.timerElements[taskId] = timerElement;
    }

    updateTotalTime(totalElapsedTimes) {
        const totalTime = Object.values(totalElapsedTimes).reduce((acc, time) => acc + time, 0);
        this.totalTimeElement.innerText = `Total Time: ${totalTime} seconds`;
    }

    updateCurrentTime() {
        const now = new Date();
        this.currentTimeElement.innerText = `Current Time: ${now.toLocaleTimeString()}`;
    }
}
/**
 * 
 * class TimerView {
    constructor() {
        this.timerElements = {};
        this.totalTimeElement = document.getElementById('total-time');
        this.currentTimeElement = document.getElementById('current-time');
        
        // Store references to start and stop buttons
        this.startButtons = document.querySelectorAll('.start-button');
        this.stopButtons = document.querySelectorAll('.stop-button');
    }

    // ... (rest of the previous view code remains the same)

    bindStartStopEvents(startHandler, stopHandler) {
        // Attach event listeners to start buttons
        this.startButtons.forEach(button => {
            button.addEventListener('click', () => {
                const taskId = button.getAttribute('data-task-id');
                startHandler(taskId);
            });
        });

        // Attach event listeners to stop buttons
        this.stopButtons.forEach(button => {
            button.addEventListener('click', () => {
                const taskId = button.getAttribute('data-task-id');
                stopHandler(taskId);
            });
        });
    }
}
 */
export default TimerView;