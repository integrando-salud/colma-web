import { defineStore } from "pinia";

export const useNotificationStore = defineStore('notification', {
    state: () => ({
        message: null,
        color: null,
    }),
    actions: {
        showNotification(message, color) {
            this.message = message;
            this.color = color;
        },
        successNotification(message) {
            this.showNotification(message, 'success');
        },
        errorNotification(message) {
            this.showNotification(message, 'danger');
        }
    }
});
