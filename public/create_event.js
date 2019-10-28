window.onload = function (e) {
    liff
    .init({
        liffId: "1627654560-Gv5AyM12" // use own liffId
    })
    .then(() => {
        document.getElementById('submit-btn').addEventListener('click', function (e) {
            let data = {
                'event': document.getElementById('event').value,
                'deadline': document.getElementById('deadline').value,
            };

            if (!liff.isInClient()) {
                sendAlertIfNotInClient();
            } else {
                liff.sendMessages([{
                    'type': 'text',
                    'text': "新增@" + JSON.stringify(data),
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
