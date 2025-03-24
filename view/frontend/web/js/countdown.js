define([
    'jquery'
], function($) {
    'use strict';

    return function(config) {
        const targetDate = new Date(config.targetDate).getTime();

        function updateCountdown() {
            const now = new Date().getTime();
            const distance = targetDate - now;

            if (distance < 0) {
                $(config.countdownSelector).html('<div class="countdown-expired">' + config.expiredMessage + '</div>');
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            $(config.daysSelector).text(days);
            $(config.hoursSelector).text(hours);
            $(config.minutesSelector).text(minutes);
            $(config.secondsSelector).text(seconds);
        }

        updateCountdown();
        return setInterval(updateCountdown, 1000);
    };
});