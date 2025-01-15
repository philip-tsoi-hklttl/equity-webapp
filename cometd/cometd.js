require('dotenv').config({ path: '../.env' });
const axios = require('axios');

(function () {
    var jsforce = require('jsforce');

    const url = 'https://um1.lightning.force.com/cometd/'+process.env.SF_API_VERSION+'/';
    //const channel = '/data/ChangeEvents';
    const subscriptionChannels = [
        //'/event/Job__ChangeEvent',
        //'/event/Job_Item__ChangeEvent'
        '/data/ChangeEvents'
    ];
    const execute_url = process.env.COMETD_EXE_URL;
    const retrieval_url = process.env.COMETD_CRON_URL;

	const term_run = process.env.COMETD_TERM_RUN;
	const term_hour = process.env.COMETD_TERM_HOUR;
	const term_minute = process.env.COMETD_TERM_MINUTE;

	const cron_run = process.env.COMETD_CRON_RUN;
	const cron_hour = process.env.COMETD_CRON_HOUR;
	const cron_minute = process.env.COMETD_CRON_MINUTE;

    console.log(getCurrentDateTime()+': Setting up jsforce...');

    const sfconn = new jsforce.Connection({
        oauth2: {
            clientId: process.env.SF_CLIENT_ID,
            clientSecret: process.env.SF_CLIENT_SECRET,
            redirectUrl: "https://login.salesforce.com/"
        }
    });

    
    console.log(getCurrentDateTime()+': Acquiring SF session Id...');

    sfconn.login(process.env.SF_USERNAME, process.env.SF_PASSWORD + process.env.SF_SECURITY, function (err, userInfo) {
        console.log(getCurrentDateTime()+': SF session id acquired: ' + sfconn.accessToken);
        console.log('------------------------');
		
		const currentTime = new Date();
		if (cron_run == 1) { 
			console.log("Cron run status = "+cron_run+", cron time = "+cron_hour+": "+cron_minute);
		}
        
        subscriptionChannels.forEach(channel => {
            sfconn.streaming.topic(channel).subscribe(function (message) {
                console.log(JSON.stringify(message));
                axios.get(execute_url)
                    .then(response => {
                        console.log(getCurrentDateTime()+': URL executed successfully');
                        console.log(JSON.stringify(response.data));
                    })
                    .catch(error => {
                        console.error(getCurrentDateTime()+': Error executing URL:', error.message);
                    });
            });
        });

        // Periodically print a message every 10 seconds
        // setInterval(() => {
        //     console.log(getCurrentDateTime() + ': Script is still running...');
        // }, 10000); // 10 seconds interval

        // Check for termination time every minute
		
        setInterval(() => {
            const currentTime = new Date();
			if (term_run == 1 && currentTime.getHours() == term_hour && currentTime.getMinutes() == term_minute) { 
			//if (currentTime.getHours() === 14 && currentTime.getMinutes() === 35) { 
                console.log(getCurrentDateTime() + ': Terminating script...');
                process.exit(); 
            }	
			
			if (cron_run == 1 && currentTime.getHours() == cron_hour && currentTime.getMinutes() == cron_minute) { 
			//if (currentTime.getHours() === 0 && currentTime.getMinutes() === 30) { 
                console.log(getCurrentDateTime() + ': Executing periodic retrieval');
                axios.get(retrieval_url)
                    .then(response => {
                        console.log(getCurrentDateTime()+': URL executed successfully');
                        console.log(JSON.stringify(response.data));
                    })
                    .catch(error => {
                        console.error(getCurrentDateTime()+': Error executing URL:', error.message);
                    });
            }
			
			//console.log(currentTime.getHours()+":"+currentTime.getMinutes() + ': Script is still running...');
            console.log(getCurrentDateTime() + ': Script is still running...');

        }, 60000); // 1 minute interval
    });
})();


function getCurrentDateTime(mode="full") {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0'); // Months are zero-based
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    if(mode=="short"){
        return `${hours}:${minutes}`;
    }
    else{
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }    
    //return new Date().toISOString();
}
