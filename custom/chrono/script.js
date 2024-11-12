var timers = {};
var startTimes = {};
var endTimes = {};
var totalElapsedTimes = {};
var startDates = {};
var endDates = {};
var tokens = {}; // Objeto para almacenar tokens únicos por tarea

var apiKey; // Variable global para la API key
var userId; // Variable global para el User ID

window.onload = function() {
    apiKey = localStorage.getItem("apiKey");
    userId = localStorage.getItem("userId");
    const storedTimers = localStorage.getItem("timers");
    const storedStartTimes = localStorage.getItem("startTimes");
    const storedElapsedTimes = localStorage.getItem("totalElapsedTimes");
    const storedEndTimes = localStorage.getItem("endTimes");
    const storedStartDates = localStorage.getItem("startDates");
    const storedEndDates = localStorage.getItem("endDates");
    if (storedTimers) {
        timers = JSON.parse(storedTimers);
    }
    if (storedStartTimes) {
        const parsedStartTimes = JSON.parse(storedStartTimes);
        for (let taskId in parsedStartTimes) {
            startTimes[taskId] = new Date(parsedStartTimes[taskId]);
            console.log("TIEMPOS START "+ startTimes[taskId]);
            document.getElementById("start-time-" + taskId).innerText = formatDate(startTimes[taskId]);

        }
    }
    if (storedElapsedTimes) {
        totalElapsedTimes = JSON.parse(storedElapsedTimes);
        for (let taskId in totalElapsedTimes) {
             document.getElementById("time-" + taskId).innerHTML = formatTime(totalElapsedTimes[taskId]);
        }
    }
    if (storedEndTimes) {
        const parsedEndTimes = JSON.parse(storedEndTimes);
        for (let taskId in parsedEndTimes) {
            endTimes[taskId] = new Date(parsedEndTimes[taskId]);
            console.log("TIEMPOS FINAL "+endTimes[taskId]);
        }
    }
    if (storedStartDates) {
        startDates = JSON.parse(storedStartDates);
        console.log(startDates);
    }
    if (storedEndDates) {
        endDates = JSON.parse(storedEndDates);
        console.log(endDates);
    }


    for (let taskId in timers) {
        if (timers[taskId]) {
            const now = new Date();
            const elapsedTime = Math.floor((now.getTime() - startTimes[taskId].getTime()) / 1000);
            totalElapsedTimes[taskId] = (totalElapsedTimes[taskId] || 0) + elapsedTime;
            startTimes[taskId] = now;
        }
    }
    
    updateTimerUI();
    updateTotalTime();
};

function generateToken() {
    let token = Math.random().toString(36).substring(2) + Date.now().toString(36);
    
    // Si el token es menor a 32 caracteres, agrega más caracteres aleatorios
    while (token.length < 34) {
        token += Math.random().toString(34).substring(2);
    }

    // Asegura que el token tenga exactamente 32 caracteres
    return token.substring(0, 34);
}


function updateTimerUI() {
    apiKey = localStorage.getItem("apiKey");
    userId = localStorage.getItem("userId");

    for (let taskId in timers) {
        const taskIcon = document.getElementById("icon-" + taskId);
        const resetIcon = document.getElementById("reset-" + taskId);
        if (timers[taskId]) {
            taskIcon.innerHTML = "<img src='img/detener.png' alt='Detener' style='height: 40px;' onclick='stopTimer(" + taskId + ", \"" + apiKey + "\", \"" + userId + "\")'>";
            resetIcon.style.display = "inline-block";
        } else {
            taskIcon.innerHTML = "<img src='img/notstarted.png' alt='Iniciar' style='height: 40px;' onclick='startTimer(" + taskId + ", \"" + apiKey + "\", \"" + userId + "\")'>";
            resetIcon.style.display = "none";
        }
    }
}

function formatTime(totalSeconds) {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    return hours + "h " + minutes + "m " + seconds + "s";
}

function formatDate(date) {
    return date.toLocaleString();
}

function updateCurrentTime() {
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, "0");
    const minutes = now.getMinutes().toString().padStart(2, "0");
    const seconds = now.getSeconds().toString().padStart(2, "0");
    document.getElementById("current-time").innerText = "Hora actual: " + hours + ":" + minutes + ":" + seconds;
}

function updateTotalTime() {
    let totalSeconds = 0;

    for (let taskId in totalElapsedTimes) {
        totalSeconds += totalElapsedTimes[taskId] || 0;
    }

    for (let taskId in timers) {
        if (timers[taskId]) {
            const now = new Date().getTime();
            const elapsedTime = now - startTimes[taskId].getTime();
            totalSeconds += Math.floor(elapsedTime / 1000);
        }
    }

    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    document.getElementById("total-time").innerText = "Tiempo total: " + hours + "h " + minutes + "m " + seconds + "s";
}

setInterval(function() {
    updateCurrentTime();
    updateTotalTime();
    updateActiveTimers();
}, 1000);

function updateActiveTimers() {
    for (let taskId in timers) {
        if (timers[taskId]) {
            const now = new Date().getTime();
            const elapsedTime = Math.floor((now - startTimes[taskId].getTime()) / 1000);
            const currentTotal = totalElapsedTimes[taskId] + elapsedTime;
            document.getElementById("time-" + taskId).innerHTML = formatTime(currentTotal);
        }
    }
}

function startTimer(taskId, apiKeyInput, userIdInput) {
    localStorage.setItem("apiKey", apiKeyInput);
    localStorage.setItem("userId", userIdInput);
    console.log(localStorage.getItem("apiKey"));
    console.log(localStorage.getItem("userId"));

    // Detener el temporizador existente si está corriendo
    for (let existingTaskId in timers) {
        if (timers[existingTaskId]) {
            stopTimer(existingTaskId);
        }
    }

    // Ensure the current timer is not already running
    if (timers[taskId]) {
        return;
    }

    // Initialize total elapsed time for the task if it doesn't exist
    if (!totalElapsedTimes[taskId]) {
        totalElapsedTimes[taskId] = 0;
    }

    tokens[taskId] = generateToken();
    localStorage.setItem(`token-${taskId}`, tokens[taskId]); // Guardar el token en localStorage
    console.log(localStorage.getItem("token-${taskId}"));
    var startTimeElement = document.getElementById("start-time-" + taskId).innerText;
    if (startTimeElement === 'no iniciado') {
        startTimes[taskId] = new Date();
        startDates[taskId] = new Date();
        document.getElementById("start-time-" + taskId).innerText = formatDate(startDates[taskId]);
    } else {
        startTimes[taskId] = new Date();
    }

    // Start the timer
    timers[taskId] = setInterval(function() {
        const now = new Date().getTime();
        const elapsedTime = Math.floor((now - startTimes[taskId].getTime()) / 1000);
        totalElapsedTimes[taskId] = (totalElapsedTimes[taskId] || 0) + elapsedTime;

        // Update the UI with the current time spent on the task
        document.getElementById("time-" + taskId).innerHTML = formatTime(totalElapsedTimes[taskId]);

        // Reset start time for accurate timing
        startTimes[taskId] = new Date();
    },1000);

    // Update the icon and reset button UI
    document.getElementById("icon-" + taskId).innerHTML = "<img src='img/detener.png' alt='Detener' style='height: 40px;' onclick='stopTimer(" + taskId + ")'>";
    document.getElementById("reset-" + taskId).style.display = "inline-block";

    // Set global variables for API key and user ID
    apiKey = apiKeyInput;
    userId = userIdInput;

    // Make the AJAX request to start the task
    const startDateForTask = formatDateForMySQL(startTimes[taskId]);
    const noteInput = document.querySelector(`input.notes[data-task-id="${taskId}"]`);
    const note = noteInput ? noteInput.value : "";
    const projectIdInput = document.querySelector(`input[data-task-id="${taskId}"]`);
    const projectId = projectIdInput ? projectIdInput.value : "";

    console.log(typeof userId);

    const taskData = {
        date_time_event: startDateForTask,
        event_location_ref: "Prueba",
        event_type: 2,
        note: note,
        fk_userid: userIdInput,
        fk_task: taskId,
        fk_project: projectId,
        token: tokens[taskId]
    };

    $.ajax({
        url: 'http://localhost/khonos-ORTRAT/api/index.php/chronoapi/chrono',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(taskData),
        headers: {
            'Content-Type': 'application/json',
            'DOLAPIKEY': '9b1ea5dfacefebb40fb1591891764236f4241041'
        },
        success: function(response) {
            console.log("Temporizador iniciado y guardado con éxito:", response);
            console.log("TAREA " +taskData.task);
            console.log("PROJECT " +taskData.project);
        },
        error: function(xhr, status, error) {
            console.error('Error al guardar el inicio del temporizador:', error);
        }
    });
    localStorage.setItem("timers", JSON.stringify(timers));
    localStorage.setItem("startTimes", JSON.stringify(startTimes));
    localStorage.setItem("totalElapsedTimes", JSON.stringify(totalElapsedTimes));
    localStorage.setItem("startDates", JSON.stringify(startDates));
    // updateTimerUI();
}

function stopTimer(taskId) {
    if (timers[taskId]) {
        clearInterval(timers[taskId]);
        timers[taskId] = null;

        const now = new Date();
        const sessionElapsedTime = Math.floor((now.getTime() - startTimes[taskId].getTime()) / 1000);
        totalElapsedTimes[taskId] += sessionElapsedTime;
        endTimes[taskId] = new Date();
        endDates[taskId] = new Date();

        // Calcular la duración de la tarea en segundos
        const taskDuration = Math.floor((now.getTime() - startTimes[taskId].getTime()) / 1000);

        // Mostrar la duración de la tarea en la consola
        console.log(`Duración de la tarea en segundos para el taskId ${taskId}:`, taskDuration);

        const endDateForTask = formatDateForMySQL(now);
        const projectIdInput = document.querySelector(`input[data-task-id="${taskId}"]`);
        const projectId = projectIdInput ? projectIdInput.value : ""; // Asegúrate de tener el input correspondiente
        const noteInput = document.querySelector(`input.notes[data-task-id="${taskId}"]`);
        const note = noteInput ? noteInput.value : "";

        const storedToken = localStorage.getItem(`token-${taskId}`);
        console.log(storedToken);

        const taskData = {
            event_type: 3,
            date_time_event: endDateForTask,
            event_location_ref: "Prueba",
            note: note,
            fk_userid: userId,
            fk_task: taskId,
            fk_project: projectId,
            token: storedToken
        };

        $.ajax({
            url: 'http://localhost/khonos-ORTRAT/api/index.php/chronoapi/chrono',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(taskData),
            headers: {
                'Content-Type': 'application/json',
                'DOLAPIKEY': '9b1ea5dfacefebb40fb1591891764236f4241041'
            },
            success: function(response) {
                console.log("Temporizador detenido y guardado con éxito:", response);
                console.log("TAREA " +taskData.task);
                console.log("PROJECT " +taskData.project);
            },
            error: function(xhr, status, error) {
                console.error('Error al guardar la detención del temporizador:', error);
            }
        });
        localStorage.setItem("timers", JSON.stringify(timers));
        localStorage.setItem("startTimes", JSON.stringify(startTimes));
        localStorage.setItem("totalElapsedTimes", JSON.stringify(totalElapsedTimes));
        localStorage.setItem("endTimes", JSON.stringify(endTimes));
        localStorage.setItem("endDates", JSON.stringify(endDates)); 
    }

    updateTimerUI();

}

function resetAllTimers() {
    for (let taskId in timers) {
        stopTimer(taskId);
        document.getElementById("time-" + taskId).innerHTML = "no iniciado";
        document.getElementById("start-time-" + taskId).innerHTML = "no iniciado";
        document.getElementById("reset-" + taskId).style.display = "none";
        totalElapsedTimes[taskId] = 0;
        startDates[taskId] = null;
    }
    timers = {};
    localStorage.clear(); // Limpiar localStorage al resetear todos los temporizadores

    updateTotalTime();
    location.reload();

}

function saveTask(apiKey, userId) {
    event.preventDefault();
    const tasksToSave = [];

    for (let taskId in totalElapsedTimes) {
        if (totalElapsedTimes[taskId] > 0) {

        console.log(startDates[taskId]);
        const timeSpentInSeconds = totalElapsedTimes[taskId];
        const timeSpent = formatTime(totalElapsedTimes[taskId]);

        try {
            startDate = startDates[taskId] ? formatDateForMySQL(startDates[taskId]) : "";
        } catch (error) {
            console.error("Error al formatear la fecha de inicio:", error);
            startDate = formatDateForMySQL(new Date()); // Asigna la fecha y hora actual
        }

        // Manejo del fin de fecha
        try {
            endDate = new Date(); // Asigna la fecha y hora actual
        } catch (error) {
            console.error("Error al formatear la fecha de fin:", error);
            endDate = formatDateForMySQL(new Date()); // Asigna la fecha y hora actual
        }
        const noteInput = document.querySelector(`input.notes[data-task-id="${taskId}"]`);
        const note = noteInput ? noteInput.value : "";
        const projectIdInput = document.querySelector(`input[data-task-id="${taskId}"]`);
        const projectId = projectIdInput ? projectIdInput.value : "";

        const task = {
            status: 1,
            fk_user_time: userId,
            fk_task: taskId,
            fecha_inicio: startDate,
            fecha_fin: formatDateForMySQL(endDate),
            tiempo_transcurrido: timeSpentInSeconds,
            nota: note,
            fk_project: projectId
        };

        tasksToSave.push(task);
    }
    }
    console.log(tasksToSave);

    $.ajax({
        url: 'http://localhost/khonos-ORTRAT/api/index.php/chronoapi/chrono',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(tasksToSave), // Convertir el array de tareas a JSON
        headers: {
            'Content-Type': 'application/json',
            'DOLAPIKEY': apiKey // Añadir el api_key a las cabeceras
        },
        success: function(response) {
            console.log("Tareas guardadas con éxito:", response);
            document.getElementById("successMessage").style.display = "block";
            resetAllTimers();
            console.log(document.getElementById("successMessage"));
        },
        error: function(xhr, status, error) {
            console.error('Error al guardar tareas:', error);
        }
    });
}

function formatDateForMySQL(date) {
    const utcYear = date.getUTCFullYear();
    const utcMonth = String(date.getUTCMonth() + 1).padStart(2, '0');
    const utcDay = String(date.getUTCDate()).padStart(2, '0');
    const utcHours = String(date.getUTCHours()).padStart(2, '0');
    const utcMinutes = String(date.getUTCMinutes()).padStart(2, '0');
    const utcSeconds = String(date.getUTCSeconds()).padStart(2, '0');

    return `${utcYear}-${utcMonth}-${utcDay} ${utcHours}:${utcMinutes}:${utcSeconds}`;
}




document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("resetButton").onclick = function() {
        document.getElementById("confirmModal").style.display = "block";
    };

    document.getElementById("confirmReset").onclick = function() {
        resetAllTimers();
        document.getElementById("confirmModal").style.display = "none";
    };

    document.getElementById("cancelReset").onclick = function() {
        document.getElementById("confirmModal").style.display = "none";
    };
});


