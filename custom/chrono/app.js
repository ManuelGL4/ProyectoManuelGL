import { TimerModel } from './model.js';
import { TimerView } from './view.js';
import { TimerController } from './controller.js';

document.addEventListener('DOMContentLoaded', () => {
    const model = new TimerModel();
    const view = new TimerView();
    const controller = new TimerController(model, view);
});