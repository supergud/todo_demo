window.onload = function (e) {
    liff
    .init({
        liffId: "1627654560-O93NjxGV" // use own liffId
    })
    .then(() => {
        let event_id = document.getElementById('event_id').value;

        if (!event_id) {
            window.alert('查無此待辦事項');

            liff.closeWindow();

            return false;
        }

        document.getElementById('submit-btn').addEventListener('click', function (e) {
            let data = {
                'event_id': event_id,
                'event': document.getElementById('event').value,
                'deadline': document.getElementById('deadline').value,
            };

            if (!liff.isInClient()) {
                sendAlertIfNotInClient();
            } else {
                liff.sendMessages([{
                    'type': 'text',
                    'text': "編輯@" + JSON.stringify(data),
                }]).then(function () {
                    liff.closeWindow();
                }).catch(function (error) {
                    window.alert('Error sending message: ' + error);
                });
            }
        });
    })
    .catch((err) => {
        // Error happens during initialization
        console.log(err.code, err.message);
    });
};

function sendAlertIfNotInClient() {
    alert('This button is unavailable as LIFF is currently being opened in an external browser.');
}
