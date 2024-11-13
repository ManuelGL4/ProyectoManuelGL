import { TimerModel } from './Temporizador.js';
import { TimerView } from './TemporizadorView.js';
import { TimerController } from './TemporizadorController.js';

document.addEventListener('DOMContentLoaded', () => {
    const model = new TimerModel();
    const view = new TimerView();
    const controller = new TimerController(model, view);

    // Expose methods globally
    window.startTimer = (taskId, apiKey, userId) => {
        controller.startTimer(taskId, apiKey, userId);
    };

    window.stopTimer = (taskId) => {
        controller.stopTimer(taskId);
    };

    window.resetTimer = (taskId) => {
        controller.resetTimer(taskId);
    };

    // Optional: Add event listener for reset all button
    const resetButton = document.getElementById('resetButton');
    if (resetButton) {
        resetButton.addEventListener('click', () => {
            // Implement logic to reset all timers
            // This might involve iterating through all tasks and calling resetTimer
        });
    }
});