/* Saved-status presentation shared by the tracking UI and its tests. */
const GreenSproutTracking = Object.freeze({
    view(status) {
        const states = {
            pending: [0, 'Order received', 'Your order has been received. Waiting for the café to begin preparation.'],
            preparing: [1, 'Being prepared', 'The café is preparing your order. This illustration follows the saved order status.'],
            ready: [2, 'Ready', 'Your order is ready. The café will mark it complete after fulfilment.'],
            completed: [3, 'Completed', 'The café has marked this order complete. Thank you for ordering.'],
            delivered: [3, 'Delivered', 'This order has been marked delivered. Thank you for ordering.'],
            cancelled: [-1, 'Cancelled', 'This order was cancelled. Contact the café if you need help.']
        };
        const state = states[status] || [-1, 'Status unavailable', 'Please contact the café for an update.'];
        return {
            index: state[0], label: state[1], description: state[2],
            progress: Math.max(0, (state[0] + 1) * 25),
            position: state[0] < 0 ? 25 : 25 + state[0] * (50 / 3),
            terminal: ['completed', 'delivered', 'cancelled'].includes(status)
        };
    }
});
