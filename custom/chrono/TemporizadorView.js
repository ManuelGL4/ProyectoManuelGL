// TimerView.js

class TimerView {
    updateTimerUI(timers) {
        for (let taskId in timers) {
            const taskIcon = document.getElementById("icon-" + taskId);
            const resetIcon = document.getElementById("reset-" + taskId);
            if (timers[taskId]) {
                taskIcon.innerHTML = "<img src='img/detener.png' alt='Detener' style='height: 40px;' onclick='controller.stopTimer(" + taskId + ")'>";
                resetIcon.style.display = "inline-block";
            } else {
                taskIcon.innerHTML = "<img src='img/notstarted.png' alt='Iniciar' style='height: 40px;' onclick='controller.startTimer(" + taskId + ")'>";
                resetIcon.style.display = "none";
            }
        }
    }

    displayTime(taskId, timeString) {
        document.getElementById("time-" + taskId).innerHTML = timeString;
    }

    displayCurrentTime(timeString) {
        document.getElementById("current-time").innerText = "Hora actual: " + timeString;
    }

    displayTotalTime(timeString) {
        document.getElementById("total-time").innerText = "Tiempo total: " + timeString;
    }

    // Otros métodos para actualizar la interfaz de usuario
}
