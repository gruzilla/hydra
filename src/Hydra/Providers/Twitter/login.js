'strict';

var casper = require('casper').create();

if (casper.cli.args.length !== 3) {
    casper.die('USAGE: casperjs login.js URL USERNAME PASSWORD');
}

var url = casper.cli.args[0];
var username = casper.cli.args[1];
var password = casper.cli.args[2];

casper.start().then(function() {
    this.open(
        url,
        {
            method: 'GET',
            headers: {
                'Accept-Language': 'en-us;q=0.5,en;q=0.3'
            }
        }
    );
});

casper.then(function() {
    if (!this.exists('form[id="oauth_form"]')) {
        this.echo('No form found. Token may not be valid.', 'ERROR');
    } else {
        this.fill(
            'form[id="oauth_form"]',
            {
                'session[username_or_email]': 'gruzilla',
                'session[password]': 'twitterrules'
            },
            true
        );
    }
});

casper.then(function() {});

casper.then(function() {
    if (this.exists('.error')) {
        this.echo(this.fetchText('.error'));
    } else {
        this.debugPage();
    }
});

casper.run();
