// TimerModel.js

class Temporizador {
    constructor() {
        this.timers = JSON.parse(localStorage.getItem("timers")) || {};
        this.startTimes = JSON.parse(localStorage.getItem("startTimes")) || {};
        this.totalElapsedTimes = JSON.parse(localStorage.getItem("totalElapsedTimes")) || {};
        this.endTimes = JSON.parse(localStorage.getItem("endTimes")) || {};
        this.startDates = JSON.parse(localStorage.getItem("startDates")) || {};
        this.endDates = JSON.parse(localStorage.getItem("endDates")) || {};
        this.tokens = {};
        this.apiKey = localStorage.getItem("apiKey");
        this.userId = localStorage.getItem("userId");
    }

    saveTimers() {
        localStorage.setItem("timers", JSON.stringify(this.timers));
        localStorage.setItem("startTimes", JSON.stringify(this.startTimes));
        localStorage.setItem("totalElapsedTimes", JSON.stringify(this.totalElapsedTimes));
        localStorage.setItem("endTimes", JSON.stringify(this.endTimes));
        localStorage.setItem("startDates", JSON.stringify(this.startDates));
        localStorage.setItem("endDates", JSON.stringify(this.endDates));
    }

    generateToken() {
        let token = Math.random().toString(36).substring(2) + Date.now().toString(36);
        while (token.length < 34) token += Math.random().toString(36).substring(2);
        return token.substring(0, 34);
    }

    setApiKeyAndUserId(apiKey, userId) {
        this.apiKey = apiKey;
        this.userId = userId;
        localStorage.setItem("apiKey", apiKey);
        localStorage.setItem("userId", userId);
    }

    // Otros métodos de lógica de negocio y manejo de datos
}
