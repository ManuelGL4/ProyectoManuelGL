export class TimerView {
    constructor() {
        // No need for a specific container, we'll work directly with task elements
    }

    updateTimerUI(taskId, time) {
        // Find the time display element for the specific task
        const timeElement = document.getElementById(`time-${taskId}`);
        
        if (!timeElement) {
            console.error(`Timer element for task ${taskId} not found`);
            return;
        }

        // Update the time display
        timeElement.textContent = time;
    }

    updateStartTimeUI(taskId, startTime) {
        // Find the start time display element
        const startTimeElement = document.getElementById(`start-time-${taskId}`);
        
        if (!startTimeElement) {
            console.error(`Start time element for task ${taskId} not found`);
            return;
        }

        // Update the start time display
        startTimeElement.textContent = startTime;
    }

    updateStatusIcon(taskId, isRunning) {
        const iconElement = document.getElementById(`icon-${taskId}`);
        const resetElement = document.getElementById(`reset-${taskId}`);
        
        if (!iconElement || !resetElement) {
            console.error(`Icon or reset element for task ${taskId} not found`);
            return;
        }

        if (isRunning) {
            // Change start icon to stop
            iconElement.innerHTML = `<img src='img/started.png' alt='Detener' style='height: 40px;' onclick='stopTimer(${taskId})'>`;
            resetElement.style.display = 'block';
            resetElement.innerHTML = `<img src='img/stop.png' alt='Resetear' style='height: 40px;'>`;
        } else {
            // Change back to start icon
            iconElement.innerHTML = `<img src='img/notstarted.png' alt='Iniciar' style='height: 40px;' onclick='startTimer(${taskId}, "${user.api_key}", "${user.id}")'>`;
            resetElement.style.display = 'none';
            resetElement.innerHTML = '';
        }
    }
}