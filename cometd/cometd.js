require('dotenv').config({ path: '../.env' });
const axios = require('axios');

(function () {
    var jsforce = require('jsforce');

    const url = 'https://um1.lightning.force.com/cometd/' + process.env.SF_API_VERSION + '/';
    const subscriptionChannels = [
        '/data/ChangeEvents'
    ];

    const cdc_run = process.env.COMETD_CDC_RUN;
    const execute_url = process.env.COMETD_EXEC_URL;
    const term_run = process.env.COMETD_TERM_RUN;
    const term_hour = process.env.COMETD_TERM_HOUR;
    const term_minute = process.env.COMETD_TERM_MINUTE;
    const cron_run = process.env.COMETD_CRON_RUN;
    const cron_hour = process.env.COMETD_CRON_HOUR;
    const cron_minute = process.env.COMETD_CRON_MINUTE;
    const cdcmimic_interval = process.env.COMETD_CDCMIMIC_INTERVAL;
    const cdcmimic_url = process.env.COMETD_CDCMIMIC_URL;
    const cdc_defer = process.env.COMETD_CDC_DEFER;
    const backup_url = process.env.COMETD_BACKUP_URL;
    const preserve_rows = process.env.COMETD_BACKUP_PRESERVE || 500;
    const chunk_size = process.env.COMETD_BACKUP_CHUNK || 10;

    let lastExecutionTime = 0;
    let deferredCallTimeout = null;

    console.log(getCurrentDateTime() + ': Setting up jsforce...');

    const sfconn = new jsforce.Connection({
        oauth2: {
            clientId: process.env.SF_CLIENT_ID,
            clientSecret: process.env.SF_CLIENT_SECRET,
            redirectUrl: "https://login.salesforce.com/"
        }
    });

    console.log(getCurrentDateTime() + ': Acquiring SF session Id...');

    sfconn.login(process.env.SF_USERNAME, process.env.SF_PASSWORD + process.env.SF_SECURITY, function (err, userInfo) {
        console.log(getCurrentDateTime() + ': SF session id acquired: ' + sfconn.accessToken);
        console.log('------------------------');

        const currentTime = new Date();
        if (cron_run == 1) {
            console.log("Cron run status = " + cron_run + ", cron time = " + cron_hour + ": " + cron_minute);
        }

        if (cdc_run == 1) {
            subscriptionChannels.forEach(channel => {
                sfconn.streaming.topic(channel).subscribe(function (message) {
                    console.log(JSON.stringify(message));
                    const now = Date.now();
                    if (now - lastExecutionTime >= cdc_defer) {
                        executeApiCall("cdc");
                    } else {
                        console.log(getCurrentDateTime() + ': Deferring API call due to rate limiting');
                        if (deferredCallTimeout) {
                            clearTimeout(deferredCallTimeout);
                        }
                        deferredCallTimeout = setTimeout(() => {
                            executeApiCall("cdc");
                        }, cdc_defer - (now - lastExecutionTime));
                    }
                });
            });
        }

        setInterval(() => {
            const currentTime = new Date();
            if (term_run == 1 && currentTime.getHours() == term_hour && currentTime.getMinutes() == term_minute) {
                console.log(getCurrentDateTime() + ': Terminating script...');
                process.exit();
            }

            if (cron_run == 1 && currentTime.getHours() == cron_hour && currentTime.getMinutes() == cron_minute) {
                console.log(getCurrentDateTime() + ': Executing periodic retrieval');
                executeApiCall("crontab");
            }

            console.log(getCurrentDateTime() + ': Script is still running...');

        }, 60000);

        if (cdcmimic_interval > 0) {
            setInterval(() => {
                const ct = getCurrentDateTime();
                console.log(ct + ': Executing CDC Mimic script per ' + cdcmimic_interval + 'seconds');
                executeApiCall("cdcmimic");
            }, cdcmimic_interval);
        }
    });

    function executeApiCall(reason) {
        console.log(getCurrentDateTime() + ': API call execution time epoch: ' + Date.now());
        axios.get(execute_url+"&reason="+reason)
            .then(response => {
                lastExecutionTime = Date.now();
                console.log(getCurrentDateTime() + ': URL executed successfully. Reason = '+reason);
                console.log("Retrieved batch number = "+JSON.stringify(response.batch));
            })
            .catch(error => {
                console.error(getCurrentDateTime() + ': Error executing URL:', error.message);
            });
    }

    // Modified cleanup function using automatebackup endpoint
    async function cleanupBatchTable() {
        try {
            const response = await axios.get(backup_url, {
                params: {
                    action: 'automatebackup',
                    preserve: preserve_rows,
                    chunk: chunk_size
                }
            });

            console.log(getCurrentDateTime() + ': Cleanup completed:', response.data);
        } catch (error) {
            console.error(getCurrentDateTime() + ': Error during cleanup:', error.message);
        }
    }

    // Schedule cleanup to run daily at 2:00 AM
    const cleanupTime = new Date();
    cleanupTime.setHours(2, 0, 0, 0);
    if (cleanupTime < new Date()) {
        cleanupTime.setDate(cleanupTime.getDate() + 1);
    }

    const timeUntilCleanup = cleanupTime - new Date();
    setTimeout(() => {
        cleanupBatchTable();
        setInterval(cleanupBatchTable, 24 * 60 * 60 * 1000); // Run daily
    }, timeUntilCleanup);

})();

function getCurrentDateTime(mode = "full") {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    if (mode == "short") {
        return `${hours}:${minutes}`;
    } else {
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }
}
